<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$error_message = '';
$success_message = '';
$debug_info = '';

// Upload klasörünü oluştur - mutlak yol kullan
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/KutuphaneSistemi/kutuphane_sistemi/uploads/books/';
$upload_url = '../uploads/books/'; // URL için

if (!is_dir($upload_dir)) {
    if (!@mkdir($upload_dir, 0777, true)) {
        $error_message = "Upload klasörü oluşturulamadı: " . $upload_dir;
        $debug_info .= "Failed to create directory: $upload_dir. ";
    } else {
        $debug_info .= "Upload klasörü oluşturuldu: $upload_dir. ";
    }
}

// Kitap silme işlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT fotograf FROM kitaplar WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
    if ($book && $book['fotograf'] && file_exists($upload_dir . $book['fotograf'])) {
        unlink($upload_dir . $book['fotograf']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM kitaplar WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: books.php?success=deleted');
    exit;
}

// Kitap ekleme/güncelleme işlemi
if ($_POST) {
    $debug_info .= "POST request received. ";
    
    $kitap_adi = trim($_POST['kitap_adi']);
    $yazar_id = !empty($_POST['yazar_id']) ? $_POST['yazar_id'] : null;
    $kategori_id = !empty($_POST['kategori_id']) ? $_POST['kategori_id'] : null;
    $isbn = trim($_POST['isbn']);
    $yayin_evi = trim($_POST['yayin_evi']);
    $yayin_yili = !empty($_POST['yayin_yili']) ? $_POST['yayin_yili'] : null;
    $sayfa_sayisi = !empty($_POST['sayfa_sayisi']) ? $_POST['sayfa_sayisi'] : null;
    $stok_adedi = !empty($_POST['stok_adedi']) ? $_POST['stok_adedi'] : 1;
    $raf_no = trim($_POST['raf_no']);
    $ozet = trim($_POST['ozet']);
    
    // Fotoğraf yükleme işlemi
    $fotograf = null;
    $upload_error = false;
    
    $debug_info .= "Files array: " . json_encode($_FILES) . " ";
    
    if (isset($_FILES['fotograf']) && !empty($_FILES['fotograf']['name'])) {
        $debug_info .= "File upload attempt. ";
        
        $file = $_FILES['fotograf'];
        $error = $file['error'];
        
        if ($error === UPLOAD_ERR_OK) {
            $debug_info .= "No upload errors. ";
            
            $filename = $file['name'];
            $tmp_name = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            $debug_info .= "Original filename: $filename, Size: $file_size, Extension: $file_ext. ";
            
            // Dosya boyutu kontrolü (2MB)
            if ($file_size > 2 * 1024 * 1024) {
                $error_message = "Dosya boyutu çok büyük! Maksimum 2MB olmalıdır. Dosya boyutu: " . round($file_size / 1024 / 1024, 2) . "MB";
                $upload_error = true;
            }
            
            // Dosya türü kontrolü
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_types)) {
                $error_message = "Desteklenmeyen dosya türü: $file_ext. Sadece JPG, PNG, GIF dosyaları yükleyebilirsiniz.";
                $upload_error = true;
            }
            
            // Geçici dosya var mı kontrol et
            if (!is_uploaded_file($tmp_name)) {
                $error_message = "Geçici dosya bulunamadı!";
                $upload_error = true;
            }
            
            // Dosya yükleme (SADECE BU KISIM KALSIN)
            if (!$upload_error) {
                $new_filename = time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                $debug_info .= "Attempting to upload to: $upload_path. ";
                
                // Klasör var mı kontrol et
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                    $debug_info .= "Created upload directory. ";
                }
                
                // Klasör yazılabilir mi kontrol et
                if (!is_writable($upload_dir)) {
                    $error_message = "Upload klasörü yazılabilir değil! Klasör: $upload_dir";
                    $upload_error = true;
                    $debug_info .= "Directory not writable. ";
                } else {
                    $debug_info .= "Directory is writable. ";
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $fotograf = $new_filename;
                        $debug_info .= "File uploaded successfully as: $new_filename. ";
                        
                        // Dosya gerçekten var mı kontrol et
                        if (file_exists($upload_path)) {
                            $debug_info .= "File exists after upload. ";
                        } else {
                            $debug_info .= "File does not exist after upload! ";
                        }
                    } else {
                        $error_message = "move_uploaded_file() başarısız! Temp file: $tmp_name, Target: $upload_path";
                        $upload_error = true;
                        $debug_info .= "move_uploaded_file failed. ";
                    }
                }
            }
        } else {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'Dosya php.ini upload_max_filesize limitini aşıyor',
                UPLOAD_ERR_FORM_SIZE => 'Dosya form MAX_FILE_SIZE limitini aşıyor',
                UPLOAD_ERR_PARTIAL => 'Dosya kısmen yüklendi',
                UPLOAD_ERR_NO_FILE => 'Dosya yüklenmedi',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı',
                UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı',
                UPLOAD_ERR_EXTENSION => 'PHP uzantısı dosya yüklemeyi durdurdu'
            ];
            
            $error_message = "Upload hatası: " . ($upload_errors[$error] ?? "Bilinmeyen hata ($error)");
            $upload_error = true;
        }
    } else {
        $debug_info .= "No file uploaded. ";
    }
    
    // Veritabanına kaydet
    if (!$upload_error) {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Güncelleme
                $book_id = $_POST['id'];
                $debug_info .= "Updating book ID: $book_id. ";
                
                if ($fotograf) {
                    // Eski fotoğrafı sil
                    $stmt = $pdo->prepare("SELECT fotograf FROM kitaplar WHERE id = ?");
                    $stmt->execute([$book_id]);
                    $old_book = $stmt->fetch();
                    if ($old_book && $old_book['fotograf'] && file_exists($upload_dir . $old_book['fotograf'])) {
                        unlink($upload_dir . $old_book['fotograf']);
                        $debug_info .= "Old photo deleted. ";
                    }
                    
                    $stmt = $pdo->prepare("UPDATE kitaplar SET kitap_adi=?, yazar_id=?, kategori_id=?, isbn=?, yayin_evi=?, yayin_yili=?, sayfa_sayisi=?, stok_adedi=?, mevcut_adet=?, raf_no=?, ozet=?, fotograf=? WHERE id=?");
                    $stmt->execute([$kitap_adi, $yazar_id, $kategori_id, $isbn, $yayin_evi, $yayin_yili, $sayfa_sayisi, $stok_adedi, $stok_adedi, $raf_no, $ozet, $fotograf, $book_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE kitaplar SET kitap_adi=?, yazar_id=?, kategori_id=?, isbn=?, yayin_evi=?, yayin_yili=?, sayfa_sayisi=?, stok_adedi=?, mevcut_adet=?, raf_no=?, ozet=? WHERE id=?");
                    $stmt->execute([$kitap_adi, $yazar_id, $kategori_id, $isbn, $yayin_evi, $yayin_yili, $sayfa_sayisi, $stok_adedi, $stok_adedi, $raf_no, $ozet, $book_id]);
                }
                
                header('Location: books.php?success=updated');
                exit;
            } else {
                // Ekleme
                $debug_info .= "Adding new book. ";
                
                $stmt = $pdo->prepare("INSERT INTO kitaplar (kitap_adi, yazar_id, kategori_id, isbn, yayin_evi, yayin_yili, sayfa_sayisi, stok_adedi, mevcut_adet, raf_no, ozet, fotograf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$kitap_adi, $yazar_id, $kategori_id, $isbn, $yayin_evi, $yayin_yili, $sayfa_sayisi, $stok_adedi, $stok_adedi, $raf_no, $ozet, $fotograf]);
                
                header('Location: books.php?success=added');
                exit;
            }
        } catch (Exception $e) {
            $error_message = "Veritabanı hatası: " . $e->getMessage();
            $debug_info .= "Database error: " . $e->getMessage();
        }
    }
}

