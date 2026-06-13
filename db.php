<?php

$conn = mysqli_connect(
    getenv('MYSQLHOST'),
    getenv('MYSQLUSER'),
    getenv('MYSQLPASSWORD'),
    getenv('MYSQLDATABASE'),
    getenv('MYSQLPORT')
);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Auto-migrate: add session_id column to cart if it doesn't exist yet
$_migCol = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'session_id'");
if ($_migCol && mysqli_num_rows($_migCol) === 0) {
    mysqli_query($conn, "DELETE FROM cart"); // clear old shared data
    mysqli_query($conn, "ALTER TABLE cart ADD COLUMN session_id VARCHAR(100) NOT NULL DEFAULT '' AFTER id");
    // Replace old product-only unique index with session+product composite index
    $_oldIdx = mysqli_query($conn, "SHOW INDEX FROM cart WHERE Key_name='uq_cart_product'");
    if ($_oldIdx && mysqli_num_rows($_oldIdx) > 0) {
        mysqli_query($conn, "ALTER TABLE cart DROP INDEX uq_cart_product");
    }
    mysqli_query($conn, "ALTER TABLE cart ADD UNIQUE KEY uq_cart_session_product (session_id, product_name)");
}
unset($_migCol, $_oldIdx);

// Auto-migrate: add customer_name/email/phone/address to orders if not present
$_migCols = ['customer_name' => "VARCHAR(100) DEFAULT NULL",
             'customer_email'   => "VARCHAR(150) DEFAULT NULL",
             'customer_phone'   => "VARCHAR(30)  DEFAULT NULL",
             'customer_address' => "TEXT         DEFAULT NULL"];
foreach ($_migCols as $_col => $_def) {
    $_r = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE '$_col'");
    if ($_r && mysqli_num_rows($_r) === 0) {
        mysqli_query($conn, "ALTER TABLE orders ADD COLUMN $_col $_def");
    }
}
unset($_migCols, $_col, $_def, $_r);
?>
