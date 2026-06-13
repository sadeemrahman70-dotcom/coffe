<?php
session_start();
include 'db.php';

$sid = mysqli_real_escape_string($conn, session_id());

// Developed by: Lujain Mansoor Al Darweesh
// Shopping cart creation and quantity and stock validation

// ── AJAX handler (called by JS buttons) ───────────────────────
if (isset($_POST['cart_ajax'])) {
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

    // إذا الكمية أقل من المخزون → زود
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
    header("Location: ShoppingCart.php");
    exit;
}

// جلب السلة من قاعدة البيانات
$result = mysqli_query($conn, "SELECT * FROM cart WHERE session_id='$sid'");

$cart = [];
while($row = mysqli_fetch_assoc($result)){
    $cart[] = $row;
}

// الحساب
$total = 0;
$count = 0;

foreach($cart as $item){
    $total += $item['price'] * $item['quantity'];
    $count += $item['quantity'];
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart - Brew & Bean</title>
<style>
:root{
  --bg:#EFE6DA;
  --panel:#F8F1E7;
  --brown:#4A2C1D;
  --brown-2:#6A3F28;
  --text:#2E2420;
  --muted:#6B5A50;
  --line:#E2D2BE;
  --radius:18px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;}

/* ── HEADER ── */
header{background:var(--brown);padding:16px 40px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.brand{display:flex;align-items:center;gap:14px;text-decoration:none;}
.brand .logo{width:54px;height:54px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,0.3);background:#fff;flex:0 0 auto;}
.brand .logo img{width:100%;height:100%;object-fit:cover;display:block;}
.brand-text h1{color:#fff;font-size:20px;font-weight:700;line-height:1.1;}
.brand-text p{color:rgba(255,255,255,0.7);font-size:12px;margin-top:2px;}
nav{display:flex;align-items:center;gap:22px;}
nav a{color:rgba(255,255,255,0.85);text-decoration:none;font-weight:600;font-size:14px;transition:.18s;}
nav a:hover{color:#fff;}
nav a.active{color:#fff;border-bottom:2px solid rgba(255,255,255,0.6);padding-bottom:2px;}
.nav-cart{background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:999px;color:#fff;text-decoration:none;font-weight:700;font-size:13px;transition:.18s;border:1px solid rgba(255,255,255,0.25);}
.nav-cart:hover{background:rgba(255,255,255,0.25);}

/* ── PAGE LAYOUT ── */
main{
  flex:1;
  width:90%;
  max-width:1100px;
  margin:36px auto;
  display:grid;
  grid-template-columns:1.5fr 1fr;
  gap:20px;
  align-items:start;
}

/* ── CARD ── */
.card{
  background:#fff;
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:24px;
  box-shadow:0 10px 28px rgba(46,36,32,0.08);
}

.card-title{
  display:flex;align-items:center;gap:10px;
  font-size:18px;font-weight:800;color:var(--brown);
  margin-bottom:20px;padding-bottom:14px;
  border-bottom:1px solid var(--line);
}
.card-title .icon{
  width:36px;height:36px;
  background:rgba(74,44,29,0.1);
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;
}

/* ── CART ITEMS ── */
.cart-item{
  display:grid;
  grid-template-columns:1fr auto auto auto;
  align-items:center;
  gap:14px;
  padding:14px 0;
  border-bottom:1px solid var(--line);
}
.cart-item:last-of-type{border-bottom:none;}

.item-name{
  font-weight:700;
  font-size:14px;
  color:var(--text);
  line-height:1.4;
}
.item-price{
  font-size:13px;
  color:var(--muted);
  margin-top:3px;
}

.qty-control{
  display:flex;align-items:center;
  border:1.5px solid var(--line);
  border-radius:999px;
  overflow:hidden;
}
.qty-btn{
  width:30px;height:30px;
  border:none;background:transparent;
  font-size:16px;font-weight:700;
  color:var(--brown);cursor:pointer;
  transition:.15s;
  display:flex;align-items:center;justify-content:center;
}
.qty-btn:hover{background:var(--panel);}
.qty-val{
  width:36px;text-align:center;
  font-size:14px;font-weight:700;
  color:var(--text);
  border:none;border-left:1px solid var(--line);border-right:1px solid var(--line);
  padding:0;background:#fff;
  -moz-appearance:textfield;
}
.qty-val::-webkit-outer-spin-button,
.qty-val::-webkit-inner-spin-button{-webkit-appearance:none;}

.item-subtotal{
  font-size:14px;font-weight:800;
  color:var(--brown);
  white-space:nowrap;
  min-width:72px;
  text-align:right;
}

.remove-btn{
  width:30px;height:30px;
  border-radius:50%;
  border:1.5px solid var(--line);
  background:#fff;
  color:var(--muted);
  font-size:16px;
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:.18s;
  flex-shrink:0;
}
.remove-btn:hover{background:#FFF0F0;border-color:#f5a0a0;color:#c0392b;}

/* empty state */
.cart-empty{
  text-align:center;
  padding:48px 20px;
  color:var(--muted);
}
.cart-empty .empty-icon{font-size:44px;margin-bottom:14px;}
.cart-empty p{font-size:14px;margin-bottom:18px;}
.cart-empty a{
  display:inline-block;
  padding:11px 24px;border-radius:999px;
  background:var(--brown);color:#fff;
  text-decoration:none;font-weight:700;font-size:14px;
  transition:.18s;
}
.cart-empty a:hover{background:var(--brown-2);}

/* continue shopping */
.continue-btn{
  display:inline-flex;align-items:center;gap:8px;
  margin-top:18px;padding:10px 20px;
  border-radius:999px;border:1.5px solid var(--line);
  background:#fff;color:var(--brown);
  font-weight:700;font-size:13px;
  cursor:pointer;text-decoration:none;transition:.18s;
}
.continue-btn:hover{background:var(--panel);border-color:var(--brown);}

/* ── ORDER SUMMARY ── */
.summary-panel{position:sticky;top:110px;}

.summary-row{
  display:flex;justify-content:space-between;
  padding:10px 0;
  border-bottom:1px solid var(--line);
  font-size:14px;
}
.summary-row:last-of-type{border-bottom:none;}
.summary-row span:first-child{color:var(--muted);font-weight:600;}
.summary-row span:last-child{font-weight:700;}

.summary-total{
  display:flex;justify-content:space-between;
  padding:14px 16px;
  background:rgba(74,44,29,0.07);
  border-radius:12px;
  margin:14px 0;
  font-weight:800;font-size:16px;color:var(--brown);
}

.checkout-btn{
  display:block;width:100%;
  padding:14px;border-radius:999px;
  border:none;background:var(--brown);color:#fff;
  font-weight:800;font-size:15px;
  cursor:pointer;transition:.18s;
  text-decoration:none;text-align:center;
}
.checkout-btn:hover{background:var(--brown-2);}
.checkout-btn:disabled{opacity:.5;cursor:not-allowed;}

.clear-btn{
  display:block;width:100%;
  padding:11px;border-radius:999px;
  border:1.5px solid var(--line);
  background:#fff;color:var(--muted);
  font-weight:700;font-size:13px;
  cursor:pointer;transition:.18s;
  margin-top:10px;
}
.clear-btn:hover{border-color:#f5a0a0;color:#c0392b;background:#fff8f8;}

/* ── TRUST BADGES ── */
.badges{
  width:90%;max-width:1100px;
  margin:0 auto 36px;
  display:grid;grid-template-columns:repeat(4,1fr);gap:14px;
}
.badge-item{
  background:#fff;border:1px solid var(--line);
  border-radius:16px;padding:16px;text-align:center;
}
.badge-item .badge-icon{font-size:26px;margin-bottom:8px;}
.badge-item h4{font-size:13px;color:var(--brown);margin-bottom:4px;font-weight:800;}
.badge-item p{font-size:12px;color:var(--muted);}

/* ── FOOTER ── */
footer{text-align:center;padding:18px;background:#fff;border-top:1px solid var(--line);color:var(--muted);font-size:13px;}

@media(max-width:900px){
  main{grid-template-columns:1fr;}
  .summary-panel{position:static;}
  .badges{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:580px){
  header{padding:14px 18px;}
  nav a:not(.nav-cart){display:none;}
  .cart-item{grid-template-columns:1fr auto auto;}
  .item-subtotal{display:none;}
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
    <a href="my_orders.php">My Orders</a>
    <a href="contact-page.php">Contact</a>
    <a href="ShoppingCart.php" class="nav-cart active">&#128722; Cart</a>
          <a href="login.php" class="nav-logout-right" data-en="Logout" data-ar="log out">Logout</a>

  </nav>
</header>

<!-- MAIN -->
<main>

  <!-- Cart Items -->
  <div class="card">
    <div class="card-title">
      <div class="icon">&#128722;</div>
      Shopping Cart
      <span id="cartCount" style="font-size:13px;color:var(--muted);font-weight:600;margin-left:4px;"></span>
    </div>

    <div>

<?php if(count($cart) == 0){ ?>

    <div class="cart-empty">
        <div class="empty-icon">🛒</div>
        <p>Your cart is empty</p>
    </div>

<?php } else { ?>

<?php $scIdx = 0; foreach($cart as $item){ $scIdx++; ?>

<div class="cart-item" id="cart-item-<?php echo $scIdx; ?>">

  <div>
    <p class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
    <p class="item-price"><?php echo $item['price']; ?> SAR each</p>
  </div>

  <!-- quantity -->
  <div class="qty-control">
    <button type="button" class="qty-btn" onclick="cartAjax(<?php echo $scIdx; ?>, <?php echo htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES); ?>, 'minus')">−</button>
    <span class="qty-val" id="qty-<?php echo $scIdx; ?>"><?php echo $item['quantity']; ?></span>
    <button type="button" class="qty-btn" onclick="cartAjax(<?php echo $scIdx; ?>, <?php echo htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES); ?>, 'plus')">+</button>
  </div>

  <!-- subtotal -->
  <span class="item-subtotal" id="sub-<?php echo $scIdx; ?>">
    <?php echo number_format($item['price'] * $item['quantity'], 2); ?> SAR
  </span>

  <!-- remove -->
  <button type="button" class="remove-btn" onclick="cartAjax(<?php echo $scIdx; ?>, <?php echo htmlspecialchars(json_encode($item['product_name']), ENT_QUOTES); ?>, 'remove')">×</button>

</div>

<?php } ?>

<?php } ?>

</div>

    <a class="continue-btn" href="products.php">&#8592; Continue Shopping</a>
  </div>

  <!-- Order Summary -->
  <div class="summary-panel">
    <div class="card">
      <div class="card-title">
        <div class="icon">&#128203;</div>
        Order Summary
      </div>

      <div class="summary-row">
        <span>Subtotal</span>
  <span><?php echo $total; ?> SAR</span>
      </div>

      <div class="summary-row">
        <span>Shipping</span>
        <span style="color:#1E6B3A;font-weight:700;">Free</span>
      </div>

      <div class="summary-row">
        <span>Items</span>
<span><?php echo $count; ?></span>
      </div>

      <div class="summary-total">
        <span>Total</span>
  <span><?php echo $total; ?> SAR</span>
      </div>

      <a href="Checkout.php"
class="checkout-btn <?php echo empty($cart) ? 'disabled' : ''; ?>"
id="checkoutBtn"
<?php echo empty($cart) ? 'onclick="return false;" style="pointer-events:none;opacity:.5;"' : ''; ?>>Proceed to Checkout &#8594;</a>
      <form method="POST">
  <button name="clear" class="clear-btn">🗑 Clear Cart</button>
</form>
    </div>
  </div>

</main>

<!-- BADGES -->
<div class="badges">
  <div class="badge-item">
    <div class="badge-icon">&#128737;</div>
    <h4>Secure Payment</h4>
    <p>100% secure checkout</p>
  </div>
  <div class="badge-item">
    <div class="badge-icon">&#128666;</div>
    <h4>Fast Delivery</h4>
    <p>Quick &amp; reliable shipping</p>
  </div>
  <div class="badge-item">
    <div class="badge-icon">&#128260;</div>
    <h4>Easy Returns</h4>
    <p>14-day return policy</p>
  </div>
  <div class="badge-item">
    <div class="badge-icon">&#127911;</div>
    <h4>Customer Support</h4>
    <p>We're here to help</p>
  </div>
</div>

<!-- FOOTER -->
<footer>&copy; 2026 Brew &amp; Bean &mdash; Premium Coffee</footer>

<script>
function cartAjax(idx, productName, action) {
  const fd = new FormData();
  fd.append('cart_ajax', '1');
  fd.append('product_name', productName);
  fd.append('action', action);

  fetch('ShoppingCart.php', { method: 'POST', body: fd })
  .then(r => r.json())
  .then(d => {

    if (!d.success) {
        alert(d.message);
        return;
    }
      if (d.removed) {
        const el = document.getElementById('cart-item-' + idx);
        if (el) el.remove();
      } else {
        const qEl = document.getElementById('qty-' + idx);
        const sEl = document.getElementById('sub-' + idx);
        if (qEl) qEl.textContent = d.qty;
        if (sEl) sEl.textContent = d.sub + ' SAR';
      }

      // تحديث الـ Summary
      const rows = document.querySelectorAll('.summary-row span:last-child');
      if (rows[0]) rows[0].textContent = d.total + ' SAR';
      if (rows[2]) rows[2].textContent = d.cnt;
      const totalEl = document.querySelector('.summary-total span:last-child');
      if (totalEl) totalEl.textContent = d.total + ' SAR';

      // لو الكارت فاضي بعد الحذف
      if (d.cnt == 0) location.reload();
    });
}
</script>

<script src="accessibility.js"></script>
</body>
</html>
