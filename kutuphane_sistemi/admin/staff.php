<?php
require_once '../config.php';
require_once 'includes/permissions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Yetki kontrolü
$permission->requirePermission('staff', 'read');

$success = '';
$error = '';

// Personel ekleme/güncelleme
if ($_POST) {
    if (isset($_POST['add_staff'])) {
        $permission->requirePermission('staff', 'create');
        
        $kullanici_adi = trim($_POST['kullanici_adi']);
        $email = trim($_POST['email']);
        $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
        $ad_soyad = trim($_POST['ad_soyad']);
        $telefon = trim($_POST['telefon']);
        $rol_id = $_POST['rol_id'];
        
        // Kullanıcı adı kontrolü
        $check = $pdo->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
        $check->execute([$kullanici_adi, $email]);
        
        if ($check->fetch()) {
            $error = "Bu kullanıcı adı veya email zaten kullanılıyor!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, telefon, kullanici_tipi, rol_id) VALUES (?, ?, ?, ?, ?, 'admin', ?)");
            if ($stmt->execute([$kullanici_adi, $email, $sifre, $ad_soyad, $telefon, $rol_id])) {
                $permission->logActivity('STAFF_CREATE', "Yeni personel eklendi: $ad_soyad", 'kullanicilar', $pdo->lastInsertId());
                $success = "Personel başarıyla eklendi!";
            } else {
                $error = "Personel eklenirken hata oluştu!";
            }
        }
    }
    
    if (isset($_POST['update_staff'])) {
        $permission->requirePermission('staff', 'update');
        
        $staff_id = $_POST['staff_id'];
        $ad_soyad = trim($_POST['ad_soyad']);
        $email = trim($_POST['email']);
        $telefon = trim($_POST['telefon']);
        $rol_id = $_POST['rol_id'];
        $durum = $_POST['durum'];
        
        $stmt = $pdo->prepare("UPDATE kullanicilar SET ad_soyad=?, email=?, telefon=?, rol_id=?, durum=? WHERE id=? AND kullanici_tipi='admin'");
        if ($stmt->execute([$ad_soyad, $email, $telefon, $rol_id, $durum, $staff_id])) {
            $permission->logActivity('STAFF_UPDATE', "Personel güncellendi: $ad_soyad", 'kullanicilar', $staff_id);
            $success = "Personel başarıyla güncellendi!";
        } else {
            $error = "Güncelleme sırasında hata oluştu!";
        }
    }
}

// Personel silme
if (isset($_GET['delete']) && $permission->hasPermission('staff', 'delete')) {
    $staff_id = $_GET['delete'];
    
    // Kendi hesabını silmeyi engelle
    if ($staff_id == $_SESSION['admin_id']) {
        $error = "Kendi hesabınızı silemezsiniz!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM kullanicilar WHERE id = ? AND kullanici_tipi = 'admin'");
        if ($stmt->execute([$staff_id])) {
            $permission->logActivity('STAFF_DELETE', "Personel silindi: ID $staff_id", 'kullanicilar', $staff_id);
            $success = "Personel başarıyla silindi!";
        }
    }
}

