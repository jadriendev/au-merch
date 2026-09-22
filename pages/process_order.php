<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal = trim($_POST['postal'] ?? '');
$payment = $_POST['payment'] ?? '';

if (
    empty($fullname) ||
    empty($phone) ||
    empty($email) ||
    empty($address) ||
    empty($city) ||
    empty($province) ||
    empty($postal) ||
    empty($payment)
) {
    header("Location: checkout.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: checkout.php");
    exit;
}

$_SESSION['last_order'] = [
    'fullname' => $fullname,
    'phone' => $phone,
    'email' => $email,
    'address' => $address,
    'city' => $city,
    'province' => $province,
    'postal' => $postal,
    'payment' => $payment
];

unset($_SESSION['cart']);

header("Location: buy-success.php");
exit;