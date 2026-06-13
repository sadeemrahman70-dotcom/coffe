<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
include "db_connect.php";

$search_value = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

/* Customers list with orders count */
$customers_query = "
    SELECT 
        c.customer_id,
        c.full_name,
        c.phone,
        c.email,
        c.address,
        COUNT(o.order_id) AS total_orders,
        MAX(o.order_date) AS last_order
    FROM customers c
    LEFT JOIN orders o ON c.customer_id = o.customer_id
";

if ($search_value !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search_value);
    $customers_query .= "
        WHERE c.full_name LIKE '%$safe_search%'
        OR c.phone LIKE '%$safe_search%'
    ";
}

$customers_query .= "
    GROUP BY c.customer_id, c.full_name, c.phone, c.email, c.address
    ORDER BY c.full_name ASC
";

$customers_result = mysqli_query($conn, $customers_query);

/* Selected customer details */
$selected_customer = null;

if ($selected_customer_id > 0) {
    $selected_query = "
        SELECT 
            c.customer_id,
            c.full_name,
            c.phone,
            c.email,
            c.address,
            COUNT(o.order_id) AS total_orders,
            MAX(o.order_date) AS last_order
        FROM customers c
        LEFT JOIN orders o ON c.customer_id = o.customer_id
        WHERE c.customer_id = $selected_customer_id
        GROUP BY c.customer_id, c.full_name, c.phone, c.email, c.address
        LIMIT 1
    ";

    $selected_result = mysqli_query($conn, $selected_query);
    if ($selected_result && mysqli_num_rows($selected_result) > 0) {
        $selected_customer = mysqli_fetch_assoc($selected_result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Customers - Brew & Bean</title>

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

  .grid{
    display:grid;
    grid-template-columns: 1.5fr 1fr;
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

  .customer-badge{
    display:inline-block;
    padding:8px 12px;
    border-radius:999px;
    font-weight:bold;
    font-size:13px;
    border:1px solid var(--line);
    background: rgba(74,44,29,0.07);
    color: var(--brown);
  }

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

  .notes-box{
    border:1px dashed rgba(74,44,29,0.35);
    border-radius:18px;
    background:#fff;
    padding:14px;
  }

  .notes-box h3{
    margin:0 0 10px;
    font-size:16px;
  }

  .notes-box p{
    margin:0;
    line-height:1.6;
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

      <a class="nav-link" href="manage_orders.php">Manage Orders <span class="chev">›</span></a>
      <a class="nav-link active" href="customers.php">Customers <span class="chev">›</span></a>
      <a class="nav-link" href="logout.php">Logout <span class="chev">⎋</span></a>
    </nav>
  </aside>

  <main class="main">

    <div class="topbar">
      <div class="title">
        <h1>Customers</h1>
        <small>View and manage customer information</small>
      </div>

      <form class="actions" method="get" action="customers.php">
        <input class="search" type="text" name="search" placeholder="Search customer name / phone..." value="<?php echo htmlspecialchars($search_value); ?>" />
        <button class="btn" type="submit">Search</button>
        <a class="btn" href="admin_dashboard.php">Back to Dashboard</a>
      </form>
    </div>

    <section class="grid">

      <div class="panel">
        <h2>Customers List</h2>

        <table>
          <thead>
            <tr>
              <th>Customer Name</th>
              <th>Phone</th>
              <th>Orders</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <?php if ($customers_result && mysqli_num_rows($customers_result) > 0) { ?>
              <?php while ($customer = mysqli_fetch_assoc($customers_result)) {
                $cdata = json_encode([
                  'id'         => (int)$customer['customer_id'],
                  'name'       => $customer['full_name'] ?? 'Unknown',
                  'phone'      => $customer['phone'] ?? 'N/A',
                  'email'      => $customer['email'] ?? 'N/A',
                  'address'    => $customer['address'] ?? 'N/A',
                  'orders'     => (int)$customer['total_orders'],
                  'last_order' => !empty($customer['last_order']) ? date("Y-m-d", strtotime($customer['last_order'])) : 'No orders yet',
                ]);
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                  <td><?php echo !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : 'N/A'; ?></td>
                  <td><?php echo (int)$customer['total_orders']; ?> Orders</td>
                  <td>
                    <span class="customer-badge">
                      <?php echo ((int)$customer['total_orders'] > 0) ? 'Active' : 'New'; ?>
                    </span>
                  </td>
                  <td>
                    <div class="mini-actions">
                      <button class="mini-btn view-customer-btn"
                        data-customer='<?php echo htmlspecialchars($cdata, ENT_QUOTES); ?>'
                        onclick="showCustomerPanel(this)">View</button>
                    </div>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr>
                <td colspan="5">No customers found.</td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <h2>Customer Details</h2>

        <div class="detail-box">
          <h3>Selected Customer</h3>
          <div class="detail-row"><span class="detail-label">Customer Name</span><div class="detail-value" id="c-name">Please select a customer</div></div>
          <div class="detail-row"><span class="detail-label">Phone Number</span><div class="detail-value" id="c-phone">N/A</div></div>
          <div class="detail-row"><span class="detail-label">Email</span><div class="detail-value" id="c-email">N/A</div></div>
          <div class="detail-row"><span class="detail-label">Address</span><div class="detail-value" id="c-address">N/A</div></div>
          <div class="detail-row"><span class="detail-label">Total Orders</span><div class="detail-value" id="c-orders">0 Orders</div></div>
          <div class="detail-row"><span class="detail-label">Last Order</span><div class="detail-value" id="c-last">No orders yet</div></div>
        </div>

        <div class="notes-box">
          <h3>Notes</h3>
          <p id="c-notes">Select any customer from the table to view their full details here.</p>
        </div>

      </div>

    </section>
  </main>
</div>
<script>
  function showCustomerPanel(btn) {
    const c = JSON.parse(btn.getAttribute('data-customer'));
    document.getElementById('c-name').textContent    = c.name;
    document.getElementById('c-phone').textContent   = c.phone;
    document.getElementById('c-email').textContent   = c.email;
    document.getElementById('c-address').textContent = c.address;
    document.getElementById('c-orders').textContent  = c.orders + ' Orders';
    document.getElementById('c-last').textContent    = c.last_order;
    document.getElementById('c-notes').textContent   = 'Customer information loaded from database.';

    document.querySelectorAll('.view-customer-btn').forEach(b => b.style.background = '');
    btn.style.background = 'rgba(74,44,29,0.1)';
  }

  const searchInput = document.querySelector('.search');
  const tableBody   = document.querySelector('tbody');
  const allRows     = Array.from(tableBody.querySelectorAll('tr'));
  const panelTitle  = document.querySelector('.panel h2');

  // رسالة "لا نتائج"
  const noResults = document.createElement('tr');
  noResults.innerHTML = '<td colspan="5" style="text-align:center;color:var(--muted);padding:20px 0;">No customers match your search.</td>';
  noResults.style.display = 'none';
  tableBody.appendChild(noResults);

  function updateCount(visible) {
    panelTitle.textContent = visible === allRows.length
      ? 'Customers List'
      : 'Customers List (' + visible + ' of ' + allRows.length + ')';
  }

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
    updateCount(visible);
  }

  searchInput.addEventListener('input', runSearch);

  // منع reload عند الضغط على Search
  searchInput.closest('form').addEventListener('submit', function(e) {
    e.preventDefault();
    runSearch();
  });

  // مسح البحث بـ Escape
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      this.value = '';
      runSearch();
      this.blur();
    }
  });

  if (searchInput.value.trim() !== '') runSearch();
</script>
<script src="accessibility.js"></script>
</body>
</html>