// Personelleri getir
$staff_list = $pdo->query("
    SELECT k.*, r.rol_adi, r.aciklama as rol_aciklama,
           (SELECT COUNT(*) FROM personel_aktivite_log WHERE personel_id = k.id AND DATE(tarih) = CURDATE()) as bugun_aktivite
    FROM kullanicilar k
    LEFT JOIN roller r ON k.rol_id = r.id
    WHERE k.kullanici_tipi = 'admin'
    ORDER BY k.id DESC
")->fetchAll();

// Rolleri getir
$roles = $pdo->query("SELECT * FROM roller WHERE durum = 'aktif' ORDER BY id")->fetchAll();

// Düzenleme için personel bilgilerini getir
$edit_staff = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = ? AND kullanici_tipi = 'admin'");
    $stmt->execute([$_GET['edit']]);
    $edit_staff = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Yönetimi</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        .role-badge { font-size: 0.75rem; }
        .activity-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .status-online { background-color: #28a745; }
        .status-offline { background-color: #6c757d; }
        .staff-card { transition: transform 0.2s; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .staff-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">👨‍💼 Personel Yönetimi</h1>
                    <div class="btn-toolbar">
                        <?php if ($permission->hasPermission('staff', 'create')): ?>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#staffModal">
                            ➕ Yeni Personel
                        </button>
                        <?php endif; ?>
                        <a href="roles.php" class="btn btn-outline-secondary">
                            🔑 Rol Yönetimi
                        </a>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center staff-card">
                            <div class="card-body">
                                <h3 class="text-primary"><?= count($staff_list) ?></h3>
                                <small class="text-muted">Toplam Personel</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center staff-card">
                            <div class="card-body">
                                <h3 class="text-success"><?= count(array_filter($staff_list, fn($s) => $s['durum'] == 'aktif')) ?></h3>
                                <small class="text-muted">Aktif Personel</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center staff-card">
                            <div class="card-body">
                                <h3 class="text-info"><?= array_sum(array_column($staff_list, 'bugun_aktivite')) ?></h3>
                                <small class="text-muted">Bugünkü Aktivite</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center staff-card">
                            <div class="card-body">
                                <h3 class="text-warning"><?= count($roles) ?></h3>
                                <small class="text-muted">Toplam Rol</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Personel Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">👥 Personel Listesi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Ad Soyad</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Durum</th>
                                        <th>Son Giriş</th>
                                        <th>Aktivite</th>
                                        <th width="200">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_list as $staff): ?>
                                    <tr>
                                        <td><?= $staff['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($staff['ad_soyad']) ?></strong>
                                            <?php if ($staff['id'] == $_SESSION['admin_id']): ?>
                                                <span class="badge bg-info">Siz</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($staff['kullanici_adi']) ?></td>
                                        <td><?= htmlspecialchars($staff['email']) ?></td>
                                        <td>
                                            <span class="badge bg-primary role-badge">
                                                <?= htmlspecialchars($staff['rol_adi'] ?? 'Rol Yok') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($staff['durum'] == 'aktif'): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($staff['son_giris']): ?>
                                                <small><?= date('d.m.Y H:i', strtotime($staff['son_giris'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Hiç giriş yapmamış</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="activity-indicator <?= $staff['bugun_aktivite'] > 0 ? 'status-online' : 'status-offline' ?>"></span>
                                            <small><?= $staff['bugun_aktivite'] ?> işlem</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="staff_detail.php?id=<?= $staff['id'] ?>" class="btn btn-outline-info" title="Detay">
                                                    👁️
                                                </a>
                                                <?php if ($permission->hasPermission('staff', 'update')): ?>
                                                <a href="?edit=<?= $staff['id'] ?>" class="btn btn-outline-warning" title="Düzenle">
                                                    ✏️
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($permission->hasPermission('staff', 'delete') && $staff['id'] != $_SESSION['admin_id']): ?>
                                                <a href="?delete=<?= $staff['id'] ?>" class="btn btn-outline-danger" 
                                                   onclick="return confirm('Bu personeli silmek istediğinizden emin misiniz?')" title="Sil">
                                                    🗑️
                                                </a>
                                                <?php endif; ?>
                                            </div>
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
    
    <!-- Personel Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="staffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $edit_staff ? 'Personel Düzenle' : 'Yeni Personel Ekle' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?php if ($edit_staff): ?>
                            <input type="hidden" name="staff_id" value="<?= $edit_staff['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad *</label>
                            <input type="text" class="form-control" name="ad_soyad" 
                                   value="<?= htmlspecialchars($edit_staff['ad_soyad'] ?? '') ?>" required>
                        </div>
                        
                        <?php if (!$edit_staff): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kullanıcı Adı *</label>
                                    <input type="text" class="form-control" name="kullanici_adi" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Şifre *</label>
                                    <input type="password" class="form-control" name="sifre" minlength="6" required>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($edit_staff['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon" 
                                   value="<?= htmlspecialchars($edit_staff['telefon'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Rol *</label>
                            <select class="form-select" name="rol_id" required>
                                <option value="">Rol Seçin</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" 
                                            <?= ($edit_staff && $edit_staff['rol_id'] == $role['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($role['rol_adi']) ?> - <?= htmlspecialchars($role['aciklama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($edit_staff): ?>
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="durum">
                                <option value="aktif" <?= ($edit_staff['durum'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="pasif" <?= ($edit_staff['durum'] == 'pasif') ? 'selected' : '' ?>>Pasif</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="<?= $edit_staff ? 'update_staff' : 'add_staff' ?>" class="btn btn-primary">
                            <?= $edit_staff ? 'Güncelle' : 'Ekle' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <?php if ($edit_staff): ?>
    <script>
        var modal = new bootstrap.Modal(document.getElementById('staffModal'));
        modal.show();
    </script>
    <?php endif; ?>
</body>
</html>