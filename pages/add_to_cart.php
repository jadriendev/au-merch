<?php

include_once "../connection/config.php";
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);
    exit();
}

if (!isset($_POST["product_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid product."
    ]);
    exit();
}

$user_id = $_SESSION["user_id"];
$product_id = $_POST["product_id"] ?? "";
$quantity = max(1, intval($_POST["quantity"] ?? 1));
$size = $_POST["size"] ?? "";
$color = $_POST["color"] ?? "";

if (!$size || !$color) {
    echo json_encode([
    "success" => false,
    "message" => "Received product_id: " . $product_id
    ]);
    exit();
}

$sql = "SELECT * FROM tbl_products WHERE product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);
    exit();
}

if ($product["stock"] <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "This product is out of stock."
    ]);
    exit();
}

if ($quantity > $product["stock"]) {
    $quantity = $product["stock"];
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$cartKey = $product_id . "_" . $size . "_" . $color;

if (isset($_SESSION["cart"][$cartKey])) {
    $newQuantity = $_SESSION["cart"][$cartKey]["quantity"] + $quantity;

    $_SESSION["cart"][$cartKey]["quantity"] = min(
        $newQuantity,
        $product["stock"]
    );
} else {
    $_SESSION["cart"][$cartKey] = [
        "product_id" => $product_id,
        "quantity" => $quantity,
        "size" => $size,
        "color" => $color
    ];
}

$cartCount = 0;

foreach ($_SESSION["cart"] as $item) {
    $cartCount += $item["quantity"];
}

echo json_encode([
    "success" => true,
    "message" => htmlspecialchars($product["product_name"]) . " has been added to your cart.",
    "cart_count" => $cartCount
]);