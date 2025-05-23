<?php
require_once 'config.php';

if ($_POST) {
    $kullanici_adi = $_POST['kullanici_adi'];
    $email = $_POST['email'];
    $sifre = password_hash($_POST['sifre'], PASSWORD_DEFAULT);
    $ad_soyad = $_POST['ad_soyad'];
    $telefon = $_POST['telefon'];
    $adres = $_POST['adres'];
    
    // Kullanıcı adı kontrolü
    $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
    $stmt->execute([$kullanici_adi, $email]);
    
    if ($stmt->fetch()) {
        $error = "Bu kullanıcı adı veya email zaten kullanılıyor!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, telefon, adres, kullanici_tipi) VALUES (?, ?, ?, ?, ?, ?, 'uye')");
        if ($stmt->execute([$kullanici_adi, $email, $sifre, $ad_soyad, $telefon, $adres])) {
            $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
        } else {
            $error = "Kayıt sırasında bir hata oluştu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Kayıt</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Üye Kayıt</h3>
                        <p class="text-muted">Kütüphane üyeliği için kayıt olun</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Kullanıcı Adı *</label>
                                        <input type="text" class="form-control" name="kullanici_adi" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Şifre *</label>
                                <input type="password" class="form-control" name="sifre" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad *</label>
                                <input type="text" class="form-control" name="ad_soyad" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" class="form-control" name="telefon">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Adres</label>
                                <textarea class="form-control" name="adres" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="member/index.php">Zaten hesabınız var mı? Giriş yapın</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 