<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$results = [];
$execution_time = 0;
$start_time = microtime(true);

if ($_POST) {
    try {
        // 1. İndeks optimizasyonu
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_kitaplar_durum ON kitaplar(durum)",
            "CREATE INDEX IF NOT EXISTS idx_kitaplar_mevcut_adet ON kitaplar(mevcut_adet)", 
            "CREATE INDEX IF NOT EXISTS idx_kitaplar_yazar_kategori ON kitaplar(yazar_id, kategori_id)",
            "CREATE INDEX IF NOT EXISTS idx_kitaplar_eklenme_tarihi ON kitaplar(eklenme_tarihi)",
            "CREATE INDEX IF NOT EXISTS idx_odunc_uye_durum ON odunc_islemleri(uye_id, durum)",
            "CREATE INDEX IF NOT EXISTS idx_odunc_kitap_durum ON odunc_islemleri(kitap_id, durum)",
            "CREATE INDEX IF NOT EXISTS idx_odunc_teslim_tarihi ON odunc_islemleri(teslim_tarihi)",
            "CREATE INDEX IF NOT EXISTS idx_kullanicilar_tip_durum ON kullanicilar(kullanici_tipi, durum)",
        ];
        
        foreach ($indexes as $index) {
            $pdo->exec($index);
        }
        $results[] = "✅ İndeksler oluşturuldu/güncellendi";
        
        // 2. Tablo optimizasyonu
        $tables = ['kitaplar', 'kullanicilar', 'odunc_islemleri', 'yazarlar', 'kategoriler'];
        foreach ($tables as $table) {
            $pdo->exec("OPTIMIZE TABLE $table");
        }
        $results[] = "✅ Tablolar optimize edildi";
        
        // 3. İstatistikleri güncelle
        $pdo->exec("ANALYZE TABLE kitaplar, kullanicilar, odunc_islemleri, yazarlar, kategoriler");
        $results[] = "✅ Tablo istatistikleri güncellendi";
        
        // 4. Eski log kayıtlarını temizle (90 günden eski)
        $stmt = $pdo->prepare("DELETE FROM odunc_islemleri WHERE durum = 'teslim_edildi' AND gercek_teslim_tarihi < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $deleted_logs = $stmt->execute() ? $stmt->rowCount() : 0;
        $results[] = "✅ $deleted_logs eski log kaydı temizlendi";
        
        // 5. Dosya cache temizleme
        $cache_dir = '../cache/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*');
            $deleted_files = 0;
            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > 3600) { // 1 saatten eski
                    unlink($file);
                    $deleted_files++;
                }
            }
            $results[] = "✅ $deleted_files eski cache dosyası temizlendi";
        }
        
        // 6. Performans istatistikleri
        $stats = [
            'total_books' => $pdo->query("SELECT COUNT(*) FROM kitaplar")->fetchColumn(),
            'total_users' => $pdo->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn(),
            'total_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri")->fetchColumn(),
            'active_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc'")->fetchColumn(),
            'db_size' => $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn()
        ];
        
        $results[] = "📊 Veritabanı boyutu: {$stats['db_size']} MB";
        $results[] = "📊 Toplam kayıt: " . number_format(array_sum(array_slice($stats, 0, 3)));
        
    } catch (Exception $e) {
        $results[] = "❌ Hata: " . $e->getMessage();
    }
    
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
}

// Mevcut performans bilgileri
$current_stats = [
    'books' => $pdo->query("SELECT COUNT(*) FROM kitaplar")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn(),
    'loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri")->fetchColumn(),
    'active_loans' => $pdo->query("SELECT COUNT(*) FROM odunc_islemleri WHERE durum = 'odunc'")->fetchColumn(),
    'db_size' => $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn()
];

