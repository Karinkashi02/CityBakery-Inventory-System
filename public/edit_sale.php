<?php
require_once('includes/load.php');
session_start(); // Make sure session is started for $_SESSION messages

// Oracle connection
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

$is_edit_mode = false;
$sale = null;

if(isset($_GET['sale_id'])) {
    $is_edit_mode = true;
    $sale_id = $_GET['sale_id'];

    $sale_sql = "SELECT 
                    s.SALE_ID,
                    s.SALE_DATE,
                    s.SALE_PRICE,
                    s.CUST_ID,
                    sp.PROD_ID,
                    sp.PROD_NAME,
                    sp.QUANTITY AS SALE_QTY,
                    sp.PROD_SALE_PRICE,
                    c.CUST_NAME
                FROM SALES s
                JOIN SALES_PRODUCT sp ON s.SALE_ID = sp.SALE_ID
                LEFT JOIN CUSTOMER c ON s.CUST_ID = c.CUST_ID
                WHERE s.SALE_ID = :sale_id";

    $sale_stmt = oci_parse($conn, $sale_sql);
    oci_bind_by_name($sale_stmt, ":sale_id", $sale_id);
    oci_execute($sale_stmt);
    $sale = oci_fetch_assoc($sale_stmt);
    oci_free_statement($sale_stmt);

    if (!$sale) {
        echo '<script>alert("Sale record not found.");window.location="sales.php";</script>';
        exit();
    }

    $original_quantity = $sale['SALE_QTY'];
    $cust_name = $sale['CUST_NAME'];
}

