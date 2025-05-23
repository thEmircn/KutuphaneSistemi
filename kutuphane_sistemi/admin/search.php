<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Arama parametreleri
$search = $_GET['search'] ?? '';
$yazar_id = $_GET['yazar_id'] ?? '';
$kategori_id = $_GET['kategori_id'] ?? '';
$durum = $_GET['durum'] ?? '';
$yayin_yili_min = $_GET['yayin_yili_min'] ?? '';
$yayin_yili_max = $_GET['yayin_yili_max'] ?? '';

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

if (!empty($yazar_id)) {
    $where_conditions[] = "k.yazar_id = ?";
    $params[] = $yazar_id;
}

if (!empty($kategori_id)) {
    $where_conditions[] = "k.kategori_id = ?";
    $params[] = $kategori_id;
}

if (!empty($durum)) {
    $where_conditions[] = "k.durum = ?";
    $params[] = $durum;
}

if (!empty($yayin_yili_min)) {
    $where_conditions[] = "k.yayin_yili >= ?";
    $params[] = $yayin_yili_min;
}

if (!empty($yayin_yili_max)) {
    $where_conditions[] = "k.yayin_yili <= ?";
    $params[] = $yayin_yili_max;
}

// Sayfalama
$page = $_GET['page'] ?? 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// SQL sorgusu
$sql = "SELECT k.*, y.ad_soyad as yazar_adi, kat.kategori_adi 
        FROM kitaplar k 
        LEFT JOIN yazarlar y ON k.yazar_id = y.id 
        LEFT JOIN kategoriler kat ON k.kategori_id = kat.id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY k.id DESC LIMIT $per_page OFFSET $offset";

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

// Yazarlar ve kategoriler (filtre için)
$authors = $pdo->query("SELECT * FROM yazarlar WHERE durum = 'aktif' ORDER BY ad_soyad")->fetchAll();
$categories = $pdo->query("SELECT * FROM kategoriler WHERE durum = 'aktif' ORDER BY kategori_adi")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Arama</title>
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
                    <h1 class="h2">Kitap Arama</h1>
                    <div class="badge bg-info"><?= $total_records ?> sonuç</div>
                </div>
                
                <!-- Arama Formu -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>🔍 Arama ve Filtreler</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="searchForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Genel Arama</label>
                                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Kitap adı, ISBN veya yazar">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Yazar</label>
                                        <select class="form-select" name="yazar_id">
                                            <option value="">Tüm Yazarlar</option>
                                            <?php foreach ($authors as $author): ?>
                                                <option value="<?= $author['id'] ?>" <?= $yazar_id == $author['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($author['ad_soyad']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select class="form-select" name="kategori_id">
                                            <option value="">Tüm Kategoriler</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>" <?= $kategori_id == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['kategori_adi']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Durum</label>
                                        <select class="form-select" name="durum">
                                            <option value="">Tüm Durumlar</option>
                                            <option value="mevcut" <?= $durum == 'mevcut' ? 'selected' : '' ?>>Mevcut</option>
                                            <option value="tukendi" <?= $durum == 'tukendi' ? 'selected' : '' ?>>Tükendi</option>
                                            <option value="pasif" <?= $durum == 'pasif' ? 'selected' : '' ?>>Pasif</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Min. Yayın Yılı</label>
                                        <input type="number" class="form-control" name="yayin_yili_min" value="<?= $yayin_yili_min ?>" min="1800" max="<?= date('Y') ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max. Yayın Yılı</label>
                                        <input type="number" class="form-control" name="yayin_yili_max" value="<?= $yayin_yili_max ?>" min="1800" max="<?= date('Y') ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">🔍 Ara</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <a href="search.php" class="btn btn-outline-secondary">🔄 Filtreleri Temizle</a>
                                    <button type="button" class="btn btn-outline-info" onclick="exportResults()">📊 Excel'e Aktar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sonuçlar -->
                <?php if ($books): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fotoğraf</th>
                                        <th>Kitap Adı</th>
                                        <th>Yazar</th>
                                        <th>Kategori</th>
                                        <th>Yayın Yılı</th>
                                        <th>Stok</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td>
                                            <?php if ($book['fotograf'] && file_exists('../uploads/books/' . $book['fotograf'])): ?>
                                                <img src="../uploads/books/<?= $book['fotograf'] ?>" width="40" height="50" class="img-thumbnail">
                                            <?php else: ?>
                                                <div class="bg-light text-center d-flex align-items-center justify-content-center" style="width: 40px; height: 50px; font-size: 20px;">
                                                    📚
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="book_detail.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                                <strong><?= htmlspecialchars($book['kitap_adi']) ?></strong>
                                            </a>
                                            <?php if ($book['isbn']): ?>
                                                <br><small class="text-muted">ISBN: <?= $book['isbn'] ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($book['yazar_adi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($book['kategori_adi'] ?? '-') ?></td>
                                        <td><?= $book['yayin_yili'] ?? '-' ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?= $book['mevcut_adet'] ?></span>
                                            /
                                            <span class="badge bg-secondary"><?= $book['stok_adedi'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($book['durum'] == 'mevcut'): ?>
                                                <span class="badge bg-success">Mevcut</span>
                                            <?php elseif ($book['durum'] == 'tukendi'): ?>
                                                <span class="badge bg-warning">Tükendi</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="book_detail.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-info">Detay</a>
                                            <a href="books.php?edit=<?= $book['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Sayfalama -->
                        <?php if ($total_pages > 1): ?>
                        <nav>
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
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <div class="py-5">
                            <h3 class="text-muted">📚 Sonuç Bulunamadı</h3>
                            <p class="text-muted">Arama kriterlerinizle eşleşen kitap bulunamadı.</p>
                            <a href="search.php" class="btn btn-primary">Yeni Arama Yap</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    function exportResults() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'excel');
        window.location.href = 'export.php?' + params.toString();
    }
    
    // Otomatik arama (input değiştiğinde)
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        let timeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('searchForm').submit();
                }
            }, 500);
        });
    });
    </script>
</body>
</html>