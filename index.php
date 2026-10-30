<?php
require 'config.php';
$title  = 'Home';
$active = 'index';

$favorit    = get_products($pdo, 'all', 3);
$total_menu = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$slides = ['kopi-vertikal.jpg', 'iced-latte.jpeg', 'cappuccino.jpeg', 'es-kopi-susu.jpg', 'ilustrasi-cafe.jpg'];

require 'includes/header.php';
?>

<!-- Banner besar dengan sudut membulat, logo di tengah, dan tombol panah -->
<section class="hero">
    <?php foreach ($slides as $i => $foto): ?>
        <div class="slide <?= $i === 0 ? 'active' : '' ?>"
             style="background-image: url('images/<?= e($foto) ?>')"></div>
    <?php endforeach; ?>

    <div class="hero-content">
        <img src="images/logo.png" alt="Sera Coffee" class="hero-logo">
        <h1>Teman Harianmu yang Setia.</h1>
        <p>Kejar deadline atau nongkrong santai, Teman Sera nggak pernah sendirian. Kami selalu sedia kopi terbaik dan senyum hangat untuk setiap langkah kecilmu.</p>
        <a href="menu.php" class="btn">Pesan Sekarang</a>
    </div>

    <button type="button" class="arrow prev" aria-label="Sebelumnya">&#8249;</button>
    <button type="button" class="arrow next" aria-label="Berikutnya">&#8250;</button>
</section>

<!-- Strip angka (ubah sesuai data asli) -->
<section class="stats">
    <div><strong><?= (int) $total_menu ?></strong><span>Menu</span></div>
    <div><strong>1</strong><span>Outlet</span></div>
    <div><strong>08-22</strong><span>Jam buka</span></div>
</section>

<section class="section">
    <h2>Favorit Teman Sera</h2>
    <div class="grid">
        <?php foreach ($favorit as $p): ?>
            <div class="card">
                <img src="images/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
                <div class="card-body">
                    <h3><?= e($p['name']) ?></h3>
                    <p class="desc"><?= e($p['description']) ?></p>
                    <p class="price"><?= rupiah($p['price']) ?></p>
                    <?= rating_line($p['avg_rating'], $p['review_count']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="center"><a href="menu.php" class="btn btn-outline">Lihat Semua Menu</a></p>
</section>

<script>
const slides = document.querySelectorAll('.slide');
let current = 0;

function show(n) {
    slides[current].classList.remove('active');
    current = (n + slides.length) % slides.length;
    slides[current].classList.add('active');
}

document.querySelector('.prev').onclick = () => show(current - 1);
document.querySelector('.next').onclick = () => show(current + 1);
setInterval(() => show(current + 1), 5000);
</script>

<?php require 'includes/footer.php'; ?>
