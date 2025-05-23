<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Gerçek zamanlı istatistikler
$today = date('Y-m-d');
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

// Ana istatistikler
$stats = [
    'total_books' => $pdo->query("SELECT COUNT(*) FROM kitaplar")->fetchColumn(),
    'available_books' => $pdo->query("SELECT SUM(mevcut_adet) FROM kitaplar WHERE durum = 'mevcut'")->fetchColumn(),
    'total_members' => $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_tipi = 'uye'")->fetchColumn(),
    'active_members' => $pdo->query("SELECT COUNT(DISTINCT uye_id) FROM odunc_islemleri WHERE odunc_tarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
    'active_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc'")->fetchColumn(),
    'overdue_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc' AND teslim_tarihi < CURDATE()")->fetchColumn(),
    'today_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE DATE(odunc_tarihi) = CURDATE()")->fetchColumn(),
    'today_returns' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE DATE(gercek_teslim_tarihi) = CURDATE()")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT SUM(gecikme_ucreti) FROM odunc_islemleri WHERE gecikme_ucreti > 0")->fetchColumn() ?? 0,
    'monthly_revenue' => $pdo->query("SELECT SUM(gecikme_ucreti) FROM odunc_islemleri WHERE gecikme_ucreti > 0 AND DATE_FORMAT(gercek_teslim_tarihi, '%Y-%m') = '$this_month'")->fetchColumn() ?? 0
];

// Trend hesaplama
$last_month_loans = $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE DATE_FORMAT(odunc_tarihi, '%Y-%m') = '$last_month'")->fetchColumn();
$this_month_loans = $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE DATE_FORMAT(odunc_tarihi, '%Y-%m') = '$this_month'")->fetchColumn();
$loan_trend = $last_month_loans > 0 ? (($this_month_loans - $last_month_loans) / $last_month_loans) * 100 : 0;

