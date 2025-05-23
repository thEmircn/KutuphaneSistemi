<?php
session_start();

// Veritabanı bağlantısı
require_once 'includes/db_connect.php';

// Eğer kullanıcı giriş yaptıysa, türüne göre yönlendir
if (isset($_SESSION['kullanici_tipi'])) {
    if ($_SESSION['kullanici_tipi'] === 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($_SESSION['kullanici_tipi'] === 'uye') {
        header("Location: user/index.php");
        exit;
    }
}

// Giriş yapılmamışsa login sayfasına yönlendir
header("Location: login.php");
exit;
?>
