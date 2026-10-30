<?php
require 'config.php';
$title  = 'Cart';
$active = 'cart';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][(int) $_POST['remove']]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'add') {
        $id = (int) $_POST['id'];

        $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            $_SESSION['cart'][$id] = min(($_SESSION['cart'][$id] ?? 0) + 1, 99);
        }
        header('Location: menu.php?added=1');
        exit;
    }

    if ($action === 'update') {
        foreach ($_POST['qty'] ?? [] as $id => $qty) {
            $qty = min((int) $qty, 99);
            if ($qty > 0) {
                $_SESSION['cart'][(int) $id] = $qty;
            } else {
                unset($_SESSION['cart'][(int) $id]);
            }
        }
        header('Location: cart.php');
        exit;
    }
}

$items = [];
$total = 0;

if (count($_SESSION['cart']) > 0) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $p) {
        $p['qty']      = $_SESSION['cart'][$p['id']];
        $p['subtotal'] = $p['price'] * $p['qty'];
        $total        += $p['subtotal'];
        $items[]       = $p;
    }

    $_SESSION['cart'] = array_intersect_key($_SESSION['cart'], array_flip(array_column($items, 'id')));
}

require 'includes/header.php';
?>

<section class="section">
    <h1>Keranjang</h1>

    <?php if (count($items) === 0): ?>
        <p>Keranjangmu masih kosong.</p>
        <a href="menu.php" class="btn">Lihat Menu</a>
    <?php else: ?>

        <form method="post">
            <input type="hidden" name="action" value="update">
            <!-- BUG FIX: tombol default saat tekan Enter. Tanpa ini, Enter di kolom jumlah
                 malah menekan tombol "Hapus" pertama. -->
            <button type="submit" hidden></button>
            <table class="table">
                <tr>
                    <th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th>
                </tr>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['name']) ?></td>
                        <td><?= rupiah($item['price']) ?></td>
                        <td>
                            <input type="number" name="qty[<?= $item['id'] ?>]"
                                   value="<?= $item['qty'] ?>" min="0" max="99" class="qty">
                        </td>
                        <td><?= rupiah($item['subtotal']) ?></td>
                        <td>
                            <button type="submit" name="remove" value="<?= $item['id'] ?>"
                                    class="link-btn">Hapus</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <p class="total">Total: <strong><?= rupiah($total) ?></strong></p>

            <button type="submit" class="btn btn-outline">Update Jumlah</button>
            <a href="checkout.php" class="btn">Checkout</a>
        </form>

    <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