// Success mesajları
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $success_message = "Kitap başarıyla eklendi!";
            break;
        case 'updated':
            $success_message = "Kitap başarıyla güncellendi!";
            break;
        case 'deleted':
            $success_message = "Kitap başarıyla silindi!";
            break;
    }
}

// Düzenleme için kitap bilgilerini getir
$edit_book = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM kitaplar WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_book = $stmt->fetch();
}

// Kitapları getir
$stmt = $pdo->query("SELECT k.*, y.ad_soyad as yazar_adi, kat.kategori_adi FROM kitaplar k LEFT JOIN yazarlar y ON k.yazar_id = y.id LEFT JOIN kategoriler kat ON k.kategori_id = kat.id ORDER BY k.id DESC");
$books = $stmt->fetchAll();

// Yazarları getir
$authors = $pdo->query("SELECT * FROM yazarlar WHERE durum = 'aktif' ORDER BY ad_soyad")->fetchAll();

// Kategorileri getir
$categories = $pdo->query("SELECT * FROM kategoriler WHERE durum = 'aktif' ORDER BY kategori_adi")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Yönetimi</title>
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
                    <h1 class="h2">Kitap Yönetimi</h1>
                    <div>
                        <button type="button" class="btn btn-primary" onclick="openAddModal()">
                            Yeni Kitap Ekle
                        </button>
                    </div>
                </div>
                
                <?php if ($debug_info): ?>
                    <div class="alert alert-info">
                        <strong>Debug:</strong> <?= $debug_info ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
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
                                        <th>ISBN</th>
                                        <th>Stok</th>
                                        <th>Mevcut</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td>
                                            <?php if ($book['fotograf'] && file_exists($upload_dir . $book['fotograf'])): ?>
                                                <img src="../uploads/books/<?= $book['fotograf'] ?>" width="50" height="60" class="img-thumbnail">
                                            <?php else: ?>
                                                <div class="bg-light text-center d-flex align-items-center justify-content-center" style="width: 50px; height: 60px; font-size: 24px;">
                                                    📚
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="book_detail.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($book['kitap_adi']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($book['yazar_adi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($book['kategori_adi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($book['isbn'] ?? '-') ?></td>
                                        <td><?= $book['stok_adedi'] ?></td>
                                        <td><?= $book['mevcut_adet'] ?></td>
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
                                            <button type="button" class="btn btn-sm btn-warning" onclick="openEditModal(<?= $book['id'] ?>)">Düzenle</button>
                                            <a href="?delete=<?= $book['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kitabı silmek istediğinizden emin misiniz?')">Sil</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Kitap Ekleme/Düzenleme Modal -->
    <div class="modal fade" id="bookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Yeni Kitap Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="bookForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="book_id">
                        
                        <!-- Fotoğraf Yükleme -->
                        <div class="mb-3">
                            <label class="form-label">Kitap Fotoğrafı</label>
                            <div id="current_photo_container" style="display: none;">
                                <div class="mb-2">
                                    <img id="current_photo" class="img-thumbnail" style="max-height: 150px;">
                                    <br><small class="text-muted">Mevcut fotoğraf</small>
                                </div>
                            </div>
                            <input type="file" class="form-control" name="fotograf" id="fotograf" accept="image/*">
                            <small class="form-text text-muted">Desteklenen formatlar: JPG, PNG, GIF (Maksimum: 2MB)</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kitap Adı *</label>
                                    <input type="text" class="form-control" name="kitap_adi" id="kitap_adi" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" name="isbn" id="isbn">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Yazar</label>
                                    <select class="form-select" name="yazar_id" id="yazar_id">
                                        <option value="">Yazar Seçin</option>
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?= $author['id'] ?>"><?= htmlspecialchars($author['ad_soyad']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select class="form-select" name="kategori_id" id="kategori_id">
                                        <option value="">Kategori Seçin</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['kategori_adi']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Yayın Evi</label>
                                    <input type="text" class="form-control" name="yayin_evi" id="yayin_evi">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Yayın Yılı</label>
                                    <input type="number" class="form-control" name="yayin_yili" id="yayin_yili" min="1800" max="<?= date('Y') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Sayfa Sayısı</label>
                                    <input type="number" class="form-control" name="sayfa_sayisi" id="sayfa_sayisi" min="1">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stok Adedi</label>
                                    <input type="number" class="form-control" name="stok_adedi" id="stok_adedi" value="1" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Raf No</label>
                                    <input type="text" class="form-control" name="raf_no" id="raf_no">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Özet</label>
                            <textarea class="form-control" name="ozet" id="ozet" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Kitap verileri
    const bookData = <?= json_encode($books) ?>;
    
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Yeni Kitap Ekle';
        document.getElementById('submitBtn').textContent = 'Ekle';
        document.getElementById('bookForm').reset();
        document.getElementById('book_id').value = '';
        document.getElementById('current_photo_container').style.display = 'none';
        
        var modal = new bootstrap.Modal(document.getElementById('bookModal'));
        modal.show();
    }
    
    function openEditModal(bookId) {
        const book = bookData.find(b => b.id == bookId);
        if (!book) return;
        
        document.getElementById('modalTitle').textContent = 'Kitap Düzenle';
        document.getElementById('submitBtn').textContent = 'Güncelle';
        
        document.getElementById('book_id').value = book.id;
        document.getElementById('kitap_adi').value = book.kitap_adi || '';
        document.getElementById('isbn').value = book.isbn || '';
        document.getElementById('yazar_id').value = book.yazar_id || '';
        document.getElementById('kategori_id').value = book.kategori_id || '';
        document.getElementById('yayin_evi').value = book.yayin_evi || '';
        document.getElementById('yayin_yili').value = book.yayin_yili || '';
        document.getElementById('sayfa_sayisi').value = book.sayfa_sayisi || '';
        document.getElementById('stok_adedi').value = book.stok_adedi || 1;
        document.getElementById('raf_no').value = book.raf_no || '';
        document.getElementById('ozet').value = book.ozet || '';
        
        if (book.fotograf) {
            document.getElementById('current_photo').src = '../uploads/books/' + book.fotograf;
            document.getElementById('current_photo_container').style.display = 'block';
        } else {
            document.getElementById('current_photo_container').style.display = 'none';
        }
        
        var modal = new bootstrap.Modal(document.getElementById('bookModal'));
        modal.show();
    }
    
    <?php if ($edit_book): ?>
    document.addEventListener('DOMContentLoaded', function() {
        openEditModal(<?= $edit_book['id'] ?>);
    });
    <?php endif; ?>
    </script>
</body>
</html>