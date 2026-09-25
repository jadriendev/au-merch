<?php
session_start();

require_once "../connection/config.php";

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

foreach ($_SESSION['cart'] as $item) {

    $productId = (int)($item['product_id'] ?? 0);
    $quantity = (int)($item['quantity'] ?? 0);

    if ($productId <= 0 || $quantity <= 0) {
        header("Location: cart.php");
        exit;
    }

    $stmt = $conn->prepare("
        SELECT product_name, stock
        FROM tbl_products
        WHERE product_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $productId);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        header("Location: cart.php");
        exit;
    }

    $stock = (int)$product['stock'];

    if ($quantity > $stock) {
        $_SESSION['stock_error'] =
            $product['product_name'] . " only has " . $stock . " item(s) available.";

        header("Location: checkout.php");
        exit;
    }
}

foreach ($_SESSION['cart'] as $item) {

    $productId = (int)$item['product_id'];
    $quantity = (int)$item['quantity'];

    $stmt = $conn->prepare("
        UPDATE tbl_products
        SET stock = stock - ?
        WHERE product_id = ?
        AND stock >= ?
    ");

    $stmt->bind_param("iii", $quantity, $productId, $quantity);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $_SESSION['stock_error'] =
            "Some products are no longer available in the requested quantity.";

        header("Location: checkout.php");
        exit;
    }
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

header("Location: buy-success");
exit;