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
$product_id = intval($_POST["product_id"]);

$sql = "SELECT * FROM tbl_products WHERE product_id = ? AND status = 'Available'";
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

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if (isset($_SESSION["cart"][$product_id])) {
    $_SESSION["cart"][$product_id]["quantity"]++;
} else {
    $_SESSION["cart"][$product_id] = [
        "quantity" => 1
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