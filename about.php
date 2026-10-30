<?php
require 'config.php';
$title  = 'About';
$active = 'about';
require 'includes/header.php';
?>

<section class="section split">
    <img src="images/ilustrasi-cafe.jpg" alt="Suasana Sera Coffee">
    <div>
        <h1>Tentang Sera Coffee</h1>
        <p>Sera Coffee lahir dari satu keyakinan sederhana: hari yang baik dimulai dari kopi yang baik.</p>
        <p>Kami memakai biji kopi pilihan dan meraciknya satu per satu. Tempatnya hangat dan nyaman, cocok buat ngobrol, kerja, atau sekadar santai.</p>
    </div>
</section>

<section class="section">
    <h2>Kenapa Sera?</h2>
    <div class="grid three">
        <div class="box"><h3>Biji Pilihan</h3><p>Dipilih dari petani lokal dan disangrai segar.</p></div>
        <div class="box"><h3>Dibuat Fresh</h3><p>Setiap pesanan dibuat saat kamu order.</p></div>
        <div class="box"><h3>Harga Bersahabat</h3><p>Kopi enak tidak harus mahal.</p></div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
