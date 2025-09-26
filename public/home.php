<?php 
require_once('includes/load.php');

// Oracle connection
$db_user = "myinventory";
$db_pass = "mypassword123";

$connStr = "(DESCRIPTION =
    (ADDRESS_LIST =
        (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
    )
    (CONNECT_DATA =
        (SERVICE_NAME = FREEPDB1)
    )
)";

$conn = oci_connect($db_user, $db_pass, $connStr);
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

// Fetch inventory data
$query = "SELECT P.PROD_NAME, P.PROD_QTY, C.CAT_NAME FROM PRODUCT P ,CATEGORY C
            WHERE P.CAT_ID = C.CAT_ID 
            ORDER BY P.PROD_QTY ASC";
$stid = oci_parse($conn, $query);
oci_execute($stid);


// Fetch total items
$query1 = "SELECT COUNT(*) AS TOTAL_ITEMS FROM PRODUCT";
$stid1 = oci_parse($conn, $query1);
oci_execute($stid1);
$TotalItems = oci_fetch_assoc($stid1)['TOTAL_ITEMS'];

// Fetch low stock count
$query2 = "SELECT COUNT(PROD_QTY) AS LOW_STOCK_COUNT FROM PRODUCT WHERE PROD_QTY <= 5";
$stid2 = oci_parse($conn, $query2);
oci_execute($stid2);
$lowStock = oci_fetch_assoc($stid2)['LOW_STOCK_COUNT'];

// Fetch categories count
$query3 = "SELECT COUNT(DISTINCT(CAT_ID)) AS TOTAL_CATEGORIES FROM PRODUCT";
$stid3 = oci_parse($conn, $query3);
oci_execute($stid3);
$totalCategories = oci_fetch_assoc($stid3)['TOTAL_CATEGORIES'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS -->
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="home_style.css">
    <title>Inventory Dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <style>
         body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            flex-direction: column;
            max-height: 600px;
            width: 1000px;
            padding: 30px;
            border-radius: 25px;
            background-color: #3a3b3c;
            margin: 30px;
            color: white;
            align-items: center;
        }
          table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            color: white;
            text-align: center;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        thead th {
            text-align: center;
        }

        tr {
            border-bottom: 2px solid #ffffff;
            
           
        }

         h1 {
            text-align: center;
            margin-bottom: 24px;
        }
        .stats {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 20px;
        }
        .stat-card {
            background:rgb(84, 84, 84);
            border-radius: 8px;
            padding: 20px 30px;
            text-align: center;
            min-width: 140px;
            width: 200px;
            box-shadow: 0px 5px 5px 5px rgba(255, 255, 255, 0.07);
        }
        .stat-card h2 {
            margin: 0;
            font-size: 2.3em;
        }
        .stat-card span {
            font-size: 1.1em;
            color: #666;
        }
        tr.low-stock td {
            color:rgb(211, 32, 0);
            font-weight: bold;
        }
        .table-scroll {
            max-height: 320px; /* Adjust as needed for 6 rows */
            overflow-y: auto;
            overflow-x: hidden;
            width: 100%;
            margin: 0 auto;
            display: block;
            scrollbar-width: thin; /* For Firefox */
        }
        .table-scroll::-webkit-scrollbar {
            width: 8px; /* For Chrome/Safari */
        }
        .table-scroll::-webkit-scrollbar-track {
            background: #3a3b3c;
        }
        .table-scroll::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
        }
        .table-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <nav class="sidebar close">
        <header>
            <div class="image-text">
            <span><img src="layout\cityBakersLogo.jpg" alt="city bakers logo" class="logo"></span>
                <div class="text logo-text">
                    <span class="name">CITY BAKERY</span>
                    <span class="profession">Inventory System</span>
                </div>
            </div>

            <i class='bx bx-chevron-right toggle'></i>
        </header>

        <div class="menu-bar">
            <div class="menu">

                <ul class="menu-links">
                <li class="nav-link">
                        <a href="home.php">
                            <i class='bx bx-home-alt icon' ></i>
                            <span class="text nav-text">Home</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="products.php">
                            <i class='bx bxs-package icon'></i>
                            <span class="text nav-text">Products</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="supplier.php">
                            <i class='bx bxs-truck icon' ></i>
                            <span class="text nav-text">Supplier</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="sales.php">
                            <i class='bx bx-bar-chart-alt-2 icon' ></i>
                            <span class="text nav-text">Sales</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="sales_report.php">
                            <i class='bx bxs-wallet-alt icon'></i>
                            <span class="text nav-text">Sales Report</span>
                        </a>
                    </li>
                    
                    <!-- <li class="nav-link">
                        <a href="users.php">
                            <i class='bx bx-user-plus icon' ></i>
                            <span class="text nav-text">Users</span>
                        </a>
                    </li> -->

                    <li class="nav-link">
                        <a href="customer.php">
                            <i class='bx bx-user-circle icon' ></i>
                            <span class="text nav-text">Customer</span>
                        </a>
                    </li>                    

                    <li class="nav-link">
                        <a href="about.html">
                        <i class='bx bxs-info-circle icon'></i>
                            <span class="text nav-text">About Us</span>
                        </a>
                    </li>

                </ul>
            </div>

            <div class="bottom-content">
                <li class="">
                    <a href="logout.php">
                        <i class='bx bx-log-out icon' ></i>
                        <span class="text nav-text">Logout</span>
                    </a>
                </li>

                <li class="mode">
                    <div class="sun-moon">
                        <i class='bx bx-moon icon moon'></i>
                        <i class='bx bx-sun icon sun'></i>
                    </div>
                    <span class="mode-text text">Dark mode</span>

                    <div class="toggle-switch">
                        <span class="switch"></span>
                    </div>
                </li>
                
            </div>
        </div>

    </nav>

    <!--animation loader-->
   <!-- <section class="home">
        <div class="container">
            <div class="loader">
                <div class="box box0">
                    <div></div>
                </div>
                <div class="box box1">
                    <div></div>
                </div>
                <div class="box box2">
                    <div></div>
                </div>
                <div class="box box3">
                    <div></div>
                </div>
                <div class="box box4">
                    <div></div>
                </div>
                <div class="box box5">
                    <div></div>
                </div>
                <div class="box box6">
                    <div></div>
                </div>
                <div class="box box7">
                    <div></div>
                </div>
                <div class="ground">
                    <div></div>
                </div>
                </div>
        <div class="simple-home" id="simpleHome" style="display:none; opacity:0;">
                <h1 style="color:#ff7200; text-align:center; margin-bottom: 1rem;">Welcome to City Bakery Inventory Dashboard</h1>
                <p style="text-align:center; font-size:1.2rem; color:#333;">
                    Manage your products, sales, and users efficiently.<br>
                    Use the sidebar to navigate through the system.
                </p>
            </div>
        </div>
    </section>-->

    <section class="home">
        <div class="container">
    <h1>Inventory Statistics</h1>
    <div class="stats">
        <div class="stat-card">
            <span style="color:white;">Total Items :</span>
            <h2 id="totalItems"><?php echo $TotalItems ?></h2>
            
        </div>
        <div class="stat-card">
            <span style="color:white;">Categories :</span>
            <h2 id="totalCategories"><?php echo $totalCategories ?></h2>
        </div>
        <div class="stat-card">
            <span style="color:white;">Low Stock :</span>
            <h2 id="lowStockCount"><?php echo $lowStock ?></h2>
        </div>
    </div>
    <h2 style="margin-bottom:10px;">Inventory List</h2>
    <table style="table-layout: fixed; width: 100%; TEXT-ALIGN: center;">
    <thead>
        <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Stock</th>
        </tr>
    </thead>
