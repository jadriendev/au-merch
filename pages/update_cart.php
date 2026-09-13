<?php

session_start();

if (!isset($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$cartKey = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';

if (!isset($_SESSION['cart'][$cartKey])) {
    header("Location: cart.php");
    exit;
}

if ($action === 'increase') {
    $_SESSION['cart'][$cartKey]['quantity']++;
}

if ($action === 'decrease') {
    $_SESSION['cart'][$cartKey]['quantity']--;

    if ($_SESSION['cart'][$cartKey]['quantity'] <= 0) {
        unset($_SESSION['cart'][$cartKey]);
    }
}

if ($action === 'remove') {
    unset($_SESSION['cart'][$cartKey]);
}

header("Location: cart.php");
exit;