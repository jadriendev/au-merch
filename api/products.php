<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../connection/config.php";

$sql = "SELECT product_id, product_name, variation, price, color, stock, image
        FROM tbl_products
        WHERE status = 'Available'";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch products."
    ]);

    exit();
}

$products = [];

while ($product = $result->fetch_assoc()) {
    $products[] = $product;
}

echo json_encode([
    "success" => true,
    "data" => $products
]);

$conn->close();