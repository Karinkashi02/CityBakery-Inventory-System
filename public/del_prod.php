<?php
require_once('includes/load.php');
$session = new Session();

$conn = oci_connect('myinventory', 'mypassword123', 'localhost/FREEPDB1');
if (!$conn) {
    $e = oci_error();
    $session->msg('d', 'Connection failed: ' . $e['message']);
    redirect('products.php', false);
}

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    
    $preserve_sql = "UPDATE SALES_PRODUCT sp
                    SET (sp.PROD_NAME, sp.PROD_SALE_PRICE) = (
                        SELECT p.PROD_NAME, p.PROD_SALE
                        FROM PRODUCT p
                        WHERE p.PROD_ID = :id
                    )
                    WHERE sp.PROD_ID = :id";
    $preserve_stmt = oci_parse($conn, $preserve_sql);
    oci_bind_by_name($preserve_stmt, ':id', $product_id);
    oci_execute($preserve_stmt);

    // Now delete the product
    $delete_sql = "DELETE FROM PRODUCT WHERE PROD_ID = :id";
    $delete_stmt = oci_parse($conn, $delete_sql);
    oci_bind_by_name($delete_stmt, ':id', $product_id);
    
    if (oci_execute($delete_stmt)) {
        $session->msg('s', 'Product deleted successfully. Sales records preserved.');
    } else {
        $e = oci_error($delete_stmt);
        $session->msg('d', 'Failed to delete product: ' . $e['message']);
    }
    
    oci_free_statement($preserve_stmt);
    oci_free_statement($delete_stmt);
    oci_close($conn);
} else {
    $session->msg('d', 'No product ID provided.');
}

redirect('products.php', false);
?>