<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Yedekleme işlemi
if (isset($_POST['create_backup'])) {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_path = '../backups/' . $backup_file;
    
    // Backups klasörünü oluştur
    if (!is_dir('../backups')) {
        mkdir('../backups', 0755, true);
    }
    
    $command = "mysqldump --host={$host} --user={$username} --password={$password} {$dbname} > {$backup_path}";
    
    if (exec($command)) {
        $success = "Yedekleme başarıyla oluşturuldu: " . $backup_file;
    } else {
        $error = "Yedekleme oluşturulamadı!";
    }
}

// Mevcut yedeklemeleri listele
$backups = [];
if (is_dir('../backups')) {
    $files = scandir('../backups');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize('../backups/' . $file),
                'date' => filemtime('../backups/' . $file)
            ];
        }
    }
    // Tarihe göre sırala (en yeni ilk)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}

// Yedekleme silme
if (isset($_GET['delete_backup'])) {
    $backup_name = $_GET['delete_backup'];
    if (file_exists('../backups/' . $backup_name)) {
        if (unlink('../backups/' . $backup_name)) {
            $success = "Yedekleme silindi: " . $backup_name;
        } else {
            $error = "Yedekleme silinemedi!";
        }
    }
    header('Location: backup.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yedekleme</title>
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
                    <h1 class="h2">Veritabanı Yedekleme</h1>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="create_backup" class="btn btn-success">
                            💾 Yeni Yedek Oluştur
                        </button>
                    </form>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <!-- Bilgi Kartı -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">ℹ️ Yedekleme Hakkında</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Otomatik Yedekleme:</h6>
                                <ul>
                                    <li>Günlük otomatik yedekleme önerilir</li>
                                    <li>Önemli işlemlerden önce manuel yedek alın</li>
                                    <li>Yedekler güvenli bir yerde saklanmalıdır</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Önemli Notlar:</h6>
                                <ul>
                                    <li>Yedeklemeler server'da /backups klasöründe saklanır</li>
                                    <li>Büyük veritabanları için süre uzayabilir</li>
                                    <li>Düzenli olarak eski yedekleri temizleyin</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mevcut Yedeklemeler -->
                <div class="card">
                    <div class="card-header">
                        <h5>Mevcut Yedeklemeler</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($backups): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Dosya Adı</th>
                                        <th>Oluşturma Tarihi</th>
                                        <th>Boyut</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <code><?= $backup['name'] ?></code>
                                        </td>
                                        <td><?= date('d.m.Y H:i:s', $backup['date']) ?></td>
                                        <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                        <td>
                                            <a href="../backups/<?= $backup['name'] ?>" class="btn btn-sm btn-primary" download>
                                                📥 İndir
                                            </a>
                                            <a href="?delete_backup=<?= $backup['name'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Bu yedeklemeyi silmek istediğinizden emin misiniz?')">
                                                🗑️ Sil
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">Henüz yedekleme bulunmuyor</h5>
                            <p class="text-muted">İlk yedeklemenizi oluşturmak için yukarıdaki butona tıklayın.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sistem Bilgileri -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Sistem Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary"><?= $pdo->query("SELECT COUNT(*) FROM kitaplar")->fetchColumn() ?></h4>
                                    <small>Toplam Kitap</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-success"><?= $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_tipi = 'uye'")->fetchColumn() ?></h4>
                                    <small>Toplam Üye</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning"><?= $pdo->query("SELECT COUNT(*) FROM odunc_islemleri")->fetchColumn() ?></h4>
                                    <small>Toplam İşlem</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info"><?= date('d.m.Y H:i') ?></h4>
                                    <small>Son Güncelleme</small>
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