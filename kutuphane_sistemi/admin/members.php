<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Üye silme işlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM kullanicilar WHERE id = ? AND kullanici_tipi = 'uye'");
    $stmt->execute([$id]);
    header('Location: members.php');
    exit;
}

// Üye ekleme/güncelleme işlemi
if ($_POST) {
    $kullanici_adi = $_POST['kullanici_adi'];
    $email = $_POST['email'];
    $ad_soyad = $_POST['ad_soyad'];
    $telefon = $_POST['telefon'];
    $adres = $_POST['adres'];
    $durum = $_POST['durum'];
    
    if (isset($_POST['id']) && $_POST['id']) {
        // Güncelleme
        $stmt = $pdo->prepare("UPDATE kullanicilar SET kullanici_adi=?, email=?, ad_soyad=?, telefon=?, adres=?, durum=? WHERE id=?");
        $stmt->execute([$kullanici_adi, $email, $ad_soyad, $telefon, $adres, $durum, $_POST['id']]);
    } else {
        // Ekleme
        $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, telefon, adres, durum, kullanici_tipi) VALUES (?, ?, ?, ?, ?, ?, ?, 'uye')");
        $stmt->execute([$kullanici_adi, $email, $sifre, $ad_soyad, $telefon, $adres, $durum]);
    }
    header('Location: members.php');
    exit;
}

// Düzenleme için üye bilgilerini getir
$edit_member = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = ? AND kullanici_tipi = 'uye'");
    $stmt->execute([$_GET['edit']]);
    $edit_member = $stmt->fetch();
}

// Üyeleri getir
$stmt = $pdo->query("SELECT * FROM kullanicilar WHERE kullanici_tipi = 'uye' ORDER BY id DESC");
$members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Yönetimi</title>
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
                    <h1 class="h2">Üye Yönetimi</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#memberModal">
                        Yeni Üye Ekle
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>Ad Soyad</th>
                                        <th>Email</th>
                                        <th>Telefon</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?= $member['id'] ?></td>
                                        <td><?= $member['kullanici_adi'] ?></td>
                                        <td>
                                            <a href="member_detail.php?id=<?= $member['id'] ?>" class="text-decoration-none">
                                                <?= $member['ad_soyad'] ?>
                                            </a>
                                        </td>
                                        <td><?= $member['email'] ?></td>
                                        <td><?= $member['telefon'] ?></td>
                                        <td><?= date('d.m.Y', strtotime($member['kayit_tarihi'])) ?></td>
                                        <td>
                                            <?php if ($member['durum'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="member_detail.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-info">Detay</a>
                                            <a href="?edit=<?= $member['id'] ?>" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#memberModal">Düzenle</a>
                                            <a href="?delete=<?= $member['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu üyeyi silmek istediğinizden emin misiniz?')">Sil</a>
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
    
    <!-- Üye Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="memberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_member ? 'Üye Düzenle' : 'Yeni Üye Ekle' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($edit_member): ?>
                            <input type="hidden" name="id" value="<?= $edit_member['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kullanıcı Adı *</label>
                                    <input type="text" class="form-control" name="kullanici_adi" value="<?= $edit_member['kullanici_adi'] ?? '' ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?= $edit_member['email'] ?? '' ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!$edit_member): ?>
                        <div class="mb-3">
                            <label class="form-label">Şifre *</label>
                            <input type="password" class="form-control" name="sifre" required>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" class="form-control" name="ad_soyad" value="<?= $edit_member['ad_soyad'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon" value="<?= $edit_member['telefon'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea class="form-control" name="adres" rows="3"><?= $edit_member['adres'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="durum">
                                <option value="aktif" <?= ($edit_member && $edit_member['durum'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="pasif" <?= ($edit_member && $edit_member['durum'] == 'pasif') ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><?= $edit_member ? 'Güncelle' : 'Ekle' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <?php if ($edit_member): ?>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('memberModal'));
        myModal.show();
    </script>
    <?php endif; ?>
</body>
</html>