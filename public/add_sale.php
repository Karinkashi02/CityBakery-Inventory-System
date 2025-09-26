<?php
    session_start();
    if (!isset($_SESSION['emp_id'])) {
        header("Location: login.php");
        exit();
    }

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

    // Fetch products
    $sqlProducts = "SELECT PROD_ID, PROD_NAME, PROD_SALE, PROD_QTY FROM PRODUCT";
    $stmtProducts = oci_parse($conn, $sqlProducts);
    oci_execute($stmtProducts);
    $products = [];
    while ($row = oci_fetch_assoc($stmtProducts)) {
        $products[] = $row;
    }
    oci_free_statement($stmtProducts);

    // Fetch customers
    $sqlCustomers = "SELECT CUST_ID, CUST_NAME FROM CUSTOMER";
    $stmtCustomers = oci_parse($conn, $sqlCustomers);
    oci_execute($stmtCustomers);
    $customers = [];
    while ($row = oci_fetch_assoc($stmtCustomers)) {
        $customers[] = $row;
    }
    oci_free_statement($stmtCustomers);

    // If form submitted
    if (isset($_POST['add_sale'])) {
        $prodId = $_POST['product-id'];
        $quantity = (int)$_POST['qty'];
        $date = $_POST['date'];
        $emp_id = $_SESSION['emp_id'];
        $cust_id = $_POST['customer-id'];

        // Get selected product details
        $sqlProduct = "SELECT * FROM PRODUCT WHERE PROD_ID = :pid";
        $stmtProduct = oci_parse($conn, $sqlProduct);
        oci_bind_by_name($stmtProduct, ":pid", $prodId);
        oci_execute($stmtProduct);
        $product = oci_fetch_assoc($stmtProduct);
        oci_free_statement($stmtProduct);

        if (!$product) {
            $_SESSION['error'] = "Product not found.";
            header("Location: add_sale.php");
            exit();
        }

        if ($quantity > $product['PROD_QTY']) {
            $_SESSION['error'] = "Not enough quantity in stock. Only ".$product['PROD_QTY']." units available.";
            header("Location: add_sale.php");
            exit();
        }

        if ($quantity <= 0) {
            $_SESSION['error'] = "Quantity must be greater than 0";
            header("Location: add_sale.php");
            exit();
        }            

        $price = $quantity * $product['PROD_SALE'];

        // Begin transaction
        oci_execute(oci_parse($conn, "SET TRANSACTION ISOLATION LEVEL SERIALIZABLE"));

        try {
            // 1. Insert into SALES table (with quantity)
            $insert_sql = "
                INSERT INTO SALES (
                    SALE_ID, SALE_DATE, CUST_ID, EMP_ID, SALE_PRICE, SALE_QTY
                ) VALUES (
                    sales_seq.NEXTVAL, TO_DATE(:saledate, 'YYYY-MM-DD'), :custId, :empId, :totalPrice, :quantity
                ) RETURNING SALE_ID INTO :sale_id";

            $stmtInsertSale = oci_parse($conn, $insert_sql);
            if (!$stmtInsertSale) {
                $e = oci_error($conn);
                throw new Exception("SQL parse error: " . $e['message']);
            }
            
            oci_bind_by_name($stmtInsertSale, ":saledate", $date);
            oci_bind_by_name($stmtInsertSale, ":custId", $cust_id);
            oci_bind_by_name($stmtInsertSale, ":empId", $emp_id);
            oci_bind_by_name($stmtInsertSale, ":totalPrice", $price);
            oci_bind_by_name($stmtInsertSale, ":quantity", $quantity);
            oci_bind_by_name($stmtInsertSale, ":sale_id", $sale_id, -1, SQLT_INT);
            
            if (!oci_execute($stmtInsertSale)) {
                $e = oci_error($stmtInsertSale);
                throw new Exception("Insert into SALES failed: " . $e['message']);
            }

            // 2. Insert into SALES_PRODUCT with quantity
            $insert_sp_sql = "INSERT INTO SALES_PRODUCT (
                SALE_ID, PROD_ID, PROD_NAME, PROD_SALE_PRICE, QUANTITY
            ) VALUES (
                :sale_id, :prod_id, :prod_name, :prod_sale_price, :quantity
            )";
            
            $insert_sp_stmt = oci_parse($conn, $insert_sp_sql);
            oci_bind_by_name($insert_sp_stmt, ":sale_id", $sale_id);
            oci_bind_by_name($insert_sp_stmt, ":prod_id", $prodId);
            oci_bind_by_name($insert_sp_stmt, ":prod_name", $product['PROD_NAME']);
            oci_bind_by_name($insert_sp_stmt, ":prod_sale_price", $product['PROD_SALE']);
            oci_bind_by_name($insert_sp_stmt, ":quantity", $quantity);

            if (!oci_execute($insert_sp_stmt)) {
                $e = oci_error($insert_sp_stmt);
                throw new Exception("Insert into SALES_PRODUCT failed: " . $e['message']);
            }

            // 3. Update product quantity
            $newQty = $product['PROD_QTY'] - $quantity;
            $sqlUpdateProduct = "UPDATE PRODUCT SET PROD_QTY = :newQty WHERE PROD_ID = :pid";
            $stmtUpdateProduct = oci_parse($conn, $sqlUpdateProduct);
            oci_bind_by_name($stmtUpdateProduct, ":newQty", $newQty);
            oci_bind_by_name($stmtUpdateProduct, ":pid", $prodId);
            
            if (!oci_execute($stmtUpdateProduct)) {
                $e = oci_error($stmtUpdateProduct);
                throw new Exception("Update product quantity failed: " . $e['message']);
            }

            // Commit transaction
            oci_commit($conn);

            $_SESSION['success'] = "Sale added successfully!";
            header("Location: add_sale.php");
            exit();

        } catch (Exception $e) {
            oci_rollback($conn);
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: add_sale.php");
            exit();
        }
    }

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
        .home {
            display: flex;
            flex-direction: column;
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
            margin-right: 60px;
        }

        .reference-container {
            width: 500px;
            display: flex;
            flex-direction: column;
            background-color: #2a2b2c;
            padding: 10px;
            border-radius: 10px;
            max-height: 500px;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        .undo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 25px;
            margin: 0px 0px 0px 0px;
        }  

        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            width: 90%;
            margin-left: 15px;
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
            color: #ffffff;
        }

        .reference-table th, .reference-table td {
            padding: 8px;
            text-align: left;
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
        <div class="container">
            <button class="undo" onclick="undoAction()">
                <i class='bx bx-undo icon'></i>
            </button>            
            <div class="form-container">                  
                <form method="post" action="add_sale.php" onsubmit="return validateForm()">
                    <h2 style="text-align: center;"><u>ADD SALE</u></h2>
                    <div class="input-box">
                        <span class="details">Product</span>
                        <select name="product-id" id="product-id" required>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['PROD_ID']; ?>"><?php echo $product['PROD_NAME']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-box">
                        <span class="details">Quantity</span>
                        <input type="number" name="qty" id="qty" required>
                    </div>

                     <div class="input-box">
                        <span class="details">Customer</span>
                        <select name="customer-id" id="customer-id" required>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['CUST_ID']; ?>"><?php echo $customer['CUST_NAME']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="input-box">
                        <span class="details">Date</span>
                        <input type="date" name="date" id="date" value="<?php echo date('Y-m-d'); ?>" required>                    </div>
                    <div class="input-box">
                        <span class="details">Total Price</span>
                        <input type="text" id="product-price" disabled>
                    </div>
                    <br>
                    <button type="submit" name="add_sale" value="Add Sale" class="btn btn-danger">Add Sale</button>
                </form>
            </div> 
            <div class="reference-container">
                <h3>Product References</h3>
                <table class="reference-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Sell Price</th>
                            <th>Quantity Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['PROD_NAME']; ?></td>
                            <td><?php echo $product['PROD_SALE']; ?></td>
                            <td><?php echo $product['PROD_QTY']; ?></td>
                        </tr>
                        <?php endforeach; ?>
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
        const productPrice = document.querySelector("#product-price");
        const productSelect = document.querySelector("#product-id");
        const qtyInput = document.querySelector("#qty");

        productSelect.addEventListener("change", updatePrice);
        qtyInput.addEventListener("input", updatePrice);

        function updatePrice() {
            const selectedProductId = productSelect.value;
            const quantity = qtyInput.value;
            if (selectedProductId && quantity) {
                const selectedProduct = <?php echo json_encode($products); ?>.find(product => product.PROD_ID == selectedProductId);
               if (selectedProduct) {
                    const price = quantity * selectedProduct.PROD_SALE;
                    productPrice.value = price.toFixed(2);
                }
            } else {
                productPrice.value = '';
            }
        }

        function validateForm() {
            const productId = document.getElementById('product-id').value;
            const qty = document.getElementById('qty').value;
            const date = document.getElementById('date').value;
            const customerId = document.getElementById('customer-id').value;
            
            if (!productId || !qty || !date || !customerId) {
                alert('Please fill in all fields');
                return false;
            }
            
            if (parseInt(qty) <= 0) {
                alert('Quantity must be greater than 0');
                return false;
            }
            
            return true;
        }

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

        const productPrice = document.querySelector("#product-price");
        const productSelect = document.querySelector("#product-id");
        const qtyInput = document.querySelector("#qty");

        productSelect.addEventListener("change", updatePrice);
        qtyInput.addEventListener("input", updatePrice);

        function updatePrice() {
            const selectedProductId = productSelect.value;
            const quantity = qtyInput.value;
            if (selectedProductId && quantity) {
                const selectedProduct = <?php echo json_encode($products); ?>.find(product => product.PROD_ID == selectedProductId);
                if (selectedProduct) {
                    const price = quantity * selectedProduct.PROD_SALE;
                    productPrice.value = price.toFixed(2);
                }
            } else {
                productPrice.value = '';
            }
        }

        function undoAction() {
            window.location= "sales.php";
        }         
    </script>

</body>
</html>