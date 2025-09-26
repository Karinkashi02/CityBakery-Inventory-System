<?php
$page_title = 'Sales Report';
$results = '';
require_once('includes/load.php');

if (isset($_POST['submit'])) {
    $req_dates = array('start-date', 'end-date');
    validate_fields($req_dates);

    if (empty($errors)) {
        $start_date = $_POST['start-date'];
        $end_date = $_POST['end-date'];
        
        // Validate dates
        if (strtotime($start_date) > strtotime($end_date)) {
            $errors[] = "End date must be after start date";
        }

        if (empty($errors)) {
            // Oracle database connection
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
                die("Database connection failed: ". $e['message']);
            }

            // Oracle-compatible query
            $sql = "SELECT TO_CHAR(TRUNC(s.SALE_DATE), 'YYYY-MM-DD') AS \"date\",
                        sp.PROD_NAME AS \"name\",
                        SUM(sp.QUANTITY) AS \"total_sales\",
                        SUM(sp.PROD_SALE_PRICE * sp.QUANTITY) AS \"total_saleing_price\"
                    FROM SALES s
                    JOIN SALES_PRODUCT sp ON s.SALE_ID = sp.SALE_ID
                    JOIN PRODUCT p ON sp.PROD_ID = p.PROD_ID
                    WHERE TRUNC(s.SALE_DATE) BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                                                AND TO_DATE(:end_date, 'YYYY-MM-DD')
                    GROUP BY TRUNC(s.SALE_DATE), sp.PROD_NAME, p.PROD_BUY, sp.PROD_SALE_PRICE
                    ORDER BY TRUNC(s.SALE_DATE)";

            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':start_date', $start_date);
            oci_bind_by_name($stmt, ':end_date', $end_date);
            
            if (!oci_execute($stmt)) {
                $e = oci_error($stmt);
                die("Query failed: ". $e['message']);
            }

            $results = [];
            while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
                $results[] = $row;
            }
            
            oci_free_statement($stmt);
            oci_close($conn);
        }
    }
}
?>

<!doctype html>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css"/>
    <style>
        @media print {
            html, body {
                font-size: 9.5pt;
                margin: 0;
                padding: 0;
            }
            .page-break {
                page-break-before: always;
                width: auto;
                margin: auto;
            }
        }
        .page-break {
            width: 980px;
            margin: 0 auto;
        }
        .sale-head {
            margin: 40px 0;
            text-align: center;
        }
        .sale-head h1, .sale-head strong {
            padding: 10px 20px;
            display: block;
        }
        .sale-head h1 {
            margin: 0;
            border-bottom: 1px solid #212121;
        }
        .table>thead:first-child>tr:first-child>th {
            border-top: 1px solid #000;
        }
        table thead tr th {
            text-align: center;
            border: 1px solid #ededed;
        }
        table tbody tr td {
            vertical-align: middle;
        }
        .sale-head, table.table thead tr th, table tbody tr td, table tfoot tr td {
            border: 1px solid #212121;
            white-space: nowrap;
        }
        .sale-head h1, table thead tr th, table tfoot tr td {
            background-color: #f8f8f8;
        }
        tfoot {
            color: #000;
            text-transform: uppercase;
            font-weight: 500;
        }
        .print-button {
          margin-left: 290px;
          width: 90px;
          height: 40px;
        }
        .error {
            color: red;
            font-weight: bold;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($results)): ?>
        <div class="page-break" id="sales-report">
            <div class="sale-head">
                <h1>CITY BAKERY SALES REPORT</h1>
                <strong><?php echo htmlspecialchars($start_date); ?> TO <?php echo htmlspecialchars($end_date); ?></strong>
            </div>
            <table class="table table-border">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Product Title</th>
                    <th>Quantity Sold</th> 
                    <th>Total Selling Price</th>
                </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['date']); ?></td>
                        <td class="desc">
                            <h6><?php echo htmlspecialchars(ucfirst($result['name'])); ?></h6>
                        </td>
                        <td class="text-right"><?php echo htmlspecialchars($result['total_sales']); ?></td>
                        <td class="text-right"><?php echo htmlspecialchars($result['total_saleing_price']); ?></td>
                        
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No sales found in this date range.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <?php
                    $grand_total = 0;
                    $total_sale_price = 0;
                    
                    if (!empty($results)) {
                        foreach ($results as $result) {
                            $grand_total += (float)$result['total_sales'];
                            $total_sale_price += (float)$result['total_saleing_price']; 
                        }
                    }
                    ?>
                    <tr class="text-right">
                        <td colspan="2"></td>
                        <td colspan="1">Total Quantity</td>
                        <td>
                            <?php echo number_format($grand_total); ?>
                        </td>
                    </tr>
                    <tr class="text-right">
                        <td colspan="2"></td>
                        <td colspan="1">Total Selling Price</td>
                        <td> RM
                            <?php echo number_format($total_sale_price, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button class='print-button' onclick='printReport()'>Print</button>
    <?php endif; ?>
    <script>
        function printReport() {
            const printContent = document.getElementById('sales-report');
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>City Bakery Sales Report</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css"/>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContent.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>