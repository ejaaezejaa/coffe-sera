<?php
require 'config.php';
$title  = 'Location';
$active = 'location';

$alamat = 'Jl. Raya Jl. Tol Jagorawi No.1, Ciawi, Kec. Ciawi, Kabupaten Bogor, Jawa Barat 16720';

require 'includes/header.php';
?>

<section class="section split">
    <div>
        <h1>Kunjungi Kami</h1>
        <p><strong>Alamat</strong><br><?= e($alamat) ?></p>
        <p><strong>Jam Buka</strong><br>Setiap Hari, Jam : 09.00 - 14.00 WIB</p>
    </div>

    <iframe class="map" loading="lazy"
        src="https://maps.google.com/maps?q=<?= urlencode($alamat) ?>&output=embed"></iframe>
</section>

<?php require 'includes/footer.php'; ?>
