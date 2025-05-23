<?php
require_once '../config.php';
require_once 'includes/permissions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$permission->requirePermission('staff', 'update');

$success = '';
$error = '';

// Rol ekleme/güncelleme
if ($_POST) {
    if (isset($_POST['save_role'])) {
        $rol_adi = trim($_POST['rol_adi']);
        $aciklama = trim($_POST['aciklama']);
        
        // İzinleri JSON olarak hazırla
        $izinler = [];
        $modules = ['books', 'members', 'loans', 'reports', 'staff', 'system'];
        $actions = ['create', 'read', 'update', 'delete'];
        
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $key = $module . '_' . $action;
                if (isset($_POST[$key])) {
                    $izinler[$module][$action] = true;
                }
            }
        }
        
        // Super admin kontrolü
        if (isset($_POST['is_super_admin'])) {
            $izinler = ['all' => true];
        }
        
        if (isset($_POST['role_id']) && $_POST['role_id']) {
            // Güncelleme
            $stmt = $pdo->prepare("UPDATE roller SET rol_adi=?, aciklama=?, izinler=? WHERE id=?");
            if ($stmt->execute([$rol_adi, $aciklama, json_encode($izinler), $_POST['role_id']])) {
                $permission->logActivity('ROLE_UPDATE', "Rol güncellendi: $rol_adi", 'roller', $_POST['role_id']);
                $success = "Rol başarıyla güncellendi!";
            }
        } else {
            // Ekleme
            $stmt = $pdo->prepare("INSERT INTO roller (rol_adi, aciklama, izinler) VALUES (?, ?, ?)");
            if ($stmt->execute([$rol_adi, $aciklama, json_encode($izinler)])) {
                $permission->logActivity('ROLE_CREATE', "Yeni rol oluşturuldu: $rol_adi", 'roller', $pdo->lastInsertId());
                $success = "Rol başarıyla eklendi!";
            }
        }
    }
}

// Rol silme
if (isset($_GET['delete'])) {
    $role_id = $_GET['delete'];
    
    // Bu rolü kullanan kullanıcı var mı kontrol et
    $check = $pdo->prepare("SELECT COUNT(*) FROM kullanicilar WHERE rol_id = ?");
    $check->execute([$role_id]);
    
    if ($check->fetchColumn() > 0) {
        $error = "Bu rol kullanımda olduğu için silinemez!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM roller WHERE id = ?");
        if ($stmt->execute([$role_id])) {
            $permission->logActivity('ROLE_DELETE', "Rol silindi: ID $role_id", 'roller', $role_id);
            $success = "Rol başarıyla silindi!";
        }
    }
}

