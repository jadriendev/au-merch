<?php

session_start();

if (!isset($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!isset($_SESSION['cart'][$id])) {
    header("Location: cart.php");
    exit;
}

if ($action === 'increase') {
    $_SESSION['cart'][$id]['quantity']++;
}

if ($action === 'decrease') {
    $_SESSION['cart'][$id]['quantity']--;

    if ($_SESSION['cart'][$id]['quantity'] <= 0) {
        unset($_SESSION['cart'][$id]);
    }
}

if ($action === 'remove') {
    unset($_SESSION['cart'][$id]);
}

header("Location: cart.php");
exit;