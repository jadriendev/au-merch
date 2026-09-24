<?php
session_start();

$cart = $_SESSION['cart'] ?? [];

$totalItems = 0;

foreach ($cart as $item) {
    $totalItems += (int)($item['quantity'] ?? 0);
}

if (empty($_SESSION['last_order'])) {
    header("Location: cart.php");
    exit;
}

$order = $_SESSION['last_order'];

$order_id = $order['order_id']
    ?? 'AU-' . str_pad((string)($order['id'] ?? 0), 5, '0', STR_PAD_LEFT);

$order_status = strtolower($order['status'] ?? 'placed');

$status_dates = $order['status_dates'] ?? [
    'placed'    => date('M j, Y g:i A'),
    'paid'      => null,
    'shipped'   => null,
    'received'  => null,
    'delivered' => null
];

$order_items = $order['items'] ?? [];

$subtotal = (float)($order['subtotal'] ?? 0);
$shipping_fee = (float)($order['shipping_fee'] ?? 0);
$total = (float)($order['total'] ?? ($subtotal + $shipping_fee));

$fullname = $order['fullname'] ?? 'Customer';
$email = $order['email'] ?? '—';
$contact = $order['contact'] ?? ($order['phone'] ?? '—');

$address = $order['address'] ?? '—';
$city = $order['city'] ?? '—';
$province = $order['province'] ?? '—';
$postal = $order['postal'] ?? '—';

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

$current_index = array_search($order_status, $status_steps);

if ($current_index === false) {
    $current_index = 0;
    $order_status = 'placed';
}

function formatStatusDate($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    return date('M j, Y g:i A', $timestamp);
}

