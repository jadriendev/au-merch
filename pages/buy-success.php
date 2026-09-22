<?php
session_start();

if (empty($_SESSION['last_order'])) {
    header("Location: cart.php");
    exit;
}

$order = $_SESSION['last_order'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery | AU Merch</title>
    <link rel="shortcut icon" href="../images/Arellano_University_New_Logo.png" type="image/x-icon">
</head>

<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fa fa-check"></i>
        </div>

        <h1>Purchase Successful!</h1>

        <p>
            Thank you, <?= htmlspecialchars($order['fullname']) ?>!
        </p>

        <p>
            Your order has been successfully placed.
        </p>

        <div class="order-details">
            <div>
                <span>Payment Method</span>
                <strong><?= strtoupper(htmlspecialchars($order['payment'])) ?></strong>
            </div>

            <div>
                <span>Delivery Address</span>
                <strong>
                    <?= htmlspecialchars($order['address']) ?>,
                    <?= htmlspecialchars($order['city']) ?>,
                    <?= htmlspecialchars($order['province']) ?>
                    <?= htmlspecialchars($order['postal']) ?>
                </strong>
            </div>
        </div>

        <a href="../pages/home.php">
            Continue Shopping
        </a>
    </div>
</body>
</html>