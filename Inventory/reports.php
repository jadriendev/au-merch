<!DOCTYPE html>
<html lang="en">
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
    <!-- Internal Vanilla CSS -->
    <link rel="stylesheet" href="css/reports.css">
    <title>Reports | AU Merch</title>
</head>
<body class="">
    <aside>
        <button class="close-btn">
            <i class="fa fa-xmark"></i>
        </button>

        <div class="logo">
            <img src="../images/Arellano_University_New_Logo.png" alt="Arellano_University_New_Logo">
            <h1>
                <span>AU Merch</span>
                <span>Merchandise Store</span>
            </h1>
        </div>

        <div class="nav-links">
            <nav>
                <ul>
                    <li class="">
                        <a href="dashboard">
                            <i class="far fa-house"></i>
                            Dashboard
                        </a>
                    </li>

                    <li class="">
                        <a href="products">
                            <i class="fa fa-box-open"></i>
                            Products
                        </a>
                    </li>

                    <li>
                        <a href="orders">
                            <i class="fa fa-cart-shopping"></i>
                            Orders
                        </a>
                    </li>

                    <li>
                        <a href="users">
                            <i class="far fa-user"></i>
                            Users
                        </a>
                    </li>

                    <li class="active">
                        <a href="reports">
                            <i class="fa fa-chart-column"></i>
                            Reports
                        </a>
                    </li>
                </ul>

                <ul class="bottom-link">
                    <li>
                        <a href="#">
                            <i class="fa fa-right-to-bracket"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <main>
        <header>
            <nav class="navbar">
                <button class="menu-btn">
                    <i class="fa fa-bars"></i>
                </button>

                <a href="#" class="notification">
                    <i class="fa fa-bell"></i>
                </a>

                <div class="profile">
                    <div class="icon">
                        A
                    </div>

                    <h4>Admin</h4>

                    <i class="fa fa-chevron-down"></i>
                </div>
            </nav>
        </header>

        <section class="top">
            <div class="max">
                <div class="greet">
                    <h1>
                        <span>Reports</span>
                        <span>View key information about your store.</span>
                    </h1>
                </div>
            </div>
        </section>

        <section class="report-filters-section">
            <div class="max">
                <div class="report-filters">

                    <div class="report-filter date-filter">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Sep 15, 2025 – Sep 22, 2025</span>
                        <i class="fa fa-chevron-down"></i>
                    </div>

                    <div class="report-filter">
                        <span>All Categories</span>
                        <i class="fa fa-chevron-down"></i>
                    </div>

                    <div class="report-filter">
                        <span>All Payment Methods</span>
                        <i class="fa fa-chevron-down"></i>
                    </div>

                    <button class="export-btn">
                        <i class="fa-regular fa-file-lines"></i>
                        Export Report
                    </button>

                </div>
            </div>
        </section>

        <section class="report-summary-section">
            <div class="max">
                <div class="report-summary">

                    <div class="report-card">
                        <div class="report-icon">
                            <i class="fa fa-cart-shopping"></i>
                        </div>

                        <div class="report-info">
                            <h3>Total Orders</h3>
                            <h2>86</h2>

                            <p>
                                <i class="fa fa-arrow-up"></i>
                                12% from last week
                            </p>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-icon">
                            <i class="fa fa-peso-sign"></i>
                        </div>

                        <div class="report-info">
                            <h3>Total Sales</h3>
                            <h2>₱ 32,650.00</h2>

                            <p>
                                <i class="fa fa-arrow-up"></i>
                                15% from last week
                            </p>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-icon">
                            <i class="fa-regular fa-user"></i>
                        </div>

                        <div class="report-info">
                            <h3>Total Customers</h3>
                            <h2>52</h2>

                            <p>
                                <i class="fa fa-arrow-up"></i>
                                8% from last week
                            </p>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="report-icon">
                            <i class="fa fa-box"></i>
                        </div>

                        <div class="report-info">
                            <h3>Total Products Sold</h3>
                            <h2>124</h2>

                            <p>
                                <i class="fa fa-arrow-up"></i>
                                10% from last week
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <section class="activity-section">
            <div class="max">
                <div class="activity-container">

                    <div class="activity-head">
                        <h2>Recent Activity</h2>
                    </div>

                    <div class="activity-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                                <tr>
                                    <td>Sep 22, 2025 10:24 AM</td>

                                    <td>
                                        <span class="activity-type order-type">
                                            Order
                                        </span>
                                    </td>

                                    <td class="details">
                                        AU-2026-0001245 - Juan Dela Cruz
                                    </td>

                                    <td>₱1,250.00</td>

                                    <td>
                                        <span class="activity-status pending">
                                            Pending
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Sep 21, 2025 08:18 PM</td>

                                    <td>
                                        <span class="activity-type order-type">
                                            Order
                                        </span>
                                    </td>

                                    <td class="details">
                                        AU-2026-0001244 - Maria Santos
                                    </td>

                                    <td>₱350.00</td>

                                    <td>
                                        <span class="activity-status processing">
                                            Processing
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Sep 20, 2025 01:06 PM</td>

                                    <td>
                                        <span class="activity-type order-type">
                                            Order
                                        </span>
                                    </td>

                                    <td class="details">
                                        AU-2026-0001243 - Renz Aguilar
                                    </td>

                                    <td>₱1,850.00</td>

                                    <td>
                                        <span class="activity-status shipped">
                                            Shipped
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Sep 19, 2025 06:33 PM</td>

                                    <td>
                                        <span class="activity-type order-type">
                                            Order
                                        </span>
                                    </td>

                                    <td class="details">
                                        AU-2026-0001242 - Leo Campos
                                    </td>

                                    <td>₱750.00</td>

                                    <td>
                                        <span class="activity-status delivered">
                                            Delivered
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Sep 18, 2025 02:17 PM</td>

                                    <td>
                                        <span class="activity-type order-type">
                                            Order
                                        </span>
                                    </td>

                                    <td class="details">
                                        AU-2026-0001241 - Jayron Reyes
                                    </td>

                                    <td>₱1,100.00</td>

                                    <td>
                                        <span class="activity-status cancelled">
                                            Cancelled
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Sep 17, 2025 04:12 PM</td>

                                    <td>
                                        <span class="activity-type product-type">
                                            Product
                                        </span>
                                    </td>

                                    <td class="details">
                                        Added new product - AU Cap
                                    </td>

                                    <td>₱350.00</td>

                                    <td>
                                        <span class="activity-status na">
                                            N/A
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Sep 16, 2025 09:45 AM</td>

                                    <td>
                                        <span class="activity-type user-type">
                                            User
                                        </span>
                                    </td>

                                    <td class="details">
                                        New user registered - Kyla Mendoza
                                    </td>

                                    <td>-</td>

                                    <td>
                                        <span class="activity-status na">
                                            N/A
                                        </span>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="activity-footer">
                        <span>Showing 7 recent activities</span>
                    </div>

                </div>
            </div>
        </section>
    </main>

    <script>
    const menuBtn = document.querySelector(".menu-btn");
    const closeBtn = document.querySelector(".close-btn");
    const sidebar = document.querySelector("aside");

    menuBtn.addEventListener("click", () => {
        sidebar.classList.add("open");
    });

    closeBtn.addEventListener("click", () => {
        sidebar.classList.remove("open");
    });

    document.addEventListener("click", (event) => {
        if (
            sidebar.classList.contains("open") &&
            !sidebar.contains(event.target) &&
            !menuBtn.contains(event.target)
        ) {
            sidebar.classList.remove("open");
        }
    });
</script>
</body>
</html>