if(isset($_POST['update_sale'])) {
    $sale_id = $_POST['sale_id'];
    $prod_id = $_POST['product-id'];
    $quantity = (int)$_POST['qty'];
    $date = $_POST['date'];
    $emp_id = $_SESSION['emp_id'];

    // Get the original sale record
    $get_original_sql = "SELECT sp.QUANTITY AS SALE_QTY, sp.PROD_ID, s.CUST_ID 
                         FROM SALES s
                         JOIN SALES_PRODUCT sp ON s.SALE_ID = sp.SALE_ID
                         WHERE s.SALE_ID = :sale_id";
    $get_original_stmt = oci_parse($conn, $get_original_sql);
    oci_bind_by_name($get_original_stmt, ":sale_id", $sale_id);
    oci_execute($get_original_stmt);
    $original_sale = oci_fetch_assoc($get_original_stmt);

    if (!$original_sale) {
        $_SESSION['error'] = "Original sale record not found";
        header("Location: edit_sale.php?sale_id=".$sale_id);
        exit();
    }

    $original_qty = $original_sale['SALE_QTY'];
    $original_prod_id = $original_sale['PROD_ID'];
    $cust_id = $original_sale['CUST_ID'];

    // Check if product exists and has enough stock
    $product_sql = "SELECT PROD_QTY, PROD_SALE FROM PRODUCT WHERE PROD_ID = :prod_id";
    $product_stmt = oci_parse($conn, $product_sql);
    oci_bind_by_name($product_stmt, ":prod_id", $prod_id);
    oci_execute($product_stmt);
    $product = oci_fetch_assoc($product_stmt);

    if (!$product) {
        $_SESSION['error'] = "Product not found";
        header("Location: edit_sale.php?sale_id=".$sale_id);
        exit();
    }

    // Calculate available quantity (current stock + original sale quantity)
    $available_qty = $product['PROD_QTY'] + $original_qty;
    
    // Validate new quantity
    if ($quantity <= 0) {
        $_SESSION['error'] = "Quantity must be greater than 0";
        header("Location: edit_sale.php?sale_id=".$sale_id);
        exit();
    }

    if ($quantity > $available_qty) {
        $_SESSION['error'] = "Not enough quantity in stock. Only ".$available_qty." units available (including returned quantity from this sale).";
        header("Location: edit_sale.php?sale_id=".$sale_id);
        exit();
    }

    // Calculate price
    $price = $quantity * $product['PROD_SALE'];

    // Begin transaction
    oci_execute(oci_parse($conn, "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE"));

    try {
        // 1. Update SALES table
        $update_sales_sql = "UPDATE SALES SET 
                            SALE_DATE = TO_DATE(:sale_date, 'YYYY-MM-DD'),
                            SALE_PRICE = :total_price
                            WHERE SALE_ID = :sale_id";
        $update_sales_stmt = oci_parse($conn, $update_sales_sql);
        oci_bind_by_name($update_sales_stmt, ":sale_date", $date);
        oci_bind_by_name($update_sales_stmt, ":total_price", $price);
        oci_bind_by_name($update_sales_stmt, ":sale_id", $sale_id);

        if (!oci_execute($update_sales_stmt)) {
            throw new Exception("Failed to update SALES table");
        }

        // 2. Update SALES_PRODUCT table
        $update_sp_sql = "UPDATE SALES_PRODUCT SET
                         PROD_ID = :prod_id,
                         QUANTITY = :quantity,
                         PROD_SALE_PRICE = :prod_sale_price
                         WHERE SALE_ID = :sale_id";
        $update_sp_stmt = oci_parse($conn, $update_sp_sql);
        oci_bind_by_name($update_sp_stmt, ":prod_id", $prod_id);
        oci_bind_by_name($update_sp_stmt, ":quantity", $quantity);
        oci_bind_by_name($update_sp_stmt, ":prod_sale_price", $product['PROD_SALE']);
        oci_bind_by_name($update_sp_stmt, ":sale_id", $sale_id);

        if (!oci_execute($update_sp_stmt)) {
            throw new Exception("Failed to update SALES_PRODUCT table");
        }

        // 3. Update inventory quantities
        if ($original_prod_id == $prod_id) {
            $qty_diff = $quantity - $original_qty;

            $update_inventory_sql = "UPDATE PRODUCT SET 
                                   PROD_QTY = PROD_QTY - :qty_diff
                                   WHERE PROD_ID = :prod_id";
            $update_inventory_stmt = oci_parse($conn, $update_inventory_sql);
            oci_bind_by_name($update_inventory_stmt, ":qty_diff", $qty_diff);
            oci_bind_by_name($update_inventory_stmt, ":prod_id", $prod_id);

            if (!oci_execute($update_inventory_stmt)) {
                throw new Exception("Failed to update inventory for same product");
            }
        } else {
            // Return to old product
            $return_qty_sql = "UPDATE PRODUCT SET 
                             PROD_QTY = PROD_QTY + :original_qty
                             WHERE PROD_ID = :original_prod_id";
            $return_qty_stmt = oci_parse($conn, $return_qty_sql);
            oci_bind_by_name($return_qty_stmt, ":original_qty", $original_qty);
            oci_bind_by_name($return_qty_stmt, ":original_prod_id", $original_prod_id);

            if (!oci_execute($return_qty_stmt)) {
                throw new Exception("Failed to return quantity to original product");
            }

            // Deduct from new product
            $deduct_qty_sql = "UPDATE PRODUCT SET 
                             PROD_QTY = PROD_QTY - :quantity
                             WHERE PROD_ID = :prod_id";
            $deduct_qty_stmt = oci_parse($conn, $deduct_qty_sql);
            oci_bind_by_name($deduct_qty_stmt, ":quantity", $quantity);
            oci_bind_by_name($deduct_qty_stmt, ":prod_id", $prod_id);

            if (!oci_execute($deduct_qty_stmt)) {
                throw new Exception("Failed to deduct quantity from new product");
            }
        }

        oci_commit($conn);

        $_SESSION['success'] = "Sale updated successfully!";
        header("Location: edit_sale.php?sale_id=".$sale_id);
        exit();

    } catch (Exception $e) {
        oci_rollback($conn);
        $_SESSION['error'] = "Error updating sale: " . $e->getMessage();
        header("Location: edit_sale.php?sale_id=".$sale_id);
        exit();
    }
}

