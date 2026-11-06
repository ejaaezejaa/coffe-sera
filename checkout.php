<?php
require 'config.php';
$title  = 'Checkout';
$active = 'cart';

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$methods = [
    'qris'     => 'QRIS',
    'transfer' => 'Transfer Bank',
    'ewallet'  => 'E-Wallet (GoPay / OVO / DANA)',
    'cod'      => 'Bayar di Kasir (Cash)',
];

$items = get_cart_items($pdo);

if (count($items) === 0) {
    header('Location: cart.php');
    exit;
}

$total = array_sum(array_column($items, 'subtotal'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $note   = trim($_POST['note'] ?? '');
    $method = $_POST['method'] ?? '';

    if ($name === '' || $phone === '') {
        $error = 'Nama dan nomor HP wajib diisi.';
    } elseif (!preg_match('/^[0-9+\- ]{8,20}$/', $phone)) {
        $error = 'Nomor HP tidak valid (8-20 karakter, hanya angka, +, - dan spasi).';
    } elseif (!isset($methods[$method])) {
        $error = 'Pilih metode pembayaran.';
    } else {
        $name = mb_substr($name, 0, 100);
        $note = mb_substr($note, 0, 255);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO orders (customer_name, phone, note, payment_method, total) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $phone, $note, $method, $total]);
            $order_id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, qty, price, options) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $stmt->execute([$order_id, $item['id'], $item['qty'], $item['price'], $item['option']]);
            }

            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            $error = 'Pesanan gagal disimpan, coba lagi ya.';
        }

        if ($error === '') {
            $_SESSION['cart'] = [];
            $_SESSION['last_order'] = $order_id;
            header('Location: success.php?id=' . $order_id);
            exit;
        }
    }
}

require 'includes/header.php';
?>

<section class="section split top">
    <form method="post" class="form">
        <h1>Checkout</h1>

        <?php if ($error !== ''): ?>
            <p class="alert error"><?= e($error) ?></p>
        <?php endif; ?>

        <label>Nama</label>
        <input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>

        <label>Nomor HP / WhatsApp</label>
        <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required>

        <label>Catatan (opsional)</label>
        <textarea name="note" rows="3" placeholder="Contoh: tanpa sedotan"><?= e($_POST['note'] ?? '') ?></textarea>

        <label>Metode Pembayaran</label>
        <?php foreach ($methods as $key => $label): ?>
            <label class="radio">
                <input type="radio" name="method" value="<?= $key ?>"
                    <?= (($_POST['method'] ?? '') === $key) ? 'checked' : '' ?>>
                <?= e($label) ?>
            </label>
        <?php endforeach; ?>

        <button type="submit" class="btn">Buat Pesanan</button>
    </form>

    <div class="box">
        <h3>Ringkasan Pesanan</h3>
        <?php foreach ($items as $item): ?>
            <p class="row">
                <span><?= e($item['name']) ?> (<?= e($item['option']) ?>) x<?= $item['qty'] ?></span>
                <span><?= rupiah($item['subtotal']) ?></span>
            </p>
        <?php endforeach; ?>
        <hr>
        <p class="row"><strong>Total</strong><strong><?= rupiah($total) ?></strong></p>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
