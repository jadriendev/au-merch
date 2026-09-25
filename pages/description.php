<?php
session_start();
require_once "../connection/config.php";

$productId = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM tbl_products WHERE product_id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: ../User/homepage.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];

$cartProducts = [];
$subtotal = 0;
$totalItems = 0;

if (!empty($cart)) {
    foreach ($cart as $cartKey => $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            unset($_SESSION['cart'][$cartKey]);
            continue;
        }

        $stmt = $conn->prepare("SELECT product_id, product_name, variation, price, stock, image, status FROM tbl_products WHERE product_id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            unset($_SESSION['cart'][$cartKey]);
            continue;
        }

        if ($quantity > $product['stock']) {
            $quantity = (int)$product['stock'];
            $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$cartKey]);
            continue;
        }

        $product['quantity'] = $quantity;
        $product['size'] = $item['size'] ?? '';
        $product['color'] = $item['color'] ?? '';
        $product['cart_key'] = $cartKey;

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
    <!-- Vanilla CSS -->
    <link rel="stylesheet" href="description.css">
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!--Tailwind CSS-->
    <script src="https://cdn.tailwindcss.com"></script>
    <title><?= $row['product_name']; ?> | AU Merch</title>
</head>
<body style="font-family: 'Roboto', sans-serif;" class="bg-gray-100 min-h-screen flex flex-col">
    <header class="sticky top-0 z-50 w-full bg-white">
        <nav class="relative flex items-center justify-between max-w-[1500px] mx-auto py-3 px-4 lg:px-4 2xl:px-0">
            <a href="../User/homepage" class="flex items-center gap-3">
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
                    <a href="../pages/cart" class="relative">
                        <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                            shopping_cart
                        </span>

                        <span class="cart-count absolute -top-2 -right-2 bg-blue-800 text-white text-[.65rem] font-bold w-4 h-4 rounded-full flex items-center justify-center">
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
                <a href="../pages/cart" class="relative">
                    <span class="text-[1.6rem] text-[#255084] material-symbols-outlined">
                        shopping_cart
                    </span>

                    <span class="cart-count absolute -top-2 -right-2 bg-blue-800 text-white text-[.65rem] font-bold w-4 h-4 rounded-full flex items-center justify-center">
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

    <main class="px-0 sm:px-3">
        <div class="desc-content">
            <section>
                <div class="nav-links">
                    <a href="../User/homepage.php">
                        Home
                    </a>

                    <div class="arrow">></div>

                    <a href="../pages/products.php">
                        Products
                    </a>

                    <div class="arrow">></div>

                    <a class="current-page" href="../pages/description?id=<?= $row['product_id'] ?>">
                        <?= $row['product_name'] ?>
                    </a>
                </div>

                <div class="contain">
                    <div class="img">
                        <div class="img-con">
                            <img src="../images/<?= $row['image'] ?>" alt="Product">
                        </div>

                        <div class="img-child">
                            <img src="../images/<?= $row['image'] ?>" alt="Select">
                        </div>
                    </div>

                    <div class="info py-0 xl:py-[1rem]">
                        <h2 class=""><?= $row['product_name']; ?></h2>

                        <div class="info-con">
                            <div class="overall-con">
                                <div class="stars">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                </div>

                                <div class="results">
                                    <h4 class="">4.8</h4>
                                    <h4 class="">(124 reviews)</h4>
                                </div>
                            </div>

                            <h1 class="">₱<?= $row['price']; ?></h1>

                            <p class=""><?= $row['mini_desc'] ?></p>
                        
                            <div class="size-con">
                                <h4 class="">Size</h4>

                                <div class="size-select">
                                    <input type="radio" name="size" id="small" value="S" onclick="toggleSize(this)">
                                    <label for="small">S</label>

                                    <input type="radio" name="size" id="medium" value="M" onclick="toggleSize(this)">
                                    <label for="medium">M</label>

                                    <input type="radio" name="size" id="large" value="L" onclick="toggleSize(this)">
                                    <label for="large">L</label>

                                    <input type="radio" name="size" id="xl" value="XL" onclick="toggleSize(this)">
                                    <label for="xl">XL</label>
                                </div>
                            </div>
                            
                            <div class="color-selection">
                                <h4 class="">Color</h4>

                                <div class="color-select">
                                    <input type="radio" name="color" id="color" value="<?= htmlspecialchars($row['color']) ?>" onclick="toggleColor(this)">
                                    <label for="color"><?= htmlspecialchars($row['color']) ?></label>
                                </div>
                            </div>

                            <div class="quantity-con">
                                <h4 class="">Quantity</h4>

                                <div class="quantity-select">
                                    <button type="button" id="decreaseBtn">−</button>
                                    <span id="quantity">1</span>
                                    <button type="button" id="increaseBtn">+</button>
                                </div>
                            </div>
                        </div>

                        <div class="button-con">
                            <button class="cta-btn" type="button" id="addToCartBtn">
                                <i class="fa fa-shopping-cart"></i>
                                Add to Cart
                            </button>

                            <button class="buynow-btn" type="button">
                                <i class="fa fa-bolt"></i>
                                Buy Now
                            </button>
                        </div>

                        <div class="authentic">
                            <div class="authentic-con">
                                <i class="fa fa-truck"></i>
                                <h4 class="">Fast & Reliable Shipping</h4>
                            </div>

                            <div class="authentic-con">
                                <i class="fa fa-circle-check"></i>
                                <h4 class="">100% Authentic Merchandise</h4>
                            </div>

                            <div class="authentic-con">
                                <i class="fa fa-rotate"></i>
                                <h4 class="">Easy Return & Exchanges</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

            <section class="">
                <div class="desc-content">
                    <div class="containers">
                        <div class="con">
                            <div class="tabs">
                                <button type="button" class="active">
                                    Description
                                </button>

                                <button type="button">
                                    Specifications
                                </button>

                                <button type="button">
                                    Shipping & Returns
                                </button>
                            </div>

                            <div class="line"></div>
                            
                            <div class="product-desc">
                                <h2 class="">Product Description</h2>
                                <p class="arellano"><?= $row['description']; ?></p>
                            
                                <div class="checks">
                                    <div class="checks-con">
                                        <i class="fa fa-check-circle"></i>
                                        <p>Premium cotton blend fabric</p>
                                    </div>

                                    <div class="checks-con">
                                        <i class="fa fa-check-circle"></i>
                                        <p>Embroided AU Logo (front)</p>
                                    </div>

                                    <div class="checks-con">
                                        <i class="fa fa-check-circle"></i>
                                        <p>Arellano University print (back)</p>
                                    </div>

                                    <div class="checks-con">
                                        <i class="fa fa-check-circle"></i>
                                        <p>Ribbed cuffs and hem</p>
                                    </div>

                                    <div class="checks-con">
                                        <i class="fa fa-check-circle"></i>
                                        <p>Available in multiple sizes and colors</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="reviews">
                            <h2 class="head">Customer Reviews</h2>
                        
                            <div class="rates">
                                <h2 class="text">
                                    <span class="">4.8</span>
                                    <span class="damn">out of 5</span>
                                </h2>

                                <button type="button" class="">
                                    Write a Review
                                </button>
                            </div>

                            <div class="stars-con">
                                <div class="stars">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                </div>

                                <div class="">
                                    <h2 class="">(124 reviews)</h2>
                                </div>
                            </div>

                            <div class="reviews-summary">
                                <div class="rating-row">
                                    <span>5 
                                        <i class="fa fa-star"></i>
                                    </span>

                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: 92%;"></div>
                                    </div>
                                    <small>92%</small>
                                </div>

                                <div class="rating-row">
                                    <span>4 
                                        <i class="fa fa-star"></i>
                                    </span>

                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: 6%;"></div>
                                    </div>

                                    <small>6%</small>
                                </div>

                                <div class="rating-row">
                                    <span>3 
                                        <i class="fa fa-star"></i>
                                    </span>

                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: 1%;"></div>
                                    </div>

                                    <small>1%</small>
                                </div>

                                <div class="rating-row">
                                    <span>2 
                                        <i class="fa fa-star"></i>
                                    </span>

                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: 1%;"></div>
                                    </div>

                                    <small>1%</small>
                                </div>

                                <div class="rating-row">
                                    <span>1 
                                        <i class="fa fa-star"></i>
                                    </span>

                                    <div class="rating-bar">
                                        <div class="rating-fill" style="width: 0%;"></div>
                                    </div>

                                    <small>0%</small>
                                </div>

                            </div>

                            <div class="review">
                                <div class="review-header">
                                    <div class="avatar">JB</div>

                                    <div>
                                        <strong>John Benedict Villegas</strong>
                                        <span>2 days ago</span>
                                    </div>
                                </div>

                                <div class="stars">★★★★★</div>

                                <p>
                                    Pinopormahan ko kasi si Maricua, Dehinse at Justmine, ang ganda nya!!
                                </p>
                            </div>
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

    <div id="cartModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 px-4">
    <div id="cartModalBox" class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl scale-95 opacity-0 transition-all duration-200">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
            <i class="fa fa-check text-2xl text-green-600"></i>
        </div>

        <h2 class="mt-4 text-xl font-bold text-gray-800">
            Added to Cart
        </h2>

        <p id="cartModalMessage" class="mt-2 text-sm text-gray-500">
            Product has been added to your cart.
        </p>

        <div class="mt-6 flex gap-3">
            <button id="continueShoppingBtn" type="button" class="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                Continue Shopping
            </button>

            <a href="../pages/cart" class="flex-1 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                View Cart
            </a>
        </div>
    </div>