// Rolleri getir
$roles = $pdo->query("
   SELECT r.*, 
          (SELECT COUNT(*) FROM kullanicilar WHERE rol_id = r.id) as kullanici_sayisi
   FROM roller r 
   ORDER BY r.id
")->fetchAll();

// Düzenleme için rol bilgilerini getir
$edit_role = null;
if (isset($_GET['edit'])) {
   $stmt = $pdo->prepare("SELECT * FROM roller WHERE id = ?");
   $stmt->execute([$_GET['edit']]);
   $edit_role = $stmt->fetch();
   if ($edit_role) {
       $edit_role['izinler'] = json_decode($edit_role['izinler'], true);
   }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Rol Yönetimi</title>
   <link href="../css/bootstrap.min.css" rel="stylesheet">
   <link href="../css/admin.css" rel="stylesheet">
   <style>
       .permission-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
       .permission-module { border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; background: #f8f9fa; }
       .permission-module h6 { color: #495057; border-bottom: 1px solid #dee2e6; padding-bottom: 0.5rem; margin-bottom: 0.75rem; }
       .form-check { margin-bottom: 0.5rem; }
       .role-badge { font-size: 0.8rem; }
   </style>
</head>
<body>
   <?php include 'includes/header.php'; ?>
   
   <div class="container-fluid">
       <div class="row">
           <?php include 'includes/sidebar.php'; ?>
           
           <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
               <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                   <h1 class="h2">🔑 Rol Yönetimi</h1>
                   <div class="btn-toolbar">
                       <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#roleModal">
                           ➕ Yeni Rol
                       </button>
                       <a href="staff.php" class="btn btn-outline-secondary">
                           ← Personel Listesi
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
               
               <!-- Rol Listesi -->
               <div class="row">
                   <?php foreach ($roles as $role): ?>
                   <div class="col-lg-6 mb-4">
                       <div class="card h-100">
                           <div class="card-header d-flex justify-content-between align-items-center">
                               <h5 class="mb-0"><?= htmlspecialchars($role['rol_adi']) ?></h5>
                               <div>
                                   <span class="badge bg-info"><?= $role['kullanici_sayisi'] ?> kişi</span>
                                   <?php if ($role['durum'] == 'aktif'): ?>
                                       <span class="badge bg-success">Aktif</span>
                                   <?php else: ?>
                                       <span class="badge bg-secondary">Pasif</span>
                                   <?php endif; ?>
                               </div>
                           </div>
                           <div class="card-body">
                               <p class="text-muted mb-3"><?= htmlspecialchars($role['aciklama']) ?></p>
                               
                               <!-- İzinleri göster -->
                               <h6>İzinler:</h6>
                               <div class="row">
                                   <?php 
                                   $permissions = json_decode($role['izinler'], true);
                                   if (isset($permissions['all']) && $permissions['all']):
                                   ?>
                                       <div class="col-12">
                                           <span class="badge bg-danger role-badge">🔥 TÜM YETKİLER</span>
                                       </div>
                                   <?php else: ?>
                                       <?php
                                       $modules = [
                                           'books' => '📚 Kitaplar',
                                           'members' => '👥 Üyeler', 
                                           'loans' => '📖 Ödünç',
                                           'reports' => '📊 Raporlar',
                                           'staff' => '👨‍💼 Personel',
                                           'system' => '⚙️ Sistem'
                                       ];
                                       
                                       foreach ($modules as $key => $label):
                                           if (isset($permissions[$key]) && !empty($permissions[$key])):
                                       ?>
                                           <div class="col-6 mb-2">
                                               <small><strong><?= $label ?></strong></small><br>
                                               <?php foreach ($permissions[$key] as $action => $allowed): ?>
                                                   <?php if ($allowed): ?>
                                                       <span class="badge bg-primary role-badge me-1">
                                                           <?= ucfirst($action) ?>
                                                       </span>
                                                   <?php endif; ?>
                                               <?php endforeach; ?>
                                           </div>
                                       <?php 
                                           endif;
                                       endforeach; 
                                       ?>
                                   <?php endif; ?>
                               </div>
                           </div>
                           <div class="card-footer">
                               <div class="btn-group btn-group-sm w-100">
                                   <a href="?edit=<?= $role['id'] ?>" class="btn btn-outline-warning">
                                       ✏️ Düzenle
                                   </a>
                                   <?php if ($role['kullanici_sayisi'] == 0 && $role['id'] > 4): // İlk 4 rol sistemin temel rolleri ?>
                                   <a href="?delete=<?= $role['id'] ?>" class="btn btn-outline-danger"
                                      onclick="return confirm('Bu rolü silmek istediğinizden emin misiniz?')">
                                       🗑️ Sil
                                   </a>
                                   <?php endif; ?>
                                   <button type="button" class="btn btn-outline-info" onclick="showRoleUsers(<?= $role['id'] ?>)">
                                       👥 Kullanıcılar
                                   </button>
                               </div>
                           </div>
                       </div>
                   </div>
                   <?php endforeach; ?>
               </div>
           </main>
       </div>
   </div>
   
   <!-- Rol Ekleme/Düzenleme Modal -->
   <div class="modal fade" id="roleModal" tabindex="-1">
       <div class="modal-dialog modal-lg">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title"><?= $edit_role ? 'Rol Düzenle' : 'Yeni Rol Oluştur' ?></h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
               </div>
               <form method="POST">
                   <div class="modal-body">
                       <?php if ($edit_role): ?>
                           <input type="hidden" name="role_id" value="<?= $edit_role['id'] ?>">
                       <?php endif; ?>
                       
                       <div class="row mb-3">
                           <div class="col-md-6">
                               <label class="form-label">Rol Adı *</label>
                               <input type="text" class="form-control" name="rol_adi" 
                                      value="<?= htmlspecialchars($edit_role['rol_adi'] ?? '') ?>" required>
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Açıklama</label>
                               <input type="text" class="form-control" name="aciklama" 
                                      value="<?= htmlspecialchars($edit_role['aciklama'] ?? '') ?>">
                           </div>
                       </div>
                       
                       <!-- Super Admin Checkbox -->
                       <div class="mb-3">
                           <div class="form-check">
                               <input class="form-check-input" type="checkbox" id="is_super_admin" name="is_super_admin"
                                      <?= ($edit_role && isset($edit_role['izinler']['all'])) ? 'checked' : '' ?>
                                      onchange="toggleSuperAdmin()">
                               <label class="form-check-label text-danger" for="is_super_admin">
                                   <strong>🔥 Super Admin (Tüm Yetkiler)</strong>
                               </label>
                           </div>
                       </div>
                       
                       <!-- İzin Matrisi -->
                       <div id="permissionMatrix">
                           <h6>İzinler:</h6>
                           <div class="permission-grid">
                               <?php
                               $modules = [
                                   'books' => ['📚 Kitaplar', ['create' => 'Ekle', 'read' => 'Görüntüle', 'update' => 'Düzenle', 'delete' => 'Sil']],
                                   'members' => ['👥 Üyeler', ['create' => 'Ekle', 'read' => 'Görüntüle', 'update' => 'Düzenle', 'delete' => 'Sil']],
                                   'loans' => ['📖 Ödünç İşlemleri', ['create' => 'Ver', 'read' => 'Görüntüle', 'update' => 'Güncelle', 'delete' => 'İptal']],
                                   'reports' => ['📊 Raporlar', ['read' => 'Görüntüle']],
                                   'staff' => ['👨‍💼 Personel', ['create' => 'Ekle', 'read' => 'Görüntüle', 'update' => 'Düzenle', 'delete' => 'Sil']],
                                   'system' => ['⚙️ Sistem', ['update' => 'Ayarlar', 'delete' => 'Temizle']]
                               ];
                               
                               foreach ($modules as $module => $config):
                                   [$title, $actions] = $config;
                               ?>
                               <div class="permission-module">
                                   <h6><?= $title ?></h6>
                                   <?php foreach ($actions as $action => $label): ?>
                                   <div class="form-check">
                                       <input class="form-check-input permission-checkbox" type="checkbox" 
                                              name="<?= $module ?>_<?= $action ?>" 
                                              id="<?= $module ?>_<?= $action ?>"
                                              <?= ($edit_role && isset($edit_role['izinler'][$module][$action])) ? 'checked' : '' ?>>
                                       <label class="form-check-label" for="<?= $module ?>_<?= $action ?>">
                                           <?= $label ?>
                                       </label>
                                   </div>
                                   <?php endforeach; ?>
                               </div>
                               <?php endforeach; ?>
                           </div>
                       </div>
                   </div>
                   <div class="modal-footer">
                       <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                       <button type="submit" name="save_role" class="btn btn-primary">
                           <?= $edit_role ? 'Güncelle' : 'Oluştur' ?>
                       </button>
                   </div>
               </form>
           </div>
       </div>
   </div>
   
   <!-- Rol Kullanıcıları Modal -->
   <div class="modal fade" id="roleUsersModal" tabindex="-1">
       <div class="modal-dialog">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title">Rol Kullanıcıları</h5>
                   <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
               </div>
               <div class="modal-body" id="roleUsersContent">
                   <div class="text-center">
                       <div class="spinner-border" role="status">
                           <span class="visually-hidden">Yükleniyor...</span>
                       </div>
                   </div>
               </div>
           </div>
       </div>
   </div>
   
   <script src="../js/bootstrap.bundle.min.js"></script>
   <script>
   function toggleSuperAdmin() {
       const superAdminCheckbox = document.getElementById('is_super_admin');
       const permissionMatrix = document.getElementById('permissionMatrix');
       const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
       
       if (superAdminCheckbox.checked) {
           permissionMatrix.style.opacity = '0.5';
           permissionCheckboxes.forEach(cb => {
               cb.disabled = true;
               cb.checked = false;
           });
       } else {
           permissionMatrix.style.opacity = '1';
           permissionCheckboxes.forEach(cb => {
               cb.disabled = false;
           });
       }
   }
   
   function showRoleUsers(roleId) {
       const modal = new bootstrap.Modal(document.getElementById('roleUsersModal'));
       modal.show();
       
       fetch(`role_users.php?role_id=${roleId}`)
           .then(response => response.text())
           .then(html => {
               document.getElementById('roleUsersContent').innerHTML = html;
           })
           .catch(error => {
               document.getElementById('roleUsersContent').innerHTML = 
                   '<div class="alert alert-danger">Kullanıcılar yüklenirken hata oluştu.</div>';
           });
   }
   
   // Sayfa yüklendiğinde super admin durumunu kontrol et
   document.addEventListener('DOMContentLoaded', function() {
       toggleSuperAdmin();
   });
   
   <?php if ($edit_role): ?>
   // Düzenleme modalını aç
   var editModal = new bootstrap.Modal(document.getElementById('roleModal'));
   editModal.show();
   <?php endif; ?>
   </script>
</body>
</html>