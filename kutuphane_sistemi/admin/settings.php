<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Ayarları güncelle
if ($_POST) {
    foreach ($_POST as $key => $value) {
        if ($key != 'submit') {
            $stmt = $pdo->prepare("UPDATE sistem_ayarlari SET ayar_degeri = ? WHERE ayar_adi = ?");
            $stmt->execute([$value, $key]);
        }
    }
    $success = "Ayarlar başarıyla güncellendi!";
}

// Sistem ayarlarını getir
$stmt = $pdo->query("SELECT ayar_adi, ayar_degeri FROM sistem_ayarlari");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $setting) {
    $settings[$setting['ayar_adi']] = $setting['ayar_degeri'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları</title>
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
                    <h1 class="h2">Sistem Ayarları</h1>
                </div>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Genel Ayarlar</h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Kütüphane Adı</label>
                                        <input type="text" class="form-control" name="kutuphane_adi" value="<?= $settings['kutuphane_adi'] ?? '' ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Kütüphane Adresi</label>
                                        <textarea class="form-control" name="kutuphane_adres" rows="3"><?= $settings['kutuphane_adres'] ?? '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Kütüphane Telefonu</label>
                                        <input type="text" class="form-control" name="kutuphane_telefon" value="<?= $settings['kutuphane_telefon'] ?? '' ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Ödünç Ayarları</h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Ödünç Süresi (Gün)</label>
                                        <input type="number" class="form-control" name="odunc_suresi" value="<?= $settings['odunc_suresi'] ?? 15 ?>" min="1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Maksimum Ödünç Kitap Sayısı</label>
                                        <input type="number" class="form-control" name="max_odunc_kitap" value="<?= $settings['max_odunc_kitap'] ?? 3 ?>" min="1">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Günlük Gecikme Ücreti (TL)</label>
                                        <input type="number" step="0.01" class="form-control" name="gecikme_ucreti" value="<?= $settings['gecikme_ucreti'] ?? 2.50 ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg">Ayarları Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- İstatistikler -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Sistem İstatistikleri</h5>
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
                                    <h4 class="text-warning"><?= $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc'")->fetchColumn() ?></h4>
                                    <small>Aktif Ödünç</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info"><?= $pdo->query("SELECT COUNT(*) FROM yazarlar")->fetchColumn() ?></h4>
                                    <small>Toplam Yazar</small>
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