// Fetch products for the reference table
$product_sql = "SELECT PROD_ID, PROD_NAME FROM PRODUCT";
$product_stmt = oci_parse($conn, $product_sql);
oci_execute($product_stmt);

$products = [];
while ($row = oci_fetch_assoc($product_stmt)) {
    $products[] = $row;
}
$product_name = "";
foreach ($products as $prod) {
    if ($sale && $prod['PROD_ID'] == $sale['PROD_ID']) {
        $product_name = $prod['PROD_NAME'];
        break;
    }
}
oci_free_statement($product_stmt);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS -->
    <link rel="stylesheet" href="sidebar.css">
    <title>Edit Sale</title>
    
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <style>
        i.bx.bx-edit {
            min-width: 30px;
            border-radius: 6px;
            height: 100%;
            font-size: 20px;
            color: #ffffff;
        }

        .home {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .result {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
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

        .input-box {
            margin: 15px;
        }

        .container {
            display: flex;
            flex-direction: row;
            height: auto;
            width: auto;
            overflow-y: auto;
            padding: 30px;
            border-radius: 25px;
            background-color: #3a3b3c;
            margin: 25px;
            color: #ffffff;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-right: 62px;
            padding: 15px;
        }

        .reference-container {
            width: 500px;
            display: flex;
            flex-direction: column;
            background-color: #2a2b2c;
            padding: 10px;
            border-radius: 10px;
            max-height: 600px;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select {
            width: 280px;
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
            margin: 0px 0px 0px 0px;
        } 
        
        button {
            width: 100%;
            padding: 10px;
            background: #181919;
            border: none;
            border-radius: 4px;
            color: #ffffff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: rgb(39, 39, 39);
        }

        .reference-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reference-table th, .reference-table td {
            padding: 8px;
            text-align: left;
            color: #ffffff;
        }

        .reference-table th {
            border-bottom: 1px solid #ffffff;
        }

        .reference-table tr {
            border-bottom: 1px solid #ffffff;
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
    <div class="result">
        <?php
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
        ?>
    </div>
    <section class="home">
        <div class="container">
            <button class="undo" onclick="undoAction()">
                <i class='bx bx-undo icon'></i>
            </button>            
            <div class="form-container">
                <!-- In the form section -->
                <form method="post" action="edit_sale.php?sale_id=<?php echo urlencode($sale['SALE_ID']); ?>">
                    <h2 style="text-align: center;"><u><?php echo $is_edit_mode ? 'EDIT SALE' : 'SELECT SALE TO EDIT'; ?></u></h2><br>

                    <?php if($is_edit_mode): ?>
                    <div class="form-group">
                        <label for="prod_id">Product</label>
                        <input type="text" class="form-control" name="prod_name_display" value="<?php echo htmlspecialchars($product_name); ?>" disabled>
                        <input type="hidden" name="product-id" value="<?php echo htmlspecialchars($sale['PROD_ID']); ?>">
                        <input type="hidden" name="sale_id" value="<?php echo htmlspecialchars($sale['SALE_ID']); ?>">

                    </div>
                    <?php endif; ?>

                    <?php if($is_edit_mode): ?>
                    <div class="form-group">
                        <label for="cust_name">Customer</label>
                        <input type="text" class="form-control" name="cust_name_display" value="<?php echo htmlspecialchars($cust_name); ?>" disabled>
                    </div>
                    <?php endif; ?>

                    <!-- Show date field only when editing a specific record -->
                    <?php if($is_edit_mode): ?>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($sale['SALE_DATE']))); ?>" required>
                    </div>
                    <?php endif; ?>

                    <?php if($is_edit_mode): ?>
                        <input type="hidden" name="original_sale_date" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($sale['SALE_DATE']))); ?>">
                    <?php endif; ?>
                    
                    
                    <?php if($is_edit_mode): ?>
                    <div class="form-group">
                        <label for="qty">Sales Quantity</label>
                        <input type="number" class="form-control" name="qty" min="1" value="<?php echo htmlspecialchars($sale['SALE_QTY']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="text" id="product-price" class="form-control" name="price" value="<?php echo htmlspecialchars($sale['SALE_PRICE']); ?>" required>
                    </div>
                    <?php else: ?>
                    <!-- Show a selection interface when no specific record is selected -->
                    <div class="form-group">
                        <label>Select a sale to edit:</label>
                        <select class="form-control" onchange="location = this.value;">
                            <option value="">-- Select a sale --</option>
                            <?php
                            // Fetch all sales for selection
                            $all_sales_sql = "SELECT P.PROD_NAME, P.PROD_ID, S.SALE_QTY, S.SALE_PRICE, S.SALE_DATE , C.CUST_NAME
                                            FROM PRODUCT P, SALES S , CUSTOMER C
                                            WHERE P.PROD_ID = S.PROD_ID
                                            AND S.CUST_ID = C.CUST_ID
                                            ORDER BY S.SALE_DATE DESC";
                            $all_sales_stmt = oci_parse($conn, $all_sales_sql);
                            oci_execute($all_sales_stmt);
                            
                            while ($sale_row = oci_fetch_assoc($all_sales_stmt)) {
                                $edit_url = "edit_sale.php?prod_id=".urlencode($sale_row['PROD_ID'])."&sale_date=".urlencode(date('Y-m-d', strtotime($sale_row['SALE_DATE'])));
                                echo "<option value='$edit_url'>";
                                echo htmlspecialchars($sale_row['PROD_NAME']) . " - " . 
                                    htmlspecialchars($sale_row['SALE_QTY']) . " units - " . 
                                    htmlspecialchars(date('Y-m-d', strtotime($sale_row['SALE_DATE'])));
                                echo "</option>";
                            }
                            oci_free_statement($all_sales_stmt);
                            ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <br>
                    <?php if($is_edit_mode): ?>
                    <button type="submit" name="update_sale" class="btn btn-primary">Update Sale</button>
                    <?php endif; ?>
                </form>
            </div>
            <div class="reference-container">
                <h3>Sales Records</h3>
                <table class="reference-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all sales data for the reference table
                    $sales_sql = "SELECT 
                                    s.SALE_ID,
                                    sp.PROD_NAME, 
                                    sp.PROD_ID,
                                    sp.PROD_SALE_PRICE, 
                                    sp.QUANTITY AS SALE_QTY,
                                    s.SALE_PRICE, 
                                    s.SALE_DATE,
                                    c.CUST_NAME
                                FROM SALES s 
                                JOIN SALES_PRODUCT sp ON s.SALE_ID = sp.SALE_ID
                                LEFT JOIN CUSTOMER c ON s.CUST_ID = c.CUST_ID
                                ORDER BY s.SALE_DATE DESC";

                    $sales_stmt = oci_parse($conn, $sales_sql);
                    oci_execute($sales_stmt);

                    while ($sale_row = oci_fetch_assoc($sales_stmt)) {
                        $edit_url = "edit_sale.php?sale_id=".urlencode($sale_row['SALE_ID']);
                        echo "<tr>";
                        echo "<td>".htmlspecialchars($sale_row['PROD_NAME'])."</td>";
                        echo "<td style='text-align:center;'>".htmlspecialchars($sale_row['SALE_QTY'])."</td>";
                        echo "<td style='text-align:center;'>".htmlspecialchars($sale_row['PROD_SALE_PRICE'])."</td>";
                        echo "<td>".htmlspecialchars($sale_row['CUST_NAME'])."</td>";
                        echo "<td>".htmlspecialchars(date('Y-m-d', strtotime($sale_row['SALE_DATE'])))."</td>";
                        echo "<td style='text-align:center;'><a href='$edit_url'><i class='bx bx-edit icon'></i></a></td>";
                        echo "</tr>";
                    }
                    oci_free_statement($sales_stmt);
                        ?>
                    </tbody>
                </table>
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
         
        function undoAction() {
            window.location="sales.php";
        }        
    </script>
</body>
</html>
