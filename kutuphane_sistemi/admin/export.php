<?php
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Export türü
$export_type = $_GET['type'] ?? 'books';
$format = $_GET['format'] ?? 'excel';

// Arama parametrelerini al
$search = $_GET['search'] ?? '';
$yazar_id = $_GET['yazar_id'] ?? '';
$kategori_id = $_GET['kategori_id'] ?? '';
$durum = $_GET['durum'] ?? '';

// Excel başlık ayarları
if ($format == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $export_type . '_raporu_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
}

// Verileri çek
switch ($export_type) {
    case 'books':
        $data = getBookData($pdo, $search, $yazar_id, $kategori_id, $durum);
        $headers = ['ID', 'Kitap Adı', 'Yazar', 'Kategori', 'ISBN', 'Yayın Evi', 'Yayın Yılı', 'Stok', 'Mevcut', 'Durum'];
        break;
    case 'members':
        $data = getMemberData($pdo);
        $headers = ['ID', 'Ad Soyad', 'Email', 'Telefon', 'Kayıt Tarihi', 'Durum', 'Toplam Ödünç'];
        break;
    case 'loans':
        $data = getLoanData($pdo);
        $headers = ['ID', 'Üye', 'Kitap', 'Ödünç Tarihi', 'Teslim Tarihi', 'Durum', 'Gecikme (Gün)'];
        break;
    case 'overdue':
        $data = getOverdueData($pdo);
        $headers = ['Üye', 'Telefon', 'Kitap', 'Teslim Tarihi', 'Gecikme', 'Ceza (TL)'];
        break;
}

function getBookData($pdo, $search, $yazar_id, $kategori_id, $durum) {
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(k.kitap_adi LIKE ? OR k.isbn LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
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
    
    $sql = "SELECT k.id, k.kitap_adi, y.ad_soyad as yazar, kat.kategori_adi as kategori, 
                   k.isbn, k.yayin_evi, k.yayin_yili, k.stok_adedi, k.mevcut_adet, k.durum
            FROM kitaplar k 
            LEFT JOIN yazarlar y ON k.yazar_id = y.id 
            LEFT JOIN kategoriler kat ON k.kategori_id = kat.id";
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getMemberData($pdo) {
    $sql = "SELECT u.id, u.ad_soyad, u.email, u.telefon, 
                   DATE(u.kayit_tarihi) as kayit_tarihi, u.durum,
                   COUNT(o.id) as toplam_odunc
            FROM kullanicilar u 
            LEFT JOIN odunc_islemleri o ON u.id = o.uye_id
            WHERE u.kullanici_tipi = 'uye'
            GROUP BY u.id
            ORDER BY u.kayit_tarihi DESC";
    
    return $pdo->query($sql)->fetchAll();
}

function getLoanData($pdo) {
    $sql = "SELECT o.id, u.ad_soyad as uye, k.kitap_adi as kitap,
                   o.odunc_tarihi, o.teslim_tarihi, o.durum,
                   DATEDIFF(CURDATE(), o.teslim_tarihi) as gecikme_gun
            FROM odunc_islemleri o
            JOIN kullanicilar u ON o.uye_id = u.id
            JOIN kitaplar k ON o.kitap_id = k.id
            ORDER BY o.odunc_tarihi DESC";
    
    return $pdo->query($sql)->fetchAll();
}

function getOverdueData($pdo) {
    $sql = "SELECT u.ad_soyad as uye, u.telefon, k.kitap_adi as kitap,
                   o.teslim_tarihi, 
                   DATEDIFF(CURDATE(), o.teslim_tarihi) as gecikme_gun,
                   (DATEDIFF(CURDATE(), o.teslim_tarihi) * 2.50) as ceza
            FROM odunc_islemleri o
            JOIN kullanicilar u ON o.uye_id = u.id
            JOIN kitaplar k ON o.kitap_id = k.id
            WHERE o.durum = 'odunc' AND o.teslim_tarihi < CURDATE()
            ORDER BY gecikme_gun DESC";
    
    return $pdo->query($sql)->fetchAll();
}

// Excel çıktısı
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= ucfirst($export_type) ?> Raporu</title>
</head>
<body>
    <table border="1">
        <tr style="background-color: #4CAF50; color: white; font-weight: bold;">
            <?php foreach ($headers as $header): ?>
                <td><?= $header ?></td>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($data as $row): ?>
        <tr>
            <?php foreach ($row as $cell): ?>
                <td><?= htmlspecialchars($cell ?? '') ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <br><br>
    <p><strong>Rapor Tarihi:</strong> <?= date('d.m.Y H:i:s') ?></p>
    <p><strong>Toplam Kayıt:</strong> <?= count($data) ?></p>
</body>
</html>