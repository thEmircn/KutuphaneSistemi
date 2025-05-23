<?php
require_once 'auth.php';

// Üye istatistikleri
$member_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as toplam_odunc,
        COUNT(CASE WHEN durum = 'odunc' THEN 1 END) as aktif_odunc,
        COUNT(CASE WHEN durum = 'teslim_edildi' THEN 1 END) as teslim_edilen,
        COUNT(CASE WHEN durum = 'odunc' AND teslim_tarihi < CURDATE() THEN 1 END) as geciken,
        SUM(CASE WHEN gecikme_ucreti > 0 THEN gecikme_ucreti ELSE 0 END) as toplam_ceza
    FROM odunc_islemleri 
    WHERE uye_id = ?
");
$member_stats->execute([$_SESSION['member_id']]);
$stats = $member_stats->fetch();

// Aktif ödünçler
$active_loans = $pdo->prepare("
    SELECT o.*, k.kitap_adi, k.fotograf, y.ad_soyad as yazar_adi,
           DATEDIFF(CURDATE(), o.teslim_tarihi) as gecikme_gun,
           DATEDIFF(o.teslim_tarihi, CURDATE()) as kalan_gun
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.uye_id = ? AND o.durum = 'odunc'
    ORDER BY o.teslim_tarihi
");
$active_loans->execute([$_SESSION['member_id']]);
$active = $active_loans->fetchAll();

// Son aktiviteler
$recent_activity = $pdo->prepare("
    SELECT o.*, k.kitap_adi, k.fotograf, y.ad_soyad as yazar_adi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.uye_id = ?
    ORDER BY o.islem_tarihi DESC
    LIMIT 10
");
$recent_activity->execute([$_SESSION['member_id']]);
$activities = $recent_activity->fetchAll();

// Favori kategoriler
$favorite_categories = $pdo->prepare("
    SELECT kat.kategori_adi, COUNT(*) as sayi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    JOIN kategoriler kat ON k.kategori_id = kat.id
    WHERE o.uye_id = ?
    GROUP BY kat.id
    ORDER BY sayi DESC
    LIMIT 5
");
$favorite_categories->execute([$_SESSION['member_id']]);
$categories = $favorite_categories->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Paneli - Dashboard</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .book-cover {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">Merhaba, <?= $_SESSION['member_name'] ?>! 👋</h1>
                    <p class="mb-0 mt-2">Kütüphane hesabınızı yönetin</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50">
                        Son giriş: <?= date('d.m.Y H:i') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <!-- Ana İçerik -->
            <div class="col-lg-8">
                <!-- İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <div style="font-size: 2rem;">📚</div>
                                <h4 class="text-primary"><?= $stats['toplam_odunc'] ?></h4>
                                <small class="text-muted">Toplam Ödünç</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <div style="font-size: 2rem;">📖</div>
                                <h4 class="text-warning"><?= $stats['aktif_odunc'] ?></h4>
                                <small class="text-muted">Aktif Ödünç</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <div style="font-size: 2rem;">✅</div>
                                <h4 class="text-success"><?= $stats['teslim_edilen'] ?></h4>
                                <small class="text-muted">Teslim Edilen</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card stat-card text-center">
                            <div class="card-body">
                                <div style="font-size: 2rem;"><?= $stats['geciken'] > 0 ? '⚠️' : '😊' ?></div>
                                <h4 class="<?= $stats['geciken'] > 0 ? 'text-danger' : 'text-success' ?>"><?= $stats['geciken'] ?></h4>
                                <small class="text-muted">Geciken</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aktif Ödünçler -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📖 Aktif Ödünçlerim</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($active): ?>
                            <?php foreach ($active as $loan): ?>
                            <div class="row align-items-center mb-3 p-3 border rounded">
                                <div class="col-auto">
                                    <?php if ($loan['fotograf'] && file_exists('../uploads/books/' . $loan['fotograf'])): ?>
                                        <img src="../uploads/books/<?= $loan['fotograf'] ?>" class="book-cover">
                                    <?php else: ?>
                                        <div class="book-cover bg-light d-flex align-items-center justify-content-center">
                                            📚
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <h6 class="mb-1"><?= htmlspecialchars($loan['kitap_adi']) ?></h6>
                                    <p class="text-muted mb-1"><?= htmlspecialchars($loan['yazar_adi'] ?? 'Bilinmeyen Yazar') ?></p>
                                    <small class="text-muted">
                                        Ödünç: <?= date('d.m.Y', strtotime($loan['odunc_tarihi'])) ?>
                                    </small>
                                </div>
                                <div class="col-auto text-end">
                                    <div class="mb-2">
                                        <?php if ($loan['gecikme_gun'] > 0): ?>
                                            <span class="badge bg-danger status-badge">
                                                <?= $loan['gecikme_gun'] ?> gün gecikme
                                            </span>
                                        <?php elseif ($loan['kalan_gun'] <= 2): ?>
                                            <span class="badge bg-warning status-badge">
                                                <?= $loan['kalan_gun'] ?> gün kaldı
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success status-badge">
                                                <?= $loan['kalan_gun'] ?> gün kaldı
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        Teslim: <?= date('d.m.Y', strtotime($loan['teslim_tarihi'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div style="font-size: 4rem;">📚</div>
                                <h5 class="text-muted">Aktif ödünç kitabınız bulunmuyor</h5>
                                <p class="text-muted">Yeni kitaplar keşfetmek için kütüphaneyi ziyaret edin!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Son Aktiviteler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">📋 Son Aktivitelerim</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($activities as $activity): ?>
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <?php if ($activity['fotograf'] && file_exists('../uploads/books/' . $activity['fotograf'])): ?>
                                        <img src="../uploads/books/<?= $activity['fotograf'] ?>" width="40" height="50" class="rounded">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 40px; height: 50px;">
                                            📚
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($activity['kitap_adi']) ?></h6>
                                    <p class="text-muted mb-1 small"><?= htmlspecialchars($activity['yazar_adi'] ?? 'Bilinmeyen Yazar') ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?= date('d.m.Y', strtotime($activity['odunc_tarihi'])) ?>
                                        </small>
                                        <span class="badge bg-<?= $activity['durum'] == 'odunc' ? 'warning' : 'success' ?>">
                                            <?= $activity['durum'] == 'odunc' ? 'Ödünçte' : 'Teslim Edildi' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Profil Bilgileri -->
                <div class="sidebar mb-4">
                    <h5 class="mb-3">👤 Profil Bilgileri</h5>
                    <p><strong>Ad Soyad:</strong> <?= $_SESSION['member_name'] ?></p>
                    <p><strong>Email:</strong> <?= $_SESSION['member_email'] ?></p>
                    <?php if ($stats['toplam_ceza'] > 0): ?>
                    <div class="alert alert-warning">
                        <strong>Toplam Ceza:</strong> <?= number_format($stats['toplam_ceza'], 2) ?> ₺
                    </div>
                    <?php endif; ?>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">Profili Düzenle</a>
                </div>
                
                <!-- Favori Kategoriler -->
                <?php if ($categories): ?>
                <div class="sidebar mb-4">
                    <h5 class="mb-3">📊 Favori Kategorilerim</h5>
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
                <?php endif; ?>
                
                <!-- Hızlı İşlemler -->
                <div class="sidebar">
                    <h5 class="mb-3">🚀 Hızlı İşlemler</h5>
                    <div class="d-grid gap-2">
                        <a href="search.php" class="btn btn-outline-primary">
                            🔍 Kitap Ara
                        </a>
                        <a href="history.php" class="btn btn-outline-secondary">
                            📋 Geçmiş
                        </a>
                        <a href="profile.php" class="btn btn-outline-info">
                            ⚙️ Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <?php if ($categories): ?>
    <script>
    // Kategori grafiği
    const ctx = document.getElementById('categoryChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($categories, 'kategori_adi')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($categories, 'sayi')) ?>,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        fontSize: 12
                    }
                }
            }
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>