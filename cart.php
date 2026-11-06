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
        unset($_SESSION['cart'][(string) $_POST['remove']]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'add') {
        $id = (int) $_POST['id'];

        $stmt = $pdo->prepare('SELECT category FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $p = $stmt->fetch();

        if ($p) {
            $choices = product_options($p['category'])['choices'];
            $code    = is_string($_POST['option'] ?? null) ? $_POST['option'] : '';

            if (!isset($choices[$code])) {
                $code = array_key_first($choices);
            }

            $key = $id . '_' . $code;
            $qty = max(1, min(99, (int) ($_POST['qty'] ?? 1)));
            $_SESSION['cart'][$key] = min(($_SESSION['cart'][$key] ?? 0) + $qty, 99);
        }

        if (($_POST['from'] ?? '') === 'product') {
            header('Location: product.php?id=' . $id . '&added=1');
        } else {
            header('Location: menu.php?added=1');
        }
        exit;
    }

    if ($action === 'update') {
        foreach ($_POST['qty'] ?? [] as $key => $qty) {
            $key = (string) $key;

            if (!isset($_SESSION['cart'][$key])) {
                continue;
            }

            $qty = min((int) $qty, 99);
            if ($qty > 0) {
                $_SESSION['cart'][$key] = $qty;
            } else {
                unset($_SESSION['cart'][$key]);
            }
        }
        header('Location: cart.php');
        exit;
    }
}

$items = get_cart_items($pdo);
$total = array_sum(array_column($items, 'subtotal'));

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
            <table class="table cart-table">
                <thead>
                    <tr>
                        <th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-product">
                                    <img src="images/<?= e($item['image']) ?>" alt="" class="thumb">
                                    <div>
                                        <a href="product.php?id=<?= $item['id'] ?>"><?= e($item['name']) ?></a><br>
                                        <small class="option"><?= e($item['option']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Harga"><?= rupiah($item['price']) ?></td>
                            <td data-label="Jumlah">
                                <div class="stepper" data-autosubmit="1">
                                    <button type="button" data-step="-1" aria-label="Kurangi">&minus;</button>
                                    <input type="number" name="qty[<?= e($item['key']) ?>]"
                                           value="<?= $item['qty'] ?>" min="1" max="99">
                                    <button type="button" data-step="1" aria-label="Tambah">+</button>
                                </div>
                            </td>
                            <td data-label="Subtotal"><?= rupiah($item['subtotal']) ?></td>
                            <td>
                                <button type="submit" name="remove" value="<?= e($item['key']) ?>"
                                        class="link-btn">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="total">Total: <strong><?= rupiah($total) ?></strong></p>

            <div class="cart-actions">
                <a href="menu.php" class="btn btn-outline">Tambah Menu Lain</a>
                <a href="checkout.php" class="btn">Checkout</a>
            </div>
        </form>

    <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
