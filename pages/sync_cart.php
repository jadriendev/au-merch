<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);
    exit();
}

$cart = json_decode(file_get_contents("php://input"), true);

if (!is_array($cart)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid cart data."
    ]);
    exit();
}

$_SESSION["cart"] = [];

foreach ($cart as $cartKey => $item) {

    $productId = (int)($item["product_id"] ?? 0);
    $quantity = (int)($item["quantity"] ?? 0);
    $size = $item["size"] ?? "";
    $color = $item["color"] ?? "";

    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }

    $_SESSION["cart"][$cartKey] = [
        "product_id" => $productId,
        "quantity" => $quantity,
        "size" => $size,
        "color" => $color
    ];
}

$cartCount = 0;

foreach ($_SESSION["cart"] as $item) {
    $cartCount += (int)$item["quantity"];
}

echo json_encode([
    "success" => true,
    "cart_count" => $cartCount
]);