// Son 7 günlük aktivite
$weekly_activity = $pdo->query("
    SELECT 
        DATE(odunc_tarihi) as gun,
        COUNT(*) as odunc_sayisi,
        COUNT(CASE WHEN durum = 'teslim_edildi' AND DATE(gercek_teslim_tarihi) = DATE(odunc_tarihi) THEN 1 END) as teslim_sayisi
    FROM odunc_islemleri 
    WHERE odunc_tarihi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(odunc_tarihi)
    ORDER BY gun
")->fetchAll();

// Saatlik aktivite (bugün)
$hourly_activity = $pdo->query("
    SELECT 
        HOUR(odunc_tarihi) as saat,
        COUNT(*) as islem_sayisi
    FROM odunc_islemleri 
    WHERE DATE(odunc_tarihi) = CURDATE()
    GROUP BY HOUR(odunc_tarihi)
    ORDER BY saat
")->fetchAll();

// En popüler kitaplar (bu ay)
$popular_books = $pdo->query("
    SELECT k.kitap_adi, y.ad_soyad as yazar, COUNT(o.id) as odunc_sayisi, k.fotograf
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE DATE_FORMAT(o.odunc_tarihi, '%Y-%m') = '$this_month'
    GROUP BY k.id
    ORDER BY odunc_sayisi DESC
    LIMIT 5
")->fetchAll();

// Yakın teslim tarihleri
$upcoming_returns = $pdo->query("
    SELECT o.*, u.ad_soyad, u.telefon, k.kitap_adi,
           DATEDIFF(o.teslim_tarihi, CURDATE()) as kalan_gun
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    JOIN kitaplar k ON o.kitap_id = k.id
    WHERE o.durum = 'odunc' 
    AND o.teslim_tarihi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY o.teslim_tarihi
    LIMIT 5
")->fetchAll();

// Son eklenen kitaplar
$recent_books = $pdo->query("
    SELECT k.*, y.ad_soyad as yazar_adi
    FROM kitaplar k
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    ORDER BY k.eklenme_tarihi DESC
    LIMIT 5
")->fetchAll();

// Sistem durumu
$system_status = [
    'low_stock' => $pdo->query("SELECT COUNT(*) FROM kitaplar WHERE mevcut_adet <= 1 AND stok_adedi > 1")->fetchColumn(),
    'out_of_stock' => $pdo->query("SELECT COUNT(*) FROM kitaplar WHERE mevcut_adet = 0")->fetchColumn(),
    'pending_reservations' => 0, // Bu özellik daha sonra eklenecek
    'system_health' => 'Excellent' // Basit sistem durumu
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kütüphane Yönetimi</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .metric-card {
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .quick-stats {
            font-size: 2rem;
            font-weight: bold;
        }
        .trend-up { color: #28a745; }
        .trend-down { color: #dc3545; }
        .widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .activity-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-excellent { background-color: #28a745; }
        .status-good { background-color: #ffc107; }
        .status-warning { background-color: #fd7e14; }
        .status-critical { background-color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">📊 Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard()">
                                🔄 Yenile
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAutoRefresh()">
                                ⏱️ Otomatik Yenile: <span id="autoRefreshStatus">Kapalı</span>
                            </button>
                        </div>
                        <div class="text-muted">
                            Son güncelleme: <span id="lastUpdate"><?= date('H:i:s') ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Hızlı İstatistikler -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Toplam Kitap</div>
                                        <div class="quick-stats text-gray-800"><?= number_format($stats['total_books']) ?></div>
                                        <div class="text-xs text-muted">Mevcut: <?= number_format($stats['available_books']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <div style="font-size: 2rem;">📚</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Üye</div>
                                        <div class="quick-stats text-gray-800"><?= number_format($stats['total_members']) ?></div>
                                        <div class="text-xs text-muted">Aktif: <?= number_format($stats['active_members']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <div style="font-size: 2rem;">👥</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Aktif Ödünç</div>
                                        <div class="quick-stats text-gray-800"><?= number_format($stats['active_loans']) ?></div>
                                        <div class="text-xs <?= $stats['overdue_loans'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                            Geciken: <?= number_format($stats['overdue_loans']) ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div style="font-size: 2rem;">📖</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Bugünkü Aktivite</div>
                                        <div class="quick-stats text-gray-800"><?= $stats['today_loans'] + $stats['today_returns'] ?></div>
                                        <div class="text-xs text-muted">
                                            Ödünç: <?= $stats['today_loans'] ?> | Teslim: <?= $stats['today_returns'] ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div style="font-size: 2rem;">⚡</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Trend ve Gelir -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">📈 Haftalık Aktivite Trendi</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="weeklyChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow mb-3">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">💰 Gelir Durumu</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="h4 text-success"><?= number_format($stats['monthly_revenue'], 2) ?> ₺</div>
                                <div class="text-muted">Bu Ay</div>
                                <hr>
                                <div class="h5"><?= number_format($stats['total_revenue'], 2) ?> ₺</div>
                                <div class="text-muted">Toplam</div>
                            </div>
                        </div>
                        
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">⚡ Sistem Durumu</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <span class="activity-indicator status-excellent"></span>
                                    <small>Sistem Sağlığı: Mükemmel</small>
                                </div>
                                <div class="mb-2">
                                    <span class="activity-indicator <?= $system_status['low_stock'] > 0 ? 'status-warning' : 'status-excellent' ?>"></span>
                                    <small>Düşük Stok: <?= $system_status['low_stock'] ?></small>
                                </div>
                                <div class="mb-2">
                                    <span class="activity-indicator <?= $system_status['out_of_stock'] > 0 ? 'status-critical' : 'status-excellent' ?>"></span>
                                    <small>Tükenen: <?= $system_status['out_of_stock'] ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Widget Sistemi -->
                <div class="row">
                    <!-- Popüler Kitaplar Widget -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">🔥 Bu Ay Popüler Kitaplar</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($popular_books as $index => $book): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <span class="badge bg-primary rounded-circle p-2"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="me-3">
                                        <?php if ($book['fotograf'] && file_exists('../uploads/books/' . $book['fotograf'])): ?>
                                            <img src="../uploads/books/<?= $book['fotograf'] ?>" width="40" height="50" class="img-thumbnail">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 50px;">📚</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold"><?= htmlspecialchars($book['kitap_adi']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($book['yazar']) ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-success"><?= $book['odunc_sayisi'] ?> kez</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Yakın Teslimler Widget -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">⏰ Yakın Teslim Tarihleri</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($upcoming_returns): ?>
                                    <?php foreach ($upcoming_returns as $return): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                        <div>
                                            <div class="font-weight-bold"><?= htmlspecialchars($return['ad_soyad']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($return['kitap_adi']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge bg-<?= $return['kalan_gun'] <= 1 ? 'danger' : 'warning' ?>">
                                                <?= $return['kalan_gun'] ?> gün
                                            </div>
                                            <div class="text-xs text-muted"><?= date('d.m.Y', strtotime($return['teslim_tarihi'])) ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <div style="font-size: 3rem;">✅</div>
                                        <p>Yakın zamanda teslim edilecek kitap yok</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Saatlik Aktivite ve Son Eklenen Kitaplar -->
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">⏰ Bugünkü Saatlik Aktivite</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="hourlyChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">📚 Son Eklenen Kitaplar</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($recent_books as $book): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <?php if ($book['fotograf'] && file_exists('../uploads/books/' . $book['fotograf'])): ?>
                                            <img src="../uploads/books/<?= $book['fotograf'] ?>" width="30" height="40" class="img-thumbnail">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 30px; height: 40px; font-size: 12px;">📚</div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                    <div class="font-weight-bold text-xs"><?= htmlspecialchars(substr($book['kitap_adi'] ?? '', 0, 25)) ?><?= strlen($book['kitap_adi'] ?? '') > 25 ? '...' : '' ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($book['yazar_adi'] ?? 'Bilinmeyen') ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <div class="text-center">
                                    <a href="books.php" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hızlı Erişim Butonları -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header widget-header">
                                <h6 class="m-0 font-weight-bold">🚀 Hızlı Erişim</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <a href="books.php" class="btn btn-outline-primary btn-lg w-100">
                                            <div style="font-size: 2rem;">📚</div>
                                            <div>Yeni Kitap</div>
                                        </a>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <a href="members.php" class="btn btn-outline-success btn-lg w-100">
                                            <div style="font-size: 2rem;">👤</div>
                                            <div>Yeni Üye</div>
                                        </a>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <a href="loans.php" class="btn btn-outline-warning btn-lg w-100">
                                            <div style="font-size: 2rem;">📖</div>
                                            <div>Ödünç Ver</div>
                                        </a>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <a href="search.php" class="btn btn-outline-info btn-lg w-100">
                                            <div style="font-size: 2rem;">🔍</div>
                                            <div>Ara</div>
                                        </a>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <a href="advanced_reports.php" class="btn btn-outline-secondary btn-lg w-100">
                                            <div style="font-size: 2rem;">📊</div>
                                            <div>Raporlar</div>
                                        </a>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <a href="notifications.php" class="btn btn-outline-danger btn-lg w-100">
                                            <div style="font-size: 2rem;">🔔</div>
                                            <div>Bildirimler</div>
                                            <?php if ($stats['overdue_loans'] > 0): ?>
                                                <span class="badge bg-danger"><?= $stats['overdue_loans'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </main>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    let autoRefreshInterval;
    let autoRefreshEnabled = false;
    
    // Haftalık aktivite grafiği
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    const weeklyChart = new Chart(weeklyCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($weekly_activity, 'gun')) ?>,
            datasets: [{
                label: 'Ödünç Verilen',
                data: <?= json_encode(array_column($weekly_activity, 'odunc_sayisi')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4
            }, {
                label: 'Teslim Alınan',
                data: <?= json_encode(array_column($weekly_activity, 'teslim_sayisi')) ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Saatlik aktivite grafiği
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    
    // 24 saatlik veri hazırla
    const hourlyData = new Array(24).fill(0);
    <?php foreach ($hourly_activity as $activity): ?>
        hourlyData[<?= $activity['saat'] ?>] = <?= $activity['islem_sayisi'] ?>;
    <?php endforeach; ?>
    
    const hourlyChart = new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + ':00'),
            datasets: [{
                label: 'İşlem Sayısı',
                data: hourlyData,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Dashboard yenile
    function refreshDashboard() {
        location.reload();
    }
    
    // Otomatik yenileme toggle
    function toggleAutoRefresh() {
        const statusElement = document.getElementById('autoRefreshStatus');
        
        if (autoRefreshEnabled) {
            clearInterval(autoRefreshInterval);
            autoRefreshEnabled = false;
            statusElement.textContent = 'Kapalı';
            statusElement.className = '';
        } else {
            autoRefreshInterval = setInterval(refreshDashboard, 30000); // 30 saniye
            autoRefreshEnabled = true;
            statusElement.textContent = 'Açık';
            statusElement.className = 'text-success';
        }
    }
    
    // Son güncelleme zamanını güncelle
    function updateLastUpdateTime() {
        document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('tr-TR');
    }
    
    // Sayfa yüklendiğinde son güncelleme zamanını ayarla
    document.addEventListener('DOMContentLoaded', function() {
        updateLastUpdateTime();
        
        // Her dakika son güncelleme zamanını güncelle
        setInterval(updateLastUpdateTime, 60000);
    });
    
    // Widget animasyonları
    document.querySelectorAll('.metric-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    </script>
</body>
</html>