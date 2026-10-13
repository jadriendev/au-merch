<?php
header("Content-Type: application/json; charset=UTF-8");

$route = $_GET["route"] ?? "";
$method = $_SERVER["REQUEST_METHOD"];

switch ($route) {
    case "products":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed."
            ]);
            exit();
        }

        require __DIR__ . "/products.php";
        exit();

    case "cart":
        if ($method !== "GET") {
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed."
            ]);
            exit();
        }

        require __DIR__ . "/cart.php";
        exit();

    case "cart-add":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed."
            ]);
            exit();
        }

        require __DIR__ . "/cart_add.php";
        exit();

    case "cart-update":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed."
            ]);
            exit();
        }

        require __DIR__ . "/cart_update.php";
        exit();

    case "cart-clear":
        if ($method !== "POST") {
            http_response_code(405);
            echo json_encode([
                "success" => false,
                "message" => "Method not allowed."
            ]);
            exit();
        }

        require __DIR__ . "/cart_clear.php";
        exit();

    default:
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "API route not found."
        ]);
        exit();
}