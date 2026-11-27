<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $id = (int) ($_POST['id'] ?? 0);

    // Hanya pembeli pesanan ini, dan hanya selama belum dibayar
    if (($_SESSION['last_order'] ?? 0) === $id) {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'batal' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        header('Location: success.php?id=' . $id . '&cancelled=1');
        exit;
    }
}

header('Location: index.php');
exit;
