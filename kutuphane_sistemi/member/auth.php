<?php
session_start();
require_once '../config.php';

// HTML güvenli çıktı için helper fonksiyon
function safe_html($text, $default = '') {
    return htmlspecialchars($text ?? $default);
}

// Üye giriş kontrolü
if (!isset($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

// Üye bilgilerini güncelle (aktif olmayan üyeleri çıkar)
$stmt = $pdo->prepare("SELECT durum FROM kullanicilar WHERE id = ? AND kullanici_tipi = 'uye'");
$stmt->execute([$_SESSION['member_id']]);
$user = $stmt->fetch();

if (!$user || $user['durum'] != 'aktif') {
    session_destroy();
    header('Location: index.php?error=account_disabled');
    exit;
}
?>