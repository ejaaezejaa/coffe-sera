<?php
require 'config.php';

$product = get_product($pdo, (int) ($_GET['id'] ?? 0));

if (!$product) {
    header('Location: menu.php');
    exit;
}

$options = product_options($product['category']);

$title  = $product['name'];
$active = 'menu';

$stmt = $pdo->prepare(
    'SELECT r.rating, r.comment, r.show_name, o.customer_name
     FROM reviews r
     JOIN orders o ON o.id = r.order_id
     WHERE r.product_id = ?
     ORDER BY r.id DESC
     LIMIT 10'
);
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();

require 'includes/header.php';
?>

<section class="section">
    <p><a href="menu.php">&larr; Kembali ke menu</a></p>

    <div class="product">
        <img src="images/<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" class="product-photo">

        <div class="product-info">
            <span class="tag"><?= $product['category'] === 'iced' ? 'Iced' : 'Hot' ?></span>
            <h1><?= e($product['name']) ?></h1>
            <?= rating_line($product['avg_rating'], $product['review_count']) ?>
            <p class="product-price"><?= rupiah($product['price']) ?></p>
            <p><?= e($product['details'] ?: $product['description']) ?></p>

            <?php if (isset($_GET['added'])): ?>
                <p class="alert">Ditambahkan ke keranjang. <a href="cart.php">Lihat keranjang</a></p>
            <?php endif; ?>

            <form method="post" action="cart.php" class="buy-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                <input type="hidden" name="from" value="product">

                <fieldset class="options">
                    <legend><?= e($options['label']) ?></legend>
                    <?php foreach ($options['choices'] as $code => $label): ?>
                        <label class="chip">
                            <input type="radio" name="option" value="<?= $code ?>" <?= $code === array_key_first($options['choices']) ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <div class="buy-row">
                    <div class="stepper">
                        <button type="button" data-step="-1" aria-label="Kurangi">&minus;</button>
                        <input type="number" name="qty" value="1" min="1" max="99">
                        <button type="button" data-step="1" aria-label="Tambah">+</button>
                    </div>

                    <button type="submit" class="btn">Tambah ke Keranjang</button>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="section">
    <h2>Ulasan</h2>

    <?php if (count($reviews) === 0): ?>
        <p>Belum ada ulasan untuk menu ini.</p>
    <?php endif; ?>

    <?php foreach ($reviews as $r): ?>
        <div class="box">
            <span class="stars"><?= stars($r['rating']) ?></span>
            <?php if ($r['comment'] !== null && $r['comment'] !== ''): ?>
                <p><?= e($r['comment']) ?></p>
            <?php endif; ?>
            <small>- <?= e(review_name($r['customer_name'], $r['show_name'])) ?></small>
        </div>
    <?php endforeach; ?>
</section>

<?php require 'includes/footer.php'; ?>
