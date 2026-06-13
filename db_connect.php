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

// Auto-migrate: add created_at to products if not present
$_r = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'created_at'");
if ($_r && mysqli_num_rows($_r) === 0) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN created_at DATETIME DEFAULT NULL");
}
unset($_r);

// One-time reset: clear created_at for all pre-existing products
if (!file_exists(__DIR__ . '/.mig_created_at_reset')) {
    mysqli_query($conn, "UPDATE products SET created_at = NULL");
    file_put_contents(__DIR__ . '/.mig_created_at_reset', date('Y-m-d H:i:s'));
}

// Auto-migrate: create search_history table if not present
$_r = mysqli_query($conn, "SHOW TABLES LIKE 'search_history'");
if ($_r && mysqli_num_rows($_r) === 0) {
    mysqli_query($conn, "CREATE TABLE search_history (
        search_id int(11) NOT NULL AUTO_INCREMENT,
        customer_id int(11) DEFAULT NULL,
        search_keyword varchar(255) NOT NULL,
        search_date datetime DEFAULT current_timestamp(),
        PRIMARY KEY (search_id),
        KEY customer_id (customer_id),
        CONSTRAINT search_history_ibfk_1 FOREIGN KEY (customer_id) REFERENCES customers (customer_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
unset($_r);
?>