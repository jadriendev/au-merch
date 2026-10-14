<?php
session_start();

require_once "../connection/config.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function redirectCheckout(string $message): void
{
    $_SESSION['checkout_error'] = $message;
    header("Location: checkout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

if (empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: ../pages/cart");
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal = trim($_POST['postal'] ?? '');
$payment = $_POST['payment'] ?? '';

$allowedPayments = [
    'gcash',
    'paymongo',
    'cod',
    'bank_transfer'
];

if (
    $fullname === '' ||
    $phone === '' ||
    $email === '' ||
    $address === '' ||
    $city === '' ||
    $province === '' ||
    $postal === '' ||
    !in_array($payment, $allowedPayments, true)
) {
    redirectCheckout("Please complete all checkout fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectCheckout("Please enter a valid email address.");
}

if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $phone)) {
    redirectCheckout("Please enter a valid Philippine mobile number.");
}

if (!preg_match('/^\d{4}$/', $postal)) {
    redirectCheckout("Please enter a valid 4-digit postal code.");
}

$userId = (int) $_SESSION['user_id'];
$cart = $_SESSION['cart'];
$shippingFee = 60.00;

try {
    $conn->begin_transaction();

    $subtotal = 0.00;
    $orderItems = [];

    /*
     * Validate products and lock their stock rows
     * until the transaction finishes.
     */
    $productStmt = $conn->prepare("
        SELECT product_id, product_name, price, stock, status
        FROM tbl_products
        WHERE product_id = ?
        FOR UPDATE
    ");

    foreach ($cart as $cartKey => $item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            throw new Exception("Invalid product or quantity in your cart.");
        }

        $productStmt->bind_param("i", $productId);
        $productStmt->execute();

        $result = $productStmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            throw new Exception("A product in your cart no longer exists.");
        }

        $stock = (int) $product['stock'];

        if ($quantity > $stock) {
            throw new Exception(
                $product['product_name'] .
                " only has " . $stock . " item(s) available."
            );
        }

        if (
    isset($product['status']) &&
    strtolower(trim((string) $product['status'])) !== 'available'
) {
    throw new Exception(
        $product['product_name'] . " is no longer available."
    );
}

        $unitPrice = (float) $product['price'];
        $itemSubtotal = round($unitPrice * $quantity, 2);

        $subtotal = round($subtotal + $itemSubtotal, 2);

        $orderItems[] = [
            'product_id' => $productId,
            'product_name' => $product['product_name'],
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'size' => (string) ($item['size'] ?? ''),
            'color' => (string) ($item['color'] ?? ''),
            'subtotal' => $itemSubtotal
        ];
    }

    $productStmt->close();

    if (empty($orderItems)) {
        throw new Exception("Your cart does not contain any valid products.");
    }

    $total = round($subtotal + $shippingFee, 2);

    /*
     * Generate a unique order code.
     */
    $orderCode = '';

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $orderCode = 'AU-' . date('Y') . '-' .
            strtoupper(bin2hex(random_bytes(4)));

        $checkStmt = $conn->prepare("
            SELECT order_id
            FROM tbl_orders
            WHERE order_code = ?
            LIMIT 1
        ");

        $checkStmt->bind_param("s", $orderCode);
        $checkStmt->execute();

        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if (!$exists) {
            break;
        }

        $orderCode = '';
    }

    if ($orderCode === '') {
        throw new Exception("Could not generate an order reference. Please try again.");
    }

    /*
     * Save the main order.
     */
    $orderStmt = $conn->prepare("
        INSERT INTO tbl_orders (
            order_code,
            user_id,
            fullname,
            email,
            contact,
            address,
            city,
            province,
            postal,
            payment,
            subtotal,
            shipping_fee,
            total,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'placed')
    ");

    $orderStmt->bind_param(
        "sissssssssddd",
        $orderCode,
        $userId,
        $fullname,
        $email,
        $phone,
        $address,
        $city,
        $province,
        $postal,
        $payment,
        $subtotal,
        $shippingFee,
        $total
    );

    $orderStmt->execute();
    $orderId = $conn->insert_id;
    $orderStmt->close();

    $historyStmt = $conn->prepare("
        INSERT INTO tbl_order_status_history (order_id, status, note, changed_by)
        VALUES (?, 'placed', 'Order placed', ?)
    ");
    $historyStmt->bind_param("ii", $orderId, $userId);
    $historyStmt->execute();
    $historyStmt->close();

    /*
     * Save every item and deduct its stock.
     */
    $itemStmt = $conn->prepare("
        INSERT INTO tbl_order_items (
            order_id,
            product_id,
            product_name,
            unit_price,
            quantity,
            size,
            color,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stockStmt = $conn->prepare("
        UPDATE tbl_products
        SET stock = stock - ?
        WHERE product_id = ?
          AND stock >= ?
    ");

    foreach ($orderItems as $item) {
        $productId = $item['product_id'];
        $productName = $item['product_name'];
        $unitPrice = $item['unit_price'];
        $quantity = $item['quantity'];
        $size = $item['size'];
        $color = $item['color'];
        $itemSubtotal = $item['subtotal'];

        $itemStmt->bind_param(
            "iisdissd",
            $orderId,
            $productId,
            $productName,
            $unitPrice,
            $quantity,
            $size,
            $color,
            $itemSubtotal
        );

        $itemStmt->execute();

        $stockStmt->bind_param(
            "iii",
            $quantity,
            $productId,
            $quantity
        );

        $stockStmt->execute();

        if ($stockStmt->affected_rows !== 1) {
            throw new Exception(
                "Stock changed while processing your order. Please try again."
            );
        }
    }

    $itemStmt->close();
    $stockStmt->close();

    /*
     * Commit only when all order records and stock updates succeed.
     */
    $conn->commit();

    unset($_SESSION['cart']);

    unset($_SESSION['last_order']);
    $_SESSION['last_order_code'] = $orderCode;

    header("Location: buy-success.php?order=" . urlencode($orderCode));
    exit;

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
    }

    die(
        "Checkout Error: " .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}