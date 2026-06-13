<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
include "db_connect.php";

/* Update order status */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id  = (int) $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $allowed   = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered'];

    if (in_array($new_status, $allowed) && $order_id > 0) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_orders.php?order_id=" . $order_id);
    exit();
}

$search_value = isset($_GET['search']) ? trim($_GET['search']) : '';
$auto_select_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

/* Orders list — collect into array */
$orders_query = "
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,
        o.customer_id,
        COALESCE(o.customer_name,    c.full_name) AS full_name,
        COALESCE(o.customer_phone,   c.phone)     AS phone,
        COALESCE(o.customer_address, c.address)   AS address
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
";

if ($search_value !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search_value);
    $orders_query .= "
        WHERE
            o.order_id LIKE '%$safe_search%'
            OR c.full_name LIKE '%$safe_search%'
    ";
}

$orders_query .= " ORDER BY o.order_date DESC";

$orders_list = [];
$res_orders = mysqli_query($conn, $orders_query);
if ($res_orders) {
    while ($row = mysqli_fetch_assoc($res_orders)) {
        $orders_list[] = $row;
    }
}

/* Fetch all items in one query */
$all_order_items = [];
if (!empty($orders_list)) {
    $ids_str = implode(',', array_map('intval', array_column($orders_list, 'order_id')));
    $items_res = mysqli_query($conn, "
        SELECT od.order_id,
               COALESCE(p.product_name, 'Unknown Product') AS product_name,
               od.quantity
        FROM order_details od
        LEFT JOIN products p ON od.product_id = p.product_id
        WHERE od.order_id IN ($ids_str)
    ");
    if ($items_res) {
        while ($item = mysqli_fetch_assoc($items_res)) {
            $all_order_items[(int)$item['order_id']][] =
                htmlspecialchars($item['product_name']) . ' × ' . (int)$item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Manage Orders - Brew & Bean</title>

<style>
  :root{
    --bg:#EFE6DA;
    --panel:#F8F1E7;
    --panel-2:#FAF3EA;
    --brown:#4A2C1D;
    --brown-2:#6A3F28;
    --text:#2E2420;
    --muted:#6B5A50;
    --line:#E2D2BE;
  }

  *{box-sizing:border-box}

  body{
    margin:0;
    font-family: Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
  }

  .layout{
    display:flex;
    min-height:100vh;
    background: var(--bg);
  }

  .sidebar{
    width:290px;
    background: var(--brown);
    color:#fff;
    padding:18px;
  }

  .brand{
    display:flex;
    gap:18px;
    align-items:center;
    padding:14px;
    border-radius:16px;
    background: rgba(255,255,255,0.10);
    margin-bottom:18px;
  }

  .brand .logo{
    width:70px;
    height:70px;
    border-radius:50%;
    overflow:hidden;
    border:3px solid rgba(255,255,255,0.3);
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
  }

  .logo img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .brand h2{
    margin:0;
    font-size:22px;
    font-weight:700;
    line-height:1.1;
  }

  .brand p{
    margin:4px 0 0;
    font-size:13px;
    color: rgba(255,255,255,0.75);
  }

  .nav{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-top:8px;
  }

  .nav .has-sub{ position:relative; }
  .nav input[type="checkbox"]{ display:none; }

  .nav .nav-link{
    text-decoration:none;
    color:#fff;
    padding:14px 14px;
    border-radius:14px;
    background: rgba(255,255,255,0.10);
    display:flex;
    align-items:center;
    justify-content:space-between;
    transition:.18s;
    font-weight:bold;
    font-size:14px;
    cursor:pointer;
  }

  .nav .nav-link:hover{
    background: rgba(255,255,255,0.16);
    transform: translateX(2px);
  }

  .nav .nav-link.active{
    background: rgba(255,255,255,0.20);
  }

  .nav .chev{
    transition:.2s;
    opacity:.9;
  }

  .submenu{
    display:none;
    margin:8px 0 0 0;
    padding:10px;
    border-radius:14px;
    background: rgba(255,255,255,0.08);
  }

  .nav input:checked ~ .submenu{
    display:grid;
    gap:10px;
  }

  .nav input:checked + label .chev{
    transform: rotate(90deg);
  }

  .submenu a{
    text-decoration:none;
    color:#fff;
    padding:12px 12px;
    border-radius:12px;
    background: rgba(255,255,255,0.10);
    font-size:13px;
    font-weight:600;
    display:flex;
    justify-content:space-between;
    align-items:center;
    transition:.18s;
  }

  .submenu a:hover{
    background: rgba(255,255,255,0.16);
    transform: translateX(2px);
  }

  .submenu a.active{
    background: rgba(255,255,255,0.20);
  }

  .main{
    flex:1;
    padding:18px;
  }

  .topbar{
    background: var(--panel-2);
    border:1px solid var(--line);
    border-radius:18px;
    padding:14px 16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin-bottom:16px;
  }

  .title h1{
    margin:0;
    font-size:20px;
  }

  .title small{
    color: var(--muted);
  }

  .actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .search{
    width:340px;
    max-width:55vw;
    padding:11px 14px;
    border-radius:999px;
    border:1px solid var(--line);
    outline:none;
    background:#fff;
  }

  .btn{
    padding:10px 14px;
    border-radius:999px;
    border:1px solid var(--brown-2);
    background:#fff;
    color: var(--brown);
    font-weight:bold;
    cursor:pointer;
    transition:.18s;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  .btn:hover{
    transform: translateY(-1px);
  }

  .btn.primary{
    background: var(--brown);
    border-color: var(--brown);
    color:#fff;
  }

  .btn.warning{
    border-color:#B8860B;
    color:#8A5A12;
    background:#fff;
  }

  .btn.success{
    border-color:#2E8B57;
    color:#1E6B3A;
    background:#fff;
  }

  .grid{
    display:grid;
    grid-template-columns: 1.55fr 1fr;
    gap:14px;
    align-items:start;
  }

  .panel{
    background: var(--panel);
    border:1px solid var(--line);
    border-radius:18px;
    padding:16px;
  }

  .panel h2{
    margin:0 0 14px;
    font-size:18px;
  }

  table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
  }

  th, td{
    text-align:left;
    padding:12px 10px;
    border-bottom:1px solid var(--line);
    vertical-align:middle;
  }

  th{
    color: var(--muted);
    font-weight:bold;
  }

  .status{
    display:inline-block;
    padding:8px 12px;
    border-radius:999px;
    font-weight:bold;
    font-size:13px;
    border:1px solid transparent;
  }

  .pending{ background:#FFF1D6; color:#8A5A12; border-color:#F2D6A5; }
  .preparing{ background:#E8F0FF; color:#234E91; border-color:#CFE0FF; }
  .out-for-delivery{ background:#FFF3E0; color:#8A4500; border-color:#FFCC80; }
  .delivered{ background:#E8FFF2; color:#1E6B3A; border-color:#BFEED2; }
  .completed{ background:#E8FFF2; color:#1E6B3A; border-color:#BFEED2; }

  .mini-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }

  .mini-btn{
    border:1px solid var(--brown-2);
    background:#fff;
    color: var(--brown);
    padding:8px 12px;
    border-radius:12px;
    cursor:pointer;
    font-weight:bold;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  .mini-btn:hover{
    background: rgba(74,44,29,0.06);
  }

  .detail-box{
    border:1px dashed rgba(74,44,29,0.35);
    border-radius:18px;
    background:#fff;
    padding:14px;
    margin-bottom:14px;
  }

  .detail-box h3{
    margin:0 0 10px;
    font-size:16px;
  }

  .detail-row{
    margin-bottom:10px;
  }

  .detail-label{
    display:block;
    font-size:13px;
    color: var(--muted);
    font-weight:bold;
    margin-bottom:4px;
  }

  .detail-value{
    background:#fff;
    border:1px solid var(--line);
    border-radius:14px;
    padding:12px;
    font-size:14px;
  }

  .order-items{
    border:1px dashed rgba(74,44,29,0.35);
    border-radius:18px;
    background:#fff;
    padding:14px;
  }

  .order-items h3{
    margin:0 0 10px;
    font-size:16px;
  }

  .order-items ul{
    margin:0;
    padding-left:18px;
  }

  .order-items li{
    margin-bottom:8px;
    color: var(--text);
  }

  @media (max-width:1100px){
    .grid{ grid-template-columns:1fr; }
    .search{ width:100%; max-width:100%; }
  }

  @media (max-width:780px){
    .layout{ flex-direction:column; }
    .sidebar{ width:100%; }
    .topbar{ flex-direction:column; align-items:stretch; }
    .actions{ justify-content:space-between; }
  }
</style>
</head>

<body>
<div class="layout">

  <aside class="sidebar">
    <div class="brand">
      <div class="logo">
        <img src="images/Brew&Bean3.jpg" alt="Logo">
      </div>
      <div>
        <h2>Brew & Bean</h2>
        <p>Admin Panel</p>
      </div>
    </div>

    <nav class="nav">
      <a class="nav-link" href="admin_dashboard.php">Dashboard <span class="chev">›</span></a>

      <div class="has-sub">
        <input type="checkbox" id="productsMenu" checked>
        <label class="nav-link" for="productsMenu">
          Manage Products <span class="chev">›</span>
        </label>

        <div class="submenu">
          <a href="add_product.php">Add Product <span>+</span></a>
          <a href="view_products.php">View / Edit Products <span>›</span></a>
          <a href="categories.php">Categories <span>›</span></a>
        </div>
      </div>

      <a class="nav-link active" href="manage_orders.php">Manage Orders <span class="chev">›</span></a>
      <a class="nav-link" href="customers.php">Customers <span class="chev">›</span></a>
      <a class="nav-link" href="logout.php">Logout <span class="chev">⎋</span></a>
    </nav>
  </aside>

  <main class="main">

    <div class="topbar">
      <div class="title">
        <h1>Manage Orders</h1>
        <small>Review, update, and complete customer orders</small>
      </div>

      <form class="actions" method="get" action="manage_orders.php">
        <input class="search" type="text" name="search" placeholder="Search order ID / customer..." value="<?php echo htmlspecialchars($search_value); ?>" />
        <button class="btn" type="submit">Search</button>
        <a class="btn" href="admin_dashboard.php">Back to Dashboard</a>
      </form>
    </div>

    <section class="grid">

      <div class="panel">
        <h2>Orders List</h2>

        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Total</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!empty($orders_list)) { ?>
              <?php foreach ($orders_list as $order) {
                $s    = strtolower($order['status'] ?? '');
                $items = $all_order_items[(int)$order['order_id']] ?? [];
                $data  = json_encode([
                  'id'      => (int)$order['order_id'],
                  'name'    => $order['full_name'] ?? 'Unknown',
                  'phone'   => $order['phone'] ?? 'N/A',
                  'address' => $order['address'] ?? 'N/A',
                  'status'  => $order['status'] ?? 'Pending',
                  'total'   => $order['total_amount'],
                  'date'    => !empty($order['order_date']) ? date("Y-m-d", strtotime($order['order_date'])) : 'N/A',
                  'items'   => $items,
                ]);
              ?>
                <tr>
                  <td>#<?php echo $order['order_id']; ?></td>
                  <td><?php echo !empty($order['full_name']) ? htmlspecialchars($order['full_name']) : 'Unknown'; ?></td>
                  <td><?php echo !empty($order['order_date']) ? date("Y-m-d", strtotime($order['order_date'])) : 'N/A'; ?></td>
                  <td><?php echo $order['total_amount']; ?> SAR</td>
                  <td>
                    <?php
                      if ($s === 'pending')           echo '<span class="status pending">Pending</span>';
                      elseif ($s === 'preparing')     echo '<span class="status preparing">Preparing</span>';
                      elseif ($s === 'out for delivery') echo '<span class="status out-for-delivery">Out for Delivery</span>';
                      elseif ($s === 'delivered')     echo '<span class="status delivered">Delivered</span>';
                      else echo '<span class="status completed">' . htmlspecialchars($order['status']) . '</span>';
                    ?>
                  </td>
                  <td>
                    <button class="mini-btn view-order-btn"
                      onclick="showOrderPanel(this)"
                      data-order='<?php echo htmlspecialchars($data, ENT_QUOTES); ?>'>
                      View
                    </button>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr><td colspan="6">No orders found.</td></tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <!-- Right panel — JS driven -->
      <div class="panel" id="orderDetailPanel">
        <h2>Order Details</h2>

        <div class="detail-box">
          <h3>Selected Order</h3>

          <div class="detail-row">
            <span class="detail-label">Order ID</span>
            <div class="detail-value" id="d-id">Please select an order</div>
          </div>
          <div class="detail-row">
            <span class="detail-label">Customer</span>
            <div class="detail-value" id="d-name">N/A</div>
          </div>
          <div class="detail-row">
            <span class="detail-label">Phone</span>
            <div class="detail-value" id="d-phone">N/A</div>
          </div>
          <div class="detail-row">
            <span class="detail-label">Status</span>
            <div class="detail-value" id="d-status">N/A</div>
          </div>
          <div class="detail-row">
            <span class="detail-label">Delivery Address</span>
            <div class="detail-value" id="d-address">N/A</div>
          </div>
          <div class="detail-row">
            <span class="detail-label">Total Amount</span>
            <div class="detail-value" id="d-total">0 SAR</div>
          </div>

          <div id="d-action"></div>
        </div>

        <div class="order-items">
          <h3>Order Items</h3>
          <ul id="d-items"><li style="color:var(--muted);">Select any order to view its items here.</li></ul>
        </div>
      </div>

    </section>
  </main>
</div>
<script>
  /* ── Show order in right panel ── */
  function showOrderPanel(btn) {
    const o = JSON.parse(btn.getAttribute('data-order'));

    document.getElementById('d-id').textContent      = '#' + o.id;
    document.getElementById('d-name').textContent    = o.name;
    document.getElementById('d-phone').textContent   = o.phone;
    document.getElementById('d-address').textContent = o.address;
    document.getElementById('d-total').textContent   = o.total + ' SAR';

    const s = o.status.toLowerCase();
    const statusMap = {
      'pending'          : '<span class="status pending">Pending</span>',
      'preparing'        : '<span class="status preparing">Preparing</span>',
      'out for delivery' : '<span class="status out-for-delivery">Out for Delivery</span>',
      'delivered'        : '<span class="status delivered">Delivered</span>'
    };
    document.getElementById('d-status').innerHTML = statusMap[s] || o.status;

    /* Items */
    const ul = document.getElementById('d-items');
    ul.innerHTML = '';
    if (o.items && o.items.length > 0) {
      o.items.forEach(function(item) {
        const li = document.createElement('li');
        li.textContent = item;
        ul.appendChild(li);
      });
    } else {
      ul.innerHTML = '<li style="color:var(--muted);">No items found.</li>';
    }

    /* Status change button */
    const actionDiv = document.getElementById('d-action');
    if (s === 'delivered') {
      actionDiv.innerHTML = '<div style="margin-top:12px;padding:10px 14px;background:#E8FFF2;border:1px solid #BFEED2;border-radius:12px;color:#1E6B3A;font-weight:bold;text-align:center;">✓ Order Delivered</div>';
    } else {
      let btnHtml = '';
      if (s === 'pending') {
        btnHtml = '<button class="btn warning" type="submit" name="new_status" value="Preparing">Mark as Preparing</button>';
      } else if (s === 'preparing') {
        btnHtml = '<button class="btn" style="border-color:#E8720C;color:#8A4500;" type="submit" name="new_status" value="Out for Delivery">Mark as Out for Delivery</button>';
      } else if (s === 'out for delivery') {
        btnHtml = '<button class="btn success" type="submit" name="new_status" value="Delivered">Mark as Delivered</button>';
      }
      actionDiv.innerHTML = btnHtml
        ? '<form method="post" action="manage_orders.php" style="margin-top:12px;"><input type="hidden" name="update_status" value="1"><input type="hidden" name="order_id" value="' + o.id + '"><div class="mini-actions">' + btnHtml + '</div></form>'
        : '';
    }

    /* Highlight active row */
    document.querySelectorAll('.view-order-btn').forEach(b => b.style.background = '');
    btn.style.background = 'rgba(74,44,29,0.1)';

    /* Scroll panel into view on small screens */
    document.getElementById('orderDetailPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  /* ── Search ── */
  const searchInput = document.querySelector('.search');
  const tableBody   = document.querySelector('tbody');
  const allRows     = Array.from(tableBody.querySelectorAll('tr'));
  const listTitle   = document.querySelector('.panel h2');

  const noResults = document.createElement('tr');
  noResults.innerHTML = '<td colspan="6" style="text-align:center;color:var(--muted);padding:20px 0;">No orders match your search.</td>';
  noResults.style.display = 'none';
  tableBody.appendChild(noResults);

  function runSearch() {
    const words = searchInput.value.trim().toLowerCase().split(/\s+/).filter(w => w.length > 0);
    let visible = 0;
    allRows.forEach(function(row) {
      if (!row.querySelector('td')) return;
      const text = row.textContent.toLowerCase();
      const match = words.length === 0 || words.some(w => text.includes(w));
      row.style.display = match ? '' : 'none';
      if (match) visible++;
    });
    noResults.style.display = visible === 0 ? '' : 'none';
    listTitle.textContent = visible === allRows.length ? 'Orders List' : 'Orders List (' + visible + ' of ' + allRows.length + ')';
  }

  searchInput.addEventListener('input', runSearch);
  searchInput.closest('form').addEventListener('submit', function(e) { e.preventDefault(); runSearch(); });
  searchInput.addEventListener('keydown', function(e) { if (e.key === 'Escape') { this.value = ''; runSearch(); this.blur(); } });

  /* Auto-open panel if redirected after status update */
  <?php if ($auto_select_id > 0): ?>
  var autoBtn = document.querySelector('[data-order]');
  document.querySelectorAll('.view-order-btn').forEach(function(btn) {
    try {
      var d = JSON.parse(btn.getAttribute('data-order'));
      if (d.id === <?= $auto_select_id ?>) { showOrderPanel(btn); }
    } catch(e) {}
  });
  <?php endif; ?>
</script>
<script src="accessibility.js"></script>
</body>
</html>
