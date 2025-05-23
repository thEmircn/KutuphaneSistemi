<?php
require_once '../config.php';
require_once 'includes/pagination.php';
require_once 'includes/cache.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Performans ayarları
$per_page = $_GET['per_page'] ?? 20;
$per_page = min(100, max(10, (int)$per_page)); // 10-100 arası sınırla

$page = $_GET['page'] ?? 1;
$search = $_GET['search'] ?? '';
$kategori_id = $_GET['kategori_id'] ?? '';
$yazar_id = $_GET['yazar_id'] ?? '';
$durum = $_GET['durum'] ?? '';

// Cache key oluştur
$cache_key = 'books_list_' . md5(serialize($_GET));

// Lazy loading için AJAX request kontrolü
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Cache'den kontrol et (sadece arama yoksa)
$use_cache = empty($search) && empty($kategori_id) && empty($yazar_id) && empty($durum);
$books = null;
$total_records = 0;

if ($use_cache) {
    $cached_data = $cache->get($cache_key);
    if ($cached_data) {
        $books = $cached_data['books'];
        $total_records = $cached_data['total'];
    }
}

// Cache'de yoksa veritabanından çek
if ($books === null) {
    // Arama koşullarını hazırla
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
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Toplam kayıt sayısı
    $count_sql = "SELECT COUNT(*) FROM kitaplar k 
                  LEFT JOIN yazarlar y ON k.yazar_id = y.id 
                  LEFT JOIN kategoriler kat ON k.kategori_id = kat.id 
                  $where_clause";
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Sayfalama hesaplama
    $pagination = new Pagination($total_records, $per_page, $page, 'books_optimized.php', $_GET);
    
    // Ana sorgu - sadece gerekli sütunları seç
    $sql = "SELECT k.id, k.kitap_adi, k.isbn, k.stok_adedi, k.mevcut_adet, k.durum, k.fotograf,
                   y.ad_soyad as yazar_adi, kat.kategori_adi,
                   k.eklenme_tarihi
            FROM kitaplar k 
            LEFT JOIN yazarlar y ON k.yazar_id = y.id 
            LEFT JOIN kategoriler kat ON k.kategori_id = kat.id 
            $where_clause
            ORDER BY k.id DESC 
            LIMIT {$pagination->getLimit()} OFFSET {$pagination->getOffset()}";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    
    // Cache'e kaydet (sadece filtresiz sorgular için)
    if ($use_cache) {
        $cache->set($cache_key, [
            'books' => $books,
            'total' => $total_records
        ], 300); // 5 dakika cache
    }
} else {
    $pagination = new Pagination($total_records, $per_page, $page, 'books_optimized.php', $_GET);
}

// AJAX request ise sadece tablo içeriğini döndür
if ($is_ajax) {
    header('Content-Type: application/json');
    ob_start();
    include 'includes/books_table_rows.php';
    $html = ob_get_clean();
    
    echo json_encode([
        'html' => $html,
        'pagination' => $pagination->render(),
        'total' => $total_records,
        'current_page' => $pagination->getCurrentPage(),
        'total_pages' => $pagination->getTotalPages()
    ]);
    exit;
}

// Filtre seçenekleri (cache'li)
$categories = $cache->get('categories_list');
if (!$categories) {
    $categories = $pdo->query("SELECT id, kategori_adi FROM kategoriler WHERE durum = 'aktif' ORDER BY kategori_adi")->fetchAll();
    $cache->set('categories_list', $categories, 1800); // 30 dakika
}

