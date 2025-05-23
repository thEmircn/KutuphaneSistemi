<?php
require_once 'auth.php';

$book_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT k.*, y.ad_soyad as yazar_adi, kat.kategori_adi,
           (SELECT COUNT(*) FROM odunc_islemleri WHERE kitap_id = k.id) as toplam_odunc
    FROM kitaplar k
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    LEFT JOIN kategoriler kat ON k.kategori_id = kat.id
    WHERE k.id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    echo '<div class="alert alert-danger">Kitap bulunamadı.</div>';
    exit;
}

// Bu üyenin bu kitapla ilgili geçmişi
$user_history = $pdo->prepare("
    SELECT * FROM odunc_islemleri 
    WHERE uye_id = ? AND kitap_id = ? 
    ORDER BY odunc_tarihi DESC 
    LIMIT 5
");
$user_history->execute([$_SESSION['member_id'], $book_id]);
$history = $user_history->fetchAll();
?>

<div class="row">
    <div class="col-md-4 text-center">
        <?php if ($book['fotograf'] && file_exists('../uploads/books/' . $book['fotograf'])): ?>
            <img src="../uploads/books/<?= $book['fotograf'] ?>" class="img-fluid rounded shadow" style="max-height: 300px;">
        <?php else: ?>
            <div class="bg-light p-5 rounded" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                <div style="font-size: 5rem;">📚</div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <?php if ($book['mevcut_adet'] > 0): ?>
                <span class="badge bg-success fs-6">✅ Mevcut (<?= $book['mevcut_adet'] ?> adet)</span>
            <?php else: ?>
                <span class="badge bg-danger fs-6">❌ Mevcut değil</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-8">
        <h3><?= safe_html($book['kitap_adi']) ?></h3>
        
        <table class="table table-borderless">
            <tr>
                <th width="30%">👤 Yazar:</th>
                <td><?= safe_html($book['yazar_adi'] ?? 'Bilinmeyen') ?></td>
            </tr>
            <tr>
                <th>🏷️ Kategori:</th>
                <td><?= safe_html($book['kategori_adi'] ?? 'Kategori yok') ?></td>
            </tr>
            <?php if ($book['isbn']): ?>
            <tr>
                <th>📘 ISBN:</th>
                <td><?= safe_html($book['isbn']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($book['yayin_evi']): ?>
            <tr>
                <th>🏢 Yayın Evi:</th>
                <td><?= safe_html($book['yayin_evi']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($book['yayin_yili']): ?>
            <tr>
                <th>📅 Yayın Yılı:</th>
                <td><?= $book['yayin_yili'] ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($book['sayfa_sayisi']): ?>
            <tr>
                <th>📄 Sayfa Sayısı:</th>
                <td><?= number_format($book['sayfa_sayisi']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>📊 Popülerlik:</th>
                <td><?= $book['toplam_odunc'] ?> kez ödünç alındı</td>
            </tr>
            <?php if ($book['raf_no']): ?>
            <tr>
                <th>📍 Raf No:</th>
                <td><?= safe_html($book['raf_no']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
        
        <?php if ($book['ozet']): ?>
        <div class="mt-3">
            <h6>📝 Özet:</h6>
            <p class="text-muted"><?= nl2br(safe_html($book['ozet'])) ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($history): ?>
        <div class="mt-4">
            <h6>📋 Ödünç Geçmişiniz:</h6>
            <div class="list-group list-group-flush">
                <?php foreach ($history as $item): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>Ödünç:</strong> <?= date('d.m.Y', strtotime($item['odunc_tarihi'])) ?>
                        </div>
                        <span class="badge bg-<?= $item['durum'] == 'odunc' ? 'warning' : 'success' ?>">
                            <?= $item['durum'] == 'odunc' ? 'Ödünçte' : 'Teslim Edildi' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>