<?php
session_start(); // Start the session

// Redirect to login page if not logged in
if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit();
}

// Get session variables
$group_id = $_SESSION['group_id'];
$current_user_id = $_SESSION['emp_id'];

// Oracle connection parameters
$username = "myinventory";
$password = "mypassword123";
$connStr = "(DESCRIPTION =
    (ADDRESS_LIST =
        (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
    )
    (CONNECT_DATA =
        (SERVICE_NAME = FREEPDB1)
    )
)";

// Connect to Oracle
$conn = oci_connect($username, $password, $connStr);
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

// Fetch EMPLOYEE records
$sqlEmp = "SELECT * FROM EMPLOYEE";
$stmtEmp = oci_parse($conn, $sqlEmp);
oci_execute($stmtEmp);

$users = [];
while ($row = oci_fetch_assoc($stmtEmp)) {
    if ($row['EMP_ID'] == $current_user_id) {
        array_unshift($users, $row);
    } else {
        $users[] = $row;
    }
}
oci_free_statement($stmtEmp);

// Fetch USER_GROUPS records
$sqlGroups = "SELECT * FROM USER_GROUPS";
$stmtGroups = oci_parse($conn, $sqlGroups);
oci_execute($stmtGroups);

$groups = [];
while ($row = oci_fetch_assoc($stmtGroups)) {
    $groups[] = $row;
}
oci_free_statement($stmtGroups);

oci_close($conn);
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
        .icon {
            min-width: 30px;
            border-radius: 6px;
            height: 100%;
            font-size: 20px;
            color: #ffffff;
        }

        h2 {
            border-bottom: 5px solid #ffffff;
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

        .home {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .page {
            display: flex;
            flex-direction: column;
            height: auto;
            width: auto;
            overflow-y: auto;
            padding: 20px;
            border-radius: 25px;
            background-color: #3a3b3c;
            margin: 25px;
            color: #ffffff;
            align-items: center;
        }

        .user-details {
            margin: 10px;
        }

        .user-actions {
            padding: 10px;
        }

        .edit-button{
            margin: 7px;
            background-color: #ffffff;
            border: none;
            color: #3a3b3c;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
            border-radius: 15px;
        }

        .add-button {
            margin: 7px;
            background-color: #181919;
            border: none;
            color:rgb(255, 255, 255);
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
            border-radius: 15px;
        }

        .delete-button {
            margin: 7px;
            background-color: #181919;
            border: none;
            color:rgb(253, 254, 255);
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
            border-radius: 15px;
        }

        select {
            margin: 7px;
            background-color: #181919;
            border: none;
            color:rgb(255, 255, 255);
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
            border-radius: 25px;
        }

        button:hover, select:hover {
            background: rgb(39, 39, 39);
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
                            <span class="text nav-text">Add Supplier</span>
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
        <div class="page">
            <h2>User Details</h2>
            <div class="user-details">
            <?php
                // Connect to Oracle
                $username = "myinventory";
                $password = "mypassword123";

                $connStr = "(DESCRIPTION =
                    (ADDRESS_LIST =
                        (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
                    )
                    (CONNECT_DATA =
                        (SERVICE_NAME = FREEPDB1)
                    )
                )";
                $conn = oci_connect($username, $password, $connStr);
                if (!$conn) {
                    $e = oci_error();
                    die("Connection failed: " . $e['message']);
                }

                // Prepare statement
                $sql = "SELECT * FROM EMPLOYEE";
                $stmt = oci_parse($conn, $sql);
                oci_execute($stmt);

                $users = [];
                while ($row = oci_fetch_assoc($stmt)) {
                    if ($row['EMP_ID'] == $current_user_id) {
                        array_unshift($users, $row);
                    } else {
                        $users[] = $row;
                    }
                }

                oci_free_statement($stmt);
                oci_close($conn);

            ?>

            <script>
                const users = <?php echo json_encode($users); ?>;
                const currentUserId = <?php echo $current_user_id; ?>;
            </script>
        </div>

            <div class="user-details">
                <p><strong>Name :</strong> <span id="user-name"></span></p>
                <p><strong>Role :</strong> <span id="user-role"></span></p>
            </div>
            <div class="user-actions">
                <?php if ($group_id == 1): ?>
                    <button class="add-button" onclick="location.href='add_users.php'">
                        ADD
                    </button>
                    <button class="delete-button" onclick="location.href='del_users.php'">
                        DELETE
                    </button>
                <?php endif; ?>
            </div>
            <div class="user-list">
                <label for="users">Select a user:</label>
                <select id="users" name="users">
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['EMP_ID']; ?>" <?php if ($user['EMP_ID'] == $current_user_id) echo 'selected'; ?>>
                            <?php echo $user['EMP_NAME']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <script>
            document.getElementById('users').addEventListener('change', function() {
                const selectedUserId = this.value;
                const user = users.find(u => u.EMP_ID == selectedUserId);

                if (user) {
                    document.getElementById('user-name').textContent = user.EMP_NAME;
                    document.getElementById('user-role').textContent = user.GROUP_ID == 1 ? 'Manager' : 'Employee';
                }
            });

            // Trigger on load
            document.getElementById('users').dispatchEvent(new Event('change'));
        </script>
    </section>

    <script>
        // Populate user details when selection changes
        document.getElementById('users').addEventListener('change', function() {
            const selectedUserId = this.value;
            const user = users.find(u => u.EMP_ID == selectedUserId);
            if (user) {
                document.getElementById('user-name').textContent = user.EMP_NAME;
                document.getElementById('user-role').textContent = user.GROUP_ID == 1 ? 'Manager' : 'Employee';
            }
        });
        // Trigger on load
        document.getElementById('users').dispatchEvent(new Event('change'));
    </script>

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
