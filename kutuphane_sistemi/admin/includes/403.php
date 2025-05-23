<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erişim Reddedildi</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .error-container { background: white; border-radius: 20px; padding: 3rem; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="error-container">
                    <div style="font-size: 5rem; color: #dc3545;">🚫</div>
                    <h1 class="text-danger mb-3">Erişim Reddedildi</h1>
                    <p class="text-muted mb-4">Bu işlemi gerçekleştirmek için yeterli yetkiniz bulunmamaktadır.</p>
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn btn-primary">🏠 Ana Sayfaya Dön</a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">← Geri Git</a>
                    </div>
                    <hr>
                    <small class="text-muted">
                        Yetki: <strong><?= $_SESSION['user_role'] ?? 'Bilinmeyen' ?></strong><br>
                        Bu işlem için yöneticinizle iletişim kurun.
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>