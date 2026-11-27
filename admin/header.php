<?php
$admin_active = $admin_active ?? '';
$admin_links = [
    'dashboard' => ['Dashboard', 'index.php'],
    'orders'    => ['Pesanan', 'orders.php'],
    'recap'     => ['Rekap Bulanan', 'recap.php'],
    'messages'  => ['Pesan', 'messages.php'],
    'resets'    => ['Reset Password', 'resets.php'],
    'password'  => ['Ganti Password', 'password.php'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> | Admin Sera Coffee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="stylesheet" href="../css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?= filemtime(__DIR__ . '/../css/admin.css') ?>">
</head>
<body>

<div id="loader" class="loader" aria-hidden="true">
    <div class="loader-cup">
        <span class="steam s1"></span>
        <span class="steam s2"></span>
        <span class="steam s3"></span>
        <div class="cup"><span class="handle"></span></div>
    </div>
    <p>Memuat...</p>
</div>
<noscript><style>.loader { display: none; }</style></noscript>
<script src="../js/loader.js"></script>

<?php if (!empty($_SESSION['admin_id'])): ?>
    <header class="admin-bar" id="admin-bar">
        <a href="index.php" class="admin-brand">
            <img src="../images/logo.png" alt="Sera Coffee">
            <span>Admin</span>
        </a>

        <!-- Tombol ini hanya tampil di HP dan tablet. Isinya dibuka dan ditutup oleh js/admin-menu.js -->
        <button type="button" class="admin-menu-btn" id="admin-menu-btn" aria-expanded="false" aria-controls="admin-menu">Menu</button>

        <div class="admin-menu" id="admin-menu">
            <nav>
                <?php foreach ($admin_links as $key => $link): ?>
                    <a href="<?= $link[1] ?>" class="<?= $admin_active === $key ? 'active' : '' ?>"><?= $link[0] ?></a>
                <?php endforeach; ?>
                <a href="../index.php" target="_blank" rel="noopener">Lihat Website</a>
            </nav>

            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="btn small btn-outline">Logout (<?= e($_SESSION['admin_name'] ?? '') ?>)</button>
            </form>
        </div>
    </header>
<?php endif; ?>

<main class="admin-main">
