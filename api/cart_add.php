<?php

session_start();

require_once "../connection/config.php";

header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message, $extra = [], $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));

    exit();
}

if (!isset($_SESSION["user_id"])) {
    respond(false, "Please login first.", [], 401);
}

$productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);
$size = trim($_POST["size"] ?? "");
$color = trim($_POST["color"] ?? "");

if (!$productId || $productId <= 0) {
    respond(false, "Invalid product.", [], 400);
}

if (!$quantity || $quantity <= 0) {
    respond(false, "Please enter a valid quantity.", [], 400);
}

if ($size === "" || $color === "") {
    respond(false, "Please select a size and color.", [], 400);
}

$stmt = $conn->prepare(
    "SELECT product_id, product_name, price, color, stock
     FROM tbl_products
     WHERE product_id = ?"
);

if (!$stmt) {
    respond(false, "Unable to process the request.", [], 500);
}

$stmt->bind_param("i", $productId);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    respond(false, "Product not found.", [], 404);
}

$stock = (int) $product["stock"];

if ($stock <= 0) {
    respond(false, "This product is out of stock.", [], 400);
}

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$currentProductQuantity = 0;

foreach ($_SESSION["cart"] as $item) {
    if ((int)($item["product_id"] ?? 0) === $productId) {
        $currentProductQuantity += (int)($item["quantity"] ?? 0);
    }
}

if (($currentProductQuantity + $quantity) > $stock) {
    $remainingStock = max(0, $stock - $currentProductQuantity);

    respond(
        false,
        "Only " . $remainingStock . " more item(s) available for this product.",
        [
            "stock" => $stock,
            "cart_quantity" => $currentProductQuantity,
            "remaining_stock" => $remainingStock
        ],
        400
    );
}

$cartKey = $productId . "_" . $size . "_" . $color;

if (isset($_SESSION["cart"][$cartKey])) {
    $_SESSION["cart"][$cartKey]["quantity"] += $quantity;
} else {
    $_SESSION["cart"][$cartKey] = [
        "product_id" => $productId,
        "quantity" => $quantity,
        "size" => $size,
        "color" => $color
    ];
}

$cartCount = 0;

foreach ($_SESSION["cart"] as $item) {
    $cartCount += (int)($item["quantity"] ?? 0);
}

respond(true, $product["product_name"] . " has been added to your cart.", [
    "cart_count" => $cartCount,
    "cart_key" => $cartKey,
    "cart_item" => $_SESSION["cart"][$cartKey],
    "user_id" => $_SESSION["user_id"]
]);