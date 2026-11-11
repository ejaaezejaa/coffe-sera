<?php
require 'config.php';

$product = get_product($pdo, (int) ($_GET['id'] ?? 0));

if (!$product) {
    header('Location: menu.php');
    exit;
}

$options = product_options($product['category']);

// Gram gula tiap level. Kuncinya sama dengan pilihan di tombol (normal, less, none)
$sugar_grams = [
    'normal' => $product['sugar_normal'] ?? 0,
    'less'   => $product['sugar_less'] ?? 0,
    'none'   => 0,
];

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

<?php if (isset($product['calories'])): ?>
<section class="section">
    <h2>Kadar Gula &amp; Nilai Gizi</h2>

    <div class="nutrition">
        <?php if ($product['category'] === 'iced'): ?>
            <div class="box">
                <h3>Nilai gizi per porsi, menurut level gula</h3>

                <div class="table-scroll">
                    <table class="info-table">
                        <tr>
                            <th></th>
                            <?php foreach ($options['choices'] as $label): ?>
                                <th><?= e($label) ?></th>
                            <?php endforeach; ?>
                        </tr>

                        <tr>
                            <td>Gula tambahan (g)</td>
                            <?php foreach ($options['choices'] as $code => $label): ?>
                                <td><?= $sugar_grams[$code] ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Kalori (kkal)</td>
                            <?php foreach ($options['choices'] as $code => $label): ?>
                                <td><?= $product['calories'] - ($product['sugar_normal'] - $sugar_grams[$code]) * 4 ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Karbohidrat (g)</td>
                            <?php foreach ($options['choices'] as $code => $label): ?>
                                <td><?= $product['carbs'] - ($product['sugar_normal'] - $sugar_grams[$code]) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Lemak (g)</td>
                            <?php foreach ($options['choices'] as $label): ?>
                                <td><?= number_format($product['fat'], 1, ',', '.') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Protein (g)</td>
                            <?php foreach ($options['choices'] as $label): ?>
                                <td><?= number_format($product['protein'], 1, ',', '.') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Kafein (mg)</td>
                            <?php foreach ($options['choices'] as $label): ?>
                                <td><?= $product['caffeine'] ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </table>
                </div>

                <p class="note">
                    Kalori dan karbohidrat tiap level dihitung dari selisih gulanya (1 gram gula kira-kira 4 kkal).
                    Lemak, protein, dan kafein sama di semua level. Angka ini perkiraan dan bisa berbeda sedikit tergantung racikan.
                </p>
            </div>
        <?php else: ?>
            <div class="box">
                <h3>Nilai gizi per porsi</h3>
                <table class="info-table">
                    <tr><td>Gula tambahan (g)</td><td><?= $product['sugar_normal'] ?></td></tr>
                    <tr><td>Kalori (kkal)</td><td><?= $product['calories'] ?></td></tr>
                    <tr><td>Karbohidrat (g)</td><td><?= $product['carbs'] ?></td></tr>
                    <tr><td>Lemak (g)</td><td><?= number_format($product['fat'], 1, ',', '.') ?></td></tr>
                    <tr><td>Protein (g)</td><td><?= number_format($product['protein'], 1, ',', '.') ?></td></tr>
                    <tr><td>Kafein (mg)</td><td><?= $product['caffeine'] ?></td></tr>
                </table>
                <p class="note">Angka ini perkiraan dan bisa berbeda sedikit tergantung racikan.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

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