// Slow query analizi
$slow_queries = [];
try {
    $slow_queries = $pdo->query("
        SELECT sql_text, exec_count, avg_timer_wait/1000000000 as avg_time_sec 
        FROM performance_schema.statement_summary_by_digest 
        WHERE schema_name = DATABASE() 
        ORDER BY avg_timer_wait DESC 
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    // Performance schema kullanılamıyorsa görmezden gel
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Optimizasyonu</title>
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
                    <h1 class="h2">⚡ Veritabanı Optimizasyonu</h1>
                    <div class="btn-toolbar">
                        <form method="POST" class="d-inline">
                            <button type="submit" class="btn btn-primary">
                                🚀 Optimizasyonu Başlat
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php if ($results): ?>
                <div class="alert alert-success">
                    <h5>✅ Optimizasyon Tamamlandı</h5>
                    <ul class="mb-0">
                        <?php foreach ($results as $result): ?>
                            <li><?= $result ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <strong>Toplam süre:</strong> <?= $execution_time ?>ms
                </div>
                <?php endif; ?>
                
                <!-- Mevcut Durum -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5>📊 Mevcut Veritabanı Durumu</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center mb-3">
                                        <h3 class="text-primary"><?= number_format($current_stats['books']) ?></h3>
                                        <small class="text-muted">Kitaplar</small>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h3 class="text-success"><?= number_format($current_stats['users']) ?></h3>
                                        <small class="text-muted">Kullanıcılar</small>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h3 class="text-info"><?= number_format($current_stats['loans']) ?></h3>
                                        <small class="text-muted">Toplam İşlem</small>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h3 class="text-warning"><?= number_format($current_stats['active_loans']) ?></h3>
                                        <small class="text-muted">Aktif Ödünç</small>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Veritabanı Boyutu:</strong> <?= $current_stats['db_size'] ?> MB
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Son Optimizasyon:</strong> 
                                        <span id="lastOptimization"><?= date('d.m.Y H:i') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6>💡 Optimizasyon İpuçları</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">✅ Düzenli olarak optimizasyon yapın</li>
                                    <li class="mb-2">✅ Eski kayıtları periyodik temizleyin</li>
                                    <li class="mb-2">✅ Cache sistemini kullanın</li>
                                    <li class="mb-2">✅ Gereksiz indeksleri kaldırın</li>
                                    <li class="mb-0">✅ Veritabanı yedeklerini unutmayın</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Yavaş Sorgular (Eğer varsa) -->
                <?php if ($slow_queries): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5>🐌 Yavaş Çalışan Sorgular</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Sorgu</th>
                                        <th>Çalıştırılma</th>
                                        <th>Ortalama Süre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slow_queries as $query): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars(substr($query['sql_text'], 0, 100)) ?>...</code></td>
                                        <td><?= number_format($query['exec_count']) ?></td>
                                        <td><?= number_format($query['avg_time_sec'], 3) ?>s</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Optimizasyon Aşamaları -->
                <div class="card">
                    <div class="card-header">
                        <h5>🔧 Optimizasyon Aşamaları</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Gerçekleştirilen İşlemler:</h6>
                                <ul>
                                    <li>📇 Veritabanı indekslerinin oluşturulması</li>
                                    <li>🔧 Tablo optimizasyonu ve defragmentasyon</li>
                                    <li>📊 İstatistik tabloların güncellenmesi</li>
                                    <li>🗑️ Eski log kayıtlarının temizlenmesi</li>
                                    <li>💾 Cache dosyalarının temizlenmesi</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Performans İyileştirmeleri:</h6>
                                <ul>
                                    <li>⚡ Sorgu hızının artırılması</li>
                                    <li>💾 Disk alanının optimize edilmesi</li>
                                    <li>🚀 Sayfa yükleme hızının iyileştirilmesi</li>
                                    <li>📈 Genel sistem performansının artırılması</li>
                                    <li>🔍 Arama işlemlerinin hızlandırılması</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <strong>💡 Önemli Not:</strong> 
                            Optimizasyon işlemi büyük veritabanlarında birkaç dakika sürebilir. 
                            İşlem sırasında sistem yavaşlayabilir.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    // Otomatik optimizasyon hatırlatması
    function checkLastOptimization() {
        const lastOpt = localStorage.getItem('lastOptimization');
        const now = new Date().getTime();
        const oneWeek = 7 * 24 * 60 * 60 * 1000;
        
        if (!lastOpt || (now - parseInt(lastOpt)) > oneWeek) {
            if (confirm('Veritabanı optimizasyonu bir haftadan uzun süredir yapılmamış. Şimdi yapmak ister misiniz?')) {
                document.querySelector('form').submit();
            }
        }
    }
    
    // Sayfa yüklendiğinde kontrol et
    document.addEventListener('DOMContentLoaded', function() {
        checkLastOptimization();
        
        // Son optimizasyon tarihini kaydet
        <?php if ($results): ?>
        localStorage.setItem('lastOptimization', new Date().getTime());
        <?php endif; ?>
    });
    </script>
</body>
</html>