<?php
session_start();

include "db_connect.php";

$orders      = [];
$order_items = [];
$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : 0;

if ($customer_id > 0) {

    /* طلبات العميل */
    $stmt = $conn->prepare("
        SELECT order_id, order_date, total_amount, status
        FROM orders
        WHERE customer_id = ?
        ORDER BY order_date DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();

    /* منتجات كل طلب */
    foreach ($orders as $order) {
        $oid  = $order['order_id'];
        $stmt = $conn->prepare("
            SELECT p.product_name, od.quantity
            FROM order_details od
            LEFT JOIN products p ON od.product_id = p.product_id
            WHERE od.order_id = ?
        ");
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $res2  = $stmt->get_result();
        $items = [];
        while ($item = $res2->fetch_assoc()) {
            $items[] = htmlspecialchars($item['product_name']) . ' x' . (int)$item['quantity'];
        }
        $order_items[$oid] = $items;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Brew & Bean</title>

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

body{
  font-family:Arial,sans-serif;
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
}

.brand{
  display:flex;
  align-items:center;
  gap:14px;
  text-decoration:none;
}

.brand .logo{
  width:54px;height:54px;
  border-radius:50%;
  overflow:hidden;
  border:2px solid rgba(255,255,255,0.3);
  background:#fff;
  flex:0 0 auto;
}

.brand .logo img{
  width:100%;height:100%;
  object-fit:cover;display:block;
}

.brand-text h1{
  color:#fff;
  font-size:20px;
  font-weight:700;
  line-height:1.1;
}

.brand-text p{
  color:rgba(255,255,255,0.7);
  font-size:12px;
  margin-top:2px;
}

nav{ display:flex;align-items:center;gap:22px; }

nav a{
  color:rgba(255,255,255,0.85);
  text-decoration:none;
  font-weight:600;
  font-size:14px;
  transition:.18s;
}

nav a:hover{ color:#fff; }
nav a.active{ color:#fff; border-bottom:2px solid rgba(255,255,255,0.6); padding-bottom:2px; }

.nav-cart{
  background:rgba(255,255,255,0.15);
  padding:8px 16px;
  border-radius:999px;
  color:#fff;
  text-decoration:none;
  font-weight:700;
  font-size:13px;
  transition:.18s;
  border:1px solid rgba(255,255,255,0.25);
}
.nav-cart:hover{ background:rgba(255,255,255,0.25); }

/* ── MAIN ── */
main{
  flex:1;
  width:90%;
  max-width:1100px;
  margin:36px auto;
  display:grid;
  grid-template-columns:1.4fr 1fr;
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
  display:flex;
  align-items:center;
  gap:10px;
  font-size:18px;
  font-weight:800;
  color:var(--brown);
  margin-bottom:20px;
  padding-bottom:14px;
  border-bottom:1px solid var(--line);
}

.card-title .icon{
  width:36px;height:36px;
  background:rgba(74,44,29,0.1);
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;
}

/* ── ORDER ROW ── */
.order-row{
  display:grid;
  grid-template-columns:60px 1fr auto auto auto;
  align-items:center;
  gap:14px;
  padding:14px 0;
  border-bottom:1px solid var(--line);
}

.order-row:last-of-type{ border-bottom:none; }

.order-num{
  background:rgba(74,44,29,0.08);
  border-radius:12px;
  text-align:center;
  padding:10px 6px;
  font-weight:800;
  font-size:13px;
  color:var(--brown);
}

.order-info p{
  font-weight:700;
  font-size:14px;
  margin-bottom:3px;
}

.order-info small{
  color:var(--muted);
  font-size:12px;
}

.status{
  padding:6px 12px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  white-space:nowrap;
}

.delivered{ background:#E8FFF2; color:#1E6B3A; border:1px solid #BFEED2; }
.processing{ background:#FFF8E1; color:#856404; border:1px solid #F2D6A5; }
.preparing{ background:#E8F0FF; color:#234E91; border:1px solid #CFE0FF; }
.out-for-delivery{ background:#FFF3E0; color:#8A4500; border:1px solid #FFCC80; }

.order-total{
  font-weight:800;
  font-size:14px;
  white-space:nowrap;
}

.view-btn{
  padding:8px 16px;
  border-radius:999px;
  border:1px solid var(--brown);
  background:#fff;
  color:var(--brown);
  font-weight:700;
  font-size:13px;
  cursor:pointer;
  transition:.18s;
  white-space:nowrap;
}

.view-btn:hover{ background:rgba(74,44,29,0.07); }
.view-btn.active{ background:var(--brown); color:#fff; }

/* ── DETAIL PANEL ── */
.detail-panel{
  position:sticky;
  top:24px;
}

.detail-empty{
  text-align:center;
  padding:40px 20px;
  color:var(--muted);
}

.detail-empty .empty-icon{ font-size:40px; margin-bottom:12px; }

.detail-item{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:10px 0;
  border-bottom:1px solid var(--line);
  font-size:14px;
}

.detail-item:last-of-type{ border:none; }

.detail-item span:first-child{ color:var(--muted); font-weight:600; }
.detail-item span:last-child{ font-weight:700; }

.detail-total{
  display:flex;
  justify-content:space-between;
  padding:14px 16px;
  background:rgba(74,44,29,0.07);
  border-radius:12px;
  margin-top:14px;
  font-weight:800;
  font-size:15px;
  color:var(--brown);
}

.items-list{
  margin:10px 0 0 0;
  padding-left:18px;
}

.items-list li{
  margin-bottom:6px;
  font-size:14px;
  color:var(--text);
}

/* ── CONTINUE BTN ── */
.continue-btn {
    display: inline-block;
    padding: 15px 35px;
    border: 2px solid #4b2e1e;
    border-radius: 40px;
    text-decoration: none;
    color: #4b2e1e;
    font-size: 18px;
    font-weight: bold;
    background-color: white;
}

.continue-btn:hover{ background:rgba(74,44,29,0.07); }

/* ── BADGES ── */
.badges{
  width:90%;
  max-width:1100px;
  margin:0 auto 36px;
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:14px;
}

.badge-item{
  background:#fff;
  border:1px solid var(--line);
  border-radius:16px;
  padding:16px;
  text-align:center;
}

.badge-item .badge-icon{ font-size:28px; margin-bottom:8px; }
.badge-item h4{ font-size:13px; color:var(--brown); margin-bottom:4px; }
.badge-item p{ font-size:12px; color:var(--muted); }

/* ── FOOTER ── */
footer{
  text-align:center;
  padding:18px;
  background:#fff;
  border-top:1px solid var(--line);
  color:var(--muted);
  font-size:13px;
}

@media(max-width:900px){
  main{ grid-template-columns:1fr; }
  .detail-panel{ position:static; }
  .badges{ grid-template-columns:repeat(2,1fr); }
}

@media(max-width:580px){
  header{ padding:14px 20px; }
  .order-row{ grid-template-columns:50px 1fr auto; }
  .order-row .status, .order-row .order-total{ display:none; }
  .badges{ grid-template-columns:1fr 1fr; }
}
</style>
</head>

<body>

<?php include "welcome_banner.php"; ?>

<!-- HEADER -->
<header>
  <a class="brand" href="code.php">
    <div class="logo">
      <img src="images/Brew&Bean3.jpg" alt="Logo">
    </div>
    <div class="brand-text">
      <h1>Brew & Bean</h1>
      <p>Premium Coffee</p>
    </div>
  </a>
  <nav>
    <a href="code.php">Home</a>
    <a href="products.php">Products</a>
    <a href="my_orders.php" class="active">My Orders</a>
    <a href="contact-page.php">Contact</a>
    <a href="ShoppingCart.php" class="nav-cart">&#128722; Cart</a>
          <a href="login.php" class="nav-logout-right" data-en="Logout" data-ar="log out">Logout</a>

  </nav>

</header>

<!-- MAIN -->
<main>

  <!-- Orders List -->
  <div class="card">
    <div class="card-title">
      <div class="icon">&#128203;</div>
      My Orders / Order History
    </div>

    <div id="ordersList">

      <?php if ($customer_id > 0 && count($orders) > 0): ?>

        <?php foreach ($orders as $order):
          $oid          = $order['order_id'];
          $status_raw   = strtolower($order['status']);
          $status_class = str_replace(' ', '-', $status_raw);
          $date         = date("d M Y", strtotime($order['order_date']));
          $total        = number_format($order['total_amount'], 2);
          $items_json   = json_encode($order_items[$oid] ?? []);
        ?>
        <div class="order-row">
          <div class="order-num">#<?php echo $oid; ?></div>
          <div class="order-info">
            <p>Order #<?php echo $oid; ?></p>
            <small><?php echo $date; ?></small>
          </div>
          <span class="status <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status']); ?></span>
          <span class="order-total"><?php echo $total; ?> SAR</span>
          <button class="view-btn"
            data-id="#<?php echo $oid; ?>"
            data-date="<?php echo $date; ?>"
            data-status="<?php echo $status_raw; ?>"
            data-total="<?php echo $total; ?> SAR"
            data-items='<?php echo htmlspecialchars($items_json, ENT_QUOTES); ?>'
            onclick="showOrder(this)">View</button>
        </div>
        <?php endforeach; ?>

      <?php elseif ($customer_id > 0): ?>
        <p style="color:var(--muted);padding:20px 0;text-align:center;">No orders found.</p>

      <?php else: ?>
        <!-- زائر: طلبات ثابتة كمثال -->
        <div class="order-row">
          <div class="order-num">#1023</div>
          <div class="order-info"><p>Order #1023</p><small>12 Mar</small></div>
          <span class="status delivered">Delivered</span>
          <span class="order-total">30 SAR</span>
          <button class="view-btn"
            data-id="#1023" data-date="12 Mar" data-status="delivered"
            data-total="30 SAR" data-items='["V60 Dripper x1"]'
            onclick="showOrder(this)">View</button>
        </div>
        <div class="order-row">
          <div class="order-num">#1024</div>
          <div class="order-info"><p>Order #1024</p><small>15 Mar</small></div>
          <span class="status processing">Processing</span>
          <span class="order-total">18 SAR</span>
          <button class="view-btn"
            data-id="#1024" data-date="15 Mar" data-status="processing"
            data-total="18 SAR" data-items='["Coffee Beans x1"]'
            onclick="showOrder(this)">View</button>
        </div>
      <?php endif; ?>

    </div>

    <a href="products.php" class="continue-btn">
    ← Continue Shopping
</a>
  </div>

  <!-- Order Details -->
  <div class="detail-panel">
    <div class="card" id="detailCard">
      <div class="card-title">
        <div class="icon">&#128196;</div>
        Order Details
      </div>
      <div class="detail-empty" id="detailEmpty">
        <div class="empty-icon">&#128203;</div>
        <p>Select an order to view its details</p>
      </div>
      <div id="detailContent" style="display:none;">
        <div class="detail-item"><span>Order ID</span><span id="dId"></span></div>
        <div class="detail-item"><span>Date</span><span id="dDate"></span></div>
        <div class="detail-item"><span>Status</span><span id="dStatus"></span></div>
        <div class="detail-item" style="flex-direction:column;align-items:flex-start;gap:8px;">
          <span>Items</span>
          <ul class="items-list" id="dItems"></ul>
        </div>
        <div class="detail-total">
          <span>Total</span>
          <span id="dTotal"></span>
        </div>
      </div>
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
    <p>Quick & reliable shipping</p>
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
<footer>© 2026 Brew & Bean</footer>

<script>
  let activeBtn = null;

  const statusLabels = {
    'pending'          : '<span class="status processing">Pending</span>',
    'preparing'        : '<span class="status preparing">Preparing</span>',
    'out for delivery' : '<span class="status out-for-delivery">Out for Delivery</span>',
    'completed'        : '<span class="status delivered">Completed</span>',
    'delivered'        : '<span class="status delivered">Delivered</span>',
    'processing'       : '<span class="status processing">Processing</span>'
  };

  function showOrder(btn) {
    if (activeBtn === btn) {
      btn.classList.remove('active');
      document.getElementById('detailContent').style.display = 'none';
      document.getElementById('detailEmpty').style.display   = 'block';
      activeBtn = null;
      return;
    }

    if (activeBtn) activeBtn.classList.remove('active');
    activeBtn = btn;
    btn.classList.add('active');

    const id     = btn.dataset.id;
    const date   = btn.dataset.date;
    const status = btn.dataset.status;
    const total  = btn.dataset.total;
    const items  = JSON.parse(btn.dataset.items || '[]');

    document.getElementById('dId').textContent    = id;
    document.getElementById('dDate').textContent  = date;
    document.getElementById('dTotal').textContent = total;
    document.getElementById('dStatus').innerHTML  = statusLabels[status] || status;

    const ul = document.getElementById('dItems');
    ul.innerHTML = '';
    items.forEach(function(item) {
      const li = document.createElement('li');
      li.textContent = item;
      ul.appendChild(li);
    });

    document.getElementById('detailEmpty').style.display   = 'none';
    document.getElementById('detailContent').style.display = 'block';
  }

  <?php if ($customer_id === 0): ?>
  // زائر: تحميل آخر طلب من localStorage
  const lastOrder = JSON.parse(localStorage.getItem('lastOrder') || 'null');
  if (lastOrder) {
    const list = document.getElementById('ordersList');
    const row  = document.createElement('div');
    row.className = 'order-row';
    const itemsJson = JSON.stringify([lastOrder.item]);
    row.innerHTML = `
      <div class="order-num">${lastOrder.id}</div>
      <div class="order-info"><p>Order ${lastOrder.id}</p><small>${lastOrder.date}</small></div>
      <span class="status processing">Processing</span>
      <span class="order-total">${lastOrder.total}</span>
      <button class="view-btn"
        data-id="${lastOrder.id}" data-date="${lastOrder.date}"
        data-status="processing" data-total="${lastOrder.total}"
        data-items='${itemsJson}'
        onclick="showOrder(this)">View</button>`;
    list.appendChild(row);
  }
  <?php endif; ?>
</script>
<script src="accessibility.js"></script>
</body>
</html>
