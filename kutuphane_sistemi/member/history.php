<?php
require_once 'auth.php';

// Sayfalama
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Ödünç geçmişi
$history_query = $pdo->prepare("
    SELECT o.*, k.kitap_adi, k.fotograf, y.ad_soyad as yazar_adi,
           DATEDIFF(CASE WHEN o.gercek_teslim_tarihi THEN o.gercek_teslim_tarihi ELSE CURDATE() END, o.teslim_tarihi) as gecikme_gun
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.uye_id = ?
    ORDER BY o.odunc_tarihi DESC
    LIMIT $per_page OFFSET $offset
");
$history_query->execute([$_SESSION['member_id']]);
$history = $history_query->fetchAll();

// Toplam kayıt sayısı
$total_records = $pdo->prepare("SELECT COUNT(*) FROM odunc_islemleri WHERE uye_id = ?");
$total_records->execute([$_SESSION['member_id']]);
$total = $total_records->fetchColumn();
$total_pages = ceil($total / $per_page);

// İstatistikler
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as toplam,
        COUNT(CASE WHEN durum = 'teslim_edildi' THEN 1 END) as teslim_edilen,
        COUNT(CASE WHEN durum = 'odunc' THEN 1 END) as aktif,
        AVG(DATEDIFF(CASE WHEN gercek_teslim_tarihi THEN gercek_teslim_tarihi ELSE CURDATE() END, odunc_tarihi)) as ortalama_gun
    FROM odunc_islemleri 
    WHERE uye_id = ?
");
$stats->execute([$_SESSION['member_id']]);
$statistics = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödünç Geçmişi - Üye Paneli</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .history-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .book-thumbnail {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Header -->
    <div class="header-section">
        <div class="container">
            <h1 class="mb-0">📋 Ödünç Geçmişim</h1>
            <p class="mb-0 mt-2">Tüm kitap ödünç işlemleriniz</p>
        </div>
    </div>
    
    <div class="container my-4">
        <!-- İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h3 class="text-primary"><?= number_format($statistics['toplam']) ?></h3>
                    <small class="text-muted">Toplam Ödünç</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h3 class="text-success"><?= number_format($statistics['teslim_edilen']) ?></h3>
                    <small class="text-muted">Teslim Edilen</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <h3 class="text-warning"><?= number_format($statistics['aktif']) ?></h3>
                    <small class="text-muted">Aktif Ödünç</small>
                </div>
            </div>
            
        </div>
        
        <!-- Geçmiş Listesi -->
        <?php if ($history): ?>
            <?php foreach ($history as $item): ?>
            <div class="card history-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <?php if ($item['fotograf'] && file_exists('../uploads/books/' . $item['fotograf'])): ?>
                                <img src="../uploads/books/<?= $item['fotograf'] ?>" class="book-thumbnail">
                            <?php else: ?>
                                <div class="book-thumbnail bg-light d-flex align-items-center justify-content-center">
                                    📚
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col">
                            <h6 class="mb-1"><?= safe_html($item['kitap_adi']) ?></h6>
                            <p class="text-muted mb-1"><?= safe_html($item['yazar_adi'] ?? 'Bilinmeyen Yazar') ?></p>
                            <div class="row">
                                <div class="col-sm-6">
                                    <small class="text-muted">
                                        <strong>Ödünç:</strong> <?= date('d.m.Y', strtotime($item['odunc_tarihi'])) ?>
                                    </small>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted">
                                        <strong>Teslim Tarihi:</strong> <?= date('d.m.Y', strtotime($item['teslim_tarihi'])) ?>
                                    </small>
                                </div>
                            </div>
                            <?php if ($item['gercek_teslim_tarihi']): ?>
                            <div class="mt-1">
                                <small class="text-success">
                                    <strong>Teslim Edildi:</strong> <?= date('d.m.Y', strtotime($item['gercek_teslim_tarihi'])) ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-auto text-end">
                            <div class="mb-2">
                                <?php if ($item['durum'] == 'odunc'): ?>
                                    <?php if ($item['gecikme_gun'] > 0): ?>
                                        <span class="badge bg-danger fs-6">
                                            <?= $item['gecikme_gun'] ?> gün gecikme
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning fs-6">Ödünçte</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-success fs-6">Teslim Edildi</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['gecikme_ucreti'] > 0): ?>
                            <div>
                                <small class="text-danger">
                                    <strong>Ceza:</strong> <?= number_format($item['gecikme_ucreti'], 2) ?> ₺
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($item['notlar']): ?>
                            <div class="mt-1">
                                <small class="text-info" title="<?= safe_html($item['notlar']) ?>">
                                    📝 Not var
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
           
           <!-- Sayfalama -->
           <?php if ($total_pages > 1): ?>
           <nav aria-label="Geçmiş navigasyonu" class="mt-4">
               <ul class="pagination justify-content-center">
                   <?php if ($page > 1): ?>
                       <li class="page-item">
                           <a class="page-link" href="?page=<?= $page - 1 ?>">« Önceki</a>
                       </li>
                   <?php endif; ?>
                   
                   <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                       <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                           <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                       </li>
                   <?php endfor; ?>
                   
                   <?php if ($page < $total_pages): ?>
                       <li class="page-item">
                           <a class="page-link" href="?page=<?= $page + 1 ?>">Sonraki »</a>
                       </li>
                   <?php endif; ?>
               </ul>
           </nav>
           <?php endif; ?>
           
       <?php else: ?>
           <div class="text-center py-5">
               <div style="font-size: 5rem;">📚</div>
               <h3 class="text-muted mb-3">Henüz ödünç işleminiz yok</h3>
               <p class="text-muted">Kütüphaneden kitap ödünç aldığınızda burada görüntülenecek.</p>
               <a href="search.php" class="btn btn-primary">Kitap Ara</a>
           </div>
       <?php endif; ?>
   </div>
   
   <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>