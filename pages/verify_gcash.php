
<?php
session_start();
require_once "../connection/config.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = false;
$orderCode = '';

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(400);
    $error = 'Invalid payment QR token.';
} else {
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare("
        SELECT t.token_id, t.order_id, t.expires_at, t.used_at,
       o.order_code, o.user_id, o.status, o.payment
        FROM tbl_gcash_demo_tokens t
        INNER JOIN tbl_orders o ON o.order_id = t.order_id
        WHERE t.token_hash = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$record) {
        http_response_code(404);
        $error = 'Payment QR was not found.';
    } else {
        $orderCode = $record['order_code'];

        if ($record['payment'] !== 'gcash') {
            $error = 'This order is not a GCash demo order.';
        } elseif ($record['used_at'] !== null) {
            if (strtolower($record['status']) === 'paid') {
                $success = true;
            } else {
                $error = 'This payment token has already been used.';
            }
        } elseif (strtotime($record['expires_at']) <= time()) {
            $error = 'This QR code has expired. Return to checkout for assistance.';
        } elseif (strtolower($record['status']) !== 'placed') {
            $error = 'This order is no longer awaiting payment.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '' && !$success) {
    if (!hash_equals($_SESSION['gcash_verify_csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        $error = 'Invalid confirmation request. Reload the page and try again.';
    } else {
        try {
            $conn->begin_transaction();

            
$lock = $conn->prepare("
    SELECT
        t.token_id,
        t.order_id,
        t.expires_at,
        t.used_at,
        o.order_code,
        o.user_id,
        o.status,
        o.payment
    FROM tbl_gcash_demo_tokens t
    INNER JOIN tbl_orders o ON o.order_id = t.order_id
    WHERE t.token_hash = ?
    FOR UPDATE
");

            $lock->bind_param("s", $tokenHash);
            $lock->execute();
            $current = $lock->get_result()->fetch_assoc();
            $lock->close();

            if (
                !$current ||
                $current['used_at'] !== null ||
                strtotime($current['expires_at']) <= time() ||
                $current['payment'] !== 'gcash' ||
                strtolower($current['status']) !== 'placed'
            ) {
                throw new RuntimeException(
                    'This QR code has expired or has already been processed.'
                );
            }

            $orderId = (int) $current['order_id'];

            $updateOrder = $conn->prepare("
                UPDATE tbl_orders
                SET status = 'paid'
                WHERE order_id = ? AND status = 'placed'
            ");
            $updateOrder->bind_param("i", $orderId);
            $updateOrder->execute();

            if ($updateOrder->affected_rows !== 1) {
                $updateOrder->close();
                throw new RuntimeException('The order could not be marked as paid.');
            }
            $updateOrder->close();

            $useToken = $conn->prepare("
                UPDATE tbl_gcash_demo_tokens
                SET used_at = NOW()
                WHERE token_id = ? AND used_at IS NULL
            ");
            $tokenId = (int) $current['token_id'];
            $useToken->bind_param("i", $tokenId);
            $useToken->execute();

            if ($useToken->affected_rows !== 1) {
                $useToken->close();
                throw new RuntimeException('The payment token has already been used.');
            }
            $useToken->close();

            $changedBy = (int) $current['user_id'];
            $note = 'GCash demo payment simulated; no real payment processed.';

            $history = $conn->prepare("
                INSERT INTO tbl_order_status_history
                    (order_id, status, note, changed_by)
                VALUES (?, 'paid', ?, ?)
            ");
            $history->bind_param("isi", $orderId, $note, $changedBy);
            $history->execute();
            $history->close();

            $conn->commit();
            $success = true;
            $orderCode = $current['order_code'];

        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
            }
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to process the demo payment. Please try again.';
        }
    }
}

if (empty($_SESSION['gcash_verify_csrf'])) {
    $_SESSION['gcash_verify_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['gcash_verify_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm GCash Demo | AU Merch</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white rounded-2xl shadow-lg p-6">
        <h1 class="text-2xl font-bold text-slate-900">GCash Demo Confirmation</h1>
        <p class="mt-2 text-sm text-slate-500">
            This is a school-project simulation. No real payment will occur.
        </p>

        <?php if ($success): ?>
            <div class="mt-6 rounded-lg bg-green-50 p-4 text-green-800">
                <h2 class="font-bold">Demo payment confirmed</h2>
                <p class="mt-1 text-sm">
                    Order <?= htmlspecialchars($orderCode) ?> is now marked as paid.
                </p>
            </div>
            <a class="mt-6 block rounded-lg bg-blue-700 px-4 py-3 text-center font-semibold text-white"
               href="buy-success.php?order=<?= urlencode($orderCode) ?>">
                View Order Tracking
            </a>
        <?php elseif ($error !== ''): ?>
            <div class="mt-6 rounded-lg bg-red-50 p-4 text-red-700">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div class="mt-6 rounded-lg bg-amber-50 p-4 text-amber-800">
                <strong>DEMO ONLY</strong>
                <p class="mt-1 text-sm">
                    Confirming will mark this order as paid in the AU Merch demo database.
                </p>
            </div>
            <form method="POST" class="mt-6">
                <input type="hidden" name="token"
                       value="<?= htmlspecialchars($token) ?>">
                <input type="hidden" name="csrf"
                       value="<?= htmlspecialchars($csrf) ?>">
                <button type="submit"
                        class="w-full rounded-lg bg-blue-700 px-4 py-3 font-semibold text-white">
                    Simulate Successful Payment
                </button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