$authors = $cache->get('authors_list');
if (!$authors) {
    $authors = $pdo->query("SELECT id, ad_soyad FROM yazarlar WHERE durum = 'aktif' ORDER BY ad_soyad")->fetchAll();
    $cache->set('authors_list', $authors, 1800); // 30 dakika
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Yönetimi (Optimize)</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <style>
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .table-container {
            position: relative;
            min-height: 400px;
        }
        .lazy-image {
            opacity: 0;
            transition: opacity 0.3s;
        }
        .lazy-image.loaded {
            opacity: 1;
        }
        .performance-info {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1050;
        }
        .filter-collapse {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">📚 Kitap Yönetimi (Optimize)</h1>
                    <div class="btn-toolbar">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearCache()">
                                🗑️ Cache Temizle
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="togglePerformanceInfo()">
                                📊 Performans
                            </button>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="openAddModal()">
                            Yeni Kitap Ekle
                        </button>
                    </div>
                </div>
                
                <!-- Filtreler (Collapsible) -->
                <div class="filter-collapse">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">🔍 Filtreler</h5>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                            <span id="filterToggleText">Gizle</span>
                        </button>
                    </div>
                    
                    <div class="collapse show" id="filterCollapse">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Arama...">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="kategori_id">
                                    <option value="">Tüm Kategoriler</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $kategori_id == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['kategori_adi']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="yazar_id">
                                    <option value="">Tüm Yazarlar</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?= $author['id'] ?>" <?= $yazar_id == $author['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($author['ad_soyad']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="durum">
                                    <option value="">Tüm Durumlar</option>
                                    <option value="mevcut" <?= $durum == 'mevcut' ? 'selected' : '' ?>>Mevcut</option>
                                    <option value="tukendi" <?= $durum == 'tukendi' ? 'selected' : '' ?>>Tükendi</option>
                                    <option value="pasif" <?= $durum == 'pasif' ? 'selected' : '' ?>>Pasif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="per_page">
                                    <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10/sayfa</option>
                                    <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20/sayfa</option>
                                    <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50/sayfa</option>
                                    <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100/sayfa</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">🔍</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tablo -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-container">
                            <div class="loading-overlay" id="loadingOverlay">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Yükleniyor...</span>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th width="80">Fotoğraf</th>
                                            <th>Kitap Adı</th>
                                            <th width="150">Yazar</th>
                                            <th width="120">Kategori</th>
                                            <th width="100">ISBN</th>
                                            <th width="80">Stok</th>
                                            <th width="80">Mevcut</th>
                                            <th width="100">Durum</th>
                                            <th width="200">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody id="booksTableBody">
                                        <?php include 'includes/books_table_rows.php'; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Sayfalama -->
                        <div id="paginationContainer">
                            <?= $pagination->render() ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Performans Bilgisi -->
    <div class="performance-info" id="performanceInfo" style="display: none;">
        <div><strong>Performans Bilgisi</strong></div>
        <div>Toplam Kayıt: <span id="totalRecords"><?= number_format($total_records) ?></span></div>
        <div>Sayfa Yüklenme: <span id="pageLoadTime">-</span>ms</div>
        <div>Son Güncelleme: <span id="lastUpdate"><?= date('H:i:s') ?></span></div>
        <div>Cache Durumu: <span id="cacheStatus"><?= $use_cache && $cached_data ? 'Aktif' : 'Pasif' ?></span></div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    let pageStartTime = performance.now();
    let currentPage = <?= $pagination->getCurrentPage() ?>;
    let totalPages = <?= $pagination->getTotalPages() ?>;
    let isLoading = false;
    
    // Sayfa yüklenme süresi
    window.addEventListener('load', function() {
        const loadTime = Math.round(performance.now() - pageStartTime);
        document.getElementById('pageLoadTime').textContent = loadTime;
    });
    
    // Lazy loading için Intersection Observer
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.onload = () => {
                        img.classList.add('loaded');
                    };
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    });
    
    // Lazy loading uygula
    function applyLazyLoading() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // AJAX ile sayfa yükleme
    function loadPage(page, pushState = true) {
        if (isLoading) return;
        
        isLoading = true;
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        const formData = new FormData(document.getElementById('filterForm'));
        formData.append('page', page);
        formData.append('ajax', '1');
        
        const params = new URLSearchParams(formData);
        
        fetch('books_optimized.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                document.getElementById('booksTableBody').innerHTML = data.html;
                document.getElementById('paginationContainer').innerHTML = data.pagination;
                document.getElementById('totalRecords').textContent = new Intl.NumberFormat().format(data.total);
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                
                currentPage = data.current_page;
                totalPages = data.total_pages;
                
                if (pushState) {
                    const url = new URL(window.location);
                    url.searchParams.set('page', page);
                    history.pushState({page: page}, '', url.toString());
                }
                
                applyLazyLoading();
            })
            .catch(error => {
                console.error('Sayfa yükleme hatası:', error);
                alert('Sayfa yüklenirken hata oluştu!');
            })
            .finally(() => {
                isLoading = false;
                document.getElementById('loadingOverlay').style.display = 'none';
            });
    }
    
    // Form submit
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadPage(1);
    });
    
    // Sayfalama click olayları (event delegation)
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link')) {
            e.preventDefault();
            const url = new URL(e.target.href);
            const page = url.searchParams.get('page');
            if (page) {
                loadPage(page);
            }
        }
    });
    
    // Browser back/forward
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.page) {
            loadPage(e.state.page, false);
        }
    });
    
    // Auto-refresh her 30 saniyede (opsiyonel)
    let autoRefreshInterval;
    function toggleAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        } else {
            autoRefreshInterval = setInterval(() => {
                loadPage(currentPage, false);
            }, 30000);
        }
    }
    
    // Cache temizleme
    function clearCache() {
        fetch('clear_cache.php', {method: 'POST'})
            .then(response => response.text())
            .then(result => {
                alert('Cache başarıyla temizlendi!');
                location.reload();
            })
            .catch(error => {
                alert('Cache temizlenirken hata oluştu!');
            });
    }
    
    // Performans bilgisi toggle
    function togglePerformanceInfo() {
        const info = document.getElementById('performanceInfo');
        info.style.display = info.style.display === 'none' ? 'block' : 'none';
    }
    
    // Filter collapse toggle
    document.getElementById('filterCollapse').addEventListener('shown.bs.collapse', function() {
        document.getElementById('filterToggleText').textContent = 'Gizle';
    });
    
    document.getElementById('filterCollapse').addEventListener('hidden.bs.collapse', function() {
        document.getElementById('filterToggleText').textContent = 'Göster';
    });
    
    // Debounced search
    let searchTimeout;
    document.querySelector('input[name="search"]').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                loadPage(1);
            }
        }, 800);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
        
        if (e.key === 'ArrowLeft' && currentPage > 1) {
            loadPage(currentPage - 1);
        }
        
        if (e.key === 'ArrowRight' && currentPage < totalPages) {
            loadPage(currentPage + 1);
        }
    });
    
    // Sayfa yüklendiğinde lazy loading başlat
    document.addEventListener('DOMContentLoaded', function() {
        applyLazyLoading();
        
        // İlk sayfa yükleme state'i
        history.replaceState({page: currentPage}, '', window.location.href);
    });
    </script>
</body>
</html>