<?php
require_once '../config.php';
require_once 'includes/cache.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cache = new SimpleCache();
    if ($cache->clear()) {
        echo 'Cache başarıyla temizlendi';
    } else {
        http_response_code(500);
        echo 'Cache temizlenirken hata oluştu';
    }
} else {
    http_response_code(405);
    echo 'Method not allowed';
}
?>