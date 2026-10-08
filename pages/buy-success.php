<?php
session_start();

require_once "../connection/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$conn->set_charset('utf8mb4');

$user_id = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$status_steps = [
    'placed',
    'paid',
    'shipped',
    'received',
    'delivered'
];

$status_labels = [
    'placed' => 'Order Placed',
    'paid' => 'Order Paid',
    'shipped' => 'Order Shipped Out',
    'received' => 'Order Received',
    'delivered' => 'Order Delivered'
];

$payment_labels = [
    'gcash' => 'GCash',
    'paymongo' => 'PayMongo',
    'cod' => 'Cash on Delivery',
    'bank_transfer' => 'Bank Transfer'
];

function formatStatusDate($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = is_numeric($date) ? (int)$date : strtotime((string)$date);

    if ($timestamp === false) {
        return htmlspecialchars((string)$date, ENT_QUOTES, 'UTF-8');
    }

    return date('M j, Y g:i A', $timestamp);
}

function peso($amount)
{
    return '₱' . number_format((float)$amount, 2);
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function addColumnIfMissing(mysqli $conn, string $table, string $column, string $definition): void
{
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");

    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensureOrderTables(mysqli $conn): void
{
    if (!empty($_SESSION['order_tables_ready'])) {
        return;
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS tbl_orders (
            order_id INT NOT NULL AUTO_INCREMENT,
            order_code VARCHAR(20) DEFAULT NULL,
            user_id INT NOT NULL,
            fullname VARCHAR(120) NOT NULL,
            email VARCHAR(255) NOT NULL,
            contact VARCHAR(30) NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            province VARCHAR(100) NOT NULL,
            postal VARCHAR(20) NOT NULL,
            payment VARCHAR(30) NOT NULL DEFAULT '',
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('placed','paid','shipped','received','delivered') NOT NULL DEFAULT 'placed',
            confirmed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (order_id),
            UNIQUE KEY uq_order_code (order_code),
            KEY idx_orders_user (user_id),
            KEY idx_orders_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS tbl_order_items (
            item_id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(55) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            size VARCHAR(20) NOT NULL DEFAULT '',
            color VARCHAR(50) NOT NULL DEFAULT '',
            subtotal DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (item_id),
            KEY idx_items_order (order_id),
            KEY idx_items_product (product_id),
            CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES tbl_orders (order_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS tbl_order_status_history (
            history_id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            status ENUM('placed','paid','shipped','received','delivered') NOT NULL,
            note VARCHAR(255) DEFAULT NULL,
            changed_by INT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (history_id),
            KEY idx_hist_order (order_id),
            CONSTRAINT fk_hist_order FOREIGN KEY (order_id) REFERENCES tbl_orders (order_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    addColumnIfMissing($conn, 'tbl_orders', 'payment', "VARCHAR(30) NOT NULL DEFAULT '' AFTER postal");
    addColumnIfMissing($conn, 'tbl_order_items', 'size', "VARCHAR(20) NOT NULL DEFAULT '' AFTER quantity");
    addColumnIfMissing($conn, 'tbl_order_items', 'color', "VARCHAR(50) NOT NULL DEFAULT '' AFTER size");

    $_SESSION['order_tables_ready'] = true;
}

function savePendingOrder(mysqli $conn, int $userId, array $pending): string
{
    $fullname = trim((string)($pending['fullname'] ?? ''));
    $email = trim((string)($pending['email'] ?? ''));
    $contact = trim((string)($pending['contact'] ?? ($pending['phone'] ?? '')));
    $address = trim((string)($pending['address'] ?? ''));
    $city = trim((string)($pending['city'] ?? ''));
    $province = trim((string)($pending['province'] ?? ''));
    $postal = trim((string)($pending['postal'] ?? ''));
    $payment = trim((string)($pending['payment'] ?? ''));

    $productStmt = $conn->prepare("SELECT product_name, price FROM tbl_products WHERE product_id = ?");

    $lines = [];
    $subtotal = 0.0;

    foreach (($pending['items'] ?? []) as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        $productStmt->bind_param('i', $productId);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();

        if (!$product) {
            continue;
        }

        $price = round((float)$product['price'], 2);
        $lineTotal = round($price * $quantity, 2);
        $subtotal += $lineTotal;

        $lines[] = [
            'product_id' => $productId,
            'product_name' => (string)$product['product_name'],
            'price' => $price,
            'quantity' => $quantity,
            'size' => (string)($item['size'] ?? ''),
            'color' => (string)($item['color'] ?? ''),
            'subtotal' => $lineTotal
        ];
    }

    $shippingFee = $lines ? (float)($pending['shipping_fee'] ?? 60) : 0.0;
    $total = round($subtotal + $shippingFee, 2);

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            INSERT INTO tbl_orders
                (user_id, fullname, email, contact, address, city, province, postal, payment, subtotal, shipping_fee, total, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'placed')
        ");
        $stmt->bind_param(
            'issssssssddd',
            $userId, $fullname, $email, $contact, $address, $city, $province, $postal, $payment,
            $subtotal, $shippingFee, $total
        );
        $stmt->execute();

        $orderId = (int)$conn->insert_id;
        $code = 'AU-' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("UPDATE tbl_orders SET order_code = ? WHERE order_id = ?");
        $stmt->bind_param('si', $code, $orderId);
        $stmt->execute();

        $itemStmt = $conn->prepare("
            INSERT INTO tbl_order_items
                (order_id, product_id, product_name, unit_price, quantity, size, color, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($lines as $line) {
            $itemStmt->bind_param(
                'iisdissd',
                $orderId, $line['product_id'], $line['product_name'], $line['price'],
                $line['quantity'], $line['size'], $line['color'], $line['subtotal']
            );
            $itemStmt->execute();
        }

        $stmt = $conn->prepare("
            INSERT INTO tbl_order_status_history (order_id, status, note, changed_by)
            VALUES (?, 'placed', 'Order placed', ?)
        ");
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();

        $conn->commit();

        return $code;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function loadOrder(mysqli $conn, array $steps, string $code, int $userId, bool $isStaff): ?array
{
    if ($isStaff) {
        $stmt = $conn->prepare("SELECT * FROM tbl_orders WHERE order_code = ?");
        $stmt->bind_param('s', $code);
    } else {
        $stmt = $conn->prepare("SELECT * FROM tbl_orders WHERE order_code = ? AND user_id = ?");
        $stmt->bind_param('si', $code, $userId);
    }

    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT product_name, unit_price AS price, quantity, size, color, subtotal
        FROM tbl_order_items
        WHERE order_id = ?
        ORDER BY item_id
    ");
    $stmt->bind_param('i', $order['order_id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("
        SELECT status, MIN(created_at) AS at
        FROM tbl_order_status_history
        WHERE order_id = ?
        GROUP BY status
    ");
    $stmt->bind_param('i', $order['order_id']);
    $stmt->execute();

    $dates = array_fill_keys($steps, null);

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $dates[$row['status']] = $row['at'];
    }

    $order['status_dates'] = $dates;

    return $order;
}

function advanceOrder(mysqli $conn, array $steps, int $orderId, int $byUser, string $note): string
{
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT status FROM tbl_orders WHERE order_id = ? FOR UPDATE");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            throw new RuntimeException('Order not found.');
        }

        $index = array_search($row['status'], $steps, true);

        if ($index === false || $index >= count($steps) - 1) {
            throw new RuntimeException('This order is already at its final step.');
        }

        $next = $steps[$index + 1];

        $stmt = $conn->prepare("UPDATE tbl_orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param('si', $next, $orderId);
        $stmt->execute();

        $noteValue = $note !== '' ? mb_substr($note, 0, 255) : null;

        $stmt = $conn->prepare("
            INSERT INTO tbl_order_status_history (order_id, status, note, changed_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('issi', $orderId, $next, $noteValue, $byUser);
        $stmt->execute();

        $conn->commit();

        return $next;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

ensureOrderTables($conn);

$stmt = $conn->prepare("SELECT role FROM tbl_users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_row = $stmt->get_result()->fetch_assoc();

$is_staff = ($user_row['role'] ?? '') === 'inventory';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_code = trim((string)($_POST['order_id'] ?? ''));
    $action = (string)($_POST['action'] ?? '');

    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request.');
    }

    if ($action === 'confirm' && $post_code !== '') {
        $stmt = $conn->prepare("
            UPDATE tbl_orders
            SET confirmed_at = NOW()
            WHERE order_code = ? AND user_id = ? AND status = 'delivered' AND confirmed_at IS NULL
        ");
        $stmt->bind_param('si', $post_code, $user_id);
        $stmt->execute();
    }

    if ($action === 'advance' && $is_staff && $post_code !== '') {
        $stmt = $conn->prepare("SELECT order_id FROM tbl_orders WHERE order_code = ?");
        $stmt->bind_param('s', $post_code);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();

        try {
            if (!$target) {
                throw new RuntimeException('Order not found.');
            }

            $new_status = advanceOrder(
                $conn,
                $status_steps,
                (int)$target['order_id'],
                $user_id,
                trim((string)($_POST['note'] ?? ''))
            );

            $_SESSION['order_flash'] = ['ok', 'Order moved to "' . $status_labels[$new_status] . '".'];
        } catch (RuntimeException $e) {
            $_SESSION['order_flash'] = ['err', $e->getMessage()];
        }
    }

    header("Location: buy-success.php?order=" . urlencode($post_code));
    exit;
}

if (!empty($_SESSION['last_order']) && is_array($_SESSION['last_order'])) {
    try {
        $saved_code = savePendingOrder($conn, $user_id, $_SESSION['last_order']);
    } catch (Throwable $e) {
        error_log('buy-success: could not save order: ' . $e->getMessage());
        http_response_code(500);
        exit('We could not record your order. Please refresh this page to try again.');
    }

    unset($_SESSION['last_order']);
    $_SESSION['last_order_code'] = $saved_code;

    header("Location: buy-success.php?order=" . urlencode($saved_code));
    exit;
}

$cart = $_SESSION['cart'] ?? [];

$totalItems = 0;

foreach ($cart as $item) {
    $totalItems += (int)($item['quantity'] ?? 0);
}

$code = trim((string)($_GET['order'] ?? ($_SESSION['last_order_code'] ?? '')));

if ($code === '' && $is_staff) {
    $latest = $conn->query("SELECT order_code FROM tbl_orders ORDER BY order_id DESC LIMIT 1")->fetch_assoc();
    $code = (string)($latest['order_code'] ?? '');
}

$order = $code !== '' ? loadOrder($conn, $status_steps, $code, $user_id, $is_staff) : null;

if (!$order) {
    header("Location: cart.php");
    exit;
}

$order_id = $order['order_code'];
$order_status = (string)$order['status'];

if (!in_array($order_status, $status_steps, true)) {
    $order_status = 'placed';
}

$status_dates = $order['status_dates'];
$confirmed_at = $order['confirmed_at'];

$order_items = $order['items'];

$subtotal = (float)$order['subtotal'];
$shipping_fee = (float)$order['shipping_fee'];
$total = (float)$order['total'];

$fullname = $order['fullname'];
$email = $order['email'];
$contact = $order['contact'];

$address = $order['address'];
$city = $order['city'];
$province = $order['province'];
$postal = $order['postal'];

$payment_label = $payment_labels[$order['payment']] ?? ($order['payment'] !== '' ? $order['payment'] : '—');

$current_index = array_search($order_status, $status_steps, true);

if ($current_index === false) {
    $current_index = 0;
    $order_status = 'placed';
}

$next_status = $status_steps[$current_index + 1] ?? null;

$flash = $_SESSION['order_flash'] ?? null;
unset($_SESSION['order_flash']);

$recent_orders = [];

if ($is_staff) {
    $recent_orders = $conn
        ->query("SELECT order_code, fullname, status FROM tbl_orders ORDER BY order_id DESC LIMIT 10")
        ->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        rel="shortcut icon"
        href="https://www.auchiefslms.com/college/pluginfile.php/1/core_admin/logocompact/300x300/1784347206/au-logo-smaller.png"
        type="image/x-icon"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@100;200;300;400;500;600;700;800;900&family=Bebas+Neue&family=Inter:wght@100..900&family=Manrope:wght@200..800&family=Montserrat:wght@100..900&family=Open+Sans:wght@300..800&family=Playfair+Display:wght@400..900&family=Poppins:wght@100;200;300;400;500;600;700;800;900&family=Quattrocento:wght@400;700&family=Roboto+Mono:wght@100..700&family=Roboto:wght@100..900&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css"
        integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >

    <script src="https://cdn.tailwindcss.com"></script>

    <title>Track Your Order | AU Merch</title>

</head>

<body
    style="font-family: 'Roboto', sans-serif;"
    class="bg-gray-100 min-h-screen flex flex-col"
>

<header class="sticky top-0 z-50 w-full bg-white border-b border-gray-100">

    <nav
        class="
            relative
            flex items-center justify-between
            max-w-[1500px]
            mx-auto
            py-3
            px-4
            lg:px-4
            2xl:px-0
        "
    >

        <a
            href="../User/homepage"
            class="flex items-center gap-3"
        >

            <img
                class="w-14 object-contain"
                src="../images/Arellano_University_New_Logo.png"
                alt="Arellano University Logo"
            >

            <h1 class="hidden lg:block text-xl font-bold">

                <span class="block text-[#0e2f4f]">
                    AU Merch
                </span>

                <span class="block text-xs tracking-wide text-gray-500 font-semibold">
                    Official Merchandise Store
                </span>

            </h1>

        </a>

        <ul class="hidden lg:flex items-center gap-16">

            <li>
                <a
                    class="
                        transition-all
                        duration-300
                        block
                        text-[.95rem]
                        text-[#576578]
                        font-semibold
                        hover:text-blue-600
                    "
                    href="../User/homepage"
                >
                    Home
                </a>
            </li>

            <li>
                <a
                    class="
                        transition-all
                        duration-300
                        block
                        text-[.95rem]
                        text-[#576578]
                        font-semibold
                        hover:text-blue-600
                    "
                    href="../pages/products"
                >
                    Products
                </a>
            </li>

            <li>
                <a
                    class="
                        transition-all
                        duration-300
                        block
                        text-[.95rem]
                        text-[#576578]
                        font-semibold
                        hover:text-blue-600
                    "
                    href="../pages/new_arrivals"
                >
                    New Arrivals
                </a>
            </li>

            <li>
                <a
                    class="
                        transition-all
                        duration-300
                        block
                        text-[.95rem]
                        text-[#576578]
                        font-semibold
                        hover:text-blue-600
                    "
                    href="../pages/whats_hot"
                >
                    What's Hot
                </a>
            </li>

        </ul>

        <div class="hidden lg:flex items-center md:gap-4 xl:gap-6 2xl:gap-10">

            <div class="relative">

                <i
                    class="
                        absolute
                        top-1/2
                        -translate-y-1/2
                        left-3
                        text-gray-500
                        text-[.90rem]
                        fa
                        fa-magnifying-glass
                    "
                ></i>

                <input
                    class="
                        bg-gray-300/30
                        h-10
                        py-2
                        pl-10
                        pr-4
                        w-full
                        rounded-full
                        text-[.80rem]
                        outline-none
                        focus:ring-0
                    "
                    type="text"
                    name="search"
                    placeholder="Search for products..."
                >

            </div>

            <div class="flex items-center gap-4">

                <a
                    href="../pages/cart"
                    class="relative"
                >

                    <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                        shopping_cart
                    </span>

                    <span
                        class="
                            cart-count
                            absolute
                            -top-2
                            -right-2
                            bg-blue-800
                            text-white
                            text-[.65rem]
                            font-bold
                            w-4
                            h-4
                            rounded-full
                            flex
                            items-center
                            justify-center
                        "
                    >
                        <?= $totalItems ?>
                    </span>

                </a>

                <div class="relative">

                    <input
                        type="checkbox"
                        id="profileToggle"
                        class="hidden peer"
                    >

                    <label
                        for="profileToggle"
                        class="cursor-pointer block"
                    >

                        <span class="text-[1.8rem] text-[#255084] material-symbols-outlined">
                            person
                        </span>

                    </label>

                    <div
                        class="
                            absolute
                            right-0
                            top-10
                            w-48
                            bg-white
                            border
                            border-gray-200
                            rounded-lg
                            shadow-lg
                            overflow-hidden
                            invisible
                            opacity-0
                            translate-y-2
                            peer-checked:visible
                            peer-checked:opacity-100
                            peer-checked:translate-y-0
                            transition-all
                            duration-200
                            z-50
                        "
                    >

                        <a
                            href="../pages/account_settings"
                            class="
                                flex
                                items-center
                                gap-3
                                px-4
                                py-3
                                text-sm
                                text-gray-700
                                hover:bg-gray-100
                                transition
                            "
                        >

                            <span class="material-symbols-outlined text-[20px]">
                                settings
                            </span>

                            Account Settings

                        </a>

                        <a
                            href="../logout.php"
                            class="
                                flex
                                items-center
                                gap-3
                                px-4
                                py-3
                                text-sm
                                text-red-500
                                hover:bg-red-50
                                transition
                            "
                        >

                            <span class="material-symbols-outlined text-[20px]">
                                logout
                            </span>

                            Logout

                        </a>

                    </div>

                </div>

            </div>

        </div>

        <div class="lg:hidden flex items-center gap-4">

            <a
                href="../pages/cart"
                class="relative"
            >

                <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                    shopping_cart
                </span>

                <span
                    class="
                        cart-count
                        absolute
                        -top-2
                        -right-2
                        bg-blue-800
                        text-white
                        text-[.65rem]
                        font-bold
                        w-4
                        h-4
                        rounded-full
                        flex
                        items-center
                        justify-center
                    "
                >
                    <?= $totalItems ?>
                </span>

            </a>

            <button
                id="menuBtn"
                type="button"
                class="text-[#255084] text-[1.5rem]"
            >

                <i
                    id="menuIcon"
                    class="fa fa-bars"
                ></i>

            </button>

        </div>

        <div
            id="mobileMenu"
            class="
                lg:hidden
                absolute
                left-0
                top-full
                w-full
                bg-white
                overflow-hidden
                max-h-0
                opacity-0
                transition-all
                duration-300
                ease-in-out
            "
        >

            <ul class="flex flex-col px-6 py-4 gap-4">

                <li>
                    <a
                        href="../User/homepage"
                        class="
                            block
                            text-[.95rem]
                            text-[#576578]
                            font-semibold
                        "
                    >
                        Home
                    </a>
                </li>

                <li>
                    <a
                        href="../pages/products"
                        class="
                            block
                            text-[.95rem]
                            text-[#576578]
                            font-semibold
                        "
                    >
                        Products
                    </a>
                </li>

                <li>
                    <a
                        href="../pages/new_arrivals"
                        class="
                            block
                            text-[.95rem]
                            text-[#576578]
                            font-semibold
                        "
                    >
                        New Arrivals
                    </a>
                </li>

                <li>
                    <a
                        href="../pages/whats_hot"
                        class="
                            block
                            text-[.95rem]
                            text-[#576578]
                            font-semibold
                        "
                    >
                        What's Hot
                    </a>
                </li>

            </ul>

            <div class="px-6 pb-4">

                <div class="relative">

                    <i
                        class="
                            absolute
                            top-1/2
                            -translate-y-1/2
                            left-3
                            text-gray-500
                            text-[.90rem]
                            fa
                            fa-magnifying-glass
                        "
                    ></i>

                    <input
                        class="
                            bg-gray-300/30
                            h-10
                            py-2
                            pl-10
                            pr-4
                            w-full
                            rounded-full
                            text-[.80rem]
                            outline-none
                        "
                        type="text"
                        name="search"
                        placeholder="Search for products..."
                    >

                </div>

            </div>

            <div class="flex flex-col px-6 pb-5 gap-3">

                <a
                    href="../pages/account_settings"
                    class="
                        text-[.95rem]
                        text-[#576578]
                        font-semibold
                        hover:text-blue-600
                        transition-all
                        duration-300
                    "
                >
                    Account Settings
                </a>

                <a
                    href="../logout.php"
                    class="
                        text-[.95rem]
                        text-red-500
                        font-semibold
                        hover:text-red-600
                        transition-all
                        duration-300
                    "
                >
                    Logout
                </a>

            </div>

        </div>

    </nav>

</header>

<main class="flex-1">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-8">

            <a
                href="../pages/cart"
                class="
                    inline-flex
                    items-center
                    gap-2
                    group
                    text-sm
                    font-semibold
                    text-[#2563eb]
                "
            >

                <span
                    class="
                        material-symbols-outlined
                        text-[20px]
                        transition-all
                        duration-300
                        group-hover:-translate-x-1
                    "
                >
                    arrow_back
                </span>

                Back to My Cart

            </a>

        </div>

        <div class="mb-10">

            <h1 class="text-3xl sm:text-4xl font-bold text-[#0e2f4f]">
                Track Your Order
            </h1>

            <p class="mt-2 text-sm text-gray-500 max-w-xl leading-6">
                Stay updated on your order delivery. Check your current status
                and view the latest updates about your order.
            </p>

        </div>

        <?php if ($flash): ?>

            <p
                class="
                    mb-6
                    rounded-lg
                    px-4
                    py-3
                    text-sm
                    <?= $flash[0] === 'ok' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' ?>
                "
            >
                <?= e($flash[1]) ?>
            </p>

        <?php endif; ?>

        <section class="mb-12 bg-white rounded-xl p-6 sm:p-8">

            <div
                class="
                    flex
                    flex-col
                    sm:flex-row
                    sm:items-end
                    sm:justify-between
                    gap-5
                    pb-8
                    border-b
                    border-gray-100
                "
            >

                <div>

                    <p
                        class="
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-gray-500
                            mb-2
                        "
                    >
                        Order Tracking
                    </p>

                    <h2 class="text-2xl sm:text-3xl font-bold text-[#0e2f4f]">
                        Order #<?= e($order_id) ?>
                    </h2>

                </div>

                <div class="sm:text-right">

                    <p class="text-xs text-gray-500 mb-2">
                        Current Status
                    </p>

                    <span
                        class="
                            inline-flex
                            items-center
                            gap-2
                            text-sm
                            font-semibold
                            text-[#255084]
                        "
                    >

                        <span class="material-symbols-outlined text-[19px]">
                            local_shipping
                        </span>

                        <?= e($status_labels[$order_status]) ?>

                    </span>

                </div>

            </div>

            <div class="relative px-2 sm:px-6 pt-10">

                <div
                    class="
                        absolute
                        top-[60px]
                        left-[10%]
                        right-[10%]
                        h-0.5
                        bg-gray-200
                    "
                ></div>

                <div
                    class="
                        absolute
                        top-[60px]
                        left-[10%]
                        h-0.5
                        bg-[#2563eb]
                        transition-all
                        duration-500
                    "
                    style="
                        width:
                        <?= $current_index === 0
                            ? '0'
                            : (($current_index / (count($status_steps) - 1)) * 80)
                        ?>%;
                    "
                ></div>

                <div class="relative grid grid-cols-5 gap-1">

                    <?php foreach ($status_steps as $index => $step): ?>

                        <?php
                        $is_completed = $index < $current_index;
                        $is_current = $index === $current_index;
                        $is_active = $index <= $current_index;
                        ?>

                        <div class="flex flex-col items-center text-center min-w-0">

                            <div
                                class="
                                    relative
                                    z-10
                                    w-9
                                    h-9
                                    sm:w-10
                                    sm:h-10
                                    rounded-full
                                    flex
                                    items-center
                                    justify-center
                                    border-2
                                    transition-all
                                    duration-300
                                    <?= $is_active
                                        ? 'border-[#2563eb] bg-[#2563eb] text-white'
                                        : 'border-gray-300 bg-white text-gray-400'
                                    ?>
                                    <?= $is_current ? 'ring-4 ring-blue-50' : '' ?>
                                "
                            >

                                <?php if ($is_completed): ?>

                                    <i class="fa fa-check text-sm"></i>

                                <?php elseif ($step === 'placed'): ?>

                                    <span class="material-symbols-outlined text-[19px]">
                                        shopping_bag
                                    </span>

                                <?php elseif ($step === 'paid'): ?>

                                    <span class="material-symbols-outlined text-[19px]">
                                        payments
                                    </span>

                                <?php elseif ($step === 'shipped'): ?>

                                    <span class="material-symbols-outlined text-[19px]">
                                        local_shipping
                                    </span>

                                <?php elseif ($step === 'received'): ?>

                                    <span class="material-symbols-outlined text-[19px]">
                                        inventory_2
                                    </span>

                                <?php elseif ($step === 'delivered'): ?>

                                    <span class="material-symbols-outlined text-[19px]">
                                        home
                                    </span>

                                <?php endif; ?>

                            </div>

                            <p
                                class="
                                    mt-3
                                    text-[10px]
                                    sm:text-sm
                                    font-semibold
                                    leading-4
                                    <?= $is_active
                                        ? 'text-[#0e2f4f]'
                                        : 'text-gray-400'
                                    ?>
                                "
                            >
                                <?= e($status_labels[$step]) ?>
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-[9px]
                                    sm:text-xs
                                    text-gray-400
                                    leading-4
                                "
                            >
                                <?= !empty($status_dates[$step] ?? null)
                                    ? formatStatusDate($status_dates[$step])
                                    : '—'
                                ?>
                            </p>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>
        
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

            <div
                class="
                    border
                    border-[#d6e4f5]
                    rounded-xl
                    bg-white
                    p-5
                    sm:p-6
                "
            >

                <h2
                    class="
                        flex
                        items-center
                        gap-2
                        text-lg
                        font-bold
                        text-[#0e2f4f]
                        mb-6
                    "
                >

                    <span class="material-symbols-outlined text-xl">
                        inventory_2
                    </span>

                    Shipping Information

                </h2>

                <div class="space-y-5">

                    <div class="flex gap-3">

                        <span class="material-symbols-outlined text-[#255084] text-xl">
                            person
                        </span>

                        <div>

                            <p class="text-xs text-gray-500">
                                Recipient Name
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                <?= e($fullname) ?>
                            </p>

                        </div>

                    </div>

                    <div class="flex gap-3">

                        <span class="material-symbols-outlined text-[#255084] text-xl">
                            call
                        </span>

                        <div>

                            <p class="text-xs text-gray-500">
                                Contact Number
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-800">
                                <?= e($contact) ?>
                            </p>

                        </div>

                    </div>

                    <div class="flex gap-3">

                        <span class="material-symbols-outlined text-[#255084] text-xl">
                            mail
                        </span>

                        <div class="min-w-0">

                            <p class="text-xs text-gray-500">
                                Email Address
                            </p>

                            <p class="mt-1 text-sm font-semibold text-gray-800 break-all">
                                <?= e($email) ?>
                            </p>

                        </div>

                    </div>

                    <div class="flex gap-3">

                        <span class="material-symbols-outlined text-[#255084] text-xl">
                            location_on
                        </span>

                        <div>

                            <p class="text-xs text-gray-500">
                                Delivery Address
                            </p>

                            <p
                                class="
                                    mt-1
                                    text-sm
                                    font-semibold
                                    text-gray-800
                                    leading-6
                                "
                            >

                                <?= e($address) ?><br>

                                <?= e($city) ?>,
                                <?= e($province) ?>
                                <?= e($postal) ?>

                            </p>

                        </div>

                    </div>

                </div>

                <div
                    class="
                        mt-6
                        pt-5
                        border-t
                        border-gray-100
                        flex
                        items-start
                        gap-2
                        text-xs
                        text-gray-500
                    "
                >

                    <span class="material-symbols-outlined text-[#255084] text-base">
                        notifications
                    </span>

                    <p>
                        We'll send you a notification when your order is updated.
                    </p>

                </div>

            </div>

            <div
                class="
                    border
                    border-[#d6e4f5]
                    rounded-xl
                    bg-white
                    p-5
                    sm:p-6
                "
            >

                <h2
                    class="
                        flex
                        items-center
                        gap-2
                        text-lg
                        font-bold
                        text-[#0e2f4f]
                        mb-6
                    "
                >

                    <span class="material-symbols-outlined text-xl">
                        shopping_bag
                    </span>

                    Order Summary

                </h2>

                <div class="space-y-4">

                    <?php if (!empty($order_items)): ?>

                        <?php foreach ($order_items as $item): ?>

                            <?php
                            $item_name = $item['product_name']
                                ?? $item['name']
                                ?? 'Product';

                            $quantity = max(1, (int)($item['quantity'] ?? 1));

                            $price = (float)(
                                $item['price']
                                ?? $item['unit_price']
                                ?? 0
                            );

                            $item_subtotal = isset($item['subtotal'])
                                ? (float)$item['subtotal']
                                : ($price * $quantity);
                            ?>

                            <div
                                class="
                                    flex
                                    items-center
                                    justify-between
                                    gap-4
                                    border-b
                                    border-gray-100
                                    pb-4
                                "
                            >

                                <div class="min-w-0">

                                    <p class="text-sm font-semibold text-gray-800">
                                        <?= e($item_name) ?>
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Quantity: <?= $quantity ?>
                                        <?php if (!empty($item['size'])): ?>
                                            | Size: <?= e($item['size']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['color'])): ?>
                                            | <?= e($item['color']) ?>
                                        <?php endif; ?>
                                    </p>

                                </div>

                                <p
                                    class="
                                        text-sm
                                        font-semibold
                                        text-gray-800
                                        whitespace-nowrap
                                    "
                                >
                                    <?= peso($item_subtotal) ?>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="text-sm text-gray-500">
                            No order items available.
                        </p>

                    <?php endif; ?>

                    <div class="space-y-3 pt-2">

                        <div class="flex justify-between gap-4 text-sm">

                            <span class="text-gray-500">
                                Subtotal
                            </span>

                            <span class="font-medium text-gray-700">
                                <?= peso($subtotal) ?>
                            </span>

                        </div>

                        <div class="flex justify-between gap-4 text-sm">

                            <span class="text-gray-500">
                                Shipping Fee
                            </span>

                            <span class="font-medium text-gray-700">
                                <?= peso($shipping_fee) ?>
                            </span>

                        </div>

                        <div
                            class="
                                flex
                                justify-between
                                gap-4
                                border-t
                                border-gray-200
                                pt-4
                            "
                        >

                            <span class="font-bold text-[#0e2f4f]">
                                Total
                            </span>

                            <span class="font-bold text-[#0e2f4f]">
                                <?= peso($total) ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <?php if ($is_staff): ?>

            <section class="mb-10 border border-amber-200 rounded-xl bg-white p-5 sm:p-6">

                <h2 class="flex items-center gap-2 text-lg font-bold text-[#0e2f4f]">

                    <span class="material-symbols-outlined text-xl">
                        admin_panel_settings
                    </span>

                    Staff Controls

                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Payment method:
                    <span class="font-semibold text-gray-700"><?= e($payment_label) ?></span>
                </p>

                <?php if ($next_status): ?>

                    <form
                        action="buy-success.php"
                        method="POST"
                        class="mt-5 flex flex-col sm:flex-row gap-3"
                    >

                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                        <input type="hidden" name="action" value="advance">
                        <input type="hidden" name="order_id" value="<?= e($order_id) ?>">

                        <input
                            type="text"
                            name="note"
                            maxlength="255"
                            placeholder="Note (optional)"
                            class="
                                flex-1
                                rounded-lg
                                border
                                border-gray-200
                                px-3
                                py-2
                                text-sm
                                outline-none
                                focus:border-blue-500
                            "
                        >

                        <button
                            type="submit"
                            class="
                                rounded-lg
                                bg-[#0e2f4f]
                                px-5
                                py-2
                                text-sm
                                font-semibold
                                text-white
                                transition
                                hover:bg-[#255084]
                                whitespace-nowrap
                            "
                        >
                            Mark as <?= e($status_labels[$next_status]) ?>
                        </button>

                    </form>

                <?php else: ?>

                    <p class="mt-5 text-sm text-gray-500">
                        This order has reached its final step.
                    </p>

                <?php endif; ?>

                <?php if (!empty($recent_orders)): ?>

                    <div class="mt-6 pt-5 border-t border-gray-100">

                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Recent orders
                        </p>

                        <div class="flex flex-wrap gap-2">

                            <?php foreach ($recent_orders as $recent): ?>

                                <a
                                    href="buy-success.php?order=<?= urlencode($recent['order_code']) ?>"
                                    class="
                                        rounded-full
                                        border
                                        px-3
                                        py-1
                                        text-xs
                                        <?= $recent['order_code'] === $order_id
                                            ? 'border-[#0e2f4f] bg-[#0e2f4f] text-white'
                                            : 'border-gray-200 text-gray-600 hover:bg-gray-50'
                                        ?>
                                    "
                                >
                                    <?= e($recent['order_code']) ?>
                                    - <?= e($recent['fullname']) ?>
                                    - <?= e($recent['status']) ?>
                                </a>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

        <?php if ($order_status === 'delivered' && empty($confirmed_at)): ?>

            <section
                class="
                    mb-10
                    border
                    border-green-200
                    rounded-xl
                    bg-white
                    p-6
                    text-center
                "
            >

                <span class="material-symbols-outlined text-4xl text-green-600">
                    check_circle
                </span>

                <h2 class="mt-3 text-xl font-bold text-[#0e2f4f]">
                    Your order has been delivered!
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    Please confirm once you have received your order.
                </p>

                <form
                    action="buy-success.php"
                    method="POST"
                    class="mt-5"
                >

                    <input
                        type="hidden"
                        name="order_id"
                        value="<?= e($order_id) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="confirm"
                    >

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e($_SESSION['csrf']) ?>"
                    >

                    <button
                        type="submit"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            gap-2
                            rounded-lg
                            bg-[#0e2f4f]
                            px-6
                            py-3
                            text-sm
                            font-semibold
                            text-white
                            transition
                            hover:bg-[#255084]
                        "
                    >

                        <span class="material-symbols-outlined text-lg">
                            check
                        </span>

                        Confirm Order Received

                    </button>

                </form>

            </section>

        <?php endif; ?>

        <?php if (!empty($confirmed_at)): ?>

            <section class="mb-10 border border-green-200 rounded-xl bg-white p-6 text-center">

                <span class="material-symbols-outlined text-4xl text-green-600">
                    task_alt
                </span>

                <h2 class="mt-3 text-xl font-bold text-[#0e2f4f]">
                    Thanks for confirming!
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    You confirmed receipt on <?= e(formatStatusDate($confirmed_at)) ?>.
                </p>

            </section>

        <?php endif; ?>

        <section
            class="
                border
                border-[#d6e4f5]
                rounded-xl
                bg-white
                p-5
                sm:p-6
                flex
                flex-col
                sm:flex-row
                items-center
                justify-between
                gap-5
            "
        >

            <div class="flex items-center gap-4">

                <div
                    class="
                        flex
                        h-12
                        w-12
                        shrink-0
                        items-center
                        justify-center
                        rounded-full
                        bg-[#e8f1fb]
                        text-[#255084]
                    "
                >

                    <span class="material-symbols-outlined text-2xl">
                        support_agent
                    </span>

                </div>

                <div>

                    <h2 class="font-bold text-[#0e2f4f]">
                        Need help?
                    </h2>

                    <p class="mt-1 text-xs text-gray-500 leading-5">
                        Have questions about your order? Contact our support team.
                    </p>

                </div>

            </div>

            <a
                href="#"
                class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-full
                    border
                    border-[#255084]
                    px-4
                    py-2
                    text-sm
                    font-semibold
                    text-[#255084]
                    transition
                    hover:bg-[#255084]
                    hover:text-white
                    whitespace-nowrap
                "
            >

                Contact Support

                <span class="material-symbols-outlined text-base">
                    arrow_forward
                </span>

            </a>

        </section>

    </div>

</main>

<footer class="py-6 sm:py-2 mt-12 bg-[#0e2f4f]">

    <div class="max-w-7xl mx-auto px-4">

        <div
            class="
                grid
                grid-cols-1
                lg:grid-cols-4
                py-9
                gap-10
                sm:gap-8
                lg:gap-5
                w-full
            "
        >

            <div class="text-center sm:text-left">

                <a
                    href="../User/homepage"
                    class="
                        flex
                        items-center
                        justify-center
                        sm:justify-start
                        gap-3
                    "
                >

                    <img
                        class="w-14 object-contain"
                        src="../images/Arellano_University_New_Logo.png"
                        alt="Arellano University Logo"
                    >

                    <h1 class="hidden lg:block text-xl font-bold">

                        <span class="block text-white">
                            AU Merch
                        </span>

                        <span class="block text-xs tracking-wide text-gray-400 font-semibold">
                            Official Merchandise Store
                        </span>

                    </h1>

                </a>

                <div class="mt-3">

                    <p
                        class="
                            max-w-[300px]
                            mx-auto
                            sm:mx-0
                            text-gray-400
                            text-[.80rem]
                        "
                    >
                        Proudly supporting the Arellano University community,
                        one product at a time.
                    </p>

                    <div
                        class="
                            mt-6
                            flex
                            items-center
                            justify-center
                            sm:justify-start
                            gap-3
                        "
                    >

                        <a
                            href="#"
                            class="
                                inline-block
                                transition-all
                                duration-300
                                hover:-translate-y-1
                            "
                        >
                            <i class="text-2xl text-white fa-brands fa-facebook"></i>
                        </a>

                        <a
                            href="#"
                            class="
                                inline-block
                                transition-all
                                duration-300
                                hover:-translate-y-1
                            "
                        >
                            <i class="text-2xl text-white fa-brands fa-instagram"></i>
                        </a>

                        <a
                            href="#"
                            class="
                                inline-block
                                transition-all
                                duration-300
                                hover:-translate-y-1
                            "
                        >
                            <i class="text-2xl text-white fa-brands fa-tiktok"></i>
                        </a>

                        <a
                            href="#"
                            class="
                                inline-block
                                transition-all
                                duration-300
                                hover:-translate-y-1
                            "
                        >
                            <i class="text-2xl text-white fa-brands fa-youtube"></i>
                        </a>

                    </div>

                </div>

            </div>

            <div class="text-center sm:text-left">

                <h3 class="text-white text-md font-semibold">
                    Quick Links
                </h3>

                <nav class="mt-3">

                    <ul class="flex flex-col gap-1">

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Home
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Products
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                New Arrivals
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                What's Hot
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                FAQ's
                            </a>
                        </li>

                    </ul>

                </nav>

            </div>

            <div class="text-center sm:text-left">

                <h3 class="text-white text-md font-semibold">
                    Shop Categories
                </h3>

                <nav class="mt-3">

                    <ul class="flex flex-col gap-1">

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Hoodies
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Shirts
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Jackets
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Hats & Caps
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Bags
                            </a>
                        </li>

                        <li>
                            <a
                                href="#"
                                class="text-sm font-semibold hover:text-white text-gray-400"
                            >
                                Accessories
                            </a>
                        </li>

                    </ul>

                </nav>

            </div>

            <div class="text-center sm:text-left">

                <h3 class="text-white text-md font-semibold">
                    Newsletter
                </h3>

                <p class="text-gray-400 text-sm mt-3">
                    Get the latest updates, new arrivals, and exclusive offers
                </p>

                <div class="relative mt-4 max-w-md mx-auto sm:mx-0">

                    <i
                        class="
                            absolute
                            right-1
                            top-1/2
                            -translate-y-1/2
                            flex
                            items-center
                            justify-center
                            bg-[#0e2f4f]/30
                            h-8
                            w-8
                            rounded-full
                            text-sm
                            text-white
                            fa
                            fa-arrow-right
                        "
                    ></i>

                    <input
                        class="
                            py-2
                            pl-3
                            pr-10
                            w-full
                            rounded-lg
                            bg-white/90
                            text-gray-400
                            text-[.90rem]
                            outline-none
                        "
                        type="text"
                        placeholder="Enter your email address"
                    >

                </div>

            </div>

        </div>

        <div class="h-px bg-gray-500"></div>

        <div
            class="
                flex
                flex-col
                sm:flex-row
                items-center
                justify-between
                gap-4
                pt-7
                pb-5
                text-center
                sm:text-left
            "
        >

            <p class="text-gray-400 text-sm">
                © 2026 AU Merch. All rights reserved.
            </p>

            <div
                class="
                    flex
                    flex-wrap
                    items-center
                    justify-center
                    gap-3
                    sm:gap-5
                "
            >

                <a
                    href="#"
                    class="text-gray-400 text-sm hover:text-white"
                >
                    Terms & Conditions
                </a>

                <div class="h-4 w-px bg-gray-400"></div>

                <a
                    href="#"
                    class="text-gray-400 text-sm hover:text-white"
                >
                    Privacy Policy
                </a>

                <div class="h-4 w-px bg-gray-400"></div>

                <a
                    href="#"
                    class="text-gray-400 text-sm hover:text-white"
                >
                    Contact Us
                </a>

            </div>

        </div>

    </div>

</footer>

<script src="../User/sidebar.js"></script>

</body>
</html>