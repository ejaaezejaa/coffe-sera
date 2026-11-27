<?php
require 'config.php';
require 'includes/orders.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

// Struk hanya bisa dilihat pembeli yang baru memesan, atau admin yang sudah login
$is_buyer = ($_SESSION['last_order'] ?? 0) === $id;
$is_admin = !empty($_SESSION['admin_id']);
$is_owner = $order && !empty($_SESSION['customer_id']) && (int) $order['customer_id'] === (int) $_SESSION['customer_id'];

if (!$order || (!$is_buyer && !$is_admin && !$is_owner)) {
    header('Location: index.php');
    exit;
}

// Tombol Kembali mengikuti dari mana struk dibuka (?from=admin, ?from=qris, ?from=account)
$from = $_GET['from'] ?? '';

if ($is_admin && $from === 'admin') {
    $back = 'admin/order.php?id=' . $id;
} elseif ($is_buyer && $from === 'qris') {
    $back = 'qris.php?id=' . $id;
} elseif ($is_owner && $from === 'account') {
    $back = 'account.php';
} elseif ($is_buyer) {
    $back = 'success.php?id=' . $id;
} elseif ($is_owner) {
    $back = 'account.php';
} else {
    $back = 'admin/order.php?id=' . $id;
}

$items  = get_order_items($pdo, $id);
$title  = 'Struk #' . $order['id'];
$active = 'cart';

require 'includes/header.php';
?>

<section class="section narrow">
    <div class="receipt">
        <div class="receipt-head">
            <img src="images/logo.png" alt="Sera Coffee" class="receipt-logo">
            <p>STRUK PEMBAYARAN</p>
        </div>

        <p class="row"><span>No. Pesanan</span><strong>#<?= $order['id'] ?></strong></p>
        <p class="row"><span>Tanggal</span><span><?= e(tanggal($order['created_at'])) ?></span></p>
        <p class="row"><span>Pembeli</span><span><?= e($order['customer_name']) ?></span></p>
        <p class="row"><span>No. HP</span><span><?= e($order['phone']) ?></span></p>

        <hr class="dashed">

        <?php foreach ($items as $item): ?>
            <div class="receipt-item">
                <p class="row">
                    <span><?= e($item['name']) ?></span>
                    <span><?= rupiah($item['price'] * $item['qty']) ?></span>
                </p>
                <small><?= e($item['options']) ?> | <?= $item['qty'] ?> x <?= rupiah($item['price']) ?></small>
            </div>
        <?php endforeach; ?>

        <hr class="dashed">

        <?php if ((int) $order['discount'] > 0): ?>
            <p class="row"><span>Subtotal</span><span><?= rupiah($order['total'] + $order['discount']) ?></span></p>
            <p class="row"><span>Promo <?= e($order['promo_code']) ?></span><span>-<?= rupiah($order['discount']) ?></span></p>
        <?php endif; ?>
        <p class="row receipt-total"><strong>TOTAL</strong><strong><?= rupiah($order['total']) ?></strong></p>
        <p class="row"><span>Pembayaran</span><span><?= e(payment_label($order['payment_method'])) ?></span></p>
        <p class="row"><span>Status</span><strong><?= e(status_label($order['status'])) ?></strong></p>

        <?php if ($order['note'] !== null && $order['note'] !== ''): ?>
            <p class="row"><span>Catatan</span><span><?= e($order['note']) ?></span></p>
        <?php endif; ?>

        <hr class="dashed">
        <p class="receipt-thanks">Terima kasih sudah memesan di Sera Coffee!</p>
    </div>

    <p class="receipt-actions no-print">
        <button type="button" class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="<?= e($back) ?>" class="btn btn-outline">Kembali</a>
    </p>
</section>

<?php require 'includes/footer.php'; ?>