</table>
    <div class="table-scroll">
   <table> 
        <tbody id="inventoryTable" style="overflow-y: auto; max-height: 320px; TEXT-ALIGN: center;">
        <?php
        // Fetch and display inventory data
        while ($row = oci_fetch_assoc($stid)) {
            $name = htmlspecialchars($row['PROD_NAME']);
            $category = htmlspecialchars($row['CAT_NAME']);
            $stock = htmlspecialchars($row['PROD_QTY']);
            $lowStockClass = ($stock <= 5) ? 'low-stock' : '';
            echo "<tr class='$lowStockClass'>
                <td>&nbsp$name</td>
                <td>&nbsp&nbsp&nbsp$category</td>
                <td>&nbsp&nbsp&nbsp&nbsp$stock</td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
        
</div>
        </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const body = document.querySelector('body');
        const sidebar = document.querySelector('nav.sidebar');
        const toggle = document.querySelector('.toggle');
        const modeSwitch = document.querySelector('.toggle-switch');
        const modeText = document.querySelector('.mode-text');

        // 1️⃣ Sidebar: Load state
        const isSidebarClosed = localStorage.getItem('sidebarClosed');
        if (isSidebarClosed === 'false') {
            sidebar.classList.remove('close');
        } else {
            sidebar.classList.add('close');
        }

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('close');
            localStorage.setItem('sidebarClosed', sidebar.classList.contains('close'));
        });

        // 2️⃣ Dark Mode: Load theme state
        const darkMode = localStorage.getItem('darkMode');
        if (darkMode === 'enabled') {
            body.classList.add('dark');
            modeText.innerText = "Light mode";
        }

        // 3️⃣ Toggle dark/light on switch click
        modeSwitch.addEventListener('click', () => {
            body.classList.toggle('dark');
            const isDark = body.classList.contains('dark');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            modeText.innerText = isDark ? "Light mode" : "Dark mode";
        });

        });
    </script>

</body>
</html>