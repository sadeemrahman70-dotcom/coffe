<?php
session_start();
include "db_connect.php";

$coffeeTools = ['filter' => [], 'espresso' => []];
$coffeeBeans = ['filter' => [], 'espresso' => []];

// جلب التصنيفات وتحديد IDs
$cats_res = mysqli_query($conn, "SELECT * FROM categories");
$beans_id = null;
$tools_id = null;
while ($c = mysqli_fetch_assoc($cats_res)) {
    if (strtolower($c['category_name']) === 'beans') $beans_id = (int)$c['category_id'];
    if (strtolower($c['category_name']) === 'tools') $tools_id = (int)$c['category_id'];
}

// استلام رقم القسم من الرابط
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// تحديد الـ initial category للـ JS
$init_cat = '';
if ($category_id > 0) {
    if ($category_id === $beans_id) $init_cat = 'beans';
    elseif ($category_id === $tools_id) $init_cat = 'tools';
}

// نجلب كل المنتجات دائماً — الـ filtering يصير في JS
$result = mysqli_query($conn, "SELECT * FROM products ORDER BY product_name ASC");

while ($row = mysqli_fetch_assoc($result)) {
    $item = [
        'name'  => $row['product_name'],
        'image' => $row['image'],
        'price' => (float)$row['price'],
        'stock' => (int)$row['stock'],
        'desc'  => $row['description']
    ];

    // تحديد الفئة بشكل صحيح من DB بدل hardcoded IDs
    $isBean = ($beans_id !== null && (int)$row['category_id'] === $beans_id);
    $method = strtolower($row['brewing_method'] ?? '');
    $hasF   = strpos($method, 'filter')   !== false;
    $hasE   = strpos($method, 'espresso') !== false;

    if (!$hasF && !$hasE) { $hasF = true; $hasE = true; }

    if ($isBean) {
        if ($hasF) $coffeeBeans['filter'][]   = $item;
        if ($hasE) $coffeeBeans['espresso'][] = $item;
    } else {
        if ($hasF) $coffeeTools['filter'][]   = $item;
        if ($hasE) $coffeeTools['espresso'][] = $item;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['search_keyword'])) {
        $keyword = $_POST['search_keyword'];
        $customer_id = $_SESSION['customer_id'] ?? null;
        $stmt = $conn->prepare("INSERT INTO search_history (customer_id, search_keyword) VALUES (?, ?)");
        $stmt->bind_param("is", $customer_id, $keyword);
        if (!$stmt->execute()) {
            error_log("Insert failed: " . $stmt->error);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products - Brew & Bean</title>
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
header{background:var(--brown);padding:16px 40px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
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

/* ── TOP BAR (below header) ── */
.topbar{
  background:#fff;
  border-bottom:1px solid var(--line);
  padding:12px 40px;
  display:flex;
  align-items:center;
  gap:14px;
  position:sticky;
  top:88px;
  z-index:150;
}

.filter-btn{
  display:flex;
  align-items:center;
  gap:8px;
  padding:9px 18px;
  border-radius:999px;
  border:1.5px solid var(--line);
  background:#fff;
  color:var(--brown);
  font-weight:700;
  font-size:13px;
  cursor:pointer;
  transition:.18s;
}
.filter-btn:hover{background:var(--panel);border-color:var(--brown);}
.filter-btn .hamburger{
  display:flex;flex-direction:column;gap:4px;
}
.filter-btn .hamburger span{
  display:block;width:16px;height:2px;background:var(--brown);border-radius:2px;transition:.2s;
}

.topbar-search{
  flex:1;
  max-width:400px;
  display:flex;
  align-items:center;
  background:var(--panel);
  border:1.5px solid var(--line);
  border-radius:999px;
  padding:8px 16px;
  gap:8px;
  transition:.18s;
}
.topbar-search:focus-within{border-color:var(--brown-2);}
.topbar-search span{color:var(--muted);font-size:14px;}
.topbar-search input{
  border:none;background:transparent;font-size:13px;
  color:var(--text);outline:none;width:100%;
}
.topbar-search input::placeholder{color:var(--muted);}

.topbar-sort{
  display:flex;align-items:center;gap:8px;margin-left:auto;
}
.topbar-sort label{font-size:12px;color:var(--muted);font-weight:600;white-space:nowrap;}
.topbar-sort select{
  padding:8px 12px;
  border-radius:999px;
  border:1.5px solid var(--line);
  background:#fff;
  font-size:12px;
  color:var(--text);
  outline:none;
  cursor:pointer;
  transition:.18s;
}
.topbar-sort select:focus{border-color:var(--brown-2);}

/* ── MAIN ── */
.main-content{
  flex:1;
  width:90%;
  max-width:1200px;
  margin:32px auto;
}

.content-head{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:22px;flex-wrap:wrap;gap:10px;
}
.content-title{font-size:20px;font-weight:800;color:var(--brown);}
.count-badge{
  font-size:12px;color:var(--muted);
  background:var(--panel);border:1px solid var(--line);
  padding:4px 14px;border-radius:999px;
}

/* ── GRID ── */
.products-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(210px,1fr));
  gap:18px;
}

.prod-card{
  background:#fff;border:1px solid var(--line);
  border-radius:var(--radius);overflow:hidden;
  transition:.22s;
  box-shadow:0 4px 14px rgba(46,36,32,0.06);
  display:flex;flex-direction:column;
}
.prod-card:hover{transform:translateY(-5px);box-shadow:0 14px 30px rgba(46,36,32,0.13);border-color:rgba(74,44,29,0.25);}
.prod-img{width:100%;height:180px;object-fit:contain;background:var(--panel);padding:14px;display:block;}
.prod-body{padding:14px;flex:1;display:flex;flex-direction:column;gap:6px;}
.prod-name{font-size:12px;font-weight:700;color:var(--text);line-height:1.45;flex:1;}
.prod-price{font-size:15px;font-weight:800;color:var(--brown);}
.prod-btn{
  margin:0 14px 14px;padding:10px;
  border-radius:999px;border:none;
  background:var(--brown);color:#fff;
  font-weight:700;font-size:13px;
  cursor:pointer;transition:.18s;
}
.prod-btn:hover{background:var(--brown-2);}
.prod-btn.added{background:#1E6B3A;}
.prod-btn.sold-out-btn{background:#ccc;cursor:not-allowed;}
.prod-btn.sold-out-btn:hover{background:#ccc;}
.stock-badge{
  display:inline-block;
  font-size:11px;font-weight:700;
  padding:3px 10px;border-radius:999px;
  margin-top:2px;
}
.stock-badge.in-stock{background:#E8FFF2;color:#1E6B3A;border:1px solid #BFEED2;}
.stock-badge.low-stock{background:#FFF8E1;color:#B45309;border:1px solid #FCD34D;}
.stock-badge.sold-out{background:#FFF0F0;color:#c0392b;border:1px solid #f5a0a0;}

/* no results */
.no-results{
  grid-column:1/-1;text-align:center;
  padding:80px 20px;color:var(--muted);
}
.no-results .nr-icon{font-size:44px;margin-bottom:14px;}

/* ── DRAWER BACKDROP ── */
.drawer-backdrop{
  display:none;
  position:fixed;inset:0;
  background:rgba(20,10,5,0.5);
  backdrop-filter:blur(2px);
  z-index:399;
  transition:opacity .28s;
}
.drawer-backdrop.open{display:block;}

/* ── DRAWER SIDEBAR ── */
.drawer{
  position:fixed;
  top:0;left:-320px;
  width:300px;
  height:100vh;
  background:#fff;
  z-index:400;
  display:flex;flex-direction:column;
  box-shadow:6px 0 40px rgba(0,0,0,0.18);
  transition:left .3s cubic-bezier(.4,0,.2,1);
  overflow:hidden;
}
.drawer.open{left:0;}

/* drawer header */
.drawer-head{
  background:var(--brown);
  padding:20px 22px;
  display:flex;align-items:center;justify-content:space-between;
  flex-shrink:0;
}
.drawer-head h3{color:#fff;font-size:16px;font-weight:800;letter-spacing:.3px;}
.drawer-close{
  width:32px;height:32px;
  border-radius:50%;
  background:rgba(255,255,255,0.15);
  border:1px solid rgba(255,255,255,0.25);
  color:#fff;font-size:16px;
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:.18s;
}
.drawer-close:hover{background:rgba(255,255,255,0.3);}

/* drawer body */
.drawer-body{flex:1;overflow-y:auto;padding:0;}

.drawer-section{
  padding:20px 22px;
  border-bottom:1px solid var(--line);
}
.drawer-section-label{
  font-size:10px;font-weight:800;
  color:var(--muted);text-transform:uppercase;
  letter-spacing:1.2px;margin-bottom:14px;display:block;
}

/* category tree */
.cat-group{margin-bottom:4px;}
.cat-parent{
  display:flex;align-items:center;justify-content:space-between;
  padding:11px 14px;border-radius:12px;
  cursor:pointer;font-weight:700;font-size:14px;
  color:var(--brown);transition:.15s;user-select:none;
}
.cat-parent:hover{background:var(--panel);}
.cat-parent.open{background:rgba(74,44,29,0.08);}
.cat-arrow{
  font-size:10px;transition:transform .22s;
  color:var(--muted);
}
.cat-parent.open .cat-arrow{transform:rotate(90deg);}

.cat-children{display:none;padding:4px 0 4px 14px;}
.cat-children.open{display:block;}

.cat-child{
  display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:10px;
  cursor:pointer;font-size:13px;color:var(--text);
  transition:.15s;font-weight:600;
}
.cat-child:hover{background:var(--panel);}
.cat-child.active{background:var(--brown);color:#fff;}
.cat-child .dot{
  width:6px;height:6px;border-radius:50%;
  background:currentColor;flex-shrink:0;
}

/* recommend box */
.drawer-recommend{
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:14px;padding:16px;
}
.drawer-recommend h4{
  font-size:13px;font-weight:800;
  color:var(--brown);margin-bottom:12px;
  display:flex;align-items:center;gap:6px;
}
.drawer-recommend select{
  width:100%;padding:9px 12px;
  border-radius:10px;border:1.5px solid var(--line);
  background:#fff;font-size:13px;color:var(--text);
  margin-bottom:12px;outline:none;
}
.drawer-recommend select:focus{border-color:var(--brown-2);}
.drawer-recommend button{
  width:100%;padding:11px;border-radius:999px;
  border:none;background:var(--brown);color:#fff;
  font-weight:700;font-size:13px;cursor:pointer;transition:.18s;
}
.drawer-recommend button:hover{background:var(--brown-2);}

/* ── DETAIL PANEL (right slide) ── */
.detail-backdrop{
  display:none;position:fixed;inset:0;
  background:rgba(20,10,5,0.5);backdrop-filter:blur(2px);z-index:498;
}
.detail-backdrop.open{display:block;}

.detail-panel{
  position:fixed;top:0;right:-460px;width:440px;height:100vh;
  background:#fff;z-index:499;
  display:flex;flex-direction:column;
  box-shadow:-8px 0 40px rgba(0,0,0,0.2);
  transition:right .32s cubic-bezier(.4,0,.2,1);
  overflow:hidden;
}
.detail-panel.open{right:0;}

.dp-header{
  background:var(--brown);padding:16px 20px;
  display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
}
.dp-header-title{color:#fff;font-size:13px;font-weight:700;opacity:.85;letter-spacing:.3px;}
.dp-close{
  width:32px;height:32px;border-radius:50%;
  background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);
  color:#fff;font-size:16px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;transition:.18s;
}
.dp-close:hover{background:rgba(255,255,255,0.3);}

.dp-body{flex:1;overflow-y:auto;padding-bottom:24px;}

.dp-img-wrap{
  width:100%;height:270px;background:var(--panel);
  display:flex;align-items:center;justify-content:center;overflow:hidden;
}
.dp-img-wrap img{max-width:100%;max-height:100%;object-fit:contain;padding:18px;}

.dp-content{padding:20px;}

.dp-name{font-size:17px;font-weight:800;color:var(--brown);line-height:1.4;margin-bottom:12px;}

.dp-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.dp-badge{
  padding:4px 12px;border-radius:999px;font-size:11px;font-weight:700;
  border:1px solid var(--line);background:var(--panel);color:var(--muted);
}
.dp-badge.origin{background:rgba(74,44,29,0.09);color:var(--brown);border-color:rgba(74,44,29,0.2);}

.dp-stars{color:#E0A800;font-size:15px;margin-bottom:14px;letter-spacing:2px;}

.dp-price-row{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.dp-price{font-size:24px;font-weight:800;color:var(--brown);}
.dp-old-price{font-size:14px;color:var(--muted);text-decoration:line-through;}
.dp-discount-tag{
  background:#E8FFF2;color:#1E6B3A;border:1px solid #BFEED2;
  padding:3px 10px;border-radius:999px;font-size:11px;font-weight:800;
}

.dp-divider{border:none;border-top:1px solid var(--line);margin:16px 0;}
.dp-section-label{font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;display:block;}

.dp-notes{
  background:var(--panel);border:1px solid var(--line);border-radius:12px;
  padding:12px 14px;font-size:13px;color:var(--text);line-height:1.6;margin-bottom:16px;
}

.dp-grind select{
  width:100%;padding:11px 14px;border-radius:12px;
  border:1.5px solid var(--line);background:#fff;
  font-size:13px;color:var(--text);outline:none;cursor:pointer;
  transition:.18s;margin-bottom:16px;
}
.dp-grind select:focus{border-color:var(--brown-2);}

.dp-qty-row{display:flex;align-items:center;gap:14px;margin-bottom:20px;}
.dp-qty-label{font-size:13px;font-weight:700;color:var(--muted);}
.dp-qty-ctrl{
  display:flex;align-items:center;
  border:1.5px solid var(--line);border-radius:999px;overflow:hidden;
}
.dp-qty-btn{
  width:34px;height:34px;border:none;background:transparent;
  font-size:18px;font-weight:700;color:var(--brown);cursor:pointer;
  display:flex;align-items:center;justify-content:center;transition:.15s;
}
.dp-qty-btn:hover{background:var(--panel);}
.dp-qty-val{
  width:40px;text-align:center;font-size:15px;font-weight:800;color:var(--text);
  background:#fff;border:none;border-left:1px solid var(--line);border-right:1px solid var(--line);
  padding:0;-moz-appearance:textfield;
}
.dp-qty-val::-webkit-outer-spin-button,.dp-qty-val::-webkit-inner-spin-button{-webkit-appearance:none;}

.dp-add-btn{
  display:block;width:100%;padding:14px;border-radius:999px;
  border:none;background:var(--brown);color:#fff;
  font-weight:800;font-size:15px;cursor:pointer;transition:.18s;
}
.dp-add-btn:hover{background:var(--brown-2);}
.dp-add-btn.added{background:#1E6B3A;}

.dp-recipe{margin-top:20px;text-align:center;}
.dp-recipe img{max-width:100%;border-radius:14px;border:1px solid var(--line);}

/* ── GRIND HELP POPUP ── */
.grind-help-btn{
  background:none;border:1.5px solid var(--line);
  border-radius:50%;width:20px;height:20px;
  font-size:11px;font-weight:800;color:var(--muted);
  cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  margin-left:6px;vertical-align:middle;transition:.18s;flex-shrink:0;
}
.grind-help-btn:hover{border-color:var(--brown);color:var(--brown);}
.grind-backdrop{
  display:none;position:fixed;inset:0;
  background:rgba(20,10,5,0.45);backdrop-filter:blur(2px);
  z-index:600;align-items:center;justify-content:center;padding:20px;
}
.grind-backdrop.open{display:flex;}
.grind-box{
  background:#fff;border-radius:18px;
  max-width:360px;width:100%;
  box-shadow:0 20px 50px rgba(0,0,0,0.2);overflow:hidden;
}
.grind-box-head{
  background:var(--brown);padding:16px 20px;
  display:flex;align-items:center;justify-content:space-between;
}
.grind-box-head h3{color:#fff;font-size:15px;font-weight:800;}
.grind-box-x{
  width:28px;height:28px;border-radius:50%;
  background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);
  color:#fff;font-size:14px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;transition:.18s;
}
.grind-box-x:hover{background:rgba(255,255,255,0.3);}
.grind-list{padding:18px 20px;display:flex;flex-direction:column;gap:12px;}
.grind-row{display:flex;align-items:center;gap:12px;}
.grind-dot{width:18px;height:18px;border-radius:4px;flex-shrink:0;}
.grind-row b{font-size:13px;color:var(--brown);min-width:90px;}
.grind-row span{font-size:12px;color:var(--muted);}
.grind-box-foot{padding:14px 20px;background:var(--panel);border-top:1px solid var(--line);text-align:center;}
.grind-box-foot button{padding:9px 24px;border-radius:999px;border:none;background:var(--brown);color:#fff;font-weight:700;font-size:13px;cursor:pointer;transition:.18s;}
.grind-box-foot button:hover{background:var(--brown-2);}
/* ── HELP WINDOW ── */
.help-overlay{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,0.45);
  z-index:600;
}

.help-overlay.open{
  display:block;
}

.help-popup{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%) scale(.9);
  width:90%;
  max-width:420px;
  background:#fff;
  border-radius:18px;
  z-index:601;
  padding:0;
  overflow:hidden;
  opacity:0;
  pointer-events:none;
  transition:.25s;
}

.help-popup.open{
  opacity:1;
  transform:translate(-50%,-50%) scale(1);
  pointer-events:auto;
}

.help-header{
  background:var(--brown);
  color:#fff;
  padding:16px 20px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.help-header button{
  background:none;
  border:none;
  color:#fff;
  font-size:18px;
  cursor:pointer;
}

.help-content{
  padding:20px;
  color:var(--text);
  line-height:1.7;
  font-size:14px;
}

.help-content ul{
  padding-left:20px;
  margin:12px 0;
}

.help-btn{
  width:100%;
  margin-top:12px;
  padding:12px;
  border:none;
  border-radius:999px;
  background:var(--panel);
  color:var(--brown);
  font-weight:700;
  cursor:pointer;
  transition:.2s;
}

.help-btn:hover{
  background:var(--line);
}
/* ── FOOTER ── */
footer{text-align:center;padding:20px;background:#fff;border-top:1px solid var(--line);color:var(--muted);font-size:13px;margin-top:auto;}

@media(max-width:700px){
  header{padding:14px 18px;}
  nav a:not(.nav-cart){display:none;}
  .topbar{padding:10px 16px;gap:10px;}
  .topbar-search{max-width:none;}
  .topbar-sort label{display:none;}
  .main-content{width:95%;margin:20px auto;}
  .products-grid{grid-template-columns:repeat(2,1fr);gap:12px;}
  .detail-panel{width:100%;right:-100%;}
  .detail-panel.open{right:0;}
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
    <a href="javascript:void(0)" onclick="showAllProducts()" class="active">Products</a>
    <a href="my_orders.php">My Orders</a>
    <a href="contact-page.php">Contact</a>
    <a href="ShoppingCart.php" class="nav-cart">&#128722; Cart</a>
    <a href="login.php" class="nav-logout-right" data-en="Logout" data-ar="log out">Logout</a>
  </nav>
</header>

<!-- TOP BAR -->
<div class="topbar">
  <button class="filter-btn" onclick="toggleDrawer()">
    <div class="hamburger">
      <span></span><span></span><span></span>
    </div>
    Menu
  </button>

  <div class="topbar-search">
    <span>&#128269;</span>
    <input type="text" id="searchInput" placeholder="Search products…" oninput="onSearch()">
  </div>

  <div class="topbar-sort">
    <label>Sort:</label>
    <select id="sortSelect" onchange="sortProducts()">
      <option value="default">Default</option>
      <option value="asc">Price ↓</option>
      <option value="desc">Price ↑</option>
    </select>
  </div>
</div>

<!-- DRAWER BACKDROP -->
<div class="drawer-backdrop" id="drawerBackdrop" onclick="toggleDrawer()"></div>

<!-- DRAWER SIDEBAR -->
<div class="drawer" id="drawer">

  <div class="drawer-head">
    <h3> Menu</h3>
    <button class="drawer-close" onclick="toggleDrawer()">&#10005;</button>
  </div>

  <div class="drawer-body">

    <!-- Categories -->
    <div class="drawer-section">
      <span class="drawer-section-label">Categories</span>

      <!-- ALL PRODUCTS -->
      <div class="cat-group">
        <div class="cat-child" id="c-all-all" onclick="showAllProducts()" style="margin-bottom:6px;">
          <span class="dot"></span>All Products
        </div>
      </div>

      <!-- FILTER COFFEE -->
      <div class="cat-group">
        <div class="cat-parent" id="par-filter" onclick="toggleCat('filter')">
          <span>&#9749;&nbsp; Filter Coffee</span>
          <span class="cat-arrow">&#9654;</span>
        </div>
        <div class="cat-children" id="ch-filter">
          <div class="cat-child" id="c-filter-tools" onclick="selectCat('filter','tools')">
            <span class="dot"></span>Tools
          </div>
          <div class="cat-child" id="c-filter-beans" onclick="selectCat('filter','beans')">
            <span class="dot"></span>Beans
          </div>
        </div>
      </div>

      <!-- ESPRESSO -->
      <div class="cat-group">
        <div class="cat-parent" id="par-espresso" onclick="toggleCat('espresso')">
          <span>&#9842;&nbsp; Espresso</span>
          <span class="cat-arrow">&#9654;</span>
        </div>
        <div class="cat-children" id="ch-espresso">
          <div class="cat-child" id="c-espresso-tools" onclick="selectCat('espresso','tools')">
            <span class="dot"></span>Tools
          </div>
          <div class="cat-child" id="c-espresso-beans" onclick="selectCat('espresso','beans')">
            <span class="dot"></span>Beans
          </div>
        </div>
      </div>

    </div>

    <!-- Recommendation -->
    <div class="drawer-section">
      <span class="drawer-section-label">Recommendation</span>
      <div class="drawer-recommend">
        <h4>&#127775; Find Your Perfect Setup</h4>
        <select id="method">
          <option value="filter">Filter Coffee</option>
          <option value="espresso">Espresso</option>
        </select>
        <button onclick="recommendProducts()">Recommend for Me</button>
      </div>
    </div>

  </div>
</div>

<!-- MAIN -->
<div class="main-content">
  <div class="content-head">
    <p class="content-title" id="productsTitle">Recommended Products</p>
    <span class="count-badge" id="countBadge"></span>
  </div>
  <div class="products-grid" id="productsList"></div>
</div>

<!-- DETAIL BACKDROP -->
<div class="detail-backdrop" id="detailBackdrop" onclick="closeDetail()"></div>

<!-- DETAIL PANEL (right side) -->
<div class="detail-panel" id="detailPanel">
  <div class="dp-header">
    <span class="dp-header-title">Product Details</span>
    <button class="dp-close" onclick="closeDetail()">&#10005;</button>
  </div>
  <div class="dp-body">
    <div class="dp-img-wrap">
      <img id="dpImg" src="" alt="">
    </div>
    <div class="dp-content">
      <h2 class="dp-name" id="dpName"></h2>
      <div class="dp-badges" id="dpBadges"></div>
      <div class="dp-stars" id="dpStars">&#9733;&#9733;&#9733;&#9733;&#9734;</div>
      <div class="dp-price-row">
        <span class="dp-price" id="dpPrice"></span>
        <span class="dp-old-price" id="dpOldPrice" style="display:none"></span>
        <span class="dp-discount-tag" id="dpDiscountTag" style="display:none"></span>
      </div>
      <hr class="dp-divider">
      <span class="dp-section-label" id="dpNotesLabel"></span>
      <div class="dp-notes" id="dpNotes"></div>
      <div class="dp-grind" id="dpGrindSection" style="display:none">
        <span class="dp-section-label" style="display:flex;align-items:center;gap:6px;">Grind <button class="grind-help-btn" onclick="openGrindHelp()" title="Grind Guide">?</button></span>
        <select id="dpGrind">
          <option value="whole">Whole Bean</option>
          <option value="coarse">Coarse Grind</option>
          <option value="medium">Medium Grind</option>
          <option value="fine">Fine Grind</option>
          <option value="espresso">Espresso Grind</option>
        </select>
      </div>
      <div class="dp-qty-row">
        <span class="dp-qty-label">Quantity</span>
        <div class="dp-qty-ctrl">
          <button class="dp-qty-btn" onclick="changeDetailQty(-1)">&#8722;</button>
          <input class="dp-qty-val" type="number" id="dpQty" value="1" min="1">
          <button class="dp-qty-btn" onclick="changeDetailQty(1)">&#43;</button>
        </div>
      </div>
      <button class="dp-add-btn" id="dpAddBtn" onclick="addFromDetailPanel()">Add to Cart</button>
	  <button class="help-btn" onclick="openHelp()">Help ?</button>
      <div class="dp-recipe" id="dpRecipe" style="display:none">
        <hr class="dp-divider">
        <span class="dp-section-label">Brewing Recipe</span>
        <img src="images/وصفة.png" alt="Brewing Recipe" onerror="this.parentElement.style.display='none'">
      </div>
    </div>
  </div>
</div>

<!-- GRIND GUIDE POPUP -->
<div class="grind-backdrop" id="grindBackdrop" onclick="if(event.target===this)closeGrindHelp()">
  <div class="grind-box">
    <div class="grind-box-head">
      <h3>Grind Guide</h3>
      <button class="grind-box-x" onclick="closeGrindHelp()">&#10005;</button>
    </div>
    <div class="grind-list">
      <div class="grind-row">
        <div class="grind-dot" ></div>
        <b>Whole Bean</b>
        <span>Freshest — grind just before brewing</span>
      </div>
      <div class="grind-row">
        <div class="grind-dot"></div>
        <b>Coarse</b>
        <span>French Press &amp; Cold Brew</span>
      </div>
      <div class="grind-row">
        <div class="grind-dot" ></div>
        <b>Medium</b>
        <span>Pour-over, V60 &amp; Drip</span>
      </div>
      <div class="grind-row">
        <div class="grind-dot" ></div>
        <b>Fine</b>
        <span>Aeropress &amp; Moka Pot</span>
      </div>
      <div class="grind-row">
        <div class="grind-dot" ></div>
        <b>Espresso</b>
        <span>Espresso machines only</span>
      </div>
    </div>
    <div class="grind-box-foot">
      <button onclick="closeGrindHelp()">Got it &#10003;</button>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>&copy; 2026 Brew &amp; Bean &mdash; Premium Coffee</footer>

<script>
/* ── DATA (from database) ── */
const coffeeTools = <?php echo json_encode($coffeeTools, JSON_UNESCAPED_UNICODE); ?>;
const coffeeBeans = <?php echo json_encode($coffeeBeans, JSON_UNESCAPED_UNICODE); ?>;

/* ── STATE ── */
let currentData = [];
let currentMethod = null;
let currentCategory = null;
let activeDetailItem = null;
let helpTimer = null;

/* ── DRAWER ── */
function toggleDrawer(){
  document.getElementById('drawer').classList.toggle('open');
  document.getElementById('drawerBackdrop').classList.toggle('open');
  document.body.style.overflow = document.getElementById('drawer').classList.contains('open') ? 'hidden' : '';
}
function closeDrawer(){
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('drawerBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

/* ── CATEGORY TREE ── */
function toggleCat(key){
  const par = document.getElementById('par-' + key);
  const ch  = document.getElementById('ch-'  + key);
  if (!par || !ch) return;
  const isOpen = ch.classList.contains('open');
  document.querySelectorAll('.cat-children').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.cat-parent').forEach(el => el.classList.remove('open'));
  if(!isOpen){ par.classList.add('open'); ch.classList.add('open'); }
}

function dedup(arr){
  const seen = new Set();
  return arr.filter(p => { if(seen.has(p.name)) return false; seen.add(p.name); return true; });
}

function selectCat(method, category){
  document.querySelectorAll('.cat-child').forEach(el => el.classList.remove('active'));
  const childEl = document.getElementById('c-' + method + '-' + category);
  if(childEl) childEl.classList.add('active');

  currentMethod   = method;
  currentCategory = category;
  currentData     = (category === 'tools' ? coffeeTools : coffeeBeans)[method] || [];
  const label = (method === 'filter' ? 'Filter Coffee' : 'Espresso') + ' — ' + (category === 'tools' ? 'Tools' : 'Beans');
  document.getElementById('sortSelect').value = 'default';
  renderProducts(currentData, label);
  closeDrawer();
}

/* ── RENDER ── */
function renderProducts(data, title){
  const list   = document.getElementById('productsList');
  const ptitle = document.getElementById('productsTitle');
  const badge  = document.getElementById('countBadge');
  ptitle.textContent = title || 'Products';
  list.innerHTML = '';

  const q = document.getElementById('searchInput').value.trim().toLowerCase();
  const shown = q ? data.filter(p => p.name.toLowerCase().includes(q)) : data;
  badge.textContent = shown.length + ' items';

  if(!shown.length){
    list.innerHTML = `<div class="no-results"><div class="nr-icon">&#128269;</div><p>No products found.</p></div>`;
    return;
  }

  shown.forEach((p, idx) => {
    const card = document.createElement('div');
    card.className  = 'prod-card';
    card.dataset.index = idx;
    card.dataset.price = p.price;
    const soldOut = (p.stock === 0);
    const stockBadge = soldOut
      ? '<span class="stock-badge sold-out">Sold Out</span>'
      : p.stock <= 5
        ? `<span class="stock-badge low-stock">Only ${p.stock} left</span>`
        : `<span class="stock-badge in-stock">${p.stock} in stock</span>`;
    card.innerHTML = `
      <img class="prod-img" src="images/${esc(p.image)}" alt="${esc(p.name)}"
           onerror="this.src='images/no-product.jpeg'" onclick="${soldOut ? '' : `openDetail(${idx})`}" style="${soldOut ? 'opacity:.5;cursor:default' : ''}">
      <div class="prod-body">
        <p class="prod-name">${esc(p.name)}</p>
        <p class="prod-price">${p.price} SAR</p>
        ${stockBadge}
      </div>
      <button class="prod-btn${soldOut ? ' sold-out-btn' : ''}" onclick="${soldOut ? '' : `addToCart(${idx},this)`}" ${soldOut ? 'disabled' : ''}>${soldOut ? 'Sold Out' : 'Add to Cart'}</button>`;
    list.appendChild(card);
  });
}

/* ── SEARCH ── */
function onSearch(){
  const keyword = document.getElementById('searchInput').value.trim();

  if(keyword.length > 0){
    fetch('products.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'search_keyword=' + encodeURIComponent(keyword)
    });
  }

  if(currentData.length){
    renderProducts(currentData, document.getElementById('productsTitle').textContent);
  }
}

/* ── SORT ── */
function sortProducts(){
  const val   = document.getElementById('sortSelect').value;
  const list  = document.getElementById('productsList');
  const cards = Array.from(list.querySelectorAll('.prod-card'));
  if(val==='default') cards.sort((a,b)=>+a.dataset.index - +b.dataset.index);
  else cards.sort((a,b)=> val==='asc' ? +a.dataset.price - +b.dataset.price : +b.dataset.price - +a.dataset.price);
  cards.forEach(c => list.appendChild(c));
}

/* ── HELPERS: tool descriptions ── */
function getToolDesc(name){
  const n=name.toLowerCase();
  if(n.includes('dripper')||n.includes('brewer')||n.includes('v60')||n.includes('chemex')||n.includes('filt hoop')||n.includes('origami'))
    return 'Produces a clean, bright cup with precise flow control. Ideal for single-cup pour-over brewing at home or café.';
  if(n.includes('kettle'))
    return 'Gooseneck spout for precise, controlled pouring. Essential for pour-over methods like V60 and Chemex.';
  if(n.includes('scale')||n.includes('timer scale'))
    return 'High-precision weighing for consistent brew ratios. Built-in timer for perfect extraction timing every cup.';
  if(n.includes('portafilter'))
    return 'Professional portafilter for consistent espresso extraction and even pressure across the coffee puck.';
  if(n.includes('tamper'))
    return 'Even distribution and calibrated pressure for optimal espresso puck preparation and balanced shots.';
  if(n.includes('pitcher')||n.includes('flair wizard'))
    return 'Stainless steel pitcher designed for smooth milk texturing and latte art. Perfect steam wand compatibility.';
  if(n.includes('thermometer'))
    return 'Monitor milk and brew temperature precisely. Achieve the perfect steaming temp every single time.';
  if(n.includes('server')||n.includes('coffee pot')||n.includes('glass server'))
    return 'Heat-resistant glass carafe to keep your brewed coffee fresh, warm, and beautifully presented.';
  if(n.includes('filter')||n.includes('paper filter'))
    return 'Premium paper filters for a clean, residue-free cup. Removes oils for a lighter, brighter flavor profile.';
  if(n.includes('cold brew'))
    return 'Easy cold brew preparation at home. Produces rich, smooth concentrate ready in 12–24 hours.';
  if(n.includes('funnel')||n.includes('distributor')||n.includes('wdt')||n.includes('clump crusher'))
    return 'Ensures even coffee distribution in the portafilter basket for balanced, channel-free espresso extraction.';
  if(n.includes('stand')||n.includes('organizer'))
    return 'Keeps your coffee tools tidy, organized, and within reach on your countertop setup.';
  if(n.includes('container')||n.includes('bean container'))
    return 'Airtight storage keeps your beans fresh longer. Precision scale base for accurate dosing every time.';
  if(n.includes('measuring cup')||n.includes('espresso measuring'))
    return 'Measure espresso volume precisely for consistent shot quality and perfect dose control.';
  if(n.includes('tasting spoon')||n.includes('tasting cup'))
    return 'Professional cupping equipment for evaluating and comparing coffee flavors and aromas.';
  if(n.includes('mat')||n.includes('silicone mat'))
    return 'Non-slip surface to protect your workspace and tools during espresso preparation.';
  if(n.includes('serving plate'))
    return 'Elegant handcrafted wooden tray to present your coffee creations beautifully to guests.';
  if(n.includes('shower screen'))
    return 'Improves water distribution for even, consistent espresso extraction across the entire puck.';
  if(n.includes('portafilter ring'))
    return 'Prevents coffee grinds from overflowing the portafilter basket during dosing — keeps your machine clean.';
  if(n.includes('holder'))
    return 'Keeps your paper filters neatly stored and easily accessible for daily brewing.';
  return 'High-quality coffee accessory crafted for precision, durability, and daily use.';
}

/* ── HELPERS: bean flavor notes ── */
function getBeanNotes(name){
  const n=name.toLowerCase();
  if(n.includes('red fruit'))      return 'Red Berries · Hibiscus · Tropical Citrus';
  if(n.includes('watermelon'))     return 'Watermelon · Green Apple · Jasmine';
  if(n.includes('passion fruit'))  return 'Passion Fruit · Mango · Bright Citrus';
  if(n.includes('cotton candy'))   return 'Cotton Candy · White Peach · Floral';
  if(n.includes('coconut lemon'))  return 'Coconut · Lemon Zest · Sweet Candy';
  if(n.includes('chocolate'))      return 'Dark Chocolate · Caramel · Roasted Hazelnut';
  if(n.includes('haraaz')||n.includes('yemeni')) return 'Dried Grape · Raisins · Dark Spice';
  if(n.includes('guji'))           return 'Blueberry · Jasmine · Black Tea';
  if(n.includes('blend'))          return 'Balanced · Caramel · Smooth Finish';
  if(n.includes('expo 2030'))      return 'Stone Fruit · Honey · Caramel';
  if(n.includes('qiddiya'))        return 'Brown Sugar · Plum · Complex Acidity';
  if(n.includes('colombia')||n.includes('colombian')) return 'Red Apple · Caramel · Citrus Brightness';
  if(n.includes('ethiopia')||n.includes('ethiopian')) return 'Jasmine · Wild Berries · Bright Acidity';
  if(n.includes('brazil')||n.includes('brazilian'))   return 'Chocolate · Hazelnut · Brown Sugar';
  if(n.includes('indonesia'))      return 'Dark Chocolate · Earthy · Low Acidity';
  if(n.includes('guatemala'))      return 'Caramel · Dark Chocolate · Hazelnut';
  if(n.includes('india')||n.includes('indian')||n.includes('nasla')) return 'Spice · Milk Chocolate · Woody Notes';
  if(n.includes('uganda'))         return 'Citrus · Brown Sugar · Milk Chocolate';
  return 'Rich · Smooth · Complex Finish';
}

/* ── HELPERS: bean origin ── */
function getBeanOrigin(name){
  const n=name.toLowerCase();
  if(n.includes('colombian')||n.includes('colombia')) return 'Colombia';
  if(n.includes('brazilian')||n.includes('brazil'))   return 'Brazil';
  if(n.includes('yemeni')||n.includes('haraaz'))      return 'Yemen';
  if(n.includes('ethiopia')||n.includes('guji'))      return 'Ethiopia';
  if(n.includes('indonesia'))   return 'Indonesia';
  if(n.includes('guatemala'))   return 'Guatemala';
  if(n.includes('uganda'))      return 'Uganda';
  if(n.includes('india')||n.includes('nasla')) return 'India';
  if(n.includes('el salvador')) return 'El Salvador';
  if(n.includes('costa rica'))  return 'Costa Rica';
  return 'Multi-Origin Blend';
}

/* ── HELPERS: detect if product is a bean ── */
function isBeanProduct(p){
  if(currentCategory === 'beans') return true;
  if(currentCategory === 'tools') return false;
  const allBeans=[...(coffeeBeans.filter||[]),...(coffeeBeans.espresso||[])];
  return allBeans.some(b=>b.name===p.name);
}

/* ── DETAIL PANEL ── */
function getFiltered(){
  const q = document.getElementById('searchInput').value.trim().toLowerCase();
  return q ? currentData.filter(p=>p.name.toLowerCase().includes(q)) : currentData;
}

function openDetail(idx){
  const p = getFiltered()[idx];
  if(!p) return;
  activeDetailItem = p;
  const isBean = isBeanProduct(p);

  /* image */
  const img = document.getElementById('dpImg');
  img.src = 'images/' + p.image;
  img.onerror = function(){ this.src='images/no-product.jpeg'; };
  img.alt = p.name;

  /* name */
  document.getElementById('dpName').textContent = p.name;

  /* badges */
  const badgesEl = document.getElementById('dpBadges');
  if(isBean){
    const origin = getBeanOrigin(p.name);
    const method = currentMethod==='filter'?'Filter':'Espresso';
    badgesEl.innerHTML =
      `<span class="dp-badge origin">&#127758; ${origin}</span><span class="dp-badge">${method}</span>`;
  } else {
    const method = currentMethod==='filter'?'Filter':'Espresso';
    badgesEl.innerHTML = `<span class="dp-badge">&#9874; ${method} Tool</span>`;
  }

  /* stars — 5 stars for premium (price>150), else 4 */
  document.getElementById('dpStars').textContent =
    p.price > 150 ? '★★★★★' : '★★★★☆';

  /* prices — every 3rd product (by name length) gets a discount */
  const hasDiscount = (p.name.length % 3 === 0);
  const oldPrice = hasDiscount ? Math.round(p.price * 1.18) : null;
  document.getElementById('dpPrice').textContent = p.price + ' SAR';
  const oldEl = document.getElementById('dpOldPrice');
  const tagEl = document.getElementById('dpDiscountTag');
  if(oldPrice){
    oldEl.textContent = oldPrice + ' SAR';
    oldEl.style.display = 'inline';
    tagEl.textContent = Math.round((1 - p.price/oldPrice)*100) + '% OFF';
    tagEl.style.display = 'inline';
  } else {
    oldEl.style.display = 'none';
    tagEl.style.display = 'none';
  }

  /* notes / description */
  if(isBean){
    document.getElementById('dpNotesLabel').textContent = 'Flavor Notes';
    document.getElementById('dpNotes').textContent = getBeanNotes(p.name);
  } else {
    document.getElementById('dpNotesLabel').textContent = 'About This Tool';
    document.getElementById('dpNotes').textContent = getToolDesc(p.name);
  }

  /* grind selector (beans only) */
  document.getElementById('dpGrindSection').style.display = isBean ? 'block' : 'none';
  document.getElementById('dpGrind').value = 'whole';

  /* recipe image (beans only) */
  document.getElementById('dpRecipe').style.display = isBean ? 'block' : 'none';

  /* reset qty & button */
  document.getElementById('dpQty').value = 1;
  const btn = document.getElementById('dpAddBtn');
  const soldOut = (p.stock === 0);
  if(soldOut){
    btn.textContent = 'Sold Out';
    btn.disabled = true;
    btn.classList.add('sold-out-btn');
  } else {
    btn.textContent = 'Add to Cart';
    btn.disabled = false;
    btn.classList.remove('added','sold-out-btn');
  }
  /* stock line */
  let stockEl = document.getElementById('dpStock');
  if(!stockEl){ stockEl = document.createElement('p'); stockEl.id='dpStock'; stockEl.style.marginBottom='12px'; document.getElementById('dpName').after(stockEl); }
  if(soldOut)          stockEl.innerHTML = '<span class="stock-badge sold-out">Out of Stock</span>';
  else if(p.stock<=5)  stockEl.innerHTML = `<span class="stock-badge low-stock">Only ${p.stock} left</span>`;
  else                 stockEl.innerHTML = `<span class="stock-badge in-stock">${p.stock} in stock</span>`;

  /* open panel */
  document.getElementById('detailPanel').classList.add('open');
  document.getElementById('detailBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';

  /* auto-open Help popup after 5 seconds */
  if(helpTimer) clearTimeout(helpTimer);
  helpTimer = setTimeout(function(){
    openHelp();
  }, 5000);
}

function closeDetail(){
  document.getElementById('detailPanel').classList.remove('open');
  document.getElementById('detailBackdrop').classList.remove('open');
  document.body.style.overflow = '';
  if(helpTimer){ clearTimeout(helpTimer); helpTimer = null; }
}

function changeDetailQty(delta){
  const inp = document.getElementById('dpQty');
  inp.value = Math.max(1, (parseInt(inp.value)||1) + delta);
}

function addFromDetailPanel(){
  if(!activeDetailItem) return;
  const qty = Math.max(1, parseInt(document.getElementById('dpQty').value)||1);
  pushCart(activeDetailItem.name, activeDetailItem.price, qty);
  const btn = document.getElementById('dpAddBtn');
  btn.textContent = '✓ Added!'; btn.classList.add('added');
  setTimeout(()=>{ btn.textContent='Add to Cart'; btn.classList.remove('added'); }, 1800);
}

/* ── CART ── */
function addToCart(idx, btn){
  const p = getFiltered()[idx]; if(!p) return;
  pushCart(p.name, p.price);
  btn.textContent='✓ Added!'; btn.classList.add('added');
  setTimeout(()=>{ btn.textContent='Add to Cart'; btn.classList.remove('added'); },1500);
}
function pushCart(name, price, qty=1){
  // Update localStorage for UI state
  let cart = JSON.parse(localStorage.getItem('cart')||'[]');
  const ex = cart.find(i=>i.name===name);
  if(ex) ex.quantity += qty;
  else cart.push({name, price:parseFloat(price), quantity:qty});
  localStorage.setItem('cart', JSON.stringify(cart));

  // Sync to database
  const fd = new FormData();
  fd.append('name', name);
  fd.append('price', price);
  fd.append('quantity', qty);
  fetch('add_to_cart.php', {method:'POST', body:fd}).catch(()=>{});
}

/* ── RECOMMENDATION ── */
function randomItem(arr){ return arr[Math.floor(Math.random()*arr.length)]; }
function pickDistinct(arr,count){
  const pool=[...arr]; const out=[];
  while(pool.length&&out.length<count) out.push(pool.splice(Math.floor(Math.random()*pool.length),1)[0]);
  return out;
}
function catOf(p){
  const n=(p.name||'').toLowerCase();
  if(n.includes('portafilter')||n.includes('bottomless')) return 'portafilter';
  if(n.includes('tamper')) return 'tamper';
  if(n.includes('wdt')||n.includes('distributor')||n.includes('funnel')||n.includes('clump crusher')) return 'distribution';
  if(n.includes('pitcher')) return 'milk';
  if(n.includes('scale')||n.includes('timer scale')) return 'scale';
  if(n.includes('dripper')||n.includes('v60')||n.includes('chemex')||n.includes('brewer')||n.includes('cold brew')) return 'brewer';
  if(n.includes('filter')||n.includes('paper')) return 'filters';
  if(n.includes('kettle')||n.includes('pour-over')) return 'kettle';
  if(n.includes('server')||n.includes('coffee pot')) return 'server';
  return 'other';
}
function recommendProducts(){
  const method = document.getElementById('method').value;
  const allTools = coffeeTools[method]||[];
  const buckets = {};
  allTools.forEach(p=>{ const c=catOf(p); (buckets[c]||=[]).push(p); });
  const required = method==='filter'
    ? ['brewer','filters','kettle','scale','server']
    : ['portafilter','tamper','distribution','scale','milk'];
  const kit=[];
  required.forEach(c=>{ if(buckets[c]?.length) kit.push(randomItem(buckets[c])); });
  const beans = pickDistinct(coffeeBeans[method]||[], 2);
  const final = [...kit,...beans].filter((v,i,a)=>v&&a.findIndex(x=>x.name===v.name)===i);
  currentData=final; currentMethod=method; currentCategory=null;
  document.querySelectorAll('.cat-child').forEach(el=>el.classList.remove('active'));
  document.getElementById('sortSelect').value='default';
  renderProducts(final,'Recommended — '+(method==='filter'?'Filter Coffee':'Espresso'));
  closeDrawer();
}

/* ── HELPERS ── */
function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* ── INIT ── */
function showAllProducts(){
  const seen = new Set();
  const all  = [];
  [...(coffeeTools.filter||[]),...(coffeeTools.espresso||[]),
   ...(coffeeBeans.filter||[]),...(coffeeBeans.espresso||[])].forEach(p=>{
    if(!seen.has(p.name)){ seen.add(p.name); all.push(p); }
  });
  currentData     = all;
  currentMethod   = null;
  currentCategory = null;
  document.querySelectorAll('.cat-child').forEach(el=>el.classList.remove('active'));
  document.querySelectorAll('.cat-children').forEach(el=>el.classList.remove('open'));
  document.querySelectorAll('.cat-parent').forEach(el=>el.classList.remove('open'));
  const allEl = document.getElementById('c-all-all');
  if(allEl) allEl.classList.add('active');
  document.getElementById('sortSelect').value = 'default';
  renderProducts(all, 'All Products');
  closeDrawer();
}

/* ── SHOW ALL OF ONE CATEGORY (beans or tools) ── */
function showCategory(category){
  const src   = category === 'tools' ? coffeeTools : coffeeBeans;
  const data  = dedup([...(src.filter||[]), ...(src.espresso||[])]);
  currentData     = data;
  currentMethod   = null;
  currentCategory = category;
  document.querySelectorAll('.cat-child').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.cat-children').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.cat-parent').forEach(el => el.classList.remove('open'));
  document.getElementById('sortSelect').value = 'default';
  renderProducts(data, category === 'tools' ? 'All Tools' : 'All Coffee Beans');
}

/* ── INIT: auto-select based on URL category ── */
const initCat = <?php echo json_encode($init_cat); ?>;
if (initCat === 'beans') {
  showCategory('beans');
} else if (initCat === 'tools') {
  showCategory('tools');
} else {
  showAllProducts();
}

/* ── GRIND GUIDE POPUP ── */
function openGrindHelp(){
  document.getElementById('grindBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeGrindHelp(){
  document.getElementById('grindBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}
function openHelp(){
  document.getElementById('helpPopup').classList.add('open');
  document.getElementById('helpOverlay').classList.add('open');
}

function closeHelp(){
  document.getElementById('helpPopup').classList.remove('open');
  document.getElementById('helpOverlay').classList.remove('open');
}
</script>
<script src="accessibility.js"></script>
<!-- HELP WINDOW -->
<div class="help-overlay" id="helpOverlay" onclick="closeHelp()"></div>

<div class="help-popup" id="helpPopup">
  <div class="help-header">
    <h3>Help & Information</h3>
    <button onclick="closeHelp()">✕</button>
  </div>

  <div class="help-content">
    <p><strong>How to use:</strong></p>

    <ul>
      <li>Click on any product image to view details.</li>
      <li>Select quantity before adding to cart.</li>
      <li>Coffee beans allow grind selection.</li>
      <li>Use the cart page to complete your order.</li>
    </ul>

    <p>If you need more support contact Brew & Bean.</p>
  </div>
</div>



</html>
