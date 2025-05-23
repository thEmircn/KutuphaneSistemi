<?php
session_start();
require_once '../config.php';

// Üye zaten giriş yapmışsa panele yönlendir
if (isset($_SESSION['member_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_POST) {
    $kullanici_adi = $_POST['kullanici_adi'];
    $sifre = $_POST['sifre'];
    
    $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ? AND kullanici_tipi = 'uye' AND durum = 'aktif'");
    $stmt->execute([$kullanici_adi]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($sifre, $user['sifre'])) {
        $_SESSION['member_id'] = $user['id'];
        $_SESSION['member_name'] = $user['ad_soyad'];
        $_SESSION['member_email'] = $user['email'];
        
        // Son giriş zamanını güncelle
        $stmt = $pdo->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        header('Location: dashboard.php');
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
    <title>Üye Paneli Girişi</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">📚 Kütüphane</h2>
                            <p class="text-muted">Üye Paneli Girişi</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control form-control-lg" name="kullanici_adi" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Şifre</label>
                                <input type="password" class="form-control form-control-lg" name="sifre" required>
                            </div>
                            <button type="submit" class="btn btn-gradient btn-lg w-100 mb-3">
                                🔑 Giriş Yap
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <a href="../register.php" class="text-decoration-none">Hesabınız yok mu? Kayıt olun</a>
                            </small>
                            <br>
                            <small class="text-muted">
                                <a href="../login.php" class="text-decoration-none">Yönetici Girişi</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>