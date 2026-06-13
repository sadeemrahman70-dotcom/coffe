<?php

session_start();

$isLoggedIn = isset($_SESSION["customer_id"]);

include 'db.php';

$sid = mysqli_real_escape_string($conn, session_id());

// Developed by: Lujain Mansoor Al Darweesh
// Checkout craetion , validation, payment validation, and stock verification

// ── AJAX handler ──────────────────────────────────────────────
if (isset($_POST['co_ajax'])) {
    header('Content-Type: application/json');
    $pname  = mysqli_real_escape_string($conn, $_POST['product_name'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'plus') {

    $stockResult = mysqli_query($conn, "
        SELECT p.stock, c.quantity
        FROM cart c
        JOIN products p ON p.product_name = c.product_name
        WHERE c.product_name='$pname'
        AND c.session_id='$sid'
        LIMIT 1
    ");

    $row = mysqli_fetch_assoc($stockResult);

    if ($row && (int)$row['quantity'] < (int)$row['stock']) {

        mysqli_query($conn, "
            UPDATE cart
            SET quantity = quantity + 1
            WHERE product_name='$pname'
            AND session_id='$sid'
        ");

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Maximum available stock reached'
        ]);
        exit;
    }

} elseif ($action === 'minus') {

    mysqli_query($conn, "
        UPDATE cart
        SET quantity = GREATEST(quantity-1,1)
        WHERE product_name='$pname'
        AND session_id='$sid'
    ");

} elseif ($action === 'remove') {

    mysqli_query($conn, "
        DELETE FROM cart
        WHERE product_name='$pname'
        AND session_id='$sid'
    ");
}

    $item = null;
    if ($action !== 'remove') {
        $ir = mysqli_query($conn, "SELECT quantity, price*quantity as sub FROM cart WHERE product_name='$pname' AND session_id='$sid'");
        $item = mysqli_fetch_assoc($ir);
    }
    $tr = mysqli_query($conn, "SELECT COALESCE(SUM(price*quantity),0) as total, COALESCE(SUM(quantity),0) as cnt FROM cart WHERE session_id='$sid'");
    $t  = mysqli_fetch_assoc($tr);

    echo json_encode([
        'success' => true,
        'removed' => ($action === 'remove'),
        'qty'     => $item ? (int)$item['quantity'] : 0,
        'sub'     => $item ? number_format((float)$item['sub'], 2) : '0.00',
        'total'   => number_format((float)$t['total'], 2),
        'cnt'     => (int)$t['cnt'],
    ]);
    exit;
}

if(isset($_POST['clear'])){
    mysqli_query($conn, "DELETE FROM cart WHERE session_id='$sid'");
    header("Location: Checkout.php");
    exit;
}

// ── معالجة الطلب ──────────────────────────────────────────────
$checkoutError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($isLoggedIn) {

        $customer_id = $_SESSION["customer_id"];

        $customerResult = mysqli_query($conn,
            "SELECT * FROM customers WHERE customer_id=$customer_id LIMIT 1"
        );

        $customer = mysqli_fetch_assoc($customerResult);

        $full_name = mysqli_real_escape_string($conn, $customer["full_name"]);
        $email     = mysqli_real_escape_string($conn, $customer["email"]);
        $phone     = mysqli_real_escape_string($conn, $customer["phone"]);

    } else {

        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $phone     = trim($_POST['phone']     ?? '');

        if ($full_name === '') {
            $checkoutError = 'Please enter your full name.';
        } elseif ($phone === '') {
            $checkoutError = 'Please enter your phone number.';
        } elseif (!preg_match('/^05\d{8}$/', $phone)) {
            $checkoutError = 'Phone number must start with 05 and be 10 digits (e.g. 05xxxxxxxx).';
        } elseif ($email === '') {
            $checkoutError = 'Please enter your email address.';
        }

        $full_name = mysqli_real_escape_string($conn, $full_name);
        $email     = mysqli_real_escape_string($conn, $email);
        $phone     = mysqli_real_escape_string($conn, $phone);
    }

    $address        = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
    $notes          = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $card_holder    = '';
    $card_last4     = '';

    if ($checkoutError === '' && trim($address) === '') {
        $checkoutError = 'Please enter your delivery address.';
    }

    if ($payment_method == 'card') {

    $card_holder = trim($_POST['card_holder'] ?? '');
    $card_number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry_month = trim($_POST['expiry_month'] ?? '');
    $expiry_year  = trim($_POST['expiry_year'] ?? '');
    $cvv         = trim($_POST['cvv'] ?? '');

    if ($checkoutError === '' && $card_holder === '') {
        $checkoutError = 'Please enter the card holder name.';
    } elseif ($checkoutError === '' && !preg_match('/^\d{16}$/', $card_number)) {
        $checkoutError = 'Card number must be 16 digits.';
    } elseif ($checkoutError === '' && ($expiry_month === '' || $expiry_year === '')) {
    $checkoutError = 'Please select expiry month and year.';
    } elseif ($checkoutError === '' && !preg_match('/^\d{3}$/', $cvv)) {
        $checkoutError = 'CVV must be 3 digits.';
    }

    $card_holder = mysqli_real_escape_string($conn, $card_holder);
    $card_last4  = substr($card_number, -4);
}

    // جلب السلة
    $cartResult = mysqli_query($conn, "SELECT * FROM cart WHERE session_id='$sid'");
    $cartItems  = [];
    $total      = 0;
    while ($row = mysqli_fetch_assoc($cartResult)) {
        $cartItems[] = $row;
        $total += $row['price'] * $row['quantity'];
    }

    if (empty($cartItems)) {
        $checkoutError = 'Your cart is empty. Please add products before placing an order.';
    }

    /* التحقق من توفر المخزون قبل تأكيد الطلب */
if ($checkoutError === '') {

    foreach ($cartItems as $item) {

        $pname = mysqli_real_escape_string($conn, $item['product_name']);
        $qty   = (int)$item['quantity'];

        // جلب كمية المخزون الحالية
        $stockResult = mysqli_query($conn, "
            SELECT stock
            FROM products
            WHERE product_name = '$pname'
            LIMIT 1
        ");

        // إذا المنتج غير موجود
        if (!$stockResult || mysqli_num_rows($stockResult) == 0) {

            $checkoutError = "This product is no longer available.";
            break;
        }

        $product = mysqli_fetch_assoc($stockResult);

        // إذا المخزون صفر
        if ((int)$product['stock'] <= 0) {

            $checkoutError = "Sorry, '$pname' is out of stock.";
            break;
        }

        // إذا الكمية المطلوبة أكبر من المتوفر
        if ((int)$product['stock'] < $qty) {

            $checkoutError = "Sorry, only " . (int)$product['stock'] . " left for '$pname'.";
            break;
        }
    }
}

    if ($checkoutError !== '') {
        // fall through to display the page with error message
        goto render_page;
    }

    // discount
    $discount = 0;
    if(isset($_POST['discount_code'])){
        $discount_code = strtoupper(trim($_POST['discount_code']));
        if($discount_code == "IAU"){
            $discount = $total * 0.25;
        }
    }
    $final_total = $total - $discount;

    // حفظ العميل أو إيجاده
   if ($isLoggedIn) {

    $customer_id = $_SESSION['customer_id'];

} else {

    $checkCustomer = mysqli_query($conn,
        "SELECT customer_id FROM customers WHERE email='$email'"
    );

    if (mysqli_num_rows($checkCustomer) > 0) {

        $customerRow = mysqli_fetch_assoc($checkCustomer);
        $customer_id = $customerRow['customer_id'];

    } else {

        mysqli_query($conn,
        "INSERT INTO customers (full_name, email, address, phone)
        VALUES ('$full_name','$email','$address','$phone')");

        $customer_id = mysqli_insert_id($conn);
    }
}
    
// إنشاء الطلب
mysqli_query($conn, "INSERT INTO orders
(customer_id, total_amount, payment_method, notes, card_holder, card_last4, status, order_date,
 customer_name, customer_email, customer_phone, customer_address)
VALUES
($customer_id, $final_total, '$payment_method', '$notes', '$card_holder', '$card_last4', 'pending', NOW(),
 '$full_name', '$email', '$phone', '$address')");

$order_id = mysqli_insert_id($conn);

 // حفظ تفاصيل الطلب
foreach ($cartItems as $item) {

    $pname    = mysqli_real_escape_string($conn, $item['product_name']);
    $productResult = mysqli_query($conn, "
    SELECT product_id
    FROM products
    WHERE product_name = '$pname'
    LIMIT 1
");

$productRow = mysqli_fetch_assoc($productResult);
$product_id = (int)$productRow['product_id'];
    $price    = (float)$item['price'];
    $qty      = (int)$item['quantity'];
    $subtotal = $price * $qty;

    // Update product stock after purchase
$updateStock = mysqli_query($conn, "
    UPDATE products
    SET stock = stock - $qty
    WHERE product_name = '$pname'
    AND stock >= $qty
");

if (mysqli_affected_rows($conn) == 0) {
    $checkoutError = "Stock update failed for '$pname'.";
    break;
}

    // Save order details
    mysqli_query($conn, "INSERT INTO order_details 
    (order_id, product_id, product_name, quantity, unit_price, subtotal)
    VALUES 
    ($order_id, $product_id, '$pname', $qty, $price, $subtotal)");
}

// مسح السلة
mysqli_query($conn, "DELETE FROM cart WHERE session_id='$sid'");

// توجيه لصفحة التأكيد
header("Location: order_confirm.php?order_id=$order_id");
exit;
}

// ── جلب السلة للعرض ───────────────────────────────────────────
render_page:
$cartResult = mysqli_query($conn, "SELECT * FROM cart WHERE session_id='$sid'");
$cartItems  = [];
$total      = 0;
$count      = 0;
while ($row = mysqli_fetch_assoc($cartResult)) {
    $cartItems[] = $row;
    $total += $row['price'] * $row['quantity'];
    $count += $row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - Brew & Bean</title>

<style>

:root{
    --bg:#EFE6DA;
    --card:#FFF8F0;
    --brown:#4A2C1D;
    --brown2:#6B4A3A;
    --cream:#F3E5D3;
    --line:#E2D2BE;
    --text:#2E2420;
    --green:#1E6B3A;
    --radius:20px;
}

*{box-sizing:border-box;margin:0;padding:0}

body{
    font-family:'DM Sans',sans-serif;
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* ── HEADER ── */
header{
    background:var(--brown);
    padding:16px 40px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    position:sticky;top:0;z-index:100;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
}
.brand{display:flex;align-items:center;gap:14px;text-decoration:none;}
.brand .logo{width:54px;height:54px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,0.3);background:#fff;flex:0 0 auto;}
.brand .logo img{width:100%;height:100%;object-fit:cover;display:block;}
.brand-text h1{color:#fff;font-size:20px;font-weight:700;line-height:1.1;}
.brand-text p{color:rgba(255,255,255,0.7);font-size:12px;margin-top:2px;}
nav{display:flex;align-items:center;gap:22px;}
nav a{color:rgba(255,255,255,0.85);text-decoration:none;font-weight:600;font-size:14px;transition:.18s;}
nav a:hover{color:#fff;}
.nav-cart{background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:999px;color:#fff;text-decoration:none;font-weight:700;font-size:13px;border:1px solid rgba(255,255,255,0.25);}

/* ── STEPS ── */
.steps-wrap{
    width:80%;max-width:700px;
    margin:36px auto 0;
    position:relative;
}
.progress-track{
    position:absolute;top:25px;left:0;right:0;height:4px;
    background:var(--line);border-radius:2px;
}
.progress-fill{
    position:absolute;top:25px;left:0;
    width:16%;height:4px;
    background:var(--brown);border-radius:2px;
    transition:width .5s ease;
}
.steps{
    display:flex;justify-content:space-between;
    position:relative;z-index:2;
}
.step{text-align:center;}
.step-circle{
    width:50px;height:50px;border-radius:50%;
    background:#EBD8C1;
    display:flex;align-items:center;justify-content:center;
    font-size:20px;margin:0 auto 8px;
    border:3px solid transparent;
    transition:.3s;
}
.step.active .step-circle{
    background:var(--brown);
    border-color:var(--brown);
    box-shadow:0 0 0 4px rgba(74,44,29,0.15);
}
.step p{font-size:12px;font-weight:600;color:#9a7b68;}
.step.active p{color:var(--brown);font-weight:700;}

/* ── MAIN ── */
.checkout-wrapper{
    width:90%;max-width:1200px;
    margin:32px auto;
    display:grid;
    grid-template-columns:1fr 1.1fr;
    gap:24px;
    align-items:start;
}

/* ── PANELS ── */
.panel{
    background:#fff;
    border:1px solid var(--line);
    border-radius:var(--radius);
    padding:28px;
    box-shadow:0 8px 28px rgba(74,44,29,0.07);
}
.panel-title{
    display:flex;align-items:center;gap:12px;
    padding-bottom:16px;
    border-bottom:1px solid var(--line);
    margin-bottom:20px;
}
.panel-icon{
    width:42px;height:42px;border-radius:12px;
    background:rgba(74,44,29,0.1);
    display:flex;align-items:center;justify-content:center;
    font-size:20px;flex-shrink:0;
}
.panel-title h2{
    font-family:'Playfair Display',serif;
    font-size:22px;color:var(--brown);
}

/* ── ORDER ITEMS ── */
.order-item{
    display:grid;
    grid-template-columns:1fr auto;
    align-items:center;
    gap:12px;
    padding:14px 0;
    border-bottom:1px solid var(--line);
}
.order-item:last-of-type{border-bottom:none;}
.item-name{font-weight:700;font-size:14px;color:var(--text);line-height:1.4;}
.item-meta{font-size:12px;color:var(--brown2);margin-top:3px;}
.item-price{
    font-weight:800;font-size:15px;color:var(--brown);
    background:var(--cream);padding:6px 14px;
    border-radius:999px;white-space:nowrap;
}

/* empty cart */
.empty-cart{
    text-align:center;padding:40px 20px;color:var(--brown2);
}
.empty-cart .icon{font-size:44px;margin-bottom:12px;}
.empty-cart a{
    display:inline-block;margin-top:14px;
    padding:10px 24px;border-radius:999px;
    background:var(--brown);color:#fff;
    text-decoration:none;font-weight:700;font-size:14px;
}

.total-row{
    display:flex;justify-content:space-between;align-items:center;
    margin-top:18px;padding:16px 20px;
    background:var(--cream);border-radius:14px;
}
.total-row span{font-size:16px;font-weight:700;color:var(--brown2);}
.total-row strong{font-size:22px;font-weight:800;color:var(--brown);font-family:'Playfair Display',serif;}

/* ── FORM ── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}

.field{margin-bottom:16px;}
.field label{
    display:block;margin-bottom:6px;
    font-weight:700;font-size:14px;color:var(--brown);
}
.input-wrap{position:relative;}
.input-wrap .ico{
    position:absolute;left:14px;top:50%;
    transform:translateY(-50%);
    font-size:15px;pointer-events:none;
}
.input-wrap input,
.input-wrap select,
.input-wrap textarea{
    width:100%;
    padding:12px 14px 12px 42px;
    border-radius:12px;
    border:1.5px solid var(--line);
    background:#fff;
    font-size:14px;font-family:'DM Sans',sans-serif;
    color:var(--text);outline:none;
    transition:.2s;
}
.input-wrap textarea{
    padding-top:12px;resize:none;height:80px;
}
.input-wrap input:focus,
.input-wrap select:focus,
.input-wrap textarea:focus{
    border-color:var(--brown);
    box-shadow:0 0 0 3px rgba(74,44,29,0.1);
}

.btn-checkout{
    width:100%;padding:15px;
    border:none;border-radius:999px;
    background:var(--brown);color:#fff;
    font-family:'DM Sans',sans-serif;
    font-size:16px;font-weight:700;
    cursor:pointer;transition:.2s;
    margin-top:8px;
    display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-checkout:hover{background:var(--brown2);transform:translateY(-1px);}
.btn-checkout:disabled{opacity:.5;cursor:not-allowed;transform:none;}

.secure-note{
    text-align:center;margin-top:12px;
    font-size:12px;color:var(--brown2);
}

/* ── BADGES ── */
.badges{
    width:90%;max-width:1200px;
    margin:0 auto 36px;
    display:grid;grid-template-columns:repeat(4,1fr);gap:14px;
}
.badge{
    background:#fff;border:1px solid var(--line);
    border-radius:16px;padding:16px;text-align:center;
}
.badge .b-icon{font-size:26px;margin-bottom:8px;}
.badge h4{font-size:13px;color:var(--brown);margin-bottom:4px;font-weight:700;}
.badge p{font-size:12px;color:var(--brown2);}

/* ── FOOTER ── */
footer{
    text-align:center;padding:18px;
    background:#fff;border-top:1px solid var(--line);
    color:var(--brown2);font-size:13px;margin-top:auto;
}

/* ── Qty controls (matches ShoppingCart) ── */
.qty-control{
    display:flex;align-items:center;
    border:1.5px solid var(--line);
    border-radius:999px;overflow:hidden;
}
.qty-btn{
    width:30px;height:30px;
    border:none;background:transparent;
    font-size:16px;font-weight:700;
    color:var(--brown);cursor:pointer;
    transition:.15s;
    display:flex;align-items:center;justify-content:center;
}
.qty-btn:hover{background:var(--cream);}
.qty-val{
    width:36px;text-align:center;
    font-size:14px;font-weight:700;
    color:var(--text);
    border-left:1px solid var(--line);border-right:1px solid var(--line);
    background:#fff;
}
.co-del-btn{
    width:30px;height:30px;border-radius:50%;
    border:1.5px solid var(--line);background:#fff;
    color:#9a7b68;font-size:16px;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    transition:.18s;
}
.co-del-btn:hover{background:#FFF0F0;border-color:#f5a0a0;color:#c0392b;}

@media(max-width:900px){
    .checkout-wrapper{grid-template-columns:1fr;}
    .form-row{grid-template-columns:1fr;}
    .badges{grid-template-columns:repeat(2,1fr);}
    header{padding:14px 18px;}
    nav a:not(.nav-cart){display:none;}
    .steps-wrap{width:95%;}
}
</style>
</head>
<body>

<?php include "welcome_banner.php"; ?>

<!-- HEADER -->
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

<!-- STEPS -->
<div class="steps-wrap">
    <div class="progress-track"></div>
    <div class="progress-fill"></div>
    <div class="steps">
        <div class="step active">
            <div class="step-circle">🛒</div>
            <p>1. Review Order</p>
        </div>
        <div class="step active">
            <div class="step-circle">👤</div>
            <p>2. Shipping &amp; Payment</p>
        </div>
        <div class="step">
            <div class="step-circle">✔</div>
            <p>3. Confirmation</p>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="checkout-wrapper">

    <!-- Order Summary -->
    <div class="panel">
        <div class="panel-title">
            <div class="panel-icon">🧾</div>
            <h2>Order Summary</h2>
        </div>

        <?php if(empty($cartItems)): ?>
        <div class="empty-cart">
            <div class="icon">🛒</div>
            <p>Your cart is empty</p>
            <a href="products.php">Browse Products</a>
        </div>
        <?php else: ?>

        <?php $coIdx = 0; foreach($cartItems as $item): $coIdx++; ?>
        <div class="order-item" id="co-item-<?php echo $coIdx; ?>">

    <div>
        <p class="item-name">
            <?php echo htmlspecialchars($item['product_name']); ?>
        </p>

        <p class="item-meta">
            Qty: <span id="co-qty-<?php echo $coIdx; ?>"><?php echo $item['quantity']; ?></span>
            × <?php echo $item['price']; ?> SAR
        </p>

        <div style="display:flex;gap:8px;margin-top:10px;align-items:center;">
            <div class="qty-control">
                <button type="button" class="qty-btn" onclick="coCart(<?php echo $coIdx; ?>, <?php echo htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES); ?>, 'minus')">−</button>
                <span class="qty-val" id="co-qn-<?php echo $coIdx; ?>"><?php echo $item['quantity']; ?></span>
                <button type="button" class="qty-btn" onclick="coCart(<?php echo $coIdx; ?>, <?php echo htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES); ?>, 'plus')">+</button>
            </div>
            <button type="button" class="co-del-btn" onclick="coCart(<?php echo $coIdx; ?>, <?php echo htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES); ?>, 'remove')" title="Remove">×</button>
        </div>
    </div>

    <span class="item-price" id="co-sub-<?php echo $coIdx; ?>">
        <?php echo number_format($item['price'] * $item['quantity'], 2); ?> SAR
    </span>

</div>
        <?php endforeach; ?>

        <?php
        $discount = 0;
        if(isset($_POST['discount_code'])){
            $discount_code = strtoupper(trim($_POST['discount_code']));
            if($discount_code == "IAU"){
                $discount = $total * 0.25;
            }
        }
        $final_total = $total - $discount;
        ?>
        <div class="total-row">
            <span>💼 Total (<span id="co-count"><?php echo $count; ?></span> items)</span>
            <div style="text-align:right;">
                <?php if($discount > 0): ?>
                <div style="color:green;font-size:14px;font-weight:bold;">Discount 25% Applied</div>
                <?php endif; ?>
                <strong id="co-total-strong"><?php echo number_format($final_total, 2); ?> SAR</strong>
            </div>
        </div>

        <form method="POST" style="margin-top:15px;">
    <button type="submit" name="clear"
    style="width:100%;padding:12px;border:none;border-radius:10px;background:#c0392b;color:white;font-weight:bold;cursor:pointer;">
        Empty Shopping Cart
    </button>
</form>

        <?php endif; ?>
    </div>

    <!-- Customer Info Form -->
    <div class="panel">
        <div class="panel-title">
            <div class="panel-icon">👤</div>
            <h2>Customer Information</h2>
        </div>

        <form method="POST" action="Checkout.php" id="checkoutForm">

        <?php if ($checkoutError !== ''): ?>
        <div style="background:#fdecea;border:1px solid #f5a0a0;border-radius:14px;padding:14px 18px;margin-bottom:16px;font-size:14px;font-weight:700;color:#c0392b;display:flex;align-items:center;gap:10px;">
            <span style="font-size:20px;">✖</span>
            <?php echo htmlspecialchars($checkoutError); ?>
        </div>
        <?php endif; ?>

       <?php if(!$isLoggedIn): ?>
<div class="form-row">
    <div class="field">
        <label>Full Name</label>
        <div class="input-wrap">
            <span class="ico">👤</span>
            <input type="text" name="full_name" placeholder="Enter your full name" required>
        </div>
    </div>

    <div class="field">
        <label>Email</label>
        <div class="input-wrap">
            <span class="ico">✉️</span>
            <input type="email" name="email" placeholder="example@email.com" required>
        </div>
    </div>
</div>
<?php endif; ?>

            <div class="field">
                <label>Address</label>
                <div class="input-wrap">
                    <span class="ico">📍</span>
                    <input type="text" name="address" placeholder="Enter delivery address" required>
                </div>
            </div>

            <div class="form-row">

    <?php if(!$isLoggedIn): ?>
    <div class="field">
        <label>Phone</label>
        <div class="input-wrap">
            <span class="ico">📞</span>
            <input type="tel" name="phone" placeholder="05xxxxxxxx" pattern="05[0-9]{8}" required>
        </div>
    </div>
    <?php endif; ?>

    <div class="field">
        <label>Payment Method</label>
        <div class="input-wrap">
            <span class="ico">💳</span>
            <select name="payment_method" id="paymentMethod">
                <option value="cash">Cash on Delivery</option>
                <option value="card">Credit Card</option>
            </select>
        </div>
    </div>
                <!-- Card Fields -->
<div id="cardFields" style="display:none;">

    <div class="field">
        <label>Card Holder Name</label>

        <div class="input-wrap">
            <span class="ico">👤</span>
            <input type="text" name="card_holder" placeholder="Name on card">
        </div>
    </div>

    <div class="field">
        <label>Card Number</label>

        <div class="input-wrap">
            <span class="ico">💳</span>
            <input type="text" name="card_number"
            placeholder="1234 5678 9012 3456">
        </div>
    </div>

    <div class="form-row">

        <div class="field">
    <label>Expiry Date</label>

    <div style="display:flex;gap:10px;">

        <select name="expiry_month"
        style="flex:1;padding:12px;border:1.5px solid var(--line);border-radius:10px;">

            <option value="">Month</option>
            <option value="01">01</option>
            <option value="02">02</option>
            <option value="03">03</option>
            <option value="04">04</option>
            <option value="05">05</option>
            <option value="06">06</option>
            <option value="07">07</option>
            <option value="08">08</option>
            <option value="09">09</option>
            <option value="10">10</option>
            <option value="11">11</option>
            <option value="12">12</option>

        </select>

        <select name="expiry_year"
        style="flex:1;padding:12px;border:1.5px solid var(--line);border-radius:10px;">

            <option value="">Year</option>
            <option value="26">2026</option>
            <option value="27">2027</option>
            <option value="28">2028</option>
            <option value="29">2029</option>
            <option value="30">2030</option>
            <option value="31">2031</option>

        </select>

    </div>
</div>

        <div class="field">
            <label>CVV</label>

            <div class="input-wrap">
                <span class="ico">🔒</span>
                <input type="password" name="cvv" placeholder="123">
            </div>
        </div>

    </div>

</div>
            </div>

            <div class="field">
                <label>Order Notes <small style="font-weight:400;color:var(--brown2);">(Optional)</small></label>
                <div class="input-wrap">
                    <span class="ico" style="top:18px;transform:none;">📝</span>
                    <textarea name="notes" placeholder="Any special requests for your order..."></textarea>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <label style="font-weight:700;color:var(--brown);margin-bottom:8px;display:block;">Discount Code</label>
                <div style="display:flex;gap:10px;">
                    <input type="text" name="discount_code" id="discountCodeInput" placeholder="Enter discount code"
                        style="flex:1;padding:12px;border:1.5px solid var(--line);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;">
                    <button type="button" onclick="applyDiscount()"
                        style="padding:12px 20px;background:var(--brown);color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;">
                        Apply
                    </button>
                </div>
                <div id="discountMsg" style="display:none;margin-top:8px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:700;"></div>
            </div>

            <button class="btn-checkout" type="submit" <?php echo empty($cartItems) ? 'disabled' : ''; ?>>
                🔒 Place Order
            </button>

            <p class="secure-note">🛡️ Your information is safe and secure</p>

        </form>
    </div>

</div>

<!-- BADGES -->
<div class="badges">
    <div class="badge"><div class="b-icon">🛡️</div><h4>Secure Payment</h4><p>100% secure checkout</p></div>
    <div class="badge"><div class="b-icon">🚚</div><h4>Fast Delivery</h4><p>Quick &amp; reliable shipping</p></div>
    <div class="badge"><div class="b-icon">↻</div><h4>Easy Returns</h4><p>14-day return policy</p></div>
    <div class="badge"><div class="b-icon">🎧</div><h4>Support</h4><p>We're here to help</p></div>
</div>

<footer>&copy; 2026 Brew &amp; Bean &mdash; Premium Coffee</footer>

<script>
// progress bar width based on step
document.querySelector('.progress-fill').style.width = '50%';
</script>

<script>
const ORIGINAL_TOTAL = <?php echo $total; ?>;

function applyDiscount() {
    const code = document.getElementById('discountCodeInput').value.trim().toUpperCase();
    const msg  = document.getElementById('discountMsg');
    const tot  = document.getElementById('co-total-strong');

    if (code === 'IAU') {
        const discount   = ORIGINAL_TOTAL * 0.25;
        const finalTotal = ORIGINAL_TOTAL - discount;

        msg.style.display      = 'block';
        msg.style.background   = '#e8f5e9';
        msg.style.color        = '#1E6B3A';
        msg.style.border       = '1px solid #a5d6a7';
        msg.textContent        = '✔ Discount 25% applied! You save ' + discount.toFixed(2) + ' SAR';

        if (tot) tot.textContent = finalTotal.toFixed(2) + ' SAR';
    } else if (code === '') {
        msg.style.display = 'none';
    } else {
        msg.style.display      = 'block';
        msg.style.background   = '#fdecea';
        msg.style.color        = '#c0392b';
        msg.style.border       = '1px solid #f5a0a0';
        msg.textContent        = '✖ Invalid discount code';
    }
}
</script>

<script>

const paymentMethod = document.getElementById('paymentMethod');
const cardFields = document.getElementById('cardFields');

paymentMethod.addEventListener('change', function() {

    if (this.value === 'card') {
        cardFields.style.display = 'block';
    } else {
        cardFields.style.display = 'none';
    }

});

</script>

<script>
function coCart(idx, productName, action) {
    const fd = new FormData();
    fd.append('co_ajax', '1');
    fd.append('product_name', productName);
    fd.append('action', action);

    fetch('Checkout.php', { method: 'POST', body: fd })
        .then(r => r.json())
       .then(d => {

  if (!d.success) {
      alert(d.message);
      return;
  }

            if (d.removed) {
                const el = document.getElementById('co-item-' + idx);
                if (el) el.remove();
            } else {
                const qn = document.getElementById('co-qn-' + idx);
                const qt = document.getElementById('co-qty-' + idx);
                const sb = document.getElementById('co-sub-' + idx);
                if (qn) qn.textContent = d.qty;
                if (qt) qt.textContent = d.qty;
                if (sb) sb.textContent = d.sub + ' SAR';
            }

            const tot = document.getElementById('co-total-strong');
            const cnt = document.getElementById('co-count');
            if (tot) tot.textContent = d.total + ' SAR';
            if (cnt) cnt.textContent = d.cnt;

            if (d.cnt == 0) location.reload();
        });
}
</script>

<script>
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const paymentMethod = document.getElementById('paymentMethod').value;

    if (paymentMethod === 'card') {
        const cardHolder = document.querySelector('input[name="card_holder"]').value.trim();
        const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\D/g, '');
        const expiryMonth = document.querySelector('select[name="expiry_month"]').value;
        const expiryYear  = document.querySelector('select[name="expiry_year"]').value;
        const cvv = document.querySelector('input[name="cvv"]').value.trim();

        if (cardHolder === '') {
            alert('Please enter the card holder name.');
            e.preventDefault();
            return;
        }

        if (!/^\d{16}$/.test(cardNumber)) {
            alert('Card number must be 16 digits.');
            e.preventDefault();
            return;
        }

        if (expiryMonth === '' || expiryYear === '') {
    alert('Please select expiry month and year.');
    e.preventDefault();
    return;
}

        if (!/^\d{3}$/.test(cvv)) {
            alert('CVV must be 3 digits.');
            e.preventDefault();
            return;
        }
    }
});
</script>
<script src="accessibility.js"></script>

</body>
</html>
