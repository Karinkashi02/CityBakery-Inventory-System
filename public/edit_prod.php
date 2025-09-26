<?php
require_once('includes/load.php');

$conn = oci_connect('myinventory', 'mypassword123', 'localhost/FREEPDB1');

$product_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['name'];
    $buy_price = $_POST['buy_price'];
    $sell_price = $_POST['sell_price'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];

    $update_sql = "UPDATE PRODUCT 
            SET PROD_NAME = :name, 
                PROD_BUY = :buy_price, 
                PROD_SALE = :sell_price, 
                PROD_QTY = :quantity, 
                PROD_DATE = SYSDATE,
                CAT_ID = :category
            WHERE PROD_ID = :id";

    $update_stmt = oci_parse($conn, $update_sql);

    oci_bind_by_name($update_stmt, ':name', $name);
    oci_bind_by_name($update_stmt, ':buy_price', $buy_price);
    oci_bind_by_name($update_stmt, ':sell_price', $sell_price);
    oci_bind_by_name($update_stmt, ':quantity', $quantity);
    oci_bind_by_name($update_stmt, ':category', $category);
    oci_bind_by_name($update_stmt, ':id', $product_id);

    if (oci_execute($update_stmt)) {
        $_SESSION['msg'] = ['s', 'Product updated successfully.'];
        oci_free_statement($update_stmt);
        oci_close($conn);
        header("Location: products.php");
        exit();
    } else {
            $e = oci_error($update_stmt);
            die('Oracle error: ' . $e['message']);
    }
}

    // Only fetch product if not submitting
    $sql = "SELECT * FROM PRODUCT WHERE PROD_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $product_id);
    oci_execute($stmt);
    $product = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$product) {
        $_SESSION['msg'] = ['d', 'Product not found.'];
        header("Location: products.php");
        exit();
    }

    // Fetch categories for the dropdown
    $cat_sql = "SELECT * FROM CATEGORY ORDER BY CAT_ID";
    $cat_stmt = oci_parse($conn, $cat_sql);
    oci_execute($cat_stmt);
    $categories = [];
    while ($row = oci_fetch_assoc($cat_stmt)) {
        $categories[] = $row;
    }
    oci_free_statement($cat_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS -->
    <link rel="stylesheet" href="sidebar.css">
    <title>Edit Product</title>
    
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

        .form-container {
            display: flex;
            flex-direction: column;
            margin-right: 20px;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: auto;
            width: 30%;
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
            min-width: 450px;
            padding: 20px;
            border-radius: 25px;
            background-color: #3a3b3c;
            color: #ffffff;
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
            <form method="post" action="edit_prod.php?id=<?php echo (int)$product['PROD_ID']; ?>">
                <h2 style="text-align: center;"><u>EDIT PRODUCT</u></h2><br>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo remove_junk($product['PROD_NAME']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="buy_price">Buying Price (1 Unit)</label>
                    <input type="number" step="0.01" class="form-control" name="buy_price" value="<?php echo number_format((float)$product['PROD_BUY'], 2, '.', ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="sell_price">Selling Price (1 Unit)</label>
                    <input type="number" step="0.01" class="form-control" name="sell_price" value="<?php echo number_format((float)$product['PROD_SALE'], 2, '.', ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" class="form-control" name="quantity" value="<?php echo remove_junk($product['PROD_QTY']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select class="form-control" name="category">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['CAT_ID']; ?>" <?php if ($category['CAT_ID'] == $product['CAT_ID']) echo 'selected'; ?> required>
                                <?php echo remove_junk($category['CAT_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <br>
                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                <br><br>
            </form>
            <?php oci_close($conn); ?>
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
            window.location="products.php";
        }
    </script>
</body>
</html>
