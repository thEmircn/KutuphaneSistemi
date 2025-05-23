<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Ödünç verme işlemi
if (isset($_POST['odunc_ver'])) {
    $uye_id = $_POST['uye_id'];
    $kitap_id = $_POST['kitap_id'];
    $odunc_tarihi = date('Y-m-d');
    $teslim_tarihi = date('Y-m-d', strtotime('+15 days'));
    
    $stmt = $pdo->prepare("INSERT INTO odunc_islemleri (uye_id, kitap_id, odunc_tarihi, teslim_tarihi) VALUES (?, ?, ?, ?)");
    $stmt->execute([$uye_id, $kitap_id, $odunc_tarihi, $teslim_tarihi]);
    
    header('Location: loans.php');
    exit;
}

// Teslim alma işlemi
if (isset($_GET['teslim'])) {
    $id = $_GET['teslim'];
    $stmt = $pdo->prepare("UPDATE odunc_islemleri SET durum = 'teslim_edildi', gercek_teslim_tarihi = CURDATE() WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: loans.php');
    exit;
}

// Ödünç işlemlerini getir
$stmt = $pdo->query("
    SELECT o.*, u.ad_soyad as uye_adi, u.telefon, k.kitap_adi, y.ad_soyad as yazar_adi,
           DATEDIFF(CURDATE(), o.teslim_tarihi) as gecikme_gun
    FROM odunc_islemleri o
    JOIN kullanicilar u ON o.uye_id = u.id
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    ORDER BY o.id DESC
");
$loans = $stmt->fetchAll();

// Aktif üyeleri getir
$members = $pdo->query("SELECT * FROM kullanicilar WHERE kullanici_tipi = 'uye' AND durum = 'aktif' ORDER BY ad_soyad")->fetchAll();

// Mevcut kitapları getir
$available_books = $pdo->query("
    SELECT k.*, y.ad_soyad as yazar_adi
    FROM kitaplar k
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE k.mevcut_adet > 0 AND k.durum = 'mevcut'
    ORDER BY k.kitap_adi
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödünç İşlemleri</title>
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
                    <h1 class="h2">Ödünç İşlemleri</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loanModal">
                        Yeni Ödünç Ver
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Üye</th>
                                        <th>Kitap</th>
                                        <th>Ödünç Tarihi</th>
                                        <th>Teslim Tarihi</th>
                                        <th>Durum</th>
                                        <th>Gecikme</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                    <tr>
                                        <td><?= $loan['id'] ?></td>
                                        <td>
                                            <?= $loan['uye_adi'] ?><br>
                                            <small class="text-muted"><?= $loan['telefon'] ?></small>
                                        </td>
                                        <td>
                                            <?= $loan['kitap_adi'] ?><br>
                                            <small class="text-muted"><?= $loan['yazar_adi'] ?></small>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($loan['odunc_tarihi'])) ?></td>
                                        <td><?= date('d.m.Y', strtotime($loan['teslim_tarihi'])) ?></td>
                                        <td>
                                            <?php if ($loan['durum'] == 'odunc'): ?>
                                                <?php if ($loan['gecikme_gun'] > 0): ?>
                                                    <span class="badge bg-danger">Gecikme</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Ödünçte</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success">Teslim Edildi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($loan['durum'] == 'odunc' && $loan['gecikme_gun'] > 0): ?>
                                                <span class="text-danger"><?= $loan['gecikme_gun'] ?> gün</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($loan['durum'] == 'odunc'): ?>
                                                <a href="?teslim=<?= $loan['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Kitap teslim alındı mı?')">Teslim Al</a>
                                                <?php else: ?>
                                               <span class="text-muted">Tamamlandı</span>
                                           <?php endif; ?>
                                       </td>
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
   
   <!-- Ödünç Verme Modal -->
   <div class="modal fade" id="loanModal" tabindex="-1">
       <div class="modal-dialog">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title">Yeni Ödünç Ver</h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
               </div>
               <form method="POST">
                   <div class="modal-body">
                       <div class="mb-3">
                           <label class="form-label">Üye Seçin *</label>
                           <select class="form-select" name="uye_id" required>
                               <option value="">Üye Seçin</option>
                               <?php foreach ($members as $member): ?>
                                   <option value="<?= $member['id'] ?>"><?= $member['ad_soyad'] ?> (<?= $member['kullanici_adi'] ?>)</option>
                               <?php endforeach; ?>
                           </select>
                       </div>
                       
                       <div class="mb-3">
                           <label class="form-label">Kitap Seçin *</label>
                           <select class="form-select" name="kitap_id" required>
                               <option value="">Kitap Seçin</option>
                               <?php foreach ($available_books as $book): ?>
                                   <option value="<?= $book['id'] ?>">
                                       <?= $book['kitap_adi'] ?> - <?= $book['yazar_adi'] ?> (Stok: <?= $book['mevcut_adet'] ?>)
                                   </option>
                               <?php endforeach; ?>
                           </select>
                       </div>
                       
                       <div class="alert alert-info">
                           <small>Ödünç süresi: 15 gün</small>
                       </div>
                   </div>
                   <div class="modal-footer">
                       <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                       <button type="submit" name="odunc_ver" class="btn btn-primary">Ödünç Ver</button>
                   </div>
               </form>
           </div>
       </div>
   </div>
   
   <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>