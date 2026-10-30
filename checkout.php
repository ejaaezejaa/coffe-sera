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

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

if (count($products) === 0) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach ($products as $p) {
    $total += $p['price'] * $_SESSION['cart'][$p['id']];
}

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
                'INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)'
            );
            foreach ($products as $p) {
                $stmt->execute([$order_id, $p['id'], $_SESSION['cart'][$p['id']], $p['price']]);
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
        <textarea name="note" rows="3" placeholder="Contoh: less sugar"><?= e($_POST['note'] ?? '') ?></textarea>

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
        <?php foreach ($products as $p): ?>
            <p class="row">
                <span><?= e($p['name']) ?> x<?= $_SESSION['cart'][$p['id']] ?></span>
                <span><?= rupiah($p['price'] * $_SESSION['cart'][$p['id']]) ?></span>
            </p>
        <?php endforeach; ?>
        <hr>
        <p class="row"><strong>Total</strong><strong><?= rupiah($total) ?></strong></p>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
