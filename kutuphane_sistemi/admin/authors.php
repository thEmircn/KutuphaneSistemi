<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Yazar silme işlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM yazarlar WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: authors.php');
    exit;
}

// Yazar ekleme/güncelleme işlemi
if ($_POST) {
    $ad_soyad = $_POST['ad_soyad'];
    $dogum_tarihi = $_POST['dogum_tarihi'] ?: null;
    $biyografi = $_POST['biyografi'];
    $durum = $_POST['durum'];
    
    if (isset($_POST['id']) && $_POST['id']) {
        // Güncelleme
        $stmt = $pdo->prepare("UPDATE yazarlar SET ad_soyad=?, dogum_tarihi=?, biyografi=?, durum=? WHERE id=?");
        $stmt->execute([$ad_soyad, $dogum_tarihi, $biyografi, $durum, $_POST['id']]);
    } else {
        // Ekleme
        $stmt = $pdo->prepare("INSERT INTO yazarlar (ad_soyad, dogum_tarihi, biyografi, durum) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ad_soyad, $dogum_tarihi, $biyografi, $durum]);
    }
    header('Location: authors.php');
    exit;
}

// Düzenleme için yazar bilgilerini getir
$edit_author = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM yazarlar WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_author = $stmt->fetch();
}

// Yazarları getir
$stmt = $pdo->query("SELECT y.*, COUNT(k.id) as kitap_sayisi FROM yazarlar y LEFT JOIN kitaplar k ON y.id = k.yazar_id GROUP BY y.id ORDER BY y.id DESC");
$authors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazar Yönetimi</title>
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
                    <h1 class="h2">Yazar Yönetimi</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#authorModal">
                        Yeni Yazar Ekle
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Ad Soyad</th>
                                        <th>Doğum Tarihi</th>
                                        <th>Kitap Sayısı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($authors as $author): ?>
                                    <tr>
                                        <td><?= $author['id'] ?></td>
                                        <td><?= $author['ad_soyad'] ?></td>
                                        <td><?= $author['dogum_tarihi'] ? date('d.m.Y', strtotime($author['dogum_tarihi'])) : '-' ?></td>
                                        <td><?= $author['kitap_sayisi'] ?></td>
                                        <td>
                                            <?php if ($author['durum'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?= $author['id'] ?>" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#authorModal">Düzenle</a>
                                            <a href="?delete=<?= $author['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu yazarı silmek istediğinizden emin misiniz?')">Sil</a>
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
    
    <!-- Yazar Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="authorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_author ? 'Yazar Düzenle' : 'Yeni Yazar Ekle' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($edit_author): ?>
                            <input type="hidden" name="id" value="<?= $edit_author['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" class="form-control" name="ad_soyad" value="<?= $edit_author['ad_soyad'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Doğum Tarihi</label>
                            <input type="date" class="form-control" name="dogum_tarihi" value="<?= $edit_author['dogum_tarihi'] ?? '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Biyografi</label>
                            <textarea class="form-control" name="biyografi" rows="4"><?= $edit_author['biyografi'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="durum">
                                <option value="aktif" <?= ($edit_author && $edit_author['durum'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="pasif" <?= ($edit_author && $edit_author['durum'] == 'pasif') ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><?= $edit_author ? 'Güncelle' : 'Ekle' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <?php if ($edit_author): ?>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('authorModal'));
        myModal.show();
    </script>
    <?php endif; ?>
</body>
</html>