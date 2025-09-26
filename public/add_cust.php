<?php
    $page_title = 'Add Product';
    require_once('includes/load.php');
    
    $conn = oci_connect('myinventory', 'mypassword123', 'localhost/FREEPDB1');
    if (!$conn) {
        $e = oci_error();
        die("Connection failed: " . $e['message']);
    }


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    
    echo "<pre>Step 1: POST received</pre>";
    // Validate fields
    $req_fields = ['customer-name', 'customer-ic'];
    foreach ($req_fields as $field) {
        if (empty(trim($_POST[$field]))) {
            echo "<pre>Step 1.1: Field '$field' is empty</pre>";
            $session->msg('d', 'Please fill in all fields.');
            redirect('add_cust.php', false);
        }
    }

     echo "<pre>Step 2: Validation passed</pre>";

    $c_name = trim($_POST['customer-name']);
    $c_id = (int)$_POST['customer-ic'];

    // Prepare INSERT
    $insert_sql = "INSERT INTO CUSTOMER (
        CUST_ID, CUST_NAME
    ) VALUES (
        :c_id, :c_name)";

    $stmt = oci_parse($conn, $insert_sql);
        if (!$stmt) {
        $e = oci_error($conn);
        echo "<pre>Step 3.1: SQL parse error: " . $e['message'] . "</pre>";
        exit;
    }

    oci_bind_by_name($stmt, ':c_name', $c_name);
    oci_bind_by_name($stmt, ':c_id', $c_id);

    $emp_id = (int)$_SESSION['emp_id'];
    oci_bind_by_name($stmt, ':emp_id', $emp_id);
    echo "<pre>EMP_ID in session: " . print_r($_SESSION['user'], true) . "</pre>";


    echo "<pre>Step 4: Bindings done</pre>";

    if (oci_execute($stmt)) {
        echo "<pre>Step 5: INSERT success</pre>";
        oci_free_statement($stmt);
        oci_close($conn);
        $session->msg('s', 'Customer added successfully.');
        redirect('customer.php', false);
    } else {
        $e = oci_error($stmt);
        echo "<pre>Step 5.1: INSERT error: " . $e['message'] . "</pre>";
        oci_free_statement($stmt);
        oci_close($conn);
        exit;
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
            <button class="undo" onclick="undoAction()">
                <i class='bx bx-undo icon'></i>
            </button>
            <h2><u>ADD CUSTOMER</u></h2><br>
            <form method="post" action="add_cust.php">
                <div class="form-group">
                    <label for="customer-name">Customer Name</label>
                    <input type="text" class="form-control" name="customer-name" placeholder="Customer Name" required>
                </div>
                <div class="form-group">
                    <label for="customer-ic">Customer IC (without '-')</label>
                    <input type="number" class="form-control" name="customer-ic" placeholder="Customer IC" required>
                </div>
                <br>
                <button type="submit" class="btn btn-danger">Add Customer</button>
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
        window.location="customer.php";
        }
    </script>

</body>
</html>