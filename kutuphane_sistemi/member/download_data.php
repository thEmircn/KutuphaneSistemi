<?php
require_once 'auth.php';

// Kullanıcı verilerini topla
$user_data = $pdo->prepare("SELECT * FROM kullanicilar WHERE id = ?");
$user_data->execute([$_SESSION['member_id']]);
$user = $user_data->fetch();

$loan_data = $pdo->prepare("
    SELECT o.*, k.kitap_adi, y.ad_soyad as yazar_adi
    FROM odunc_islemleri o
    JOIN kitaplar k ON o.kitap_id = k.id
    LEFT JOIN yazarlar y ON k.yazar_id = y.id
    WHERE o.uye_id = ?
    ORDER BY o.odunc_tarihi DESC
");
$loan_data->execute([$_SESSION['member_id']]);
$loans = $loan_data->fetchAll();

// Hassas bilgileri kaldır
unset($user['sifre']);
unset($user['id']);

$export_data = [
    'user_info' => $user,
    'loan_history' => $loans,
    'export_date' => date('Y-m-d H:i:s'),
    'total_loans' => count($loans)
];

// JSON olarak indir
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="kisisel_verilerim_' . date('Y-m-d') . '.json"');
header('Cache-Control: max-age=0');

echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>