<?php
$css    = $css ?? 'products';
$active = $active ?? '';
$nav = [
    'dashboard' => ['dashboard.php', 'far fa-house', 'Dashboard'],
    'products'  => ['products.php', 'fa fa-box-open', 'Products'],
    'logs'      => ['logs.php', 'fa fa-clock-rotate-left', 'Stock Logs'],
];
$initial = strtoupper(substr($_SESSION['first_name'] ?? 'A', 0, 1));
$flash   = pull_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://www.auchiefslms.com/college/pluginfile.php/1/core_admin/logocompact/300x300/1784347206/au-logo-smaller.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="css/<?= e($css) ?>.css">
    <link rel="stylesheet" href="css/inventory.css">
    <title><?= e($title ?? 'Admin') ?> | AU Merch</title>
</head>
<body>
    <aside>
        <button class="close-btn"><i class="fa fa-xmark"></i></button>
        <div class="logo">
            <img src="../images/Arellano_University_New_Logo.png" alt="Arellano University Logo">
            <h1><span>AU Merch</span><span>Inventory Admin</span></h1>
        </div>
        <div class="nav-links">
            <nav>
                <ul>
                    <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
                    <li class="<?= $active === $key ? 'active' : '' ?>">
                        <a href="<?= e($href) ?>"><i class="<?= e($icon) ?>"></i> <?= e($label) ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <ul class="bottom-link">
                    <li><a href="logout.php"><i class="fa fa-right-to-bracket"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <main>
        <header>
            <nav class="navbar">
                <button class="menu-btn"><i class="fa fa-bars"></i></button>
                <a href="products.php?status=Low+Stock" class="notification" title="Low stock items"><i class="fa fa-bell"></i></a>
                <div class="profile">
                    <div class="icon"><?= e($initial) ?></div>
                    <h4><?= e($_SESSION['first_name'] ?? 'Admin') ?></h4>
                    <i class="fa fa-chevron-down"></i>
                </div>
            </nav>
        </header>
        <?php if ($flash): ?>
        <div class="max"><div class="flash <?= e($flash[1]) ?>"><?= e($flash[0]) ?></div></div>
        <?php endif; ?>
