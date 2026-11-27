<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/orders.php';

// Buka "/admin" (tanpa garis miring) diarahkan ke "/admin/", supaya link relatif di halaman admin benar
$path = strtok($_SERVER['REQUEST_URI'], '?');
if (substr($path, -4) !== '.php' && substr($path, -1) !== '/') {
    header('Location: ' . $path . '/', true, 301);
    exit;
}

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

// Pesanan QRIS / E-Wallet yang kelamaan belum dibayar dibatalkan otomatis
expire_old_orders($pdo);

// Panggil di awal setiap halaman admin (kecuali login)
function require_admin() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }

    // Password awal wajib diganti sebelum boleh membuka halaman lain
    $halaman = basename($_SERVER['SCRIPT_NAME']);
    if (!empty($_SESSION['admin_must_change']) && $halaman !== 'password.php') {
        header('Location: password.php');
        exit;
    }
}
