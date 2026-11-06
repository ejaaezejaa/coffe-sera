<?php
$active = $active ?? '';
$menu_links = [
    'index'    => ['Home', 'index.php'],
    'about'    => ['About', 'about.php'],
    'menu'     => ['Menu', 'menu.php'],
    'location' => ['Location', 'location.php'],
    'contact'  => ['Contact', 'contact.php'],
    'cart'     => ['Cart', 'cart.php'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Sera Coffee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="images/favicon.png?v=<?= filemtime(__DIR__ . '/../images/favicon.png') ?>">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="images/favicon.png">
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <img src="images/logo.png" alt="Sera Coffee">
    </a>

    <button type="button" class="menu-toggle" aria-label="Buka menu">&#9776;</button>

    <nav>
        <?php foreach ($menu_links as $key => $link): ?>
            <a href="<?= $link[1] ?>" class="<?= $active === $key ? 'active' : '' ?>">
                <?= $link[0] ?>
                <?php if ($key === 'cart' && cart_count() > 0): ?>
                    <span class="badge"><?= cart_count() ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>

<main>
