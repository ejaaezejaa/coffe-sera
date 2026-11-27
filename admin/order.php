<?php
require __DIR__ . '/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Ubah status pesanan (misalnya dari "Menunggu pembayaran" jadi "Sudah dibayar")
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Hapus pesanan yang dibatalkan (misalnya pesanan palsu atau kosong)
    if (($_POST['action'] ?? '') === 'delete' && $order['status'] === 'batal') {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM reviews WHERE order_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
        $pdo->commit();

        header('Location: orders.php?deleted=1');
        exit;
    }

    $new_status = $_POST['status'] ?? '';

    if (isset(status_options()[$new_status])) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$new_status, $id]);
    }

    header('Location: order.php?id=' . $id . '&updated=1');
    exit;
}

$items        = get_order_items($pdo, $id);
$title        = 'Pesanan #' . $order['id'];
$admin_active = 'orders';

require __DIR__ . '/header.php';
?>

<p><a href="orders.php">&larr; Semua pesanan</a></p>
<h1>Pesanan #<?= $order['id'] ?></h1>

<?php if (isset($_GET['updated'])): ?>
    <p class="alert">Status pesanan diperbarui.</p>
<?php endif; ?>

<div class="two-box">
    <div class="box">
        <h3>Pembeli</h3>
        <p class="row"><span>Nama</span><strong><?= e($order['customer_name']) ?></strong></p>
        <p class="row"><span>No. HP</span><span><?= e($order['phone']) ?></span></p>
        <p class="row"><span>Waktu</span><span><?= e(tanggal($order['created_at'])) ?></span></p>
        <p class="row"><span>Pembayaran</span><span><?= e(payment_label($order['payment_method'])) ?></span></p>
        <?php if ($order['note'] !== null && $order['note'] !== ''): ?>
            <p class="row"><span>Catatan</span><span><?= e($order['note']) ?></span></p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3>Status</h3>
        <p><span class="status status-<?= e($order['status']) ?>"><?= e(status_label($order['status'])) ?></span></p>

        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <label>Ubah status</label>
            <select name="status">
                <?php foreach (status_options() as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn small">Simpan</button>
        </form>

        <?php if ($order['status'] === 'batal'): ?>
            <form method="post" onsubmit="return confirm('Hapus pesanan ini selamanya?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="link-btn">Hapus pesanan ini</button>
            </form>
        <?php endif; ?>

        <p><a href="../receipt.php?id=<?= $order['id'] ?>&from=admin" class="btn btn-outline small">Lihat / Cetak Struk</a></p>
    </div>
</div>

<h2>Isi pesanan</h2>

<div class="table-scroll">
    <table class="table data-table">
        <thead>
            <tr><th>Menu</th><th>Pilihan</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td data-label="Menu"><?= e($item['name']) ?></td>
                    <td data-label="Pilihan"><?= e($item['options']) ?></td>
                    <td data-label="Jumlah"><?= $item['qty'] ?></td>
                    <td data-label="Harga"><?= rupiah($item['price']) ?></td>
                    <td data-label="Subtotal"><?= rupiah($item['price'] * $item['qty']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ((int) $order['discount'] > 0): ?>
                <tr>
                    <td colspan="5" class="total-cell">Promo <?= e($order['promo_code']) ?>: -<?= rupiah($order['discount']) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td colspan="5" class="total-cell">Total: <strong><?= rupiah($order['total']) ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/footer.php'; ?>
