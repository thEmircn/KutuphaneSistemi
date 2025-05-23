<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

// Üye bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = ? AND kullanici_tipi = 'uye'");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: members.php');
    exit;
}

// Üye istatistikleri
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as toplam_odunc,
        COUNT(CASE WHEN durum = 'odunc' THEN 1 END) as aktif_odunc,
        COUNT(CASE WHEN durum = 'teslim_edildi' THEN 1 END) as teslim_edilen,
        COUNT(CASE WHEN durum = 'odunc' AND teslim_tarihi < CURDATE() THEN 1 END) as geciken
    FROM odunc_islemleri 
    WHERE uye_id = ?
");
$stats->execute([$id]);
$member_stats = $stats->fetch();

// Aktif ödünçler
$active_loans = $pdo->prepare("
    SELECT o.*, k.kitap_adi, y.ad_soyad as yazar_adi,
           DATEDIFF(CURDATE(), o.teslim_tarihi) as gecikme_gun
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.uye_id = ? AND o.durum = 'odunc'
    ORDER BY o.teslim_tarihi
");
$active_loans->execute([$id]);
$active = $active_loans->fetchAll();

// Ödünç geçmişi
$loan_history = $pdo->prepare("
    SELECT o.*, k.kitap_adi, y.ad_soyad as yazar_adi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.uye_id = ?
    ORDER BY o.odunc_tarihi DESC
    LIMIT 20
");
$loan_history->execute([$id]);
$history = $loan_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Detayı - <?= $member['ad_soyad'] ?></title>
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
                    <h1 class="h2"><?= $member['ad_soyad'] ?></h1>
                    <div>
                        <a href="members.php" class="btn btn-secondary">← Geri Dön</a>
                        <a href="members.php?edit=<?= $member['id'] ?>" class="btn btn-warning">Düzenle</a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <!-- Üye Bilgileri -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Üye Bilgileri</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Ad Soyad:</th>
                                        <td><?= $member['ad_soyad'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kullanıcı Adı:</th>
                                        <td><?= $member['kullanici_adi'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><?= $member['email'] ?></td>
                                    </tr>
                                    <tr>
                                        <th>Telefon:</th>
                                        <td><?= $member['telefon'] ?: '-' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kayıt Tarihi:</th>
                                        <td><?= date('d.m.Y', strtotime($member['kayit_tarihi'])) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Son Giriş:</th>
                                        <td><?= $member['son_giris'] ? date('d.m.Y H:i', strtotime($member['son_giris'])) : 'Hiç giriş yapmamış' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Durum:</th>
                                        <td>
                                            <span class="badge bg-<?= $member['durum'] == 'aktif' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($member['durum']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                
                                <?php if ($member['adres']): ?>
                                <div class="mt-3">
                                    <h6>Adres:</h6>
                                    <p><?= nl2br($member['adres']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- İstatistikler -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6>Ödünç İstatistikleri</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary"><?= $member_stats['toplam_odunc'] ?></h4>
                                        <small>Toplam</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-warning"><?= $member_stats['aktif_odunc'] ?></h4>
                                        <small>Aktif</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-success"><?= $member_stats['teslim_edilen'] ?></h4>
                                        <small>Teslim Edilen</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-danger"><?= $member_stats['geciken'] ?></h4>
                                        <small>Geciken</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Aktif Ödünçler -->
                        <?php if ($active): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5>Aktif Ödünçler</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Kitap</th>
                                                <th>Ödünç Tarihi</th>
                                                <th>Teslim Tarihi</th>
                                                <th>Durum</th>
                                                <th>İşlem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active as $loan): ?>
                                            <tr>
                                                <td>
                                                    <?= $loan['kitap_adi'] ?><br>
                                                    <small class="text-muted"><?= $loan['yazar_adi'] ?></small>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($loan['odunc_tarihi'])) ?></td>
                                                <td><?= date('d.m.Y', strtotime($loan['teslim_tarihi'])) ?></td>
                                                <td>
                                                    <?php if ($loan['gecikme_gun'] > 0): ?>
                                                        <span class="badge bg-danger">Gecikme (<?= $loan['gecikme_gun'] ?> gün)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Ödünçte</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="../admin/loans.php?teslim=<?= $loan['id'] ?>" class="btn btn-sm btn-success">Teslim Al</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Ödünç Geçmişi -->
                        <div class="card <?= $active ? 'mt-3' : '' ?>">
                            <div class="card-header">
                                <h5>Ödünç Geçmişi</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($history): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Kitap</th>
                                                <th>Ödünç Tarihi</th>
                                                <th>Teslim Tarihi</th>
                                                <th>Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($history as $loan): ?>
                                            <tr>
                                                <td>
                                                    <?= $loan['kitap_adi'] ?><br>
                                                    <small class="text-muted"><?= $loan['yazar_adi'] ?></small>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($loan['odunc_tarihi'])) ?></td>
                                                <td><?= date('d.m.Y', strtotime($loan['teslim_tarihi'])) ?></td>
                                                <td>
                                                    <?php if ($loan['durum'] == 'odunc'): ?>
                                                        <span class="badge bg-warning">Ödünçte</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Teslim Edildi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">Henüz ödünç işlemi bulunmuyor.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            </div>
   </div>
   
   <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>