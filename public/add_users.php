<?php
$page_title = 'Add User';
require_once('includes/load.php');
session_start();

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

// Load groups from Oracle table "GROUPS"
$groups = [];
$group_sql = "SELECT GROUP_ID, GROUP_NAME FROM GROUPS ORDER BY GROUP_ID";
$group_stmt = oci_parse($conn, $group_sql);
oci_execute($group_stmt);
while ($row = oci_fetch_assoc($group_stmt)) {
    $groups[] = $row;
}
oci_free_statement($group_stmt);

if (isset($_POST['add_user'])) {
    $errors = [];

    if (empty($errors)) {
        $name = trim($_POST['full-name']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $groupid = (int)$_POST['group_id'];

        // Check if full name or username already exists
        $check_sql = "SELECT * FROM EMPLOYEE WHERE NAME = :name OR USERNAME = :username";
        $check_stmt = oci_parse($conn, $check_sql);
        oci_bind_by_name($check_stmt, ":name", $name);
        oci_bind_by_name($check_stmt, ":username", $username);
        oci_execute($check_stmt);

        if ($row = oci_fetch_assoc($check_stmt)) {
            $_SESSION['msg'] = ['d', 'Full Name or Username already exists!'];
            header("Location: add_users.php");
            exit();
        }

        // Insert user
        $insert_sql = "INSERT INTO EMPLOYEE (EMP_NAME, USERNAME, PASSWORD, GROUP_ID) VALUES (:name, :username, :password, :groupid)";
        $insert_stmt = oci_parse($conn, $insert_sql);
        oci_bind_by_name($insert_stmt, ":name", $name);
        oci_bind_by_name($insert_stmt, ":username", $username);
        oci_bind_by_name($insert_stmt, ":password", $password);
        oci_bind_by_name($insert_stmt, ":groupid", $groupid);
        $result = oci_execute($insert_stmt);

        if ($result) {
            $_SESSION['msg'] = ['s', 'User account has been created!'];
            oci_free_statement($insert_stmt);
            oci_close($conn);
            header("Location: add_users.php");
            exit();
        } else {
            $e = oci_error($insert_stmt);
            $_SESSION['msg'] = ['d', 'Failed to create account: ' . $e['message']];
            oci_free_statement($insert_stmt);
            oci_close($conn);
            header("Location: add_users.php");
            exit();
        }
    } else {
        $_SESSION['msg'] = ['d', implode('<br>', $errors)];
        header("Location: add_users.php");
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
    <!-- CSS -->
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
            width: 220px;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .undo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 25px;
            margin: 0px 350px 0px 0px;
        }         

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
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
            background:rgb(39, 39, 39);
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
                    
                    <li class="nav-link">
                        <a href="users.php">
                            <i class='bx bx-user-plus icon' ></i>
                            <span class="text nav-text">Users</span>
                        </a>
                    </li>

                    <li class="nav-link">
                        <a href="customer.php">
                            <i class='bx bx-user-circle icon' ></i>
                            <span class="text nav-text">Add Customer</span>
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
        <div class="container">
            <button class="undo" onclick="undoAction()">
                <i class='bx bx-undo icon'></i>
            </button>            
            <?php echo display_msg($msg); ?>
            <form method="post" action="add_users.php">
                <div class="form-group">
                    <label for="full-name">Name</label>
                    <input type="text" class="form-control" name="full-name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <label for="level">User Role</label>
                    <select class="form-control" name="group_id">
                        <?php foreach ($groups as $group) : ?>
                            <option value="<?php echo $group['GROUP_ID']; ?>">
                            <?php echo ucwords($group['GROUP_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                </select>
                <br><br>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
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

