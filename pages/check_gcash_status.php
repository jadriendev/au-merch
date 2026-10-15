
<?php
session_start();
require_once "../connection/config.php";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$orderCode = trim($_GET['order'] ?? '');
$userId = (int) $_SESSION['user_id'];

if ($orderCode === '') {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
    SELECT status, payment
    FROM tbl_orders
    WHERE order_code = ? AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("si", $orderCode, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => $order !== null,
    'paid' => $order !== null
        && strtolower($order['status']) === 'paid'
        && $order['payment'] === 'gcash'
]);
