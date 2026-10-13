
<?php

session_start();

require_once __DIR__ . "/../connection/config.php";

header("Content-Type: application/json; charset=UTF-8");

$cart = $_SESSION["cart"] ?? [];

$cartProducts = [];
$subtotal = 0;
$totalItems = 0;

foreach ($cart as $cartKey => $item) {
    $productId = (int)($item["product_id"] ?? 0);
    $quantity = (int)($item["quantity"] ?? 0);

    if ($productId <= 0 || $quantity <= 0) {
        unset($_SESSION["cart"][$cartKey]);
        continue;
    }

    $stmt = $conn->prepare("
        SELECT product_id, product_name, variation, price, stock, image, status
        FROM tbl_products
        WHERE product_id = ?
    ");

    $stmt->bind_param("i", $productId);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        unset($_SESSION["cart"][$cartKey]);
        continue;
    }

    $product["quantity"] = $quantity;
    $product["size"] = $item["size"] ?? "";
    $product["color"] = $item["color"] ?? "";
    $product["cart_key"] = $cartKey;

    $cartProducts[] = $product;

    $subtotal += (float)$product["price"] * $quantity;
    $totalItems += $quantity;
}

$shipping = !empty($cartProducts) ? 60 : 0;
$total = $subtotal + $shipping;

echo json_encode([
    "success" => true,
    "data" => [
        "items" => $cartProducts,
        "total_items" => $totalItems,
        "subtotal" => $subtotal,
        "shipping" => $shipping,
        "total" => $total
    ]
]);

exit();
