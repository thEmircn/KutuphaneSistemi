<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Tarih aralığı
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Ayın ilk günü
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Bugün

// İstatistikler
$stats = [
    'total_books' => $pdo->query("SELECT COUNT(*) FROM kitaplar")->fetchColumn(),
    'total_members' => $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_tipi = 'uye'")->fetchColumn(),
    'active_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc'")->fetchColumn(),
    'overdue_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc' AND teslim_tarihi < CURDATE()")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT SUM(gecikme_ucreti) FROM odunc_islemleri WHERE gercek_teslim_tarihi IS NOT NULL")->fetchColumn() ?? 0
];

// Aylık istatistikler (son 12 ay)
$monthly_stats = $pdo->query("
    SELECT 
        DATE_FORMAT(odunc_tarihi, '%Y-%m') as ay,
        COUNT(*) as odunc_sayisi,
        COUNT(DISTINCT uye_id) as aktif_uye_sayisi
    FROM odunc_islemleri 
    WHERE odunc_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(odunc_tarihi, '%Y-%m')
    ORDER BY ay
")->fetchAll();

// En popüler kitaplar
$popular_books = $pdo->query("
    SELECT k.kitap_adi, y.ad_soyad as yazar, COUNT(o.id) as odunc_sayisi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.odunc_tarihi >= '$start_date' AND o.odunc_tarihi <= '$end_date'
    GROUP BY k.id
    ORDER BY odunc_sayisi DESC
    LIMIT 10
")->fetchAll();

// En aktif üyeler
$active_members = $pdo->query("
    SELECT u.ad_soyad, u.email, COUNT(o.id) as odunc_sayisi
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    WHERE o.odunc_tarihi >= '$start_date' AND o.odunc_tarihi <= '$end_date'
    GROUP BY u.id
    ORDER BY odunc_sayisi DESC
    LIMIT 10
")->fetchAll();

// Kategori bazlı dağılım
$category_stats = $pdo->query("
    SELECT kat.kategori_adi, COUNT(o.id) as odunc_sayisi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    JOIN kategoriler kat ON k.kategori_id = kat.id
    WHERE o.odunc_tarihi >= '$start_date' AND o.odunc_tarihi <= '$end_date'
    GROUP BY kat.id
    ORDER BY odunc_sayisi DESC
")->fetchAll();

// Günlük aktivite (son 30 gün)
$daily_activity = $pdo->query("
    SELECT 
        DATE(odunc_tarihi) as gun,
        COUNT(*) as odunc_sayisi
    FROM odunc_islemleri 
    WHERE odunc_tarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(odunc_tarihi)
    ORDER BY gun
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelişmiş Raporlar</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">📊 Gelişmiş Raporlar</h1>
                    <div class="btn-toolbar">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                📄 Rapor İndir
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?type=books&format=excel">📚 Kitaplar (Excel)</a></li>
                                <li><a class="dropdown-item" href="export.php?type=members&format=excel">👥 Üyeler (Excel)</a></li>
                                <li><a class="dropdown-item" href="export.php?type=loans&format=excel">📖 Ödünç İşlemleri (Excel)</a></li>
                                <li><a class="dropdown-item" href="export.php?type=overdue&format=excel">⚠️ Geciken Kitaplar (Excel)</a></li>
                            </ul>
                        </div>
                        <button onclick="window.print()" class="btn btn-outline-secondary">🖨️ Yazdır</button>
                    </div>
                </div>
                
                <!-- Tarih Filtresi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>📅 Rapor Dönemi</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">🔄 Güncelle</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Genel İstatistikler -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Toplam Kitap</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_books']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300">📚</i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Toplam Üye</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_members']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300">👥</i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Aktif Ödünç</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['active_loans']) ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book-open fa-2x text-gray-300">📖</i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Gecikme Geliri</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_revenue'], 2) ?> ₺</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill fa-2x text-gray-300">💰</i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Grafikler -->
                <div class="row">
                    <!-- Aylık Trend -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">📈 Aylık Ödünç Trendi (Son 12 Ay)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kategori Dağılımı -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">🏷️ Kategori Dağılımı</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Günlük Aktivite -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">📅 Günlük Aktivite (Son 30 Gün)</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="dailyChart" height="50"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tablolar -->
                <div class="row">
                    <!-- En Popüler Kitaplar -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">🔥 En Popüler Kitaplar</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Kitap</th>
                                                <th>Yazar</th>
                                                <th>Ödünç</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_books as $book): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($book['kitap_adi']) ?></td>
                                                <td><?= htmlspecialchars($book['yazar']) ?></td>
                                                <td><span class="badge bg-primary"><?= $book['odunc_sayisi'] ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- En Aktif Üyeler -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">⭐ En Aktif Üyeler</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Üye</th>
                                                <th>Email</th>
                                                <th>Ödünç</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_members as $member): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['ad_soyad']) ?></td>
                                                <td><?= htmlspecialchars($member['email']) ?></td>
                                                <td><span class="badge bg-success"><?= $member['odunc_sayisi'] ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
    // Aylık Trend Grafiği
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($monthly_stats, 'ay')) ?>,
            datasets: [{
                label: 'Ödünç Sayısı',
                data: <?= json_encode(array_column($monthly_stats, 'odunc_sayisi')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }, {
                label: 'Aktif Üye',
                data: <?= json_encode(array_column($monthly_stats, 'aktif_uye_sayisi')) ?>,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Kategori Dağılımı
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($category_stats, 'kategori_adi')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($category_stats, 'odunc_sayisi')) ?>,
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 205, 86)',
                    'rgb(75, 192, 192)',
                    'rgb(153, 102, 255)',
                    'rgb(255, 159, 64)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Günlük Aktivite
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dailyChart = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($daily_activity, 'gun')) ?>,
            datasets: [{
                label: 'Günlük Ödünç',
                data: <?= json_encode(array_column($daily_activity, 'odunc_sayisi')) ?>,
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
            }
        }
    });
    </script>
</body>
</html>