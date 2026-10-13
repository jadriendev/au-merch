
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

unset($_SESSION["cart"]);

echo json_encode([
    "success" => true,
    "message" => "Cart cleared successfully.",
    "cart_count" => 0
]);

exit();
