<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Kategori silme işlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM kategoriler WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: categories.php');
    exit;
}

// Kategori ekleme/güncelleme işlemi
if ($_POST) {
    $kategori_adi = $_POST['kategori_adi'];
    $aciklama = $_POST['aciklama'];
    $durum = $_POST['durum'];
    
    if (isset($_POST['id']) && $_POST['id']) {
        // Güncelleme
        $stmt = $pdo->prepare("UPDATE kategoriler SET kategori_adi=?, aciklama=?, durum=? WHERE id=?");
        $stmt->execute([$kategori_adi, $aciklama, $durum, $_POST['id']]);
    } else {
        // Ekleme
        $stmt = $pdo->prepare("INSERT INTO kategoriler (kategori_adi, aciklama, durum) VALUES (?, ?, ?)");
        $stmt->execute([$kategori_adi, $aciklama, $durum]);
    }
    header('Location: categories.php');
    exit;
}

// Düzenleme için kategori bilgilerini getir
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kategoriler WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch();
}

// Kategorileri getir
$stmt = $pdo->query("SELECT k.*, COUNT(kt.id) as kitap_sayisi FROM kategoriler k LEFT JOIN kitaplar kt ON k.id = kt.kategori_id GROUP BY k.id ORDER BY k.id DESC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori Yönetimi</title>
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
                    <h1 class="h2">Kategori Yönetimi</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        Yeni Kategori Ekle
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kategori Adı</th>
                                        <th>Açıklama</th>
                                        <th>Kitap Sayısı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td><?= $category['kategori_adi'] ?></td>
                                        <td><?= substr($category['aciklama'], 0, 50) ?><?= strlen($category['aciklama']) > 50 ? '...' : '' ?></td>
                                        <td><?= $category['kitap_sayisi'] ?></td>
                                        <td>
                                            <?php if ($category['durum'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?edit=<?= $category['id'] ?>" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#categoryModal">Düzenle</a>
                                            <a href="?delete=<?= $category['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">Sil</a>
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
    
    <!-- Kategori Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_category ? 'Kategori Düzenle' : 'Yeni Kategori Ekle' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori Adı *</label>
                            <input type="text" class="form-control" name="kategori_adi" value="<?= $edit_category['kategori_adi'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="aciklama" rows="3"><?= $edit_category['aciklama'] ?? '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="durum">
                                <option value="aktif" <?= ($edit_category && $edit_category['durum'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="pasif" <?= ($edit_category && $edit_category['durum'] == 'pasif') ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary"><?= $edit_category ? 'Güncelle' : 'Ekle' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <?php if ($edit_category): ?>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('categoryModal'));
        myModal.show();
    </script>
    <?php endif; ?>
</body>
</html>