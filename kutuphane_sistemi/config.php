<?php
$host = 'localhost';
$dbname = 'kutuphane_sistemi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

session_start();

// Upload klasörü için mutlak yol
$upload_base_dir = $_SERVER['DOCUMENT_ROOT'] . '/KutuphaneSistemi/kutuphane_sistemi/uploads/';

// Klasörleri oluştur
if (!is_dir($upload_base_dir)) {
    @mkdir($upload_base_dir, 0777, true);
}
if (!is_dir($upload_base_dir . 'books/')) {
    @mkdir($upload_base_dir . 'books/', 0777, true);
}

// Debug için
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>