<?php require_once('includes/load.php'); ?>

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

        .addButton {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #bbb;
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

        .container {
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

       
    .form-container {
        background-color:#696969;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .another-container {
        background-color: #696969;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,td {
            padding: 10px;
            text-align: center;
        }

        td {
            border: 2px solid #ffffff;
        }

        th {
            border-bottom: 2px solid #ffffff;
        }

        tr {
            border-bottom: 2px solid #ffffff;
        }

        .btn {
        display: inline-block;
        padding: 10px 20px;
        background-color: #181919; /* Blue background color */
        color: white; /* White text color */
        border: none;
        border-radius: 25px; /* Make it round */
        cursor: pointer;
        font-size: 13px;
        text-align: center;
        text-decoration: none;
    }

    .btn:hover {
        background-color: rgb(39, 39, 39); /* Darker blue on hover */
    }

    p.report-heading {
        font-size: 30px;
        font-weight: bold;
        border-bottom: 2px solid white; /* Blue underline */
        display: inline-block; /* Ensures the underline spans the text width */
        margin-bottom: 10px; /* Optional: Adjust spacing after the heading */
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
            <div class="row">
                <div class="col-md-6">
                    <table>
                        <tbody>
                            <div class="form-container">
                                <?php echo '<p class="report-heading">GENERATE REPORT</p>'; ?>
                                <form method="post" action="report_process.php" target="_blank">
                                    <div class="input-group">
                                        <label> Date Range</label><br>
                                        <input type="date" class="datepicker form-control" name="start-date" placeholder="From" required>
                                        <span class="input-group-addon"><i class='bx bx-chevron-right'></i></span>
                                        <input type="date" class="datepicker form-control" name="end-date" placeholder="To" required>
                                    </div>
                                    <br>
                                    <button type="submit" name="submit" class="btn btn-danger">Generate Report</button>
                                </form>
                            </div>
                        </tbody>
                    </table>
                </div>
            </div>
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