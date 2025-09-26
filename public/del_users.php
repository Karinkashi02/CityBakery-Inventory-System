<?php
session_start();
require_once('includes/load.php'); // If you have shared helper functions here

if (isset($_POST['delete_user'])) {
    // Simple validation
    $errors = [];
    if (empty($_POST['username'])) $errors[] = "Username is required.";
    if (empty($_POST['password'])) $errors[] = "Password is required.";

    if (empty($errors)) {
        // Clean inputs
        $username = (trim($_POST['username']));
        $password = trim($_POST['password']);

        // Connect to Oracle
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

        // Check if user exists
        $check_sql = "SELECT * FROM EMPLOYEE
                    WHERE USERNAME = :username
                    AND PASSWORD = :password";
        $check_stmt = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stmt, ":username", $username);
        oci_bind_by_name($check_stmt, ":password", $password);
        oci_execute($check_stmt);

        $row = oci_fetch_assoc($check_stmt);

        if ($row) {
            // Delete user
            $delete_sql = "DELETE FROM EMPLOYEE 
                        WHERE USERNAME = :username 
                        AND PASSWORD = :password";
            $delete_stmt = oci_parse($conn, $delete_sql);
            oci_bind_by_name($delete_stmt, ":username", $username);
            oci_bind_by_name($delete_stmt, ":password", $password);
            $result = oci_execute($delete_stmt);

            if (!$result) {
                $e = oci_error($delete_stmt);
                file_put_contents('delete_log.txt', "DELETE ERROR: " . $e['message'] . "\n", FILE_APPEND);
            }


            if ($result) {
                $_SESSION['msg'] = ['s', "{$username} has been deleted."];
                oci_free_statement($delete_stmt);
                oci_close($conn);
                header("Location: del_users.php");
                exit();
            } else {
                $e = oci_error($delete_stmt);
                $_SESSION['msg'] = ['d', "Failed to delete {$username}: " . $e['message']];
                oci_free_statement($delete_stmt);
                oci_close($conn);
                header("Location: del_users.php");
                exit();
            }
        } else {
            $_SESSION['msg'] = ['d', "Username and password do not match."];
            oci_free_statement($check_stmt);
            oci_close($conn);
            header("Location: del_users.php");
            exit();
        }
    } else {
        $_SESSION['msg'] = ['d', implode('<br>', $errors)];
        header("Location: del_users.php");
        exit();
    }
}

if (isset($_SESSION['msg'])) {
    $type = $_SESSION['msg'][0];
    $text = $_SESSION['msg'][1];
    echo "<div class='msg {$type}'>{$text}</div>";
    unset($_SESSION['msg']);
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar.css">
    <title>Inventory Dashboard</title>
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
            margin: 0px 380px 0px 0px;
        }         

        .container {
            display: flex;
            flex-direction: column;
            height: auto;
            width: 500px;
            overflow-y: auto;
            padding: 20px;
            border-radius: 25px;
            background-color: #3a3b3c;
            margin: 25px;
            color: #ffffff;
            align-items: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select {
            width: 350px;
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
            background: #0056b3;
        }

        .msg {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-size: 16px;
        }

        .msg-s {
            background: #d4edda;
            color: #155724;
        }

        .msg-d {
            background: #f8d7da;
            color: #721c24;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
                <span><img src="layout/cityBakersLogo.jpg" alt="city bakers logo" class="logo"></span>
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
                        <a href="home.php"><i class='bx bx-home-alt icon'></i>
                        <span class="text nav-text">Home</span></a>
                    </li>
                    <li>
                        <a href="products.php"><i class='bx bxs-package icon'></i>
                        <span class="text nav-text">Products</span></a>
                    </li>
                    <li class="nav-link">
                        <a href="sales.php"><i class='bx bx-bar-chart-alt-2 icon'></i>
                        <span class="text nav-text">Sales</span></a>
                    </li>
                    <li class="nav-link">
                        <a href="sales_report.php">
                            <i class='bx bxs-wallet-alt icon'></i>
                            <span class="text nav-text">Sales Report</span>
                        </a>
                    </li>
                    <li class="nav-link">
                        <a href="users.php"><i class='bx bx-user-plus icon'></i>
                        <span class="text nav-text">Users</span></a>
                    </li>
                    <li>
                        <a href="about.html"><i class='bx bxs-info-circle icon'></i>
                        <span class="text nav-text">About Us</span></a>
                    </li>
                </ul>
            </div>
            <div class="bottom-content">
                <li><a href="logout.php"><i class='bx bx-log-out icon'></i><span class="text nav-text">Logout</span></a></li>
                <li class="mode">
                    <div class="sun-moon"><i class='bx bx-moon icon moon'></i><i class='bx bx-sun icon sun'></i></div>
                    <span class="mode-text text">Dark mode</span>
                    <div class="toggle-switch"><span class="switch"></span></div>
                </li>
            </div>
        </div>
    </nav>

    <section class="home">
        <div class="container">
            <button class="undo" onclick="undoAction()">
                <i class='bx bx-undo icon'></i>
            </button>            
            <div class="login-box">
                <?php echo display_msg($msg); ?>
                <form action="del_users.php" method="post">
                    <div class="user-box">
                        <h2 style="text-align: center;"><u>DELETE USER</u></h2>
                        <br>
                        <label>Username</label>
                        <input type="text" name="username" required="">
                    </div>
                    <div class="user-box">
                        <label>Password</label>
                        <input type="password" name="password" required="">
                        <br><br>
                    </div>
                    <center>
                        <button type="submit" name="delete_user">CONFIRM</button>
                    </center>
                    <br>
                </form>
            </div>
        </div>

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
        window.location="users.php";
        }        
    </script>
</body>
</html>
