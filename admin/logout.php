<?php
require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_must_change']);
    session_regenerate_id(true);
}

header('Location: login.php');
exit;
