<?php

session_start();

require_once "../connection/config.php";

$cart = $_SESSION['cart'] ?? [];

$cartProducts = [];
$subtotal = 0;
$totalItems = 0;

if (!empty($cart)) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $types = str_repeat('i', count($productIds));

    $stmt = $conn->prepare("SELECT product_id, product_name, variation, price, stock, image, status FROM tbl_products WHERE product_id IN ($placeholders)");

    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $id = $product['product_id'];
        $quantity = $cart[$id]['quantity'];

        if ($quantity > $product['stock']) {
            $quantity = $product['stock'];
            $_SESSION['cart'][$id]['quantity'] = $quantity;
        }

        $product['quantity'] = $quantity;

        $cartProducts[] = $product;

        $subtotal += $product['price'] * $quantity;
        $totalItems += $quantity;
    }
}

$shipping = !empty($cartProducts) ? 60 : 0;
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Favicon-->
    <link rel="shortcut icon" href="https://www.auchiefslms.com/college/pluginfile.php/1/core_admin/logocompact/300x300/1784347206/au-logo-smaller.png" type="image/x-icon">
    <!--Google Font Roboto-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Bebas+Neue&family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Quattrocento:wght@400;700&family=Roboto+Mono:ital,wght@0,100..700;1,100..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!-- Materials Icon -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!--Tailwind CSS-->
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Cart | AU Merch</title>
</head>
<body style="font-family: 'Roboto', sans-serif;" class="bg-gray-100 min-h-screen flex flex-col">
    <header class="sticky top-0 z-50 w-full bg-white">
        <nav class="relative flex items-center justify-between max-w-[1500px] mx-auto py-3 px-4 lg:px-4 2xl:px-0">
            <a href="homepage.php" class="flex items-center gap-3">
                <img class="w-14 object-contain" src="../images/Arellano_University_New_Logo.png" alt="Arellano_University_New_Logo">
                <h1 class="hidden lg:block text-xl font-bold">
                    <span class="block text-[#0e2f4f]">AU Merch</span>
                    <span class="block text-xs tracking-wide text-gray-500 font-semibold">Official Merchandise Store</span>
                </h1>
            </a>

            <ul class="hidden lg:flex items-center gap-16">
                <li><a class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600" href="../User/homepage">Home</a></li>
                <li><a class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600" href="../pages/products">Products</a></li>
                <li><a class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600" href="../pages/new_arrivals">New Arrivals</a></li>
                <li><a class="transition-all duration-300 block text-[.95rem] text-[#576578] font-semibold hover:text-blue-600" href="../pages/whats_hot">What's Hot</a></li>
            </ul>

            <div class="hidden lg:flex items-center md:gap-4 xl:gap-6 2xl:gap-10">
                <div class="relative">
                    <i class="absolute top-1/2 -translate-y-1/2 left-3 text-gray-500 text-[.90rem] fa fa-magnifying-glass"></i>
                    <input class="bg-gray-300/30 h-10 py-2 pl-10 pr-4 w-full rounded-full text-[.80rem] outline-none focus:outline-none focus:ring-0" type="text" name="search" placeholder="Search for products...">
                </div>

                <div class="flex items-center gap-4">
                    <a href="/pages/cart" class="relative">
                        <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                            shopping_cart
                        </span>

                        <span class="absolute -top-2 -right-2 bg-blue-800 text-white text-[.65rem] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                            <?= $totalItems ?>
                        </span>
                    </a>

                    <div class="relative">
                        <input type="checkbox" id="profileToggle" class="hidden peer">

                        <label for="profileToggle" class="cursor-pointer block">
                            <span class="text-[1.8rem] text-[#255084] material-symbols-outlined">
                                person
                            </span>
                        </label>

                        <div class="absolute right-0 top-10 w-48 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden invisible opacity-0 translate-y-2 peer-checked:visible peer-checked:opacity-100 peer-checked:translate-y-0 transition-all duration-200 z-50">
                            <a href="/pages/account_settings" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition">
                                <span class="material-symbols-outlined text-[20px]">
                                    settings
                                </span>
                                Account Settings
                            </a>

                            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-sm text-red-500 hover:bg-red-50 transition">
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
                <a href="/pages/cart" class="relative">
                    <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                        shopping_cart
                    </span>

                    <span class="absolute -top-2 -right-2 bg-blue-800 text-white text-[.65rem] font-bold w-4 h-4 rounded-full flex items-center justify-center">
                        <?= $totalItems ?>
                    </span>
                </a>

                <button id="menuBtn" class="text-[#255084] text-[1.5rem]">
                    <i id="menuIcon" class="fa fa-bars"></i>
                </button>
            </div>

            <div id="mobileMenu" class="lg:hidden absolute left-0 top-full w-full bg-white overflow-hidden max-h-0 opacity-0 transition-all duration-300 ease-in-out">
                <ul class="flex flex-col px-6 py-4 gap-4">
                    <li>
                        <a href="../User/homepage" class="block text-[.95rem] text-[#576578] font-semibold" href="/User/homepage">
                            Home
                        </a>
                    </li>

                    <li>
                        <a href="../pages/products" class="block text-[.95rem] text-[#576578] font-semibold" href="pages/products">
                            Products
                        </a>
                    </li>

                    <li>
                        <a href="../pages/new_arrivals" class="block text-[.95rem] text-[#576578] font-semibold" href="pages/new_arrivals">
                            New Arrivals
                        </a>
                    </li>

                    <li>
                        <a href="../pages/whats_hot" class="block text-[.95rem] text-[#576578] font-semibold" href="pages/whats_hot">
                            What's Hot
                        </a>
                    </li>
                </ul>

                <div class="px-6 pb-4">
                    <div class="relative">
                        <i class="absolute top-1/2 -translate-y-1/2 left-3 text-gray-500 text-[.90rem] fa fa-magnifying-glass"></i>
                        <input class="bg-gray-300/30 h-10 py-2 pl-10 pr-4 w-full rounded-full text-[.80rem] outline-none focus:outline-none focus:ring-0" type="text" name="search" placeholder="Search for products...">
                    </div>
                </div>

                <div class="flex flex-col px-6 pb-5 gap-3">
                    <a href="pages/account_settings" class="text-[.95rem] text-[#576578] font-semibold hover:text-blue-600 transition-all duration-300">
                        Account Settings
                    </a>

                    <a href="../logout.php" class="text-[.95rem] text-red-500 font-semibold hover:text-red-600 transition-all duration-300">
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        <section class="pt-8 sm:pt-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <div class="flex items-center gap-2">
                    <a href="../User/homepage" class="text-blue-600 font-semibold text-xs sm:text-[.90rem]">
                        Home
                    </a>

                    <div class="text-gray-500 text-sm sm:text-[1rem]">></div>

                    <a href="/pages/cart" class="text-gray-500 font-semibold text-xs sm:text-[.90rem]">
                        Cart
                    </a>
                </div>

                <div class="mt-2">
                    <h1 class="text-3xl sm:text-4xl font-bold text-[#0e2f4f]">
                        Your Cart
                    </h1>

                    <p class="text-xs sm:text-sm text-gray-500 font-semibold mt-1">
                        Review your items and make sure everything is correct before checkout.
                    </p>
                </div>
            </div>
        </section>

        <section class="py-8">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-1 lg:grid-cols-[8fr_4fr] gap-4">
                    <div class="py-2 px-4 sm:px-6 bg-white border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between py-3 border-b">
                            <h2 class="text-[#0e2f4f] text-lg sm:text-xl font-bold">Cart Items (<?= $totalItems ?>)</h2>
                            
                            <a href="clear_cart.php" class="text-xs sm:text-sm font-semibold text-blue-600">
                                <i class="fa fa-trash text-[.75rem] mr-1"></i>
                                Clear Cart
                            </a>
                        </div>

                        <?php if (empty($cartProducts)): ?>
                        <div class="py-12 text-center">
                            <span class="material-symbols-outlined text-gray-300 text-6xl">
                                shopping_cart
                            </span>

                            <h3 class="text-[#0e2f4f] font-bold text-lg mt-3">
                                Your cart is empty
                            </h3>

                            <p class="text-gray-500 text-sm mt-1">
                                Add some products to your cart.
                            </p>

                            <a href="../User/homepage" class="inline-flex items-center gap-2 mt-5 bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold">
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>

                    <?php foreach ($cartProducts as $product): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-4 border-b">
                            <div class="flex items-center gap-4 sm:gap-8">
                                <div class="bg-gray-300/30 w-[90px] h-[90px] sm:w-[120px] sm:h-[120px] rounded-lg flex-shrink-0 flex items-center justify-center">
                                    <img class="w-20 sm:w-28 object-contain" src="../images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                                </div>

                                <div>

                                    <h3 class="text-[#0e2f4f] text-[.95rem] sm:text-[1rem] font-bold">
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </h3>

                                    <h4 class="text-gray-500 font-semibold text-xs sm:text-sm mt-1">
                                        <?= htmlspecialchars($product['variation']) ?>
                                    </h4>

                                    <select name="size" class="mt-3 sm:mt-4 py-2 px-2 rounded-lg border border-gray-200 text-blue-600 font-semibold text-xs sm:text-[.90rem] cursor-pointer outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                                        <option value="Small">Size: S</option>
                                        <option value="Medium">Size: M</option>
                                        <option value="Large">Size: L</option>
                                    </select>

                                </div>

                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-5 sm:gap-10">
                                <div class="flex flex-col">
                                    <h2 class="text-[#0e2f4f] font-bold text-[1rem] sm:text-[1.1rem]">
                                        ₱<?= number_format($product['price'], 2) ?>
                                    </h2>

                                    <div class="flex items-center mt-1 w-fit border border-gray-200 rounded-lg overflow-hidden">

                                        <a href="update_cart.php?action=decrease&id=<?= $product['product_id'] ?>" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                            −
                                        </a>

                                        <div class="w-px h-8 bg-gray-200"></div>

                                        <span class="w-10 sm:w-14 text-center font-semibold text-gray-800">
                                            <?= $product['quantity'] ?>
                                        </span>

                                        <div class="w-px h-8 bg-gray-200"></div>

                                        <a href="update_cart.php?action=increase&id=<?= $product['product_id'] ?>" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                            +
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <h2 class="text-[#0e2f4f] font-bold text-[.95rem] sm:text-[1rem]">
                                        ₱<?= number_format($product['price'] * $product['quantity'], 2) ?>
                                    </h2>

                                    <a href="update_cart.php?action=remove&id=<?= $product['product_id'] ?>" class="text-gray-500 hover:text-red-500">
                                        <i class="text-[.70rem] fa fa-x"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="py-3">
                            <a href="../User/homepage" class="flex items-center gap-2 text-blue-600 text-sm font-semibold">
                                <i class="text-sm fa fa-arrow-left"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>

                    <div class="py-6 px-4 sm:px-6 bg-white border border-gray-200 rounded-lg">
                        <h2 class="text-[#0e2f4f] text-xl font-bold">Order Summary</h2>

                        <div class="mt-5 flex items-center justify-between">
                            <h4 class="text-gray-500 font-semibold text-sm">
                                Subtotal (<?= $totalItems ?> items)
                            </h4>

                            <h3 class="text-[#0e2f4f] font-bold text-md">
                                ₱<?= number_format($subtotal, 2) ?>
                            </h3>
                        </div>

                        <div class="border-b pb-5 mt-2 flex items-center justify-between">
                            <h4 class="text-gray-500 font-semibold text-sm">
                                Shipping Fee
                            </h4>

                            <h3 class="text-[#0e2f4f] font-bold text-md">
                                ₱<?= number_format($shipping, 2) ?>
                            </h3>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <h2 class="text-[#0e2f4f] text-xl font-bold">
                                Total
                            </h2>

                            <h1 class="text-[#0e2f4f] font-bold text-xl">
                                ₱<?= number_format($total, 2) ?>
                            </h1>
                        </div>

                        <a href="../pages/checkout" class="w-full mt-5 text-sm text-white flex items-center gap-2 py-[.60rem] px-4 rounded-lg font-semibold justify-center bg-blue-600">
                            <span class="text-[1rem] material-symbols-outlined">lock</span>
                            Proceed to Checkout
                            <i class="fa fa-arrow-right text-[.70rem]"></i>
                        </a>

                        <p class="text-gray-400 text-sm text-center mt-4">Secure checkout powered by</p>

                        <div class="flex items-center justify-center mt-3 gap-1">
                            <div class="w-[80px] flex items-center justify-center text-sm font-bold border border-gray-200 px-2 rounded-lg">
                                <i class="fa-brands fa-paypal text-lg text-[#003087]"></i>
                                PayPal
                            </div>

                            <div class="w-[80px] flex items-center justify-center text-sm font-bold border border-gray-200 px-2 rounded-lg">
                                <i class="fa-brands fa-cc-visa text-lg text-[#003087]"></i>
                            </div>

                            <div class="w-[80px] flex items-center justify-center text-sm font-bold border border-gray-200 px-2 rounded-lg">
                                <i class="fa-brands fa-google-pay text-xl text-[#003087]"></i>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 bg-blue-400/10 py-5 px-4 rounded-lg w-full mt-8">
                            <img class="h-12 w-12 flex-shrink-0" src="../images/shield-check-svgrepo-com.svg" alt="">

                            <div>
                                <h3 class="text-blue-600 text-sm font-semibold">100% Authentic Merchandise</h3>
                                <p class="text-gray-400 text-xs mt-1">Official AU products, guaranteed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="">
            <div class="max-w-7xl mx-auto">
                <div class="">
                    <h1 class="text-[#0e2f4f] text-xl font-bold">You Might Also Like</h1>

                    <div class="">
                        <div class="grid grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mt-4">
                            <a href="#" class="w-full pb-5 rounded-lg bg-gray-100/50 overflow-hidden">
                                <div class="relative bg-gray-200/30 rounded-t-lg w-full py-3">
                                    <div class="flex items-center justify-end px-3 mb-2">
                                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this)" class="flex items-center justify-center">
                                            <i class="fa-regular fa-heart text-lg text-gray-700"></i>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-center h-44 sm:h-48 md:h-52">
                                        <img class="flex items-center justify-center h-44 sm:h-48 md:h-52" src="../images/hoodie.png" alt="Products">
                                    </div>
                                </div>

                                <div class="px-3 sm:px-4">
                                    <h3 class="text-[#0e2f4f] font-bold text-base sm:text-lg mt-3">AU Hoodie</h3>
                                    <h4 class="mt-1 text-gray-500 text-xs sm:text-sm">Premium Cotton | Navy</h4>

                                    <h1 class="mt-5 sm:mt-6 text-lg sm:text-xl text-[#0e2f4f] font-bold">₱499</h1>

                                    <button type="button" onclick="event.preventDefault(); event.stopPropagation();" class="flex items-center justify-center transition-all duration-300 group hover:bg-blue-600 border-2 mt-4 border-blue-600 py-2 w-full rounded-full">
                                        <span class="text-blue-600 group-hover:text-white transition-all duration-300 text-sm sm:text-base">
                                            <i style="-webkit-text-fill-color: transparent; -webkit-text-stroke: 1px;" class="text-[.80rem] sm:text-[.90rem] mr-1 fa fa-cart-shopping"></i>
                                            Add to Cart
                                        </span>
                                    </button>
                                </div>
                            </a>

                            <a href="#" class="w-full pb-5 rounded-lg bg-gray-100/50 overflow-hidden">
                                <div class="relative bg-gray-200/30 rounded-t-lg w-full py-3">
                                    <div class="flex items-center justify-end px-3 mb-2">
                                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this)" class="flex items-center justify-center">
                                            <i class="fa-regular fa-heart text-lg text-gray-700"></i>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-center h-44 sm:h-48 md:h-52">
                                        <img class="flex items-center justify-center h-44 sm:h-48 md:h-52" src="../images/hoodie.png" alt="Products">
                                    </div>
                                </div>

                                <div class="px-3 sm:px-4">
                                    <h3 class="text-[#0e2f4f] font-bold text-base sm:text-lg mt-3">AU Hoodie</h3>
                                    <h4 class="mt-1 text-gray-500 text-xs sm:text-sm">Premium Cotton | Navy</h4>

                                    <h1 class="mt-5 sm:mt-6 text-lg sm:text-xl text-[#0e2f4f] font-bold">₱499</h1>

                                    <button type="button" onclick="event.preventDefault(); event.stopPropagation();" class="flex items-center justify-center transition-all duration-300 group hover:bg-blue-600 border-2 mt-4 border-blue-600 py-2 w-full rounded-full">
                                        <span class="text-blue-600 group-hover:text-white transition-all duration-300 text-sm sm:text-base">
                                            <i style="-webkit-text-fill-color: transparent; -webkit-text-stroke: 1px;" class="text-[.80rem] sm:text-[.90rem] mr-1 fa fa-cart-shopping"></i>
                                            Add to Cart
                                        </span>
                                    </button>
                                </div>
                            </a>

                            <a href="#" class="w-full pb-5 rounded-lg bg-gray-100/50 overflow-hidden">
                                <div class="relative bg-gray-200/30 rounded-t-lg w-full py-3">
                                    <div class="flex items-center justify-end px-3 mb-2">
                                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this)" class="flex items-center justify-center">
                                            <i class="fa-regular fa-heart text-lg text-gray-700"></i>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-center h-44 sm:h-48 md:h-52">
                                        <img class="flex items-center justify-center h-44 sm:h-48 md:h-52" src="../images/hoodie.png" alt="Products">
                                    </div>
                                </div>

                                <div class="px-3 sm:px-4">
                                    <h3 class="text-[#0e2f4f] font-bold text-base sm:text-lg mt-3">AU Hoodie</h3>
                                    <h4 class="mt-1 text-gray-500 text-xs sm:text-sm">Premium Cotton | Navy</h4>

                                    <h1 class="mt-5 sm:mt-6 text-lg sm:text-xl text-[#0e2f4f] font-bold">₱499</h1>

                                    <button type="button" onclick="event.preventDefault(); event.stopPropagation();" class="flex items-center justify-center transition-all duration-300 group hover:bg-blue-600 border-2 mt-4 border-blue-600 py-2 w-full rounded-full">
                                        <span class="text-blue-600 group-hover:text-white transition-all duration-300 text-sm sm:text-base">
                                            <i style="-webkit-text-fill-color: transparent; -webkit-text-stroke: 1px;" class="text-[.80rem] sm:text-[.90rem] mr-1 fa fa-cart-shopping"></i>
                                            Add to Cart
                                        </span>
                                    </button>
                                </div>
                            </a>

                            <a href="#" class="w-full pb-5 rounded-lg bg-gray-100/50 overflow-hidden">
                                <div class="relative bg-gray-200/30 rounded-t-lg w-full py-3">
                                    <div class="flex items-center justify-end px-3 mb-2">
                                        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(this)" class="flex items-center justify-center">
                                            <i class="fa-regular fa-heart text-lg text-gray-700"></i>
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-center h-44 sm:h-48 md:h-52">
                                        <img class="flex items-center justify-center h-44 sm:h-48 md:h-52" src="../images/hoodie.png" alt="Products">
                                    </div>
                                </div>

                                <div class="px-3 sm:px-4">
                                    <h3 class="text-[#0e2f4f] font-bold text-base sm:text-lg mt-3">AU Hoodie</h3>
                                    <h4 class="mt-1 text-gray-500 text-xs sm:text-sm">Premium Cotton | Navy</h4>

                                    <h1 class="mt-5 sm:mt-6 text-lg sm:text-xl text-[#0e2f4f] font-bold">₱499</h1>

                                    <button type="button" onclick="event.preventDefault(); event.stopPropagation();" class="flex items-center justify-center transition-all duration-300 group hover:bg-blue-600 border-2 mt-4 border-blue-600 py-2 w-full rounded-full">
                                        <span class="text-blue-600 group-hover:text-white transition-all duration-300 text-sm sm:text-base">
                                            <i style="-webkit-text-fill-color: transparent; -webkit-text-stroke: 1px;" class="text-[.80rem] sm:text-[.90rem] mr-1 fa fa-cart-shopping"></i>
                                            Add to Cart
                                        </span>
                                    </button>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="py-6 sm:py-2 mt-12 bg-[#0e2f4f]">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-4 py-9 gap-10 sm:gap-8 lg:gap-5 w-full">
                <div class="text-center sm:text-left">
                    <a href="homepage.php" class="flex items-center justify-center sm:justify-start gap-3">
                        <img class="w-14 object-contain" src="../images/Arellano_University_New_Logo.png" alt="Arellano_University_New_Logo">
                        <h1 class="hidden lg:block text-xl font-bold">
                            <span class="block text-white">AU Merch</span>
                            <span class="block text-xs tracking-wide text-gray-400 font-semibold">Official Merchandise Store</span>
                        </h1>
                    </a>

                    <div class="mt-3">
                        <p class="max-w-[300px] mx-auto sm:mx-0 text-gray-400 text-[.80rem]">Proudly supporting the Arellano University community, one product at a time.</p>
                        
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
                    <h3 class="text-white text-md font-semibold">Quick Links</h3>
                    <nav class="mt-3">
                        <ul class="flex flex-col gap-1">
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Home</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Products</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">New Arrivals</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">What's Hot</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">FAQ's</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="text-center sm:text-left">
                    <h3 class="text-white text-md font-semibold">Shop Categories</h3>
                    <nav class="mt-3">
                        <ul class="flex flex-col gap-1">
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Hoodies</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Shirts</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Jackets</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Hats & Caps</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Bags</a></li>
                            <li><a href="#" class="text-sm font-semibold transition-all duration-300 hover:text-white text-gray-400">Accessories</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="text-center sm:text-left">
                    <h3 class="text-white text-md font-semibold">Newsletter</h3>
                    <p class="text-gray-400 text-sm mt-3">Get the latest updates, new arrivals, and exclusive offers</p>
                
                    <div class="relative mt-4 max-w-md mx-auto sm:mx-0">
                        <i class="absolute right-1 top-1/2 -translate-y-1/2 flex items-center justify-center bg-[#0e2f4f]/30 h-8 w-8 rounded-full text-sm text-white fa fa-arrow-right"></i>
                        <input class="py-2 pl-3 pr-10 w-full rounded-lg bg-white/90 text-gray-400 text-[.90rem] outline-none" type="text" placeholder="Enter your email address">
                    </div>
                </div>
            </div>

            <div class="">
                <div class="h-px bg-gray-500"></div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-7 pb-5 text-center sm:text-left">
                    <p class="text-gray-400 text-sm">© 2026 AU Merch. All rights reserved.</p>

                    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-5">
                        <a href="#" class="text-gray-400 text-sm transition-all duration-300 hover:text-white">
                            Terms & Conditions
                        </a>

                        <div class="h-4 w-px bg-gray-400"></div>

                        <a href="#" class="text-gray-400 text-sm transition-all duration-300 hover:text-white">
                            Privacy Policy
                        </a>

                        <div class="h-4 w-px bg-gray-400"></div>

                        <a href="#" class="text-gray-400 text-sm transition-all duration-300 hover:text-white">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

<script src="../User/sidebar.js"></script>
<script src="../User/carousel.js"></script>
<script src="../User/heart.js"></script>
</body>
</html>