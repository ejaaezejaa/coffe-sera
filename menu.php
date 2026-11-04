<?php
require 'config.php';
$title  = 'Menu';
$active = 'menu';

$cat = $_GET['cat'] ?? 'all';

$products = get_products($pdo, $cat);

$reviews = $pdo->query(
    "SELECT r.rating, r.comment, r.show_name, p.name AS product, o.customer_name
     FROM reviews r
     JOIN products p ON p.id = r.product_id
     JOIN orders o ON o.id = r.order_id
     WHERE r.comment IS NOT NULL AND r.comment <> ''
     ORDER BY r.id DESC
     LIMIT 6"
)->fetchAll();

require 'includes/header.php';
?>

<section class="section">
    <h1>Menu Kami</h1>

    <div class="filters">
        <a href="menu.php" class="<?= $cat === 'all' ? 'active' : '' ?>">Semua</a>
        <a href="menu.php?cat=iced" class="<?= $cat === 'iced' ? 'active' : '' ?>">Iced</a>
        <a href="menu.php?cat=hot" class="<?= $cat === 'hot' ? 'active' : '' ?>">Hot</a>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <p class="alert">Ditambahkan ke keranjang. <a href="cart.php">Lihat keranjang</a></p>
    <?php endif; ?>

    <div class="grid">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <img src="images/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
                <div class="card-body">
                    <h3><a href="product.php?id=<?= $p['id'] ?>" class="card-link"><?= e($p['name']) ?></a></h3>
                    <p class="desc"><?= e($p['description']) ?></p>
                    <p class="price"><?= rupiah($p['price']) ?></p>
                    <?= rating_line($p['avg_rating'], $p['review_count']) ?>

                    <form method="post" action="cart.php">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn small">+ Keranjang</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <h2>Kata Pelanggan</h2>
    <?php if (count($reviews) === 0): ?>
        <p>Belum ada ulasan. Jadilah yang pertama setelah memesan!</p>
    <?php endif; ?>

    <?php foreach ($reviews as $r): ?>
        <div class="box">
            <span class="stars"><?= stars($r['rating']) ?></span>
            <strong><?= e($r['product']) ?></strong>
            <p><?= e($r['comment']) ?></p>
            <small>- <?= e(review_name($r['customer_name'], $r['show_name'])) ?></small>
        </div>
    <?php endforeach; ?>
</section>

<?php require 'includes/footer.php'; ?>
