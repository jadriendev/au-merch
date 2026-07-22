<!DOCTYPE html>
<html lang="en" style="font-family: 'Roboto', sans-serif;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Favicon-->
    <link rel="shortcut icon" href="https://www.auchiefslms.com/college/pluginfile.php/1/core_admin/logocompact/300x300/1784347206/au-logo-smaller.png" type="image/x-icon">
    <!--Google Font Roboto-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Bebas+Neue&family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Manrope:wght@200..800&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Quattrocento:wght@400;700&family=Roboto+Mono:ital,wght@0,100..700;1,100..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!--Font Awesome-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!--Tailwind CSS-->
    <script src="https://cdn.tailwindcss.com"></script>
    <title>AU Merch | Buy Official Arellano University Shirts, Hoodies & Merchandise</title>
</head>
<body class="min-h-screen flex flex-col">
    <nav id="navbar" class="fixed top-0 left-0 w-full text-white z-50 flex items-center justify-between px-4 lg:px-5 py-3 transition-all duration-300">
        <div class="flex items-center gap-3">
            <div class="block lg:hidden">
                <button id="openSidebar">
                    <i class="text-2xl fa fa-bars"></i>
                </button>
            </div>
            <a href="homepage.html">
                <img class="w-[40px] lg:w-[50px] object-cover" src="../images/Arellano_University_New_Logo.png" alt="Arellano University Logo">
            </a>
            
            <h1 class="font-bold text-lg lg:text-2xl">AU Merch</h1>
        </div>

        <div class="hidden lg:flex">
            <ul class="flex items-center gap-7">
                <li class="text-lg font-bold"><a class="transition-all duration-300 lg:hover:text-gray-300" href="#home">Home</a></li>
                <li class="text-lg font-bold"><a class="transition-all duration-300 lg:hover:text-gray-300" href="#products">Products</a></li>
                <li class="text-lg font-bold"><a class="transition-all duration-300 lg:hover:text-gray-300" href="#newarrivals">New Arrivals</a></li>
                <li class="text-lg font-bold"><a class="transition-all duration-300 lg:hover:text-gray-300" href="#what">What's Hot</a></li>
            </ul>
        </div>

        <div class="">
            <ul class="flex items-center gap-5">
                <li>
                    <a class="" href="cart.php">
                        <i class="text-lg md:text-xl lg:text-xl fa fa-shopping-cart"></i>
                    </a>
                </li>

                <li class="hidden lg:flex relative group">
                    <a href="#" class="flex items-center gap-1">
                        <i class="text-xl fa fa-user-circle"></i>
                        <i class="text-lg fa fa-caret-down"></i>
                    </a>

                    <div class="absolute right-0 top-6 mt-2 w-44 bg-white border border-gray-200 rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <a href="profile.php" class="block px-4 py-2 text-black hover:bg-gray-100">
                            Profile
                        </a>

                        <a href="settings.php" class="block text-black px-4 py-2 hover:bg-gray-100">
                            Settings
                        </a>

                        <hr>

                        <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                            Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div id="overlay" class="fixed inset-0 bg-black/50 z-40 hidden"></div>

    <div id="sidebar" class="fixed top-0 left-0 h-full w-72 bg-white shadow-lg -translate-x-full transition-transform duration-300 z-50 flex flex-col">
        <div class="flex items-center justify-between p-3 border-b">
            <div class="flex items-center gap-3">
                <img class="w-10" src="../images/Arellano_University_New_Logo.png" alt="">
                <h2 class="font-bold text-xl">AU Merch</h2>
            </div>

            <button id="closeSidebar">
                <i class="fa fa-times text-xl"></i>
            </button>
        </div>

        <div class="flex-1 p-3">
            <ul class="space-y-4">
                <li>
                    <a href="#home" class="block text-lg font-semibold hover:text-cyan-500">
                        Home
                    </a>
                </li>

                <li>
                    <a href="#products" class="block text-lg font-semibold hover:text-cyan-500">
                        Products
                    </a>
                </li>

                <li>
                    <a href="#newarrivals" class="block text-lg font-semibold hover:text-cyan-500">
                        New Arrivals
                    </a>
                </li>

                <li>
                    <a href="#what" class="block text-lg font-semibold hover:text-cyan-500">
                        What's Hot
                    </a>
                </li>
            </ul>
        </div>

        <div class="border-t p-5">
            <ul class="space-y-3">
                <li>
                    <a href="profile.php" class="flex items-center gap-3 hover:text-cyan-500">
                        <i class="fa fa-user"></i>
                        Profile
                    </a>
                </li>

                <li>
                    <a href="settings.php" class="flex items-center gap-3 hover:text-cyan-500">
                        <i class="fa fa-cog"></i>
                        Settings
                    </a>
                </li>

                <li>
                    <a href="logout.php" class="flex items-center gap-3 text-red-600 hover:text-red-700">
                        <i class="fa fa-sign-out"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <main class="flex-1">
        <section id="home" class="relative h-[500px] lg:h-[750px] overflow-hidden bg-gradient-to-br from-[#49B4E3] via-[#2B95C2] to-[#1B6F94]">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle,white_1px,transparent_1px)] bg-[size:26px_26px]"></div>

            <div class="relative z-10 pt-24">
                bobo ka tnginamo
            </div>
        </section>

        <section class="pt-64">
            <div class="">ff</div>
        </section>
    </main>
</body>
<script src="sidebar.js"></script>
</html>