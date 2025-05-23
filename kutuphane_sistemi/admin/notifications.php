<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Bugünün tarihi
$today = date('Y-m-d');

// Geciken kitaplar
$overdue_books = $pdo->query("
    SELECT o.*, u.ad_soyad, u.telefon, u.email, k.kitap_adi, y.ad_soyad as yazar_adi,
           DATEDIFF('$today', o.teslim_tarihi) as gecikme_gun
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.durum = 'odunc' AND o.teslim_tarihi < '$today'
    ORDER BY gecikme_gun DESC
")->fetchAll();

// Yarın teslim edilecek kitaplar
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$due_tomorrow = $pdo->query("
    SELECT o.*, u.ad_soyad, u.telefon, u.email, k.kitap_adi, y.ad_soyad as yazar_adi
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.durum = 'odunc' AND o.teslim_tarihi = '$tomorrow'
    ORDER BY u.ad_soyad
")->fetchAll();

// Stokta azalan kitaplar
$low_stock = $pdo->query("
    SELECT k.*, y.ad_soyad as yazar_adi
    FROM kitaplar k
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE k.mevcut_adet <= 1 AND k.stok_adedi > 1
    ORDER BY k.mevcut_adet, k.kitap_adi
")->fetchAll();

// Yeni üyeler (son 7 gün)
$week_ago = date('Y-m-d', strtotime('-7 days'));
$new_members = $pdo->query("
    SELECT * FROM kullanicilar 
    WHERE kullanici_tipi = 'uye' AND kayit_tarihi >= '$week_ago'
    ORDER BY kayit_tarihi DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirimler</title>
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
                <h1 class="h2">Bildirimler ve Uyarılar</h1>
                   <div>
                       <span class="badge bg-danger me-2"><?= count($overdue_books) ?> Geciken</span>
                       <span class="badge bg-warning me-2"><?= count($due_tomorrow) ?> Yarın Teslim</span>
                       <span class="badge bg-info"><?= count($low_stock) ?> Düşük Stok</span>
                   </div>
               </div>
               
               <!-- Geciken Kitaplar -->
               <?php if ($overdue_books): ?>
               <div class="card mb-4">
                   <div class="card-header bg-danger text-white">
                       <h5 class="mb-0">🚨 Geciken Kitaplar (<?= count($overdue_books) ?>)</h5>
                   </div>
                   <div class="card-body">
                       <div class="table-responsive">
                           <table class="table table-striped">
                               <thead>
                                   <tr>
                                       <th>Üye</th>
                                       <th>İletişim</th>
                                       <th>Kitap</th>
                                       <th>Teslim Tarihi</th>
                                       <th>Gecikme</th>
                                       <th>Ceza</th>
                                       <th>İşlem</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($overdue_books as $book): ?>
                                   <tr>
                                       <td>
                                           <a href="member_detail.php?id=<?= $book['uye_id'] ?>" class="text-decoration-none">
                                               <?= $book['ad_soyad'] ?>
                                           </a>
                                       </td>
                                       <td>
                                           <?= $book['telefon'] ?><br>
                                           <small class="text-muted"><?= $book['email'] ?></small>
                                       </td>
                                       <td>
                                           <strong><?= $book['kitap_adi'] ?></strong><br>
                                           <small class="text-muted"><?= $book['yazar_adi'] ?></small>
                                       </td>
                                       <td><?= date('d.m.Y', strtotime($book['teslim_tarihi'])) ?></td>
                                       <td><span class="badge bg-danger"><?= $book['gecikme_gun'] ?> gün</span></td>
                                       <td><?= number_format($book['gecikme_gun'] * 2.50, 2) ?> TL</td>
                                       <td>
                                           <a href="tel:<?= $book['telefon'] ?>" class="btn btn-sm btn-primary">📞 Ara</a>
                                           <a href="mailto:<?= $book['email'] ?>" class="btn btn-sm btn-info">📧 Mail</a>
                                       </td>
                                   </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                       </div>
                   </div>
               </div>
               <?php endif; ?>
               
               <!-- Yarın Teslim Edilecek Kitaplar -->
               <?php if ($due_tomorrow): ?>
               <div class="card mb-4">
                   <div class="card-header bg-warning text-dark">
                       <h5 class="mb-0">⏰ Yarın Teslim Edilecek Kitaplar (<?= count($due_tomorrow) ?>)</h5>
                   </div>
                   <div class="card-body">
                       <div class="table-responsive">
                           <table class="table table-striped">
                               <thead>
                                   <tr>
                                       <th>Üye</th>
                                       <th>İletişim</th>
                                       <th>Kitap</th>
                                       <th>Teslim Tarihi</th>
                                       <th>İşlem</th>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($due_tomorrow as $book): ?>
                                   <tr>
                                       <td>
                                           <a href="member_detail.php?id=<?= $book['uye_id'] ?>" class="text-decoration-none">
                                               <?= $book['ad_soyad'] ?>
                                           </a>
                                       </td>
                                       <td>
                                           <?= $book['telefon'] ?><br>
                                           <small class="text-muted"><?= $book['email'] ?></small>
                                       </td>
                                       <td>
                                           <strong><?= $book['kitap_adi'] ?></strong><br>
                                           <small class="text-muted"><?= $book['yazar_adi'] ?></small>
                                       </td>
                                       <td><?= date('d.m.Y', strtotime($book['teslim_tarihi'])) ?></td>
                                       <td>
                                           <a href="tel:<?= $book['telefon'] ?>" class="btn btn-sm btn-primary">📞 Ara</a>
                                           <a href="mailto:<?= $book['email'] ?>" class="btn btn-sm btn-info">📧 Mail</a>
                                       </td>
                                   </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                       </div>
                   </div>
               </div>
               <?php endif; ?>
               
               <div class="row">
                   <!-- Düşük Stok Uyarısı -->
                   <?php if ($low_stock): ?>
                   <div class="col-md-6">
                       <div class="card mb-4">
                           <div class="card-header bg-info text-white">
                               <h5 class="mb-0">📦 Düşük Stok Uyarısı (<?= count($low_stock) ?>)</h5>
                           </div>
                           <div class="card-body">
                               <div class="table-responsive">
                                   <table class="table table-sm">
                                       <thead>
                                           <tr>
                                               <th>Kitap</th>
                                               <th>Yazar</th>
                                               <th>Mevcut</th>
                                               <th>İşlem</th>
                                           </tr>
                                       </thead>
                                       <tbody>
                                           <?php foreach ($low_stock as $book): ?>
                                           <tr>
                                               <td>
                                                   <a href="book_detail.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                                       <?= substr($book['kitap_adi'], 0, 30) ?><?= strlen($book['kitap_adi']) > 30 ? '...' : '' ?>
                                                   </a>
                                               </td>
                                               <td><?= $book['yazar_adi'] ?></td>
                                               <td>
                                                   <span class="badge bg-<?= $book['mevcut_adet'] == 0 ? 'danger' : 'warning' ?>">
                                                       <?= $book['mevcut_adet'] ?>
                                                   </span>
                                               </td>
                                               <td>
                                                   <a href="books.php?edit=<?= $book['id'] ?>" class="btn btn-sm btn-outline-primary">Stok Ekle</a>
                                               </td>
                                           </tr>
                                           <?php endforeach; ?>
                                       </tbody>
                                   </table>
                               </div>
                           </div>
                       </div>
                   </div>
                   <?php endif; ?>
                   
                   <!-- Yeni Üyeler -->
                   <?php if ($new_members): ?>
                   <div class="col-md-6">
                       <div class="card mb-4">
                           <div class="card-header bg-success text-white">
                               <h5 class="mb-0">👥 Yeni Üyeler (Son 7 Gün)</h5>
                           </div>
                           <div class="card-body">
                               <div class="table-responsive">
                                   <table class="table table-sm">
                                       <thead>
                                           <tr>
                                               <th>Ad Soyad</th>
                                               <th>Email</th>
                                               <th>Kayıt Tarihi</th>
                                               <th>İşlem</th>
                                           </tr>
                                       </thead>
                                       <tbody>
                                           <?php foreach ($new_members as $member): ?>
                                           <tr>
                                               <td>
                                                   <a href="member_detail.php?id=<?= $member['id'] ?>" class="text-decoration-none">
                                                       <?= $member['ad_soyad'] ?>
                                                   </a>
                                               </td>
                                               <td><?= $member['email'] ?></td>
                                               <td><?= date('d.m.Y', strtotime($member['kayit_tarihi'])) ?></td>
                                               <td>
                                                   <a href="member_detail.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-outline-info">Detay</a>
                                               </td>
                                           </tr>
                                           <?php endforeach; ?>
                                       </tbody>
                                   </table>
                               </div>
                           </div>
                       </div>
                   </div>
                   <?php endif; ?>
               </div>
               
               <!-- Bildirim Yoksa -->
               <?php if (!$overdue_books && !$due_tomorrow && !$low_stock && !$new_members): ?>
               <div class="card">
                   <div class="card-body text-center">
                       <div class="py-5">
                           <h3 class="text-muted">✅ Tüm işlemler normal</h3>
                           <p class="text-muted">Şu anda dikkat edilmesi gereken bir durum bulunmuyor.</p>
                       </div>
                   </div>
               </div>
               <?php endif; ?>
           </main>
       </div>
   </div>
   
   <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>