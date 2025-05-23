<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Genel istatistikler
$total_books = $pdo->query("SELECT COUNT(*) FROM kitaplar")->fetchColumn();
$total_members = $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_tipi = 'uye'")->fetchColumn();
$active_loans = $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc'")->fetchColumn();
$overdue_loans = $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc' AND teslim_tarihi < CURDATE()")->fetchColumn();

// En çok ödünç alınan kitaplar
$popular_books = $pdo->query("
    SELECT k.kitap_adi, y.ad_soyad as yazar, COUNT(o.id) as odunc_sayisi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    GROUP BY k.id
    ORDER BY odunc_sayisi DESC
    LIMIT 10
")->fetchAll();

// En aktif üyeler
$active_members = $pdo->query("
    SELECT u.ad_soyad, u.email, COUNT(o.id) as odunc_sayisi
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    GROUP BY u.id
    ORDER BY odunc_sayisi DESC
    LIMIT 10
")->fetchAll();

// Geciken kitaplar
$overdue_books = $pdo->query("
    SELECT u.ad_soyad, u.telefon, k.kitap_adi, o.teslim_tarihi,
           DATEDIFF(CURDATE(), o.teslim_tarihi) as gecikme_gun
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    JOIN kitaplar k ON o.kitap_id = k.id
    WHERE o.durum = 'odunc' AND o.teslim_tarihi < CURDATE()
    ORDER BY gecikme_gun DESC
")->fetchAll();

// Aylık istatistikler
$monthly_stats = $pdo->query("
    SELECT 
        MONTH(odunc_tarihi) as ay,
        YEAR(odunc_tarihi) as yil,
        COUNT(*) as toplam_odunc
    FROM odunc_islemleri 
    WHERE odunc_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY YEAR(odunc_tarihi), MONTH(odunc_tarihi)
    ORDER BY yil DESC, ay DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlar</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Raporlar ve İstatistikler</h1>
                    <button onclick="window.print()" class="btn btn-secondary">
                        🖨️ Yazdır
                    </button>
                </div>
                
                <!-- Genel İstatistikler -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Toplam Kitap</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_books ?></div>
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
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_members ?></div>
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
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $active_loans ?></div>
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
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Geciken</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $overdue_loans ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- En Popüler Kitaplar -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>En Çok Ödünç Alınan Kitaplar</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Kitap</th>
                                                <th>Yazar</th>
                                                <th>Ödünç Sayısı</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_books as $book): ?>
                                            <tr>
                                                <td><?= $book['kitap_adi'] ?></td>
                                                <td><?= $book['yazar'] ?></td>
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
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>En Aktif Üyeler</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Üye</th>
                                                <th>Email</th>
                                                <th>Ödünç Sayısı</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_members as $member): ?>
                                            <tr>
                                                <td><?= $member['ad_soyad'] ?></td>
                                                <td><?= $member['email'] ?></td>
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
                
                <!-- Geciken Kitaplar -->
                <?php if (!empty($overdue_books)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5>⚠️ Geciken Kitaplar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Üye</th>
                                        <th>Telefon</th>
                                        <th>Kitap</th>
                                        <th>Teslim Tarihi</th>
                                        <th>Gecikme</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdue_books as $book): ?>
                                    <tr>
                                        <td><?= $book['ad_soyad'] ?></td>
                                        <td><?= $book['telefon'] ?></td>
                                        <td><?= $book['kitap_adi'] ?></td>
                                        <td><?= date('d.m.Y', strtotime($book['teslim_tarihi'])) ?></td>
                                        <td><span class="badge bg-danger"><?= $book['gecikme_gun'] ?> gün</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Aylık İstatistikler -->
                <div class="card">
                    <div class="card-header">
                        <h5>Aylık Ödünç İstatistikleri</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ay/Yıl</th>
                                        <th>Toplam Ödünç</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_stats as $stat): ?>
                                    <tr>
                                        <td><?= $stat['ay'] ?>/<?= $stat['yil'] ?></td>
                                        <td><span class="badge bg-info"><?= $stat['toplam_odunc'] ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>