<?php
    $page_title = 'Add Supplier';
    require_once('includes/load.php');
    
    $conn = oci_connect('myinventory', 'mypassword123', 'localhost/FREEPDB1');
    if (!$conn) {
        $e = oci_error();
        die("Connection failed: " . $e['message']);
    }

function display_session_message() {
    if (isset($_SESSION['error'])) {
        echo "<div style='background:#ffdddd; color:#a94442; border:1px solid #a94442; padding:10px; margin-bottom:10px; border-radius:4px;'>";
        echo htmlspecialchars($_SESSION['error']);
        echo "</div>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo "<div style='background:#ddffdd; color:#3c763d; border:1px solid #3c763d; padding:10px; margin-bottom:10px; border-radius:4px;'>";
        echo htmlspecialchars($_SESSION['success']);
        echo "</div>";
        unset($_SESSION['success']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (empty(trim($_POST['supplier-name']))) {
        $_SESSION['error'] = 'Please fill in the supplier name.';
        header('Location: add_supp.php');
        exit();
    }
    $s_name = trim($_POST['supplier-name']);
    // Check if supplier name already exists (case-insensitive)
    $check_sql = "SELECT COUNT(*) AS CNT FROM SUPPLIER WHERE LOWER(SUP_NAME) = LOWER(:s_name)";
    $check_stmt = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stmt, ':s_name', $s_name);
    oci_execute($check_stmt);
    $row = oci_fetch_assoc($check_stmt);
    oci_free_statement($check_stmt);
    if ($row['CNT'] > 0) {
        $_SESSION['error'] =  $s_name . ' already exists.';
        header('Location: add_supp.php');
        exit();
    }
    // Prepare INSERT using sequence for SUP_ID
    $insert_sql = "INSERT INTO SUPPLIER (SUP_ID, SUP_NAME) VALUES (sup_id_seq.NEXTVAL, :s_name)";
    $stmt = oci_parse($conn, $insert_sql);
    if (!$stmt) {
        $e = oci_error($conn);
        $_SESSION['error'] = 'SQL parse error: ' . $e['message'];
        header('Location: add_supp.php');
        exit();
    }
    oci_bind_by_name($stmt, ':s_name', $s_name);
    if (oci_execute($stmt)) {
        oci_free_statement($stmt);
        oci_close($conn);
        $_SESSION['success'] = $s_name . ' added successfully.';
        header('Location: add_supp.php');
        exit();
    } else {
        $e = oci_error($stmt);
        oci_free_statement($stmt);
        oci_close($conn);
        $_SESSION['error'] = 'INSERT error: ' . $e['message'];
        header('Location: add_supp.php');
        exit();
    }
}    
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="sidebar.css">
    <title>Customer Dashboard</title>
    
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

    
    <style>
        .home {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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

        .undo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 25px;
            margin: 0px 350px 0px 0px;
        } 
        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: auto;
            min-width: 350px;
            padding: 20px;
            border-radius: 25px;
            background-color: #3a3b3c;
            color: #ffffff;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select {
            width: 300px;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #181919;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color:rgb(39, 39, 39);
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
                            <i class='bx bxs-truck icon'></i>
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

    <section class="home">
        <div class="container">
            <?php display_session_message(); ?>
            <button class="undo" onclick="undoAction()">
                <i class='bx bx-undo icon'></i>
            </button>
            <h2><u>ADD SUPPLIER</u></h2><br>
            <form method="post" action="add_supp.php">
                <div class="form-group">
                    <label for="supplier-name">Supplier Name</label>
                    <input type="text" class="form-control" name="supplier-name" placeholder="Supplier Name" required>
                </div>
                <br>
                <button type="submit" class="btn btn-danger">Add Supplier</button>
                <br><br>
            </form>
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

        function undoAction() {
            window.location= "supplier.php";
        }  
    </script>

</body>
</html>