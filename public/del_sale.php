<?php
require_once('includes/load.php');

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

if(isset($_GET['sale_id'])) {
    $sale_id = $_GET['sale_id'];
    
    // First get the sale details
    $get_sale_sql = "SELECT s.PROD_ID, s.SALE_QTY, sp.PROD_NAME 
                     FROM SALES s
                     JOIN SALES_PRODUCT sp ON s.SALE_ID = sp.SALE_ID
                     WHERE s.SALE_ID = :sale_id";
    $get_sale_stmt = oci_parse($conn, $get_sale_sql);
    oci_bind_by_name($get_sale_stmt, ":sale_id", $sale_id);
    oci_execute($get_sale_stmt);
    $sale = oci_fetch_assoc($get_sale_stmt);
    
    if ($sale) {
        $quantity = $sale['SALE_QTY'];
        $prod_id = $sale['PROD_ID'];
        
        // Update inventory
        $update_inventory_sql = "UPDATE PRODUCT SET PROD_QTY = PROD_QTY + :quantity 
                                 WHERE PROD_ID = :prod_id";
        $update_inventory_stmt = oci_parse($conn, $update_inventory_sql);
        oci_bind_by_name($update_inventory_stmt, ":quantity", $quantity);
        oci_bind_by_name($update_inventory_stmt, ":prod_id", $prod_id);
        oci_execute($update_inventory_stmt);
        
        $delete_sp_sql = "DELETE FROM SALES_PRODUCT WHERE SALE_ID = :sale_id";
        $delete_sp_stmt = oci_parse($conn, $delete_sp_sql);
        oci_bind_by_name($delete_sp_stmt, ":sale_id", $sale_id);
        oci_execute($delete_sp_stmt);

        // Then delete from SALES
        $delete_sql = "DELETE FROM SALES WHERE SALE_ID = :sale_id";
        $delete_stmt = oci_parse($conn, $delete_sql);
        oci_bind_by_name($delete_stmt, ":sale_id", $sale_id);
        oci_execute($delete_stmt);
        
        if(oci_execute($delete_stmt)) {
            $session->msg('s', "Sale deleted successfully.");
        } else {
            $e = oci_error($delete_stmt);
            $session->msg('d', "Failed to delete sale: ".$e['message']);
        }
    }
}

oci_close($conn);
redirect("sales.php");
?>