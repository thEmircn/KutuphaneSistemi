<?php
require_once 'auth.php';

$success = '';
$error = '';

// Profil güncelleme
if ($_POST) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $adres = trim($_POST['adres']);
    $eski_sifre = $_POST['eski_sifre'] ?? '';
    $yeni_sifre = $_POST['yeni_sifre'] ?? '';
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'] ?? '';
    
    // Email kontrolü
    $email_check = $pdo->prepare("SELECT id FROM kullanicilar WHERE email = ? AND id != ?");
    $email_check->execute([$email, $_SESSION['member_id']]);
    if ($email_check->fetch()) {
        $error = "Bu email adresi başka bir kullanıcı tarafından kullanılıyor!";
    } else {
        // Şifre değişikliği kontrolü
        $update_password = false;
        if (!empty($eski_sifre) || !empty($yeni_sifre)) {
            if (empty($eski_sifre)) {
                $error = "Mevcut şifrenizi girmelisiniz!";
            } elseif (empty($yeni_sifre)) {
                $error = "Yeni şifrenizi girmelisiniz!";
            } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
                $error = "Yeni şifreler eşleşmiyor!";
            } elseif (strlen($yeni_sifre) < 6) {
                $error = "Şifre en az 6 karakter olmalıdır!";
            } else {
                // Eski şifre kontrolü
                $user_check = $pdo->prepare("SELECT sifre FROM kullanicilar WHERE id = ?");
                $user_check->execute([$_SESSION['member_id']]);
                $user = $user_check->fetch();
                
                if (!password_verify($eski_sifre, $user['sifre'])) {
                    $error = "Mevcut şifreniz yanlış!";
                } else {
                    $update_password = true;
                }
            }
        }
        
        if (empty($error)) {
            try {
                if ($update_password) {
                    $hashed_password = password_hash($yeni_sifre, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE kullanicilar SET ad_soyad=?, email=?, telefon=?, adres=?, sifre=? WHERE id=?");
                    $stmt->execute([$ad_soyad, $email, $telefon, $adres, $hashed_password, $_SESSION['member_id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE kullanicilar SET ad_soyad=?, email=?, telefon=?, adres=? WHERE id=?");
                    $stmt->execute([$ad_soyad, $email, $telefon, $adres, $_SESSION['member_id']]);
                }
                
                $_SESSION['member_name'] = $ad_soyad;
                $_SESSION['member_email'] = $email;
                $success = "Profiliniz başarıyla güncellendi!";
            } catch (Exception $e) {
                $error = "Güncelleme sırasında bir hata oluştu!";
            }
        }
    }
}

// Kullanıcı bilgilerini getir
$user_query = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user_query->execute([$_SESSION['member_id']]);
$user = $user_query->fetch();

// Üye istatistikleri
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as toplam_odunc,
        COUNT(CASE WHEN durum = 'odunc' THEN 1 END) as aktif_odunc,
        SUM(CASE WHEN gecikme_ucreti > 0 THEN gecikme_ucreti ELSE 0 END) as toplam_ceza,
        MAX(odunc_tarihi) as son_odunc
    FROM odunc_islemleri 
    WHERE uye_id = ?
");
$stats->execute([$_SESSION['member_id']]);
$user_stats = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Üye Paneli</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .profile-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            margin: 0 auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-item {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($_SESSION['member_name'], 0, 1)) ?>
                    </div>
                </div>
                <div class="col">
                    <h1 class="mb-1"><?= $_SESSION['member_name'] ?></h1>
                    <p class="mb-0"><?= $_SESSION['member_email'] ?></p>
                    <small class="text-white-50">
                        Üye olma tarihi: <?= date('d.m.Y', strtotime($user['kayit_tarihi'])) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container my-4">
        <div class="row">
            <!-- Sol Kolon - İstatistikler -->
            <div class="col-lg-4 mb-4">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div style="font-size: 2rem;">📚</div>
                        <h4 class="text-primary mt-2"><?= number_format($user_stats['toplam_odunc']) ?></h4>
                        <small class="text-muted">Toplam Ödünç</small>
                    </div>
                    
                    <div class="stat-item">
                        <div style="font-size: 2rem;">📖</div>
                        <h4 class="text-warning mt-2"><?= number_format($user_stats['aktif_odunc']) ?></h4>
                        <small class="text-muted">Aktif Ödünç</small>
                    </div>
                    
                    
                    
                    <div class="stat-item">
                        <div style="font-size: 2rem;">⏰</div>
                        <h6 class="text-info mt-2">
                            <?= $user_stats['son_odunc'] ? date('d.m.Y', strtotime($user_stats['son_odunc'])) : 'Hiç' ?>
                        </h6>
                        <small class="text-muted">Son Ödünç</small>
                    </div>
                </div>
                
                <!-- Hesap Durumu -->
                <div class="card profile-card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">ℹ️ Hesap Durumu</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Durum:</span>
                            <span class="badge bg-<?= $user['durum'] == 'aktif' ? 'success' : 'danger' ?>">
                                <?= ucfirst($user['durum']) ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Son Giriş:</span>
                            <small class="text-muted">
                                <?= $user['son_giris'] ? date('d.m.Y H:i', strtotime($user['son_giris'])) : 'Hiç' ?>
                            </small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Üyelik Süresi:</span>
                            <small class="text-muted">
                                <?= floor((time() - strtotime($user['kayit_tarihi'])) / (60 * 60 * 24)) ?> gün
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sağ Kolon - Profil Formu -->
            <div class="col-lg-8">
                <div class="card profile-card">
                    <div class="card-header">
                        <h5 class="mb-0">✏️ Profil Bilgilerini Düzenle</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ad Soyad *</label>
                                        <input type="text" class="form-control" name="ad_soyad" 
                                               value="<?= safe_html($user['ad_soyad']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" 
                                               value="<?= safe_html($user['email']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" class="form-control" name="telefon" 
                                               value="<?= safe_html($user['telefon']) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" 
                                               value="<?= safe_html($user['kullanici_adi']) ?>" readonly>
                                        <div class="form-text">Kullanıcı adı değiştirilemez</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adres</label>
                                <textarea class="form-control" name="adres" rows="3"><?= safe_html($user['adres']) ?></textarea>
                            </div>
                            
                            <hr>
                            
                            <h6 class="mb-3">🔐 Şifre Değiştir</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Mevcut Şifre</label>
                                        <input type="password" class="form-control" name="eski_sifre">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Yeni Şifre</label>
                                        <input type="password" class="form-control" name="yeni_sifre" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Yeni Şifre (Tekrar)</label>
                                        <input type="password" class="form-control" name="yeni_sifre_tekrar" minlength="6">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 Değişiklikleri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Hesap İşlemleri -->
                <div class="card profile-card mt-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">⚠️ Hesap İşlemleri</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Veri İndirme</h6>
                                <p class="text-muted small">Kişisel verilerinizi indirin</p>
                                <button type="button" class="btn btn-outline-info" onclick="downloadData()">
                                    📥 Verilerimi İndir
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h6>Hesap Silme</h6>
                                <p class="text-muted small">Hesabınızı kalıcı olarak silin</p>
                                <button type="button" class="btn btn-outline-danger" onclick="deleteAccount()">
                                    🗑️ Hesabı Sil
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    function downloadData() {
        if (confirm('Kişisel verilerinizi JSON formatında indirmek istediğinizden emin misiniz?')) {
            window.location.href = 'download_data.php';
        }
    }
    
    function deleteAccount() {
        if (confirm('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
            if (confirm('Son kez soruyorum: Hesabınızı kalıcı olarak silmek istediğinizden emin misiniz?')) {
                window.location.href = 'delete_account.php';
            }
        }
    }
    
    // Şifre eşleşme kontrolü
    document.addEventListener('DOMContentLoaded', function() {
        const newPassword = document.querySelector('input[name="yeni_sifre"]');
        const confirmPassword = document.querySelector('input[name="yeni_sifre_tekrar"]');
        
        function checkPasswordMatch() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Şifreler eşleşmiyor!');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        }
        
        newPassword.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
    });
    </script>
</body>
</html>