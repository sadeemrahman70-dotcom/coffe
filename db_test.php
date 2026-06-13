<?php
include "db_connect.php";

echo "<style>
body{font-family:Arial,sans-serif;background:#EFE6DA;padding:30px;}
h2{color:#4A2C1D;margin-bottom:16px;}
.ok{background:#E8FFF2;border:1px solid #BFEED2;color:#1E6B3A;padding:10px 16px;border-radius:10px;margin-bottom:10px;font-weight:700;}
.err{background:#FFF0F0;border:1px solid #f5a0a0;color:#c0392b;padding:10px 16px;border-radius:10px;margin-bottom:10px;font-weight:700;}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.07);margin-bottom:24px;}
th{background:#4A2C1D;color:#fff;padding:10px 14px;text-align:left;font-size:13px;}
td{padding:10px 14px;border-bottom:1px solid #E2D2BE;font-size:13px;}
tr:last-child td{border-bottom:none;}
</style>";

echo "<h2>&#9989; Database Test — Brew &amp; Bean</h2>";

/* 1. Connection */
if ($conn) {
    echo "<div class='ok'>&#10003; Connected to database: <b>brew_bean</b> on port 3307</div>";
} else {
    echo "<div class='err'>&#10007; Connection failed: " . mysqli_connect_error() . "</div>";
    exit;
}

/* 2. Tables check */
$tables = ['categories','products','orders','order_details','customers'];
foreach ($tables as $t) {
    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM `$t`");
    if ($r) {
        $row = mysqli_fetch_assoc($r);
        echo "<div class='ok'>&#10003; Table <b>$t</b> — {$row['cnt']} rows</div>";
    } else {
        echo "<div class='err'>&#10007; Table <b>$t</b> not found or error</div>";
    }
}

/* 3. Sample categories */
echo "<h2 style='color:#4A2C1D;margin:24px 0 12px'>Categories</h2>";
$cats = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
echo "<table><tr><th>ID</th><th>Name</th></tr>";
while ($c = mysqli_fetch_assoc($cats))
    echo "<tr><td>{$c['category_id']}</td><td>{$c['category_name']}</td></tr>";
echo "</table>";

/* 4. Sample products (latest 10) */
echo "<h2 style='color:#4A2C1D;margin:0 0 12px'>Products (latest 10)</h2>";
$prods = mysqli_query($conn, "
    SELECT p.product_id, p.product_name, p.price, p.stock, c.category_name
    FROM products p LEFT JOIN categories c ON p.category_id=c.category_id
    ORDER BY p.product_id DESC LIMIT 10
");
echo "<table><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th></tr>";
while ($p = mysqli_fetch_assoc($prods))
    echo "<tr>
            <td>{$p['product_id']}</td>
            <td>".htmlspecialchars($p['product_name'])."</td>
            <td>".htmlspecialchars($p['category_name'])."</td>
            <td>{$p['price']} SAR</td>
            <td>{$p['stock']}</td>
          </tr>";
echo "</table>";

/* 5. Orders summary */
echo "<h2 style='color:#4A2C1D;margin:0 0 12px'>Recent Orders (latest 5)</h2>";
$orders = mysqli_query($conn, "
    SELECT o.order_id, o.order_date, o.total_amount, o.status, c.full_name
    FROM orders o LEFT JOIN customers c ON o.customer_id=c.customer_id
    ORDER BY o.order_id DESC LIMIT 5
");
echo "<table><tr><th>ID</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th></tr>";
while ($o = mysqli_fetch_assoc($orders))
    echo "<tr>
            <td>{$o['order_id']}</td>
            <td>".htmlspecialchars($o['full_name'] ?? 'Guest')."</td>
            <td>{$o['order_date']}</td>
            <td>{$o['total_amount']} SAR</td>
            <td>{$o['status']}</td>
          </tr>";
echo "</table>";

echo "<p style='color:#6B5A50;font-size:12px;'>&#128274; Delete this file after testing.</p>";
?>