function peso($amount)
{
    return '₱' . number_format((float)$amount, 2);
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

    <nav class="relative flex items-center justify-between max-w-[1500px] mx-auto py-3 px-4 lg:px-4 2xl:px-0">

        <a href="../User/homepage" class="flex items-center gap-3">

            <img
                class="w-14 object-contain"
                src="../images/Arellano_University_New_Logo.png"
                alt="Arellano University Logo"
            >

            <h1 class="hidden lg:block text-xl font-bold">
                <span class="block text-[#0e2f4f]">AU Merch</span>

                <span class="block text-xs tracking-wide text-gray-500 font-semibold">
                    Official Merchandise Store
                </span>
            </h1>

        </a>

        <ul class="hidden lg:flex items-center gap-16">

            <li>
                <a
                    class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600"
                    href="../User/homepage"
                >
                    Home
                </a>
            </li>

            <li>
                <a
                    class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600"
                    href="../pages/products"
                >
                    Products
                </a>
            </li>

            <li>
                <a
                    class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600"
                    href="../pages/new_arrivals"
                >
                    New Arrivals
                </a>
            </li>

            <li>
                <a
                    class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600"
                    href="../pages/whats_hot"
                >
                    What's Hot
                </a>
            </li>

        </ul>

        <div class="hidden lg:flex items-center md:gap-4 xl:gap-6 2xl:gap-10">

            <div class="relative">

                <i class="absolute top-1/2 -translate-y-1/2 left-3 text-gray-500 text-[.90rem] fa fa-magnifying-glass"></i>

                <input
                    class="bg-gray-300/30 h-10 py-2 pl-10 pr-4 w-full rounded-full text-[.80rem] outline-none focus:ring-0"
                    type="text"
                    name="search"
                    placeholder="Search for products..."
                >

            </div>

            <div class="flex items-center gap-4">

                <a href="../pages/cart" class="relative">

                    <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                        shopping_cart
                    </span>

                    <span
                        class="cart-count absolute -top-2 -right-2 bg-blue-800 text-white text-[.65rem] font-bold w-4 h-4 rounded-full flex items-center justify-center"
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
                        class="absolute right-0 top-10 w-48 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden invisible opacity-0 translate-y-2 peer-checked:visible peer-checked:opacity-100 peer-checked:translate-y-0 transition-all duration-200 z-50"
                    >

                        <a
                            href="/pages/account_settings"
                            class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition"
                        >
                            <span class="material-symbols-outlined text-[20px]">
                                settings
                            </span>

                            Account Settings
                        </a>

                        <a
                            href="../logout.php"
                            class="flex items-center gap-3 px-4 py-3 text-sm text-red-500 hover:bg-red-50 transition"
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

            <a href="../pages/cart" class="relative">

                <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                    shopping_cart
                </span>

                <span
                    class="cart-count absolute -top-2 -right-2 bg-blue-800 text-white text-[.65rem] font-bold w-4 h-4 rounded-full flex items-center justify-center"
                >
                    <?= $totalItems ?>
                </span>

            </a>

            <button
                id="menuBtn"
                class="text-[#255084] text-[1.5rem]"
            >
                <i id="menuIcon" class="fa fa-bars"></i>
            </button>

        </div>

        <div
            id="mobileMenu"
            class="lg:hidden absolute left-0 top-full w-full bg-white overflow-hidden max-h-0 opacity-0 transition-all duration-300 ease-in-out"
        >

            <ul class="flex flex-col px-6 py-4 gap-4">

                <li>
                    <a
                        href="../User/homepage"
                        class="block text-[.95rem] text-[#576578] font-semibold"
                    >
                        Home
                    </a>
                </li>

                <li>
                    <a
                        href="../pages/products"
                        class="block text-[.95rem] text-[#576578] font-semibold"
                    >
                        Products
                    </a>
                </li>

                <li>
                    <a
                        href="../pages/new_arrivals"
                        class="block text-[.95rem] text-[#576578] font-semibold"
                    >
                        New Arrivals
                    </a>
                </li>

                <li>
                    <a
                        href="../pages/whats_hot"
                        class="block text-[.95rem] text-[#576578] font-semibold"
                    >
                        What's Hot
                    </a>
                </li>

            </ul>

            <div class="px-6 pb-4">

                <div class="relative">

                    <i class="absolute top-1/2 -translate-y-1/2 left-3 text-gray-500 text-[.90rem] fa fa-magnifying-glass"></i>

                    <input
                        class="bg-gray-300/30 h-10 py-2 pl-10 pr-4 w-full rounded-full text-[.80rem] outline-none"
                        type="text"
                        name="search"
                        placeholder="Search for products..."
                    >

                </div>

            </div>

            <div class="flex flex-col px-6 pb-5 gap-3">

                <a
                    href="pages/account_settings"
                    class="text-[.95rem] text-[#576578] font-semibold hover:text-blue-600 transition-all duration-300"
                >
                    Account Settings
                </a>

                <a
                    href="../logout.php"
                    class="text-[.95rem] text-red-500 font-semibold hover:text-red-600 transition-all duration-300"
                >
                    Logout
                </a>

            </div>

        </div>

    </nav>

</header>

<main class="flex-1">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-4">
            <a
                href="../pages/cart"
                class="inline-flex items-center gap-2 group text-sm font-semibold text-[#2563eb]"
            >
                <span class="material-symbols-outlined text-[20px] transition-all duration-300 group-hover:-translate-x-[.20rem]">arrow_back</span>
                Back to My Cart
            </a>
        </div>
        <div class="mb-10">

            <h1 class="text-3xl sm:text-4xl font-bold text-[#0e2f4f]">
                Delivery Tracking
            </h1>

            <p class="mt-2 text-gray-500">
                Track your order status and see the latest updates on your delivery.
            </p>

        </div>

        <div class="mb-10">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <div>

                    <h2 class="text-2xl font-bold text-[#0e2f4f]">
                        Order #<?= htmlspecialchars($order_id) ?>
                    </h2>

                </div>

                <div class="sm:text-right">

                    <p class="text-sm text-gray-500 mb-1">
                        Current Status
                    </p>

                    <p class="text-lg font-bold text-[#255084]">
                        <?= htmlspecialchars($status_labels[$order_status]) ?>
                    </p>

                </div>

            </div>

        </div>

        <section class="py-12 border-b border-gray-200">

            <div class="relative">

                <div class="absolute top-5 left-[10%] right-[10%] h-1 bg-gray-200"></div>

                <div
                    class="absolute top-5 left-[10%] h-1 bg-[#255084] transition-all duration-500"
                    style="width: <?= $current_index === 0 ? '0' : (($current_index / (count($status_steps) - 1)) * 80) ?>%;"
                ></div>

                <div class="relative grid grid-cols-5 gap-2">

                    <?php foreach ($status_steps as $index => $step): ?>

                        <?php
                        $is_completed = $index < $current_index;
                        $is_current = $index === $current_index;
                        $is_active = $index <= $current_index;
                        ?>

                        <div class="flex flex-col items-center text-center">

                            <div
                                class="
                                    w-10 h-10 rounded-full flex items-center justify-center
                                    border-2 z-10 bg-white
                                    <?= $is_active
                                        ? 'border-[#255084] bg-[#255084] text-white'
                                        : 'border-gray-300 text-gray-400'
                                    ?>
                                "
                            >

                                <?php if ($step === 'placed'): ?>
                                    
                                    <span class="material-symbols-outlined text-[20px] text-[#255084]">local_shipping</span>

                                <?php elseif ($is_completed): ?>

                                    <i class="fa fa-check text-sm"></i>

                                <?php elseif ($is_current): ?>

                                    <i class="fa fa-circle text-[7px]"></i>

                                <?php else: ?>

                                    <span class="text-sm">
                                        <?= $index + 1 ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <p
                                class="
                                    mt-3 text-xs sm:text-sm font-semibold
                                    <?= $is_active ? 'text-[#0e2f4f]' : 'text-gray-400' ?>
                                "
                            >
                                <?= htmlspecialchars($status_labels[$step]) ?>
                            </p>

                            <?php if (!empty($status_dates[$step])): ?>

                                <p class="mt-1 text-[10px] sm:text-xs text-gray-400">
                                    <?= formatStatusDate($status_dates[$step]) ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-12 py-12 border-b border-gray-200">

            <div>

                <h2 class="text-xl font-bold text-[#0e2f4f] mb-6">
                    Shipping Information
                </h2>

                <div class="space-y-5">

                    <div>

                        <p class="text-sm text-gray-500">
                            Customer
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">
                            <?= htmlspecialchars($fullname) ?>
                        </p>

                    </div>

                    <div>

                        <p class="text-sm text-gray-500">
                            Contact Number
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">
                            <?= htmlspecialchars($contact) ?>
                        </p>

                    </div>

                    <div>

                        <p class="text-sm text-gray-500">
                            Email
                        </p>

                        <p class="mt-1 font-semibold text-gray-800">
                            <?= htmlspecialchars($email) ?>
                        </p>

                    </div>

                    <div>

                        <p class="text-sm text-gray-500">
                            Delivery Address
                        </p>

                        <p class="mt-1 font-semibold text-gray-800 leading-6">
                            <?= htmlspecialchars($address) ?><br>
                            <?= htmlspecialchars($city) ?>,
                            <?= htmlspecialchars($province) ?>
                            <?= htmlspecialchars($postal) ?>
                        </p>

                    </div>

                </div>

            </div>

            <div>

                <h2 class="text-xl font-bold text-[#0e2f4f] mb-6">
                    Order Summary
                </h2>

                <div class="space-y-4">

                    <?php if (!empty($order_items)): ?>

                        <?php foreach ($order_items as $item): ?>

                            <?php
                            $item_name = $item['product_name']
                                ?? $item['name']
                                ?? 'Product';

                            $quantity = (int)($item['quantity'] ?? 1);

                            $price = (float)(
                                $item['price']
                                ?? $item['unit_price']
                                ?? 0
                            );

                            $item_subtotal = (float)(
                                $item['subtotal']
                                ?? ($price * $quantity)
                            );
                            ?>

                            <div class="flex items-center justify-between gap-4">

                                <div>

                                    <p class="font-semibold text-gray-800">
                                        <?= htmlspecialchars($item_name) ?>
                                    </p>

                                    <p class="text-sm text-gray-500">
                                        ×<?= $quantity ?>
                                    </p>

                                </div>

                                <p class="font-semibold text-gray-800">
                                    <?= peso($item_subtotal) ?>
                                </p>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p class="text-gray-500 text-sm">
                            No order items available.
                        </p>

                    <?php endif; ?>

                    <div class="border-t border-gray-200 pt-5 mt-5 space-y-3">

                        <div class="flex justify-between">

                            <span class="text-gray-500">
                                Subtotal
                            </span>

                            <span class="font-semibold text-gray-800">
                                <?= peso($subtotal) ?>
                            </span>

                        </div>

                        <div class="flex justify-between">

                            <span class="text-gray-500">
                                Shipping Fee
                            </span>

                            <span class="font-semibold text-gray-800">
                                <?= peso($shipping_fee) ?>
                            </span>

                        </div>

                        <div class="flex justify-between pt-3">

                            <span class="text-lg font-bold text-[#0e2f4f]">
                                Total
                            </span>

                            <span class="text-lg font-bold text-[#0e2f4f]">
                                <?= peso($total) ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>

        <?php if ($order_status === 'delivered'): ?>

            <section class="py-10 text-center border-b border-gray-200">

                <div class="max-w-xl mx-auto">

                    <div class="w-14 h-14 mx-auto rounded-full bg-green-100 text-green-600 flex items-center justify-center">

                        <i class="fa fa-check text-xl"></i>

                    </div>

                    <h2 class="mt-4 text-xl font-bold text-[#0e2f4f]">
                        Your order has been delivered
                    </h2>

                    <p class="mt-2 text-gray-500">
                        Your order has reached its final delivery status.
                    </p>

                    <form
                        action="confirm-order.php"
                        method="POST"
                        class="mt-6"
                    >

                        <input
                            type="hidden"
                            name="order_id"
                            value="<?= htmlspecialchars($order_id) ?>"
                        >

                        <button
                            type="submit"
                            class="px-6 py-3 bg-[#0e2f4f] text-white rounded-lg font-semibold hover:bg-[#255084] transition"
                        >
                            Confirm Order Received
                        </button>

                    </form>

                </div>

            </section>

        <?php endif; ?>

        <section class="py-10 text-center">

            <h2 class="text-lg font-bold text-[#0e2f4f]">
                Need help?
            </h2>

            <p class="mt-2 text-gray-500">
                If you have questions about your order, please contact support.
            </p>

            <a
                href="#"
                class="inline-block mt-4 text-[#255084] font-semibold hover:underline"
            >
                Contact Support
            </a>

        </section>

    </div>

</main>

<footer class="py-6 sm:py-2 mt-12 bg-[#0e2f4f]">

    <div class="max-w-7xl mx-auto px-4">

        <div class="grid grid-cols-1 lg:grid-cols-4 py-9 gap-10 sm:gap-8 lg:gap-5 w-full">

            <div class="text-center sm:text-left">

                <a
                    href="../User/homepage"
                    class="flex items-center justify-center sm:justify-start gap-3"
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

                    <p class="max-w-[300px] mx-auto sm:mx-0 text-gray-400 text-[.80rem]">
                        Proudly supporting the Arellano University community, one product at a time.
                    </p>

                    <div class="mt-6 flex items-center justify-center sm:justify-start gap-3">

                        <a href="#" class="inline-block transition-all duration-300 hover:-translate-y-1">
                            <i class="text-2xl text-white fa-brands fa-facebook"></i>
                        </a>

                        <a href="#" class="inline-block transition-all duration-300 hover:-translate-y-1">
                            <i class="text-2xl text-white fa-brands fa-instagram"></i>
                        </a>

                        <a href="#" class="inline-block transition-all duration-300 hover:-translate-y-1">
                            <i class="text-2xl text-white fa-brands fa-tiktok"></i>
                        </a>

                        <a href="#" class="inline-block transition-all duration-300 hover:-translate-y-1">
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
                            <a href="#" class="text-sm font-semibold hover:text-white text-gray-400">
                                Home
                            </a>
                        </li>

                        <li>
                            <a href="#" class="text-sm font-semibold hover:text-white text-gray-400">
                                Products
                            </a>
                        </li>

                        <li>
                            <a href="#" class="text-sm font-semibold hover:text-white text-gray-400">
                                New Arrivals
                            </a>
                        </li>

                        <li>
                            <a href="#" class="text-sm font-semibold hover:text-white text-gray-400">
                                What's Hot
                            </a>
                        </li>

                        <li>
                            <a href="#" class="text-sm font-semibold hover:text-white text-gray-400">
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

                        <li><a href="#" class="text-sm font-semibold hover:text-white text-gray-400">Hoodies</a></li>
                        <li><a href="#" class="text-sm font-semibold hover:text-white text-gray-400">Shirts</a></li>
                        <li><a href="#" class="text-sm font-semibold hover:text-white text-gray-400">Jackets</a></li>
                        <li><a href="#" class="text-sm font-semibold hover:text-white text-gray-400">Hats & Caps</a></li>
                        <li><a href="#" class="text-sm font-semibold hover:text-white text-gray-400">Bags</a></li>
                        <li><a href="#" class="text-sm font-semibold hover:text-white text-gray-400">Accessories</a></li>

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
                        class="absolute right-1 top-1/2 -translate-y-1/2 flex items-center justify-center bg-[#0e2f4f]/30 h-8 w-8 rounded-full text-sm text-white fa fa-arrow-right"
                    ></i>

                    <input
                        class="py-2 pl-3 pr-10 w-full rounded-lg bg-white/90 text-gray-400 text-[.90rem] outline-none"
                        type="text"
                        placeholder="Enter your email address"
                    >

                </div>

            </div>

        </div>

        <div class="h-px bg-gray-500"></div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-7 pb-5 text-center sm:text-left">

            <p class="text-gray-400 text-sm">
                © 2026 AU Merch. All rights reserved.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-5">

                <a href="#" class="text-gray-400 text-sm hover:text-white">
                    Terms & Conditions
                </a>

                <div class="h-4 w-px bg-gray-400"></div>

                <a href="#" class="text-gray-400 text-sm hover:text-white">
                    Privacy Policy
                </a>

                <div class="h-4 w-px bg-gray-400"></div>

                <a href="#" class="text-gray-400 text-sm hover:text-white">
                    Contact Us
                </a>

            </div>

        </div>

    </div>

</footer>

<script src="../User/sidebar.js"></script>
<script src="../User/carousel.js"></script>
<script src="../User/heart.js"></script>
<script src="../User/radio.js"></script>
<script src="../User/quantity.js"></script>

</body>
</html>