<?php
$page_title = 'Add Supplier';
require_once('includes/load.php');
    
// Start session and check permissions
session_start();
if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit();
}
$group_id = $_SESSION['group_id'];

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
    die("Connection failed: " . htmlspecialchars($e['message']));
}

// Build query
$searchInput = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchInput = trim($_GET['search']);
    $sql = "SELECT S.SUP_ID , S.SUP_NAME, COUNT(P.PROD_ID) AS TOTAL_SUPPLIES
            FROM SUPPLIER S LEFT JOIN PRODUCT P ON S.SUP_ID = P.SUP_ID
            WHERE LOWER(S.SUP_NAME) LIKE '%' || :search || '%'
            OR LOWER(S.SUP_ID) LIKE '%' || :search || '%'
            GROUP BY S.SUP_ID, S.SUP_NAME";
    $stmt = oci_parse($conn, $sql);
    $searchLower = strtolower($searchInput);
    oci_bind_by_name($stmt, ':search', $searchLower);
} else {
    $sql = "SELECT S.SUP_ID , S.SUP_NAME, COUNT(P.PROD_ID) AS TOTAL_SUPPLIES
            FROM SUPPLIER S LEFT JOIN PRODUCT P
            ON S.SUP_ID = P.SUP_ID
            GROUP BY S.SUP_ID, S.SUP_NAME";
    $stmt = oci_parse($conn, $sql);
}

oci_execute($stmt);
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
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* ... */

        .search {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 500px;
            margin-right: 475px;
        }

        .search-icon {
            margin-right: 10px;
            color: white;
        }

        .search-input {
            padding: 10px;
            border-radius: 5px;
            border: none;
            outline: none;
            color: black;
            width: 100%;
        }

        .icon {
            min-width: 30px;
            border-radius: 6px;
            height: 100%;
            font-size: 20px;
            color: #ffffff;
        }

        .logo {
            display: flex;
            flex-direction: row;
            align-items: center;
            width: 50px;
            height: 50px;
            background-color: #181919;
            border-radius: 50%;
            margin-right: 10px;
        }

        .addButton {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #3a3b3c;
            border: none;
            color: #ffffff; 
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .addButton:hover {
            background-color:rgb(39, 39, 39);
        }

        .icon:hover {
            color: #ffffff;
            transition: all 0.5s ease;
        }

        .home {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            display: flex;
            flex-direction: column;
            max-height: 600px;
            width: auto;
            overflow-y: auto;
            padding: 20px;
            border-radius: 25px;
            background-color: #3a3b3c;
            margin: 25px;
            color: white;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
        }

        th,td {
            padding: 10px;
            text-align: center;
        }

        th {
            border-bottom: 2px solid #ffffff;
        }

        tr {
            border-bottom: 2px solid #ffffff;
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
                        <i class='bx bx-home-alt icon'></i>
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
                        <i class='bx bxs-truck icon'></i>
                        <span class="text nav-text">Supplier</span>
                    </a>
                </li>
                <li class="nav-link">
                    <a href="sales.php">
                        <i class='bx bx-bar-chart-alt-2 icon'></i>
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
                        <i class='bx bx-user-plus icon'></i>
                        <span class="text nav-text">Users</span>
                    </a>
                </li> -->
                <li class="nav-link">
                    <a href="customer.php">
                        <i class='bx bx-user-circle icon'></i>
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
                    <i class='bx bx-log-out icon'></i>
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

    <section class="home">
        <?php if ($group_id == 1): ?>
            <button class="addButton" onclick="window.location.href='add_supp.php'">
                <b>Add Supplier</b>
            </button>
        <?php endif; ?>
        <form action="supplier.php" method="get" id="searchForm">
        <div class="search">
            <span class="search-icon material-symbols-outlined">search</span>
            <input class="search-input" type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchInput); ?>" id="searchInput">
        </div>
        </form>

        <div class="container">
            <?php echo display_msg($msg); ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Total Supplies</th>
                        <?php if ($group_id == 1): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php 
                       echo '<h2 style="font-weight: bold;">SUPPLIER LIST</h2>';
                        while ($row = oci_fetch_assoc($stmt)) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['SUP_NAME']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['TOTAL_SUPPLIES']) . '</td>';
                            echo '<td>';
                                if ($group_id == 1) {
                                    echo '<a href="edit_supp.php?id=' . urlencode($row['SUP_ID']) . '"><i class="bx bx-edit-alt icon"></i></a>';
                                } else {
                                    echo '';
                                }
                            echo '</td>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        oci_free_statement($stmt);
                        oci_close($conn);
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