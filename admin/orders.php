<?php
require __DIR__ . '/auth.php';
require_admin();

$title        = 'Semua Pesanan';
$admin_active = 'orders';

$filter = $_GET['status'] ?? '';
if (!isset(status_options()[$filter])) {
    $filter = '';
}

// Hapus semua pesanan yang dibatalkan sekaligus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'purge') {
    csrf_check();

    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM reviews WHERE order_id IN (SELECT id FROM orders WHERE status = 'batal')");
    $pdo->exec("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE status = 'batal')");
    $pdo->exec("DELETE FROM orders WHERE status = 'batal'");
    $pdo->commit();

    header('Location: orders.php?deleted=1');
    exit;
}

$sql = 'SELECT o.*, (SELECT COALESCE(SUM(qty), 0) FROM order_items WHERE order_id = o.id) AS cups
        FROM orders o';
$params = [];

if ($filter !== '') {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}

$sql .= ' ORDER BY o.id DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require __DIR__ . '/header.php';
?>

<h1>Semua Pesanan</h1>

<?php if (isset($_GET['deleted'])): ?>
    <p class="alert">Pesanan yang dibatalkan sudah dihapus.</p>
<?php endif; ?>

<div class="filters">
    <a href="orders.php" class="<?= $filter === '' ? 'active' : '' ?>">Semua</a>
    <?php foreach (status_options() as $key => $label): ?>
        <a href="orders.php?status=<?= $key ?>" class="<?= $filter === $key ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($filter === 'batal' && count($orders) > 0): ?>
    <form method="post" onsubmit="return confirm('Hapus SEMUA pesanan yang dibatalkan selamanya?');">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="purge">
        <button type="submit" class="btn btn-outline small">Hapus semua yang dibatalkan</button>
    </form>
<?php endif; ?>

<?php if (count($orders) === 0): ?>
    <p>Tidak ada pesanan.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>No.</th><th>Waktu</th><th>Pembeli</th><th>No. HP</th><th>Cup</th><th>Total</th><th>Pembayaran</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td data-label="No.">#<?= $o['id'] ?></td>
                        <td data-label="Waktu"><?= e(tanggal($o['created_at'])) ?></td>
                        <td data-label="Pembeli"><?= e($o['customer_name']) ?></td>
                        <td data-label="No. HP"><?= e($o['phone']) ?></td>
                        <td data-label="Cup"><?= (int) $o['cups'] ?></td>
                        <td data-label="Total"><?= rupiah($o['total']) ?></td>
                        <td data-label="Pembayaran"><?= e(payment_label($o['payment_method'])) ?></td>
                        <td data-label="Status"><span class="status status-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                        <td><a href="order.php?id=<?= $o['id'] ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
