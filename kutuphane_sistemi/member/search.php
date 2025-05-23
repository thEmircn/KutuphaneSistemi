<?php
require_once 'auth.php';

// Arama parametreleri
$search = $_GET['search'] ?? '';
$kategori_id = $_GET['kategori_id'] ?? '';
$yazar_id = $_GET['yazar_id'] ?? '';
$mevcut_only = isset($_GET['mevcut_only']);

// Arama sorgusu oluştur
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(k.kitap_adi LIKE ? OR k.isbn LIKE ? OR y.ad_soyad LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($kategori_id)) {
    $where_conditions[] = "k.kategori_id = ?";
    $params[] = $kategori_id;
}

if (!empty($yazar_id)) {
    $where_conditions[] = "k.yazar_id = ?";
    $params[] = $yazar_id;
}

if ($mevcut_only) {
    $where_conditions[] = "k.mevcut_adet > 0 AND k.durum = 'mevcut'";
}

// Sayfalama
$page = $_GET['page'] ?? 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// SQL sorgusu
$sql = "SELECT k.*, y.ad_soyad as yazar_adi, kat.kategori_adi 
        FROM kitaplar k 
        LEFT JOIN yazarlar y ON k.yazar_id = y.id 
        LEFT JOIN kategoriler kat ON k.kategori_id = kat.id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY k.kitap_adi LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Toplam kayıt sayısı
$count_sql = "SELECT COUNT(*) FROM kitaplar k 
              LEFT JOIN yazarlar y ON k.yazar_id = y.id 
              LEFT JOIN kategoriler kat ON k.kategori_id = kat.id";

if (!empty($where_conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Kategoriler ve yazarlar
$categories = $pdo->query("SELECT * FROM kategoriler WHERE durum = 'aktif' ORDER BY kategori_adi")->fetchAll();
$authors = $pdo->query("SELECT * FROM yazarlar WHERE durum = 'aktif' ORDER BY ad_soyad")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Ara - Üye Paneli</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .book-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 100%;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .book-cover {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .availability-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .filter-sidebar {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <h1 class="mb-3">🔍 Kitap Ara</h1>
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-lg" name="search" 
                           value="<?= safe_html($search) ?>" placeholder="Kitap adı, yazar veya ISBN...">
                </div>
                <div class="col-md-4">
                    <select class="form-select form-select-lg" name="kategori_id">
                        <option value="">Tüm Kategoriler</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= $kategori_id == $category['id'] ? 'selected' : '' ?>>
                                <?= safe_html($category['kategori_adi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-light btn-lg w-100">
                        <i class="fas fa-search"></i> Ara
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="container my-4">
        <div class="row">
            <!-- Filtreler -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-3">🎛️ Filtreler</h5>
                    <form method="GET">
                        <input type="hidden" name="search" value="<?= safe_html($search) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Yazar</label>
                            <select class="form-select" name="yazar_id">
                                <option value="">Tüm Yazarlar</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= $author['id'] ?>" <?= $yazar_id == $author['id'] ? 'selected' : '' ?>>
                                        <?= safe_html($author['ad_soyad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="kategori_id">
                                <option value="">Tüm Kategoriler</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $kategori_id == $category['id'] ? 'selected' : '' ?>>
                                        <?= safe_html($category['kategori_adi']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mevcut_only" <?= $mevcut_only ? 'checked' : '' ?>>
                                <label class="form-check-label">
                                    Sadece mevcut kitaplar
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">Filtrele</button>
                        <a href="search.php" class="btn btn-outline-secondary w-100">Temizle</a>
                    </form>
                </div>
            </div>
            
            <!-- Sonuçlar -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><?= number_format($total_records) ?> kitap bulundu</h4>
                    <div class="text-muted">
                        Sayfa <?= $page ?> / <?= $total_pages ?>
                    </div>
                </div>
                
                <?php if ($books): ?>
                <div class="row">
                    <?php foreach ($books as $book): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card book-card">
                            <div class="position-relative">
                                <?php if ($book['fotograf'] && file_exists('../uploads/books/' . $book['fotograf'])): ?>
                                    <img src="../uploads/books/<?= $book['fotograf'] ?>" class="card-img-top book-cover" alt="Kitap Kapağı">
                                <?php else: ?>
                                    <div class="card-img-top book-cover bg-light d-flex align-items-center justify-content-center">
                                        <div style="font-size: 4rem;">📚</div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="availability-badge">
                                    <?php if ($book['mevcut_adet'] > 0): ?>
                                        <span class="badge bg-success">Mevcut (<?= $book['mevcut_adet'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tükendi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h6 class="card-title"><?= safe_html($book['kitap_adi']) ?></h6>
                                <p class="card-text text-muted mb-2">
                                    <small>👤 <?= safe_html($book['yazar_adi'] ?? 'Bilinmeyen') ?></small>
                                </p>
                                <p class="card-text text-muted mb-2">
                                    <small>🏷️ <?= safe_html($book['kategori_adi'] ?? 'Kategori yok') ?></small>
                                </p>
                                
                                <?php if ($book['yayin_yili']): ?>
                                    <p class="card-text text-muted mb-2">
                                        <small>📅 <?= $book['yayin_yili'] ?></small>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($book['isbn']): ?>
                                    <p class="card-text text-muted mb-3">
                                        <small>📘 ISBN: <?= safe_html($book['isbn']) ?></small>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-grid">
                                    <button type="button" class="btn btn-primary" onclick="showBookDetails(<?= $book['id'] ?>)">
                                        📖 Detayları Gör
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Sayfalama -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfa navigasyonu">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Önceki</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Sonraki »</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size: 5rem;">🔍</div>
                    <h3 class="text-muted mb-3">Sonuç bulunamadı</h3>
                    <p class="text-muted">Arama kriterlerinizi değiştirerek tekrar deneyin.</p>
                    <a href="search.php" class="btn btn-primary">Yeni Arama</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Kitap Detay Modal -->
    <div class="modal fade" id="bookDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📖 Kitap Detayları</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookDetailContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Yükleniyor...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    function showBookDetails(bookId) {
        const modal = new bootstrap.Modal(document.getElementById('bookDetailModal'));
        modal.show();
        
        // AJAX ile kitap detaylarını yükle
        fetch(`book_detail.php?id=${bookId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('bookDetailContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('bookDetailContent').innerHTML = 
                    '<div class="alert alert-danger">Kitap detayları yüklenirken hata oluştu.</div>';
            });
    }
    </script>
</body>
</html>