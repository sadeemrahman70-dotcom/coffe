<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
include "db_connect.php";

/* Dashboard counts */
$total_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders");
$total_orders = $total_orders_query ? mysqli_fetch_assoc($total_orders_query)['total'] : 0;

$pending_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Pending'");
$pending_orders = $pending_orders_query ? mysqli_fetch_assoc($pending_orders_query)['total'] : 0;

$completed_orders_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Completed' OR status='Delivered'");
$completed_orders = $completed_orders_query ? mysqli_fetch_assoc($completed_orders_query)['total'] : 0;

$total_products_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
$total_products = $total_products_query ? mysqli_fetch_assoc($total_products_query)['total'] : 0;

/* Chart: orders by status */
$preparing_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Preparing'");
$preparing_orders = $preparing_query ? mysqli_fetch_assoc($preparing_query)['total'] : 0;

$out_delivery_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status='Out for Delivery'");
$out_delivery_orders = $out_delivery_query ? mysqli_fetch_assoc($out_delivery_query)['total'] : 0;

/* Time-based counts */
$today_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE DATE(order_date) = CURDATE()");
$today_orders = $today_query ? mysqli_fetch_assoc($today_query)['total'] : 0;

$week_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)");
$week_orders = $week_query ? mysqli_fetch_assoc($week_query)['total'] : 0;

$month_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE YEAR(order_date) = YEAR(CURDATE()) AND MONTH(order_date) = MONTH(CURDATE())");
$month_orders = $month_query ? mysqli_fetch_assoc($month_query)['total'] : 0;

$year_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE YEAR(order_date) = YEAR(CURDATE())");
$year_orders = $year_query ? mysqli_fetch_assoc($year_query)['total'] : 0;

/* Last 7 days trend */
$trend_query = mysqli_query($conn, "
    SELECT DATE(order_date) AS day, COUNT(*) AS cnt
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(order_date)
    ORDER BY day ASC
");
$trend_map = [];
if ($trend_query) {
    while ($row = mysqli_fetch_assoc($trend_query)) {
        $trend_map[$row['day']] = (int)$row['cnt'];
    }
}
$trend_labels = [];
$trend_values = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $trend_labels[] = date('d M', strtotime($d));
    $trend_values[] = $trend_map[$d] ?? 0;
}

