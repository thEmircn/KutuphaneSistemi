<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? 0;

// Kitap bilgilerini getir
$stmt = $pdo->prepare("
    SELECT k.*, y.ad_soyad as yazar_adi, kat.kategori_adi,
           (SELECT COUNT(*) FROM odunc_islemleri WHERE kitap_id = k.id) as toplam_odunc,
           (SELECT COUNT(*) FROM odunc_islemleri WHERE kitap_id = k.id AND durum = 'odunc') as aktif_odunc
    FROM kitaplar k
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    LEFT JOIN kategoriler kat ON k.kategori_id = kat.id
    WHERE k.id = ?
");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: books.php');
    exit;
}

// Ödünç geçmişi
$loan_history = $pdo->prepare("
    SELECT o.*, u.ad_soyad, u.telefon
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    WHERE o.kitap_id = ?
    ORDER BY o.odunc_tarihi DESC
    LIMIT 20
");
$loan_history->execute([$id]);
$loans = $loan_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Detayı - <?= $book['kitap_adi'] ?></title>
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
                    <h1 class="h2"><?= $book['kitap_adi'] ?></h1>
                    <div>
                        <a href="books.php" class="btn btn-secondary">← Geri Dön</a>
                        <a href="books.php?edit=<?= $book['id'] ?>" class="btn btn-warning">Düzenle</a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if ($book['fotograf']): ?>
                                    <img src="../uploads/books/<?= $book['fotograf'] ?>" class="img-fluid mb-3" style="max-height: 300px;" alt="Kitap Kapağı">
                                <?php else: ?>
                                    <div class="bg-light p-5 mb-3" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                                        <span class="text-muted">📚<br>Fotoğraf Yok</span>
                                    </div>
                                <?php endif; ?>
                                
                                <h5><?= $book['kitap_adi'] ?></h5>
                                <p class="text-muted"><?= $book['yazar_adi'] ?></p>
                                
                                <div class="d-grid gap-2">
                                    <span class="badge bg-<?= $book['durum'] == 'mevcut' ? 'success' : ($book['durum'] == 'tukendi' ? 'warning' : 'secondary') ?> p-2">
                                        <?= ucfirst($book['durum']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stok Bilgileri -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6>Stok Bilgileri</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary"><?= $book['stok_adedi'] ?></h4>
                                        <small>Toplam Stok</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?= $book['mevcut_adet'] ?></h4>
                                        <small>Mevcut</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-info"><?= $book['toplam_odunc'] ?></h4>
                                        <small>Toplam Ödünç</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-warning"><?= $book['aktif_odunc'] ?></h4>
                                        <small>Aktif Ödünç</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <!-- Kitap Bilgileri -->
                        <div class="card">
                            <div class="card-header">
                                <h5>Kitap Bilgileri</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>ISBN:</th>
                                                <td><?= $book['isbn'] ?: '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th>Yazar:</th>
                                                <td><?= $book['yazar_adi'] ?: '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th>Kategori:</th>
                                                <td><?= $book['kategori_adi'] ?: '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th>Yayın Evi:</th>
                                                <td><?= $book['yayin_evi'] ?: '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>Yayın Yılı:</th>
                                                <td><?= $book['yayin_yili'] ?: '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th>Sayfa Sayısı:</th>
                                                <td><?= $book['sayfa_sayisi'] ?: '-' ?></td>
                                            </tr>
                                            <tr>
                                                <th>Dil:</th>
                                                <td><?= $book['dil'] ?></td>
                                            </tr>
                                            <tr>
                                                <th>Raf No:</th>
                                                <td><?= $book['raf_no'] ?: '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <?php if ($book['ozet']): ?>
                                <div class="mt-3">
                                    <h6>Özet:</h6>
                                    <p><?= nl2br($book['ozet']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Ödünç Geçmişi -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5>Ödünç Geçmişi</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($loans): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Üye</th>
                                                <th>Ödünç Tarihi</th>
                                                <th>Teslim Tarihi</th>
                                                <th>Durum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($loans as $loan): ?>
                                            <tr>
                                                <td>
                                                    <?= $loan['ad_soyad'] ?><br>
                                                    <small class="text-muted"><?= $loan['telefon'] ?></small>
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