<?php
require_once 'config.php';

if ($_POST) {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];
    
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND kullanici_tipi = 'admin'");
    $stmt->execute([$kullanici_adi]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($sifre, $user['sifre'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['ad_soyad'];
        header('Location: admin/dashboard.php');
        exit;
    } else {
        $error = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kütüphane Yönetim Sistemi - Giriş</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Kütüphane Yönetim Sistemi</h3>
                        <p class="text-muted">Admin Paneli Girişi</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" name="kullanici_adi" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control" name="sifre" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="register.php">Üye olmak için kayıt olun</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>