/* Recent orders */
$recent_orders_query = mysqli_query($conn, "
    SELECT o.order_id, o.order_date, o.total_amount, o.status,
           COALESCE(o.customer_name, c.full_name) AS full_name
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT 3
");

/* Sold-out & low-stock products */
$soldout_query    = mysqli_query($conn, "SELECT product_name, image, stock FROM products WHERE stock <= 5 ORDER BY stock ASC, product_name ASC");
$soldout_products = [];
if ($soldout_query) while ($r = mysqli_fetch_assoc($soldout_query)) $soldout_products[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Coffee Store Admin - Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    --radius:18px;
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
    width: 290px;
    background: var(--brown);
    color:#fff;
    padding: 18px;
  }

  .brand{
    display:flex;
    gap:18px;
    align-items:center;
    padding: 14px;
    border-radius: 16px;
    background: rgba(255,255,255,0.10);
    margin-bottom: 18px;
  }

  .brand .logo{
    width:70px;
    height:70px;
    border-radius:50%;
    overflow:hidden;
    border: 3px solid rgba(255,255,255,0.3);
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink: 0;
  }

  .logo img{
    width:100%;
    height:100%;
    object-fit:cover;
    border-radius:50%;
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
    color: rgba(255,255,255,0.7);
  }

  .nav{
    display:flex;
    flex-direction:column;
    gap:12px;
    margin-top: 8px;
  }

  .nav .has-sub{
    position: relative;
  }

  .nav input[type="checkbox"]{
    display:none;
  }

  .nav .nav-link{
    text-decoration:none;
    color:#fff;
    padding: 14px 14px;
    border-radius: 14px;
    background: rgba(255,255,255,0.10);
    display:flex;
    align-items:center;
    justify-content:space-between;
    transition: .18s;
    font-weight: bold;
    font-size: 14px;
    cursor:pointer;
  }

  .nav .nav-link:hover{
    background: rgba(255,255,255,0.2);
    transform: translateX(4px);
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
    margin: 8px 0 0 0;
    padding: 10px;
    border-radius: 14px;
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
    padding: 12px 12px;
    border-radius: 12px;
    background: rgba(255,255,255,0.10);
    font-size: 13px;
    font-weight: 600;
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
    padding: 18px;
  }

  .topbar{
    background: var(--panel-2);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 14px 16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap: 14px;
    margin-bottom: 16px;
  }

  .title h1{
    margin:0;
    font-size: 20px;
  }

  .title small{
    color: var(--muted);
  }

  .actions{
    display:flex;
    align-items:center;
    gap: 10px;
    flex-wrap:wrap;
  }

  .search{
    width: 340px;
    max-width: 55vw;
    padding: 11px 14px;
    border-radius: 999px;
    border: 1px solid var(--line);
    outline: none;
    background:#fff;
  }

  .btn{
    padding: 10px 14px;
    border-radius: 999px;
    border: 1px solid var(--brown-2);
    background: #fff;
    color: var(--brown);
    font-weight:bold;
    cursor:pointer;
    transition: .18s;
    text-decoration:none;
    display:inline-block;
  }

  .btn:hover{
    transform: translateY(-1px);
  }

  .btn.primary{
    background: var(--brown);
    border-color: var(--brown);
    color:#fff;
  }

  .cards{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
  }

  .card{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 16px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    min-height: 92px;
  }

  .card .meta small{
    color: var(--muted);
  }

  .card .meta h3{
    margin: 8px 0 0;
    font-size: 26px;
  }

  .chip{
    padding: 10px 14px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: rgba(74,44,29,0.07);
    color: var(--brown);
    font-weight:bold;
    font-size: 13px;
    white-space:nowrap;
  }

  .grid{
    display:grid;
    grid-template-columns: 1.65fr 1fr;
    gap: 14px;
  }

  .panel{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 16px;
  }

  .panel h2{
    margin: 0 0 14px;
    font-size: 18px;
  }

  table{
    width:100%;
    border-collapse:collapse;
    font-size: 14px;
  }

  th, td{
    text-align:left;
    padding: 12px 10px;
    border-bottom: 1px solid var(--line);
  }

  th{
    color: var(--muted);
    font-weight: bold;
  }

  .status{
    display:inline-block;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: bold;
    font-size: 13px;
    border: 1px solid transparent;
  }

  .pending{ background:#FFF1D6; color:#8A5A12; border-color:#F2D6A5; }
  .preparing{ background:#E8F0FF; color:#234E91; border-color:#CFE0FF; }
  .done{ background:#E8FFF2; color:#1E6B3A; border-color:#BFEED2; }

  .mini-btn{
    border: 1px solid var(--brown-2);
    background: #fff;
    color: var(--brown);
    padding: 8px 12px;
    border-radius: 12px;
    font-weight:bold;
    text-decoration:none;
    display:inline-block;
  }

  .mini-btn:hover{
    background: rgba(74,44,29,0.06);
  }

  .qa{
    display:grid;
    gap: 14px;
  }

  .qa-box{
    border: 1px dashed rgba(74,44,29,0.40);
    border-radius: 18px;
    background: #fff;
    padding: 14px;
  }

  .qa-box h3{
    margin:0 0 6px;
    font-size: 16px;
  }

  .qa-box p{
    margin:0 0 12px;
    color: var(--muted);
  }

  .row{
    display:flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  /* Chart panels */
  .charts-grid{
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-top: 14px;
  }

  .chart-panel{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 16px;
  }

  .chart-panel h2{
    margin: 0 0 14px;
    font-size: 18px;
  }

  .chart-wrap{
    position: relative;
    height: 220px;
  }

  .period-stats{
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }

  .period-stat{
    background: rgba(74,44,29,0.07);
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 8px 12px;
    text-align: center;
    flex: 1;
    min-width: 54px;
  }

  .period-stat small{
    display: block;
    font-size: 11px;
    color: var(--muted);
    margin-bottom: 2px;
  }

  .period-stat strong{
    font-size: 20px;
    color: var(--brown);
    font-weight: 800;
    line-height: 1;
  }

  @media (max-width: 1100px){
    .cards{ grid-template-columns: repeat(2, 1fr); }
    .grid{ grid-template-columns: 1fr; }
    .charts-grid{ grid-template-columns: 1fr; }
    .search{ width: 100%; max-width: 100%; }
  }

  @media (max-width: 780px){
    .layout{ flex-direction:column; }
    .sidebar{ width:100%; }
    .cards{ grid-template-columns: 1fr; }
    .topbar{ flex-direction:column; align-items:stretch; }
    .actions{ justify-content: flex-start; }
  }

  /* ── SOLD-OUT ALERT POPUP ── */
  .so-overlay{display:none;position:fixed;inset:0;background:rgba(20,10,5,0.5);backdrop-filter:blur(2px);z-index:900;}
  .so-overlay.open{display:block;}
  .so-popup{
    position:fixed;top:50%;left:50%;
    transform:translate(-50%,-50%) scale(.92);
    width:90%;max-width:480px;
    background:#fff;border-radius:20px;
    z-index:901;overflow:hidden;
    opacity:0;pointer-events:none;transition:.25s;
  }
  .so-popup.open{opacity:1;transform:translate(-50%,-50%) scale(1);pointer-events:auto;}
  .so-head{background:var(--brown);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;}
  .so-head h3{color:#fff;font-size:15px;font-weight:800;margin:0;display:flex;align-items:center;gap:8px;}
  .so-close{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.2);border:none;color:#fff;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.18s;}
  .so-close:hover{background:rgba(255,255,255,0.35);}
  .so-body{padding:16px 20px;max-height:320px;overflow-y:auto;}
  .so-item{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;border:1px solid #f5a0a0;background:#FFF5F5;margin-bottom:8px;}
  .so-item:last-child{margin-bottom:0;}
  .so-item img{width:44px;height:44px;object-fit:contain;border-radius:8px;background:#fff;border:1px solid #f5a0a0;flex-shrink:0;padding:3px;}
  .so-item-name{font-size:13px;font-weight:700;color:#c0392b;flex:1;line-height:1.4;}
  .so-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;white-space:nowrap;}
  .so-foot{padding:14px 20px;background:var(--panel);border-top:1px solid var(--line);display:flex;gap:10px;justify-content:flex-end;}
  .so-dismiss{padding:9px 20px;border-radius:999px;font-size:13px;font-weight:700;background:#fff;color:var(--brown);border:1.5px solid var(--line);cursor:pointer;transition:.18s;}
  .so-dismiss:hover{background:var(--bg);}
  .so-action{padding:9px 20px;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;background:var(--brown);color:#fff;border:1.5px solid var(--brown);transition:.18s;}
  .so-action:hover{background:var(--brown-2);}
</style>
</head>

<body>
  <div class="layout">

    <aside class="sidebar">
      <div class="brand">
        <div class="logo">
          <img src="images/Brew&Bean3.jpg" alt="Brew & Bean Logo">
        </div>

        <div>
          <h2>Brew & Bean</h2>
          <p>Admin Panel</p>
        </div>
      </div>

      <nav class="nav">
        <a class="nav-link active" href="admin_dashboard.php">
          Dashboard <span class="chev">›</span>
        </a>

        <div class="has-sub">
          <input type="checkbox" id="productsMenu">
          <label class="nav-link" for="productsMenu">
            Manage Products <span class="chev">›</span>
          </label>

          <div class="submenu">
            <a href="add_product.php">Add Product <span>+</span></a>
            <a href="view_products.php">View / Edit Products <span>›</span></a>
            <a href="categories.php">Categories <span>›</span></a>
          </div>
        </div>

        <a class="nav-link" href="manage_orders.php">
          Manage Orders <span class="chev">›</span>
        </a>

        <a class="nav-link" href="customers.php">
          Customers <span class="chev">›</span>
        </a>

        <a class="nav-link" href="logout.php">
          Logout <span class="chev">⎋</span>
        </a>
      </nav>
    </aside>

    <main class="main">

      <div class="topbar">
        <div class="title">
          <h1>Admin Dashboard</h1>
          <small>Overview of orders, products, and store performance</small>
        </div>

        <form class="actions" action="view_products.php" method="get">
          <input class="search" type="text" name="search" placeholder="Search orders / products..." />
          <button class="btn" type="submit">Search</button>
          <a class="btn" href="manage_orders.php">Export</a>
          <a class="btn primary" href="add_product.php">New Product</a>
        </form>
      </div>

      <section class="cards">
        <div class="card">
          <div class="meta">
            <small>Total Orders</small>
            <h3><?php echo $total_orders; ?></h3>
          </div>
          <div class="chip">All orders</div>
        </div>

        <div class="card">
          <div class="meta">
            <small>Pending Orders</small>
            <h3><?php echo $pending_orders; ?></h3>
          </div>
          <div class="chip">Need action</div>
        </div>

        <div class="card">
          <div class="meta">
            <small>Completed Orders</small>
            <h3><?php echo $completed_orders; ?></h3>
          </div>
          <div class="chip">Delivered</div>
        </div>

        <div class="card">
          <div class="meta">
            <small>Total Products</small>
            <h3><?php echo $total_products; ?></h3>
          </div>
          <div class="chip">Active</div>
        </div>
      </section>

      <section class="grid">

        <div class="panel">
          <h2>Recent Orders</h2>

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
              <?php if ($recent_orders_query && mysqli_num_rows($recent_orders_query) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($recent_orders_query)) { ?>
                  <tr>
                    <td>#<?php echo $row['order_id']; ?></td>
                    <td><?php echo !empty($row['full_name']) ? htmlspecialchars($row['full_name']) : 'Unknown'; ?></td>
                    <td><?php echo date("Y-m-d", strtotime($row['order_date'])); ?></td>
                    <td><?php echo $row['total_amount']; ?> SAR</td>
                    <td>
                      <?php
                        $s = strtolower($row['status']);
                        if ($s === 'pending') {
                            echo '<span class="status pending">Pending</span>';
                        } elseif ($s === 'preparing') {
                            echo '<span class="status preparing">Preparing</span>';
                        } elseif ($s === 'out for delivery') {
                            echo '<span class="status" style="background:#FFF3E0;color:#8A4500;border:1px solid #FFCC80;">Out for Delivery</span>';
                        } elseif ($s === 'delivered') {
                            echo '<span class="status done">Delivered</span>';
                        } else {
                            echo '<span class="status done">' . htmlspecialchars($row['status']) . '</span>';
                        }
                      ?>
                    </td>
                    <td><a class="mini-btn" href="manage_orders.php">View</a></td>
                  </tr>
                <?php } ?>
              <?php } else { ?>
                <tr>
                  <td colspan="6">No orders found.</td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <div class="panel">
          <h2>Quick Actions</h2>

          <div class="qa">
            <div class="qa-box">
              <h3>Add New Product</h3>
              <p>Create a new coffee item (beans, capsules, tools, etc.).</p>
              <div class="row">
                <a class="btn primary" href="add_product.php">Add Product</a>
                <a class="btn" href="view_products.php">Manage Products</a>
              </div>
            </div>

            <div class="qa-box">
              <h3>Manage Orders</h3>
              <p>Approve, prepare, and complete customer orders.</p>
              <div class="row">
                <a class="btn primary" href="manage_orders.php">View Orders</a>
                <a class="btn" href="manage_orders.php">Pending Only</a>
              </div>
            </div>
          </div>
        </div>

      </section>

      <!-- ===== Charts Grid ===== -->
      <div class="charts-grid">

        <!-- Chart 1: Orders by Status -->
        <div class="chart-panel">
          <h2>Orders by Status</h2>
          <div class="chart-wrap">
            <canvas id="ordersChart"></canvas>
          </div>
        </div>

        <!-- Chart 2: Orders Over Time -->
        <div class="chart-panel">
          <h2>Orders Over Time</h2>
          <div class="period-stats">
            <div class="period-stat">
              <small>Today</small>
              <strong><?php echo (int)$today_orders; ?></strong>
            </div>
            <div class="period-stat">
              <small>This Week</small>
              <strong><?php echo (int)$week_orders; ?></strong>
            </div>
            <div class="period-stat">
              <small>This Month</small>
              <strong><?php echo (int)$month_orders; ?></strong>
            </div>
            <div class="period-stat">
              <small>This Year</small>
              <strong><?php echo (int)$year_orders; ?></strong>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="trendChart"></canvas>
          </div>
        </div>

      </div>

    </main>
  </div>

  <!-- SOLD-OUT ALERT POPUP -->
  <?php if (!empty($soldout_products)): ?>
  <div class="so-overlay" id="soOverlay" onclick="closeSoldOut()"></div>
  <div class="so-popup" id="soPopup">
    <div class="so-head">
      <h3>&#9888; Stock Alert &mdash; <?php echo count($soldout_products); ?> product<?php echo count($soldout_products) > 1 ? 's' : ''; ?> need attention</h3>
      <button class="so-close" onclick="closeSoldOut()">&#10005;</button>
    </div>
    <div class="so-body">
      <?php foreach ($soldout_products as $p): ?>
      <div class="so-item">
        <img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="" onerror="this.src='images/no-product.jpeg'">
        <span class="so-item-name"><?php echo htmlspecialchars($p['product_name']); ?></span>
        <?php if ((int)$p['stock'] === 0): ?>
          <span class="so-badge" style="background:#FFF0F0;color:#c0392b;border:1px solid #f5a0a0;">Sold Out</span>
        <?php else: ?>
          <span class="so-badge" style="background:#FFF8E1;color:#B45309;border:1px solid #FCD34D;">Only <?php echo (int)$p['stock']; ?> left</span>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="so-foot">
      <button class="so-dismiss" onclick="closeSoldOut()">Dismiss</button>
      <a class="so-action" href="view_products.php">Update Stock &#8594;</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- ===== Chart.js Scripts ===== -->
  <script>
    /* Chart 1: Orders by Status */
    const ctx1 = document.getElementById('ordersChart').getContext('2d');
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: ['Pending', 'Preparing', 'Out for Delivery', 'Completed'],
        datasets: [{
          label: 'Orders',
          data: [
            <?php echo (int)$pending_orders; ?>,
            <?php echo (int)$preparing_orders; ?>,
            <?php echo (int)$out_delivery_orders; ?>,
            <?php echo (int)$completed_orders; ?>
          ],
          backgroundColor: [
            'rgba(242,183,79,0.85)',
            'rgba(79,130,230,0.85)',
            'rgba(232,144,50,0.85)',
            'rgba(52,168,100,0.85)'
          ],
          borderColor: [
            'rgba(200,140,30,1)',
            'rgba(50,100,200,1)',
            'rgba(180,100,20,1)',
            'rgba(30,130,70,1)'
          ],
          borderWidth: 1.5,
          borderRadius: 10,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: c => ` ${c.parsed.y} orders` } }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1, color: '#6B5A50', precision: 0 },
            grid: { color: 'rgba(226,210,190,0.6)' }
          },
          x: {
            ticks: { color: '#4A2C1D', font: { weight: 'bold' }, maxRotation: 0 },
            grid: { display: false }
          }
        }
      }
    });

    /* Chart 2: Orders Trend (last 7 days) */
    const ctx2 = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx2, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($trend_labels); ?>,
        datasets: [{
          label: 'Orders',
          data: <?php echo json_encode($trend_values); ?>,
          fill: true,
          backgroundColor: 'rgba(74,44,29,0.08)',
          borderColor: 'rgba(74,44,29,0.65)',
          borderWidth: 2.5,
          pointBackgroundColor: 'rgba(74,44,29,0.8)',
          pointRadius: 5,
          pointHoverRadius: 7,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: c => ` ${c.parsed.y} orders` } }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1, color: '#6B5A50', precision: 0 },
            grid: { color: 'rgba(226,210,190,0.6)' }
          },
          x: {
            ticks: { color: '#4A2C1D', font: { weight: 'bold' } },
            grid: { display: false }
          }
        }
      }
    });
  </script>

  <script>
    /* ── SOLD-OUT POPUP ── */
    function closeSoldOut(){
      const o = document.getElementById('soOverlay');
      const p = document.getElementById('soPopup');
      if(o) o.classList.remove('open');
      if(p) p.classList.remove('open');
    }
    setTimeout(function(){
      const o = document.getElementById('soOverlay');
      const p = document.getElementById('soPopup');
      if(o && p){ o.classList.add('open'); p.classList.add('open'); }
    }, 5000);
  </script>
<script src="accessibility.js"></script>
</body>
</html>