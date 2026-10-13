
<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit();
}

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);

    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    $input = $_POST;
}

$cartKey = $input["cart_key"] ?? "";
$action = $input["action"] ?? "";

if (
    !is_string($cartKey) ||
    $cartKey === "" ||
    !isset($_SESSION["cart"][$cartKey])
) {
    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Cart item not found."
    ]);

    exit();
}

if (!in_array($action, ["increase", "decrease", "remove"], true)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid cart action."
    ]);

    exit();
}

if ($action === "increase") {
    $_SESSION["cart"][$cartKey]["quantity"]++;
}

if ($action === "decrease") {
    $_SESSION["cart"][$cartKey]["quantity"]--;

    if ($_SESSION["cart"][$cartKey]["quantity"] <= 0) {
        unset($_SESSION["cart"][$cartKey]);
    }
}

if ($action === "remove") {
    unset($_SESSION["cart"][$cartKey]);
}

$totalItems = 0;

foreach ($_SESSION["cart"] ?? [] as $item) {
    $totalItems += (int)($item["quantity"] ?? 0);
}

echo json_encode([
    "success" => true,
    "message" => "Cart updated successfully.",
    "cart_count" => $totalItems,
    "cart" => $_SESSION["cart"] ?? []
]);

exit();