</div>

<script src="../User/sidebar.js"></script>
<script src="../User/carousel.js"></script>
<script src="../User/heart.js"></script>
<script src="../User/radio.js"></script>
<script src="../User/quantity.js"></script>

<script>
const cartModal = document.getElementById('cartModal');
const cartModalBox = document.getElementById('cartModalBox');
const cartModalMessage = document.getElementById('cartModalMessage');
const continueShoppingBtn = document.getElementById('continueShoppingBtn');

function showCartModal(message) {
    cartModalMessage.textContent = message;
    cartModal.classList.remove('hidden');
    cartModal.classList.add('flex');

    requestAnimationFrame(() => {
        cartModalBox.classList.remove('scale-95', 'opacity-0');
        cartModalBox.classList.add('scale-100', 'opacity-100');
    });
}

function closeCartModal() {
    cartModalBox.classList.remove('scale-100', 'opacity-100');
    cartModalBox.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        cartModal.classList.add('hidden');
        cartModal.classList.remove('flex');
    }, 200);
}

continueShoppingBtn.addEventListener('click', closeCartModal);

cartModal.addEventListener('click', (event) => {
    if (event.target === cartModal) {
        closeCartModal();
    }
});

const userId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
const CART_STORAGE_KEY = `au_cart_${userId}`;

function saveCartToLocalStorage(cartKey, cartItem) {

    let cart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY)) || {};

    if (cart[cartKey]) {

        cart[cartKey].quantity = cartItem.quantity;

    } else {

        cart[cartKey] = cartItem;

    }

    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
}

document.getElementById('addToCartBtn').addEventListener('click', async () => {
    const size = document.querySelector('input[name="size"]:checked');
    const color = document.querySelector('input[name="color"]:checked');
    const quantity = document.getElementById('quantity').textContent;

    if (!size || !color) {
        alert('Please select a size and color.');
        return;
    }

    const formData = new FormData();

    formData.append('product_id', '<?= $row["product_id"] ?>');
    formData.append('quantity', quantity);
    formData.append('size', size.value);
    formData.append('color', color.value);

    try {
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showCartModal(data.message);

            document.querySelectorAll('.cart-count').forEach(element => {
                element.textContent = data.cart_count;
            });

            saveCartToLocalStorage(data.cart_key, data.cart_item);
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error(error);
        alert('Something went wrong while adding the product to your cart.');
    }
});
</script>
</body>
</html>