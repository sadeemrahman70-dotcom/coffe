<?php
include 'db.php';

// Developed by: Lujain Mansoor Al Darweesh
// Order confirmation and order details display

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if(!$order_id){
    header("Location: products.php");
    exit;
}

// جلب بيانات الطلب
$orderResult = mysqli_query($conn, "
    SELECT o.order_id, o.total_amount, o.status,
           COALESCE(o.customer_name,    c.full_name) AS full_name,
           COALESCE(o.customer_email,   c.email)     AS email,
           COALESCE(o.customer_phone,   c.phone)     AS phone,
           COALESCE(o.customer_address, c.address)   AS address
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE o.order_id = $order_id
");
$order = mysqli_fetch_assoc($orderResult);

if(!$order){
    header("Location: products.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Confirmed - Brew & Bean</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap');

:root{
    --bg:#EFE6DA;--brown:#4A2C1D;--brown2:#6B4A3A;
    --line:#E2D2BE;--cream:#F3E5D3;--green:#1E6B3A;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:#2E2420;min-height:100vh;display:flex;flex-direction:column;}

header{background:var(--brown);padding:16px 40px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 4px 20px rgba(0,0,0,0.15);}
.brand{display:flex;align-items:center;gap:14px;text-decoration:none;}
.brand .logo{width:54px;height:54px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,0.3);background:#fff;flex:0 0 auto;}
.brand .logo img{width:100%;height:100%;object-fit:cover;display:block;}
.brand-text h1{color:#fff;font-size:20px;font-weight:700;line-height:1.1;}
.brand-text p{color:rgba(255,255,255,0.7);font-size:12px;margin-top:2px;}
nav{display:flex;align-items:center;gap:22px;}
nav a{color:rgba(255,255,255,0.85);text-decoration:none;font-weight:600;font-size:14px;transition:.18s;}
nav a:hover{color:#fff;}
.nav-cart{background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:999px;color:#fff;text-decoration:none;font-weight:700;font-size:13px;border:1px solid rgba(255,255,255,0.25);}

.confirm-wrap{
    width:90%;max-width:600px;
    margin:60px auto;
    text-align:center;
}

.check-circle{
    width:90px;height:90px;border-radius:50%;
    background:var(--green);
    display:flex;align-items:center;justify-content:center;
    font-size:40px;margin:0 auto 24px;
    box-shadow:0 8px 30px rgba(30,107,58,0.3);
    animation:pop .5s cubic-bezier(.36,.07,.19,.97);
}
@keyframes pop{0%{transform:scale(0)}80%{transform:scale(1.1)}100%{transform:scale(1)}}

h2{
    font-family:'Playfair Display',serif;
    font-size:32px;color:var(--brown);margin-bottom:10px;
}
.subtitle{color:var(--brown2);font-size:15px;margin-bottom:32px;}

.confirm-card{
    background:#fff;border:1px solid var(--line);
    border-radius:20px;padding:28px;
    box-shadow:0 8px 28px rgba(74,44,29,0.07);
    text-align:left;
    margin-bottom:24px;
}
.confirm-card h3{
    font-family:'Playfair Display',serif;
    font-size:18px;color:var(--brown);
    margin-bottom:16px;padding-bottom:12px;
    border-bottom:1px solid var(--line);
}
.info-row{
    display:flex;justify-content:space-between;
    padding:10px 0;border-bottom:1px solid var(--line);
    font-size:14px;
}
.info-row:last-child{border-bottom:none;}
.info-row span:first-child{color:var(--brown2);font-weight:600;}
.info-row span:last-child{font-weight:700;color:#2E2420;}

.order-badge{
    display:inline-block;
    background:var(--cream);
    border:1px solid var(--line);
    padding:6px 18px;border-radius:999px;
    font-weight:800;font-size:18px;color:var(--brown);
    margin-bottom:4px;
}

.btn-home{
    display:inline-block;
    padding:14px 36px;border-radius:999px;
    background:var(--brown);color:#fff;
    text-decoration:none;font-weight:700;font-size:15px;
    transition:.2s;margin-right:10px;
}
.btn-home:hover{background:var(--brown2);}
.btn-orders{
    display:inline-block;
    padding:14px 36px;border-radius:999px;
    border:1.5px solid var(--line);
    background:#fff;color:var(--brown);
    text-decoration:none;font-weight:700;font-size:15px;
    transition:.2s;
}
.btn-orders:hover{background:var(--cream);}

footer{text-align:center;padding:18px;background:#fff;border-top:1px solid var(--line);color:var(--brown2);font-size:13px;margin-top:auto;}
@media(max-width:900px){header{padding:14px 18px;}nav a:not(.nav-cart){display:none;}}
</style>
</head>
<body>

<header>
    <a class="brand" href="code.php">
        <div class="logo"><img src="images/Brew&Bean3.jpg" alt="Logo"></div>
        <div class="brand-text">
            <h1>Brew &amp; Bean</h1>
            <p>Premium Coffee</p>
        </div>
    </a>
    <nav>
        <a href="code.php">Home</a>
        <a href="products.php">Products</a>
        <a href="ShoppingCart.php" class="nav-cart">🛒 Cart</a>
        <a href="contact-page.php">Contact</a>
    </nav>
</header>

<div class="confirm-wrap">
    <div class="check-circle">✓</div>
    <h2>Order Placed Successfully!</h2>
    <p class="subtitle">Thank you <?php echo htmlspecialchars($order['full_name']); ?>! Your order is being processed.</p>

    <div class="confirm-card">
        <h3>📋 Order Details</h3>
        <div class="info-row">
            <span>Order ID</span>
            <span class="order-badge">#<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></span>
        </div>
        <div class="info-row">
            <span>Name</span>
            <span><?php echo htmlspecialchars($order['full_name']); ?></span>
        </div>
        <div class="info-row">
            <span>Email</span>
            <span><?php echo htmlspecialchars($order['email']); ?></span>
        </div>
        <div class="info-row">
            <span>Phone</span>
            <span><?php echo htmlspecialchars($order['phone']); ?></span>
        </div>
        <div class="info-row">
            <span>Address</span>
            <span><?php echo htmlspecialchars($order['address']); ?></span>
        </div>
        <div class="info-row">
            <span>Total</span>
            <span style="color:var(--brown);font-size:18px;"><?php echo number_format($order['total_amount'], 2); ?> SAR</span>
        </div>
        <div class="info-row">
            <span>Status</span>
            <span style="color:var(--green);">⏳ Pending</span>
        </div>
    </div>

    <a href="products.php" class="btn-home">🛍 Continue Shopping</a>
    <a href="my_orders.php" class="btn-orders">📦 My Orders</a>
</div>

<footer>&copy; 2026 Brew &amp; Bean &mdash; Premium Coffee</footer>
<script src="accessibility.js"></script>
</body>
</html>
