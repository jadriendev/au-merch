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
    <link rel="stylesheet" href="css/orders.css">
    <title>Orders | AU Merch</title>
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

                    <li class="active">
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

                    <li>
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
                        <span>Orders</span>
                        <span>Manage customer orders and update their status</span>
                    </h1>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="input-fields">
                    <div class="input">
                        <i class="fa fa-magnifying-glass"></i>
                        <input type="text" name="search" id="search" placeholder="Search order code or customer name...">
                    </div>

                    <div class="status-filter">
                        <select name="status" id="status">
                            <option value="All Status">All Status</option>
                            <option value="Available">Available</option>
                            <option value="Accessories">Unavailable</option>
                        </select>
                    </div>

                    <div class="categories">
                        <select name="categories" id="categories">
                            <option value="All Categories">All Categories</option>
                            <option value="Apparel">Apparel</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                    </div>
                </div>
            </div>
        </section>
 
        <section>
            <div class="max">
                <div class="section-container">
                    <div class="recent-order">
                        <div class="table-2">
                            <table>
                                <thead>
                                    <th class="left-radius">#</th>
                                    <th>Order Code</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th class="right-radius">Actions</th>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td class="order-2">1</td>
                                        <td class="color-change">AU-0000</td>
                                        <td>Apparel</td>
                                        <td>₱200,000</td>
                                        <td>GCash</td>
                                        <td>
                                            <div class="status order-delivered">
                                                Delivered
                                            </div>
                                        </td>

                                        <td>Sep 22, 2025 10:34 PM</td>

                                        <td>
                                            <div class="buttons">
                                                <button type="button" class="edit-btn" onclick="openEditStatus()">
                                                    Edit
                                                </button>

                                                <a href="delete.php">
                                                    Delete
                                                </a>
                                            </div>

                                            <div class="edit-status" id="editStatus">
                                                <div class="edit-status-box">
                                                    <h3>Edit Order Status</h3>

                                                    <select>
                                                        <option value="order-placed">Order Placed</option>
                                                        <option value="order-paid">Order Paid</option>
                                                        <option value="order-shipped-out">Order Shipped Out</option>
                                                        <option value="order-received">Order Received</option>
                                                        <option value="order-delivered">Order Delivered</option>
                                                        <option value="cancelled">Cancelled</option>
                                                    </select>

                                                    <div class="edit-buttons">
                                                        <button type="button" onclick="closeEditStatus()">Cancel</button>
                                                        <button type="button">Save</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="order-2">2</td>
                                        <td class="color-change">AU-0000</td>
                                        <td>Apparel</td>
                                        <td>₱200,000</td>
                                        <td>GCash</td>
                                        <td>
                                            <div class="status order-shipped-out">
                                                Order Shipped Out
                                            </div>
                                        </td>

                                        <td>Sep 22, 2025 10:34 PM</td>

                                        <td>
                                            <div class="buttons">
                                                <button type="button" class="edit-btn" onclick="openEditStatus()">
                                                    Edit
                                                </button>

                                                <a href="delete.php">
                                                    Delete
                                                </a>
                                            </div>

                                            <div class="edit-status" id="editStatus">
                                                <div class="edit-status-box">
                                                    <h3>Edit Order Status</h3>

                                                    <select>
                                                        <option value="order-placed">Order Placed</option>
                                                        <option value="order-paid">Order Paid</option>
                                                        <option value="order-shipped-out">Order Shipped Out</option>
                                                        <option value="order-received">Order Received</option>
                                                        <option value="order-delivered">Order Delivered</option>
                                                        <option value="cancelled">Cancelled</option>
                                                    </select>

                                                    <div class="edit-buttons">
                                                        <button type="button" onclick="closeEditStatus()">Cancel</button>
                                                        <button type="button">Save</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination">
                            <span class="showing-products">
                                Showing 1–8 of 8 products
                            </span>

                            <div class="pagination-buttons">
                                <a href="#" class="page-arrow">
                                    <i class="fa fa-chevron-left"></i>
                                </a>

                                <a href="#" class="page active-page">1</a>

                                <a href="#" class="page-arrow">
                                    <i class="fa fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>                        
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

    function openEditStatus() {
        document.getElementById("editStatus").style.display = "flex";
    }

    function closeEditStatus() {
        document.getElementById("editStatus").style.display = "none";
    }
</script>
</body>
</html>