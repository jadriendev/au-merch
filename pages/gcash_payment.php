
<?php
session_start();
require_once "../connection/config.php";

if (empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$orderCode = trim($_GET['order'] ?? '');
$token = $_SESSION['gcash_demo_tokens'][$orderCode] ?? '';

if ($orderCode === '' || $token === '') {
    header("Location: checkout.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT order_id, total, status, payment
    FROM tbl_orders
    WHERE order_code = ? AND user_id = ?
    LIMIT 1
");

$userId = (int) $_SESSION['user_id'];
$stmt->bind_param("si", $orderCode, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$order ||
    $order['payment'] !== 'gcash' ||
    strtolower($order['status']) !== 'placed'
) {
    header("Location: buy-success.php?order=" . urlencode($orderCode));
    exit;
}

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$scanUrl = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : 'http'
) . '://' . $_SERVER['HTTP_HOST']
    . $basePath
    . '/verify_gcash.php?token='
    . urlencode($token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GCash Demo Payment | AU Merch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900">GCash Demo Payment</h1>
        <p class="mt-2 text-sm text-slate-500">
            AU Merch order: <?= htmlspecialchars($orderCode) ?>
        </p>

        <p class="mt-5 text-slate-600">Demo amount</p>
        <p class="text-3xl font-bold text-blue-700">
            ₱<?= number_format((float) $order['total'], 2) ?>
        </p>

        <div class="mt-6 flex justify-center">
            <div id="qrcode" class="p-3 border rounded-xl"></div>
        </div>

        <p class="mt-4 text-sm text-slate-600">
            Scan this QR code using your phone to open the demo confirmation page.
        </p>

        <div class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
            DEMO ONLY. This is not a real GCash payment and no money will be transferred.
        </div>

        <a href="buy-success.php?order=<?= urlencode($orderCode) ?>"
           class="mt-6 inline-block text-blue-700 underline">
            Return to order tracking
        </a>
    </main>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: <?= json_encode($scanUrl) ?>,
            width: 220,
            height: 220,
            correctLevel: QRCode.CorrectLevel.M
        });
    </script>
</body>
</html>
