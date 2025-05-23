<?php
require_once '../config.php';
require_once 'includes/permissions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$permission->requirePermission('staff', 'read');

$staff_id = $_GET['id'] ?? 0;

// Personel bilgilerini getir
$staff = $pdo->prepare("
    SELECT k.*, r.rol_adi, r.aciklama as rol_aciklama, r.izinler
    FROM kullanicilar k
    LEFT JOIN roller r ON k.rol_id = r.id
    WHERE k.id = ? AND k.kullanici_tipi = 'admin'
");
$staff->execute([$staff_id]);
$staff_info = $staff->fetch();

if (!$staff_info) {
    header('Location: staff.php');
    exit;
}

// Aktivite logları
$activities = $pdo->prepare("
    SELECT * FROM personel_aktivite_log 
    WHERE personel_id = ? 
    ORDER BY tarih DESC 
    LIMIT 50
");
$activities->execute([$staff_id]);
$activity_log = $activities->fetchAll();

// İstatistikler
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as toplam_aktivite,
        COUNT(CASE WHEN DATE(tarih) = CURDATE() THEN 1 END) as bugun_aktivite,
        COUNT(CASE WHEN DATE(tarih) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as hafta_aktivite,
        COUNT(CASE WHEN DATE(tarih) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as ay_aktivite
    FROM personel_aktivite_log 
    WHERE personel_id = ?
");
$stats->execute([$staff_id]);
$staff_stats = $stats->fetch();

$permissions = json_decode($staff_info['izinler'] ?? '{}', true);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Detayı - <?= htmlspecialchars($staff_info['ad_soyad']) ?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .profile-avatar { width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto; }
        .stat-card { text-align: center; padding: 1.5rem; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .activity-item { border-left: 3px solid #007bff; padding-left: 1rem; margin-bottom: 1rem; }
        .permission-badge { font-size: 0.75rem; margin: 0.2rem; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="profile-avatar">
                                    <?= strtoupper(substr($staff_info['ad_soyad'], 0, 1)) ?>
                                </div>
                            </div>
                            <div class="col">
                                <h1 class="mb-1"><?= htmlspecialchars($staff_info['ad_soyad']) ?></h1>
                                <p class="mb-0"><?= htmlspecialchars($staff_info['rol_adi'] ?? 'Rol Atanmamış') ?></p>
                                <small class="text-white-50">
                                    Kayıt: <?= date('d.m.Y', strtotime($staff_info['kayit_tarihi'])) ?>
                                    <?php if ($staff_info['son_giris']): ?>
                                        | Son Giriş: <?= date('d.m.Y H:i', strtotime($staff_info['son_giris'])) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-auto">
                                <a href="staff.php" class="btn btn-light">← Geri Dön</a>
                                <?php if ($permission->hasPermission('staff', 'update')): ?>
                                <a href="staff.php?edit=<?= $staff_info['id'] ?>" class="btn btn-warning">Düzenle</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="container my-4">
                    <div class="row">
                        <!-- Sol Kolon - Bilgiler -->
                        <div class="col-lg-4">
                            <!-- İstatistikler -->
                            <div class="row mb-4">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h4 class="text-primary"><?= $staff_stats['bugun_aktivite'] ?></h4>
                                        <small class="text-muted">Bugün</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h4 class="text-success"><?= $staff_stats['hafta_aktivite'] ?></h4>
                                        <small class="text-muted">Bu Hafta</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h4 class="text-info"><?= $staff_stats['ay_aktivite'] ?></h4>
                                        <small class="text-muted">Bu Ay</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <h4 class="text-warning"><?= $staff_stats['toplam_aktivite'] ?></h4>
                                        <small class="text-muted">Toplam</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kişisel Bilgiler -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">👤 Kişisel Bilgiler</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Email:</strong> <?= htmlspecialchars($staff_info['email']) ?></p>
                                    <p><strong>Telefon:</strong> <?= htmlspecialchars($staff_info['telefon'] ?? 'Belirtilmemiş') ?></p>
                                    <p><strong>Kullanıcı Adı:</strong> <?= htmlspecialchars($staff_info['kullanici_adi']) ?></p>
                                    <p><strong>Durum:</strong> 
                                        <span class="badge bg-<?= $staff_info['durum'] == 'aktif' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($staff_info['durum']) ?>
                                        </span>
                                    </p>
                                    <?php if ($staff_info['adres']): ?>
                                    <p><strong>Adres:</strong><br><?= nl2br(htmlspecialchars($staff_info['adres'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- İzinler -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">🔑 Yetkiler</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($permissions['all']) && $permissions['all']): ?>
                                        <span class="badge bg-danger permission-badge">🔥 TÜM YETKİLER</span>
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
                                        <div class="mb-2">
                                            <strong><?= $label ?>:</strong><br>
                                            <?php foreach ($permissions[$key] as $action => $allowed): ?>
                                                <?php if ($allowed): ?>
                                                    <span class="badge bg-primary permission-badge">
                                                        <?= ucfirst($action) ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        ___
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sağ Kolon - Aktivite Logları -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">📋 Aktivite Geçmişi</h6>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <?php if ($activity_log): ?>
                                        <?php foreach ($activity_log as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?= htmlspecialchars($activity['aktivite_turu']) ?></strong>
                                                    <?php if ($activity['aktivite_detay']): ?>
                                                        <p class="mb-1 text-muted"><?= htmlspecialchars($activity['aktivite_detay']) ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($activity['etkilenen_tablo']): ?>
                                                        <small class="text-info">
                                                            Tablo: <?= htmlspecialchars($activity['etkilenen_tablo']) ?>
                                                            <?php if ($activity['etkilenen_kayit_id']): ?>
                                                                (ID: <?= $activity['etkilenen_kayit_id'] ?>)
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('d.m.Y H:i', strtotime($activity['tarih'])) ?>
                                                </small>
                                            </div>
                                            <?php if ($activity['ip_adresi']): ?>
                                                <small class="text-muted">IP: <?= htmlspecialchars($activity['ip_adresi']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-5">
                                            <div style="font-size: 3rem;">📝</div>
                                            <p>Henüz aktivite kaydı bulunmuyor.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
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