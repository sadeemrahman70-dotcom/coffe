<?php
session_start();
include "db_connect.php";



/* جلب التصنيفات مع أول صورة لكل تصنيف */
$cats_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");
$categories  = [];
while ($c = mysqli_fetch_assoc($cats_result)) $categories[] = $c;

/* صور مخصصة للتصنيفات — اسم التصنيف => اسم الملف */
$cat_image_map = [
    'Tools' => 'ماكينة قهوة.WEBP',
    'Beans' => 'محاصيل قهوة.WEBP',
];

/* أول صورة لكل تصنيف (احتياطي إذا لم تكن هناك صورة مخصصة) */
$cat_images = [];
foreach ($categories as $cat) {
    $cid = (int)$cat['category_id'];
    $img = mysqli_query($conn, "SELECT image FROM products WHERE category_id=$cid AND image != '' LIMIT 1");
    $row = mysqli_fetch_assoc($img);
    $cat_images[$cid] = $row ? $row['image'] : '';
}

/* جلب أحدث 8 منتجات */
$featured_result = mysqli_query($conn, "
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.product_id DESC LIMIT 8
");
$featured = [];
while ($p = mysqli_fetch_assoc($featured_result)) $featured[] = $p;

/* جلب منتجات لكل تصنيف (4 لكل) */
$by_category = [];
foreach ($categories as $cat) {
    $cid  = (int)$cat['category_id'];
    $res  = mysqli_query($conn, "SELECT * FROM products WHERE category_id=$cid ORDER BY product_id DESC LIMIT 4");
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    if (count($rows)) $by_category[] = ['name' => $cat['category_name'], 'products' => $rows];
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Brew & Bean - Premium Coffee</title>
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
body{ font-family:Arial,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;transition:direction .2s; }

/* ── HEADER ── */
header{ background:var(--brown);padding:16px 40px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100; }
.brand{ display:flex;align-items:center;gap:14px;text-decoration:none; }
.brand .logo{ width:54px;height:54px;border-radius:50%;overflow:hidden;border:2px solid rgba(255,255,255,0.3);background:#fff;flex:0 0 auto; }
.brand .logo img{ width:100%;height:100%;object-fit:cover;display:block; }
.brand-text h1{ color:#fff;font-size:20px;font-weight:700;line-height:1.1; }
.brand-text p{ color:rgba(255,255,255,0.7);font-size:12px;margin-top:2px; }
nav{ display:flex;align-items:center;gap:22px; }
nav a{ color:rgba(255,255,255,0.85);text-decoration:none;font-weight:600;font-size:14px;transition:.18s; }
nav a:hover{ color:#fff; }
nav a.active{ color:#fff;border-bottom:2px solid rgba(255,255,255,0.6);padding-bottom:2px; }
.nav-cart{ background:rgba(255,255,255,0.15);padding:8px 16px;border-radius:999px;color:#fff;text-decoration:none;font-weight:700;font-size:13px;transition:.18s;border:1px solid rgba(255,255,255,0.25); }
.nav-cart:hover{ background:rgba(255,255,255,0.25); }

/* ── LANG TOGGLE ── */
.lang-toggle{
  display:flex;
  background:rgba(255,255,255,0.12);
  border-radius:999px;
  border:1px solid rgba(255,255,255,0.25);
  overflow:hidden;
}
.lang-btn{
  padding:7px 14px;
  font-size:12px;
  font-weight:700;
  color:rgba(255,255,255,0.7);
  cursor:pointer;
  background:transparent;
  border:none;
  transition:.18s;
}
.lang-btn.active{ background:rgba(255,255,255,0.25);color:#fff; }
.lang-btn:hover{ color:#fff; }

/* ── HERO ── */
.hero{
  position:relative;
  color:#fff;
  padding:120px 40px;
  text-align:center;
  overflow:hidden;
  min-height:520px;
  display:flex;
  align-items:center;
  justify-content:center;
}

/* صورة الخلفية — غيّر المسار لما تضيف صورتك */
.hero-bg{
  position:absolute;
  inset:0;
  background: url('images/BEAN.jpg') center/cover no-repeat;
  transform:scale(1.04);
  transition:transform 8s ease;
}

.hero-bg.loaded{ transform:scale(1); }

/* طبقة التعتيم الداكنة */
.hero-overlay{
  position:absolute;
  inset:0;
  background:linear-gradient(
    160deg,
    rgba(20,10,5,0.78) 0%,
    rgba(74,44,29,0.65) 60%,
    rgba(20,10,5,0.80) 100%
  );
}

.hero-content{ position:relative;z-index:1;max-width:700px;margin:0 auto; }
.hero h2{ font-size:48px;font-weight:800;line-height:1.2;margin-bottom:18px;text-shadow:0 2px 20px rgba(0,0,0,0.4); }
.hero p{ font-size:18px;opacity:.9;margin-bottom:36px;line-height:1.7;text-shadow:0 1px 8px rgba(0,0,0,0.3); }
.hero-btns{ display:flex;gap:14px;justify-content:center;flex-wrap:wrap; }
.btn-primary{ padding:14px 34px;border-radius:999px;background:#fff;color:var(--brown);font-weight:800;font-size:15px;text-decoration:none;transition:.2s;border:2px solid #fff;box-shadow:0 4px 18px rgba(0,0,0,0.25); }
.btn-primary:hover{ background:transparent;color:#fff; }
.btn-outline{ padding:14px 34px;border-radius:999px;background:rgba(255,255,255,0.1);color:#fff;font-weight:700;font-size:15px;text-decoration:none;border:2px solid rgba(255,255,255,0.7);transition:.2s;backdrop-filter:blur(4px); }
.btn-outline:hover{ background:rgba(255,255,255,0.2); }

/* ── SECTION ── */
section{ width:90%;max-width:1200px;margin:52px auto; }
.sec-head{ display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px; }
.section-title{ font-size:22px;font-weight:800;color:var(--brown); }
.section-sub{ color:var(--muted);font-size:13px;margin-top:4px; }
.view-all{ font-size:13px;font-weight:700;color:var(--brown-2);text-decoration:none;border-bottom:1px solid var(--brown-2); }
.view-all:hover{ opacity:.75; }

/* ── CATEGORIES ROW ── */
.cat-row{ display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px; }
.cat-card{
  background:#fff;
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:0;
  text-align:center;
  text-decoration:none;
  color:var(--text);
  transition:.2s;
  box-shadow:0 6px 16px rgba(46,36,32,0.06);
  overflow:hidden;
}
.cat-card:hover{ transform:translateY(-4px);box-shadow:0 12px 26px rgba(46,36,32,0.12);border-color:var(--brown); }
.cat-img{
  width:100%;
  height:140px;
  object-fit:contain;
  background:var(--panel);
  display:block;
  padding:14px;
}
.cat-label{
  padding:12px 10px;
  font-size:14px;
  font-weight:800;
  color:var(--brown);
  border-top:1px solid var(--line);
}

/* ── PRODUCT GRID ── */
.prod-grid{ display:grid;grid-template-columns:repeat(4,1fr);gap:16px; }
.prod-card{ background:#fff;border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;transition:.2s;box-shadow:0 6px 16px rgba(46,36,32,0.06);display:flex;flex-direction:column; }
.prod-card:hover{ transform:translateY(-4px);box-shadow:0 14px 28px rgba(46,36,32,0.12); }
.prod-img{ width:100%;height:190px;object-fit:contain;background:var(--panel);padding:10px;display:block; }
.prod-body{ padding:14px;flex:1;display:flex;flex-direction:column;gap:6px; }
.prod-cat{ font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase; }
.prod-name{ font-size:13px;font-weight:700;color:var(--text);line-height:1.4;flex:1; }
.prod-price{ font-size:15px;font-weight:800;color:var(--brown); }
.prod-btn{ margin:0 14px 14px;padding:10px;border-radius:999px;border:none;background:var(--brown);color:#fff;font-weight:700;font-size:13px;cursor:pointer;transition:.18s;width:calc(100% - 28px); }
.prod-btn:hover{ background:var(--brown-2); }

/* ── BANNER ── */
.banner{ background:linear-gradient(135deg,var(--brown) 0%,var(--brown-2) 100%);border-radius:var(--radius);padding:48px 40px;color:#fff;display:flex;justify-content:space-between;align-items:center;gap:24px;flex-wrap:wrap; }
.banner h2{ font-size:26px;font-weight:800;margin-bottom:8px; }
.banner p{ opacity:.85;font-size:15px; }
.banner-btn{ padding:14px 32px;border-radius:999px;background:#fff;color:var(--brown);font-weight:800;font-size:15px;text-decoration:none;white-space:nowrap;transition:.2s;flex-shrink:0; }
.banner-btn:hover{ background:var(--bg); }

/* ── FEATURES ── */
.features{ display:grid;grid-template-columns:repeat(4,1fr);gap:14px; }
.feat{ background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px 20px;text-align:center; }
.feat-circle{ width:64px;height:64px;border-radius:50%;background:rgba(74,44,29,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:28px; }
.feat h4{ font-size:14px;font-weight:800;color:var(--brown);margin-bottom:6px; }
.feat p{ font-size:12px;color:var(--muted);line-height:1.5; }

/* ── DETAIL PANEL ── */
.detail-backdrop{display:none;position:fixed;inset:0;background:rgba(20,10,5,0.5);backdrop-filter:blur(2px);z-index:498;}
.detail-backdrop.open{display:block;}
.detail-panel{position:fixed;top:0;right:-460px;width:440px;height:100vh;background:#fff;z-index:499;display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,0.2);transition:right .32s cubic-bezier(.4,0,.2,1);overflow:hidden;}
.detail-panel.open{right:0;}
.dp-header{background:var(--brown);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.dp-header-title{color:#fff;font-size:13px;font-weight:700;opacity:.85;}
.dp-close{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:#fff;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.18s;}
.dp-close:hover{background:rgba(255,255,255,0.3);}
.dp-body{flex:1;overflow-y:auto;padding-bottom:24px;}
.dp-img-wrap{width:100%;height:270px;background:var(--panel);display:flex;align-items:center;justify-content:center;}
.dp-img-wrap img{max-width:100%;max-height:100%;object-fit:contain;padding:18px;}
.dp-content{padding:20px;}
.dp-name{font-size:17px;font-weight:800;color:var(--brown);line-height:1.4;margin-bottom:12px;}
.dp-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.dp-badge{padding:4px 12px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid var(--line);background:var(--panel);color:var(--muted);}
.dp-badge.origin{background:rgba(74,44,29,0.09);color:var(--brown);border-color:rgba(74,44,29,0.2);}
.dp-stars{color:#E0A800;font-size:15px;margin-bottom:14px;letter-spacing:2px;}
.dp-price-row{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.dp-price{font-size:24px;font-weight:800;color:var(--brown);}
.dp-old-price{font-size:14px;color:var(--muted);text-decoration:line-through;}
.dp-discount-tag{background:#E8FFF2;color:#1E6B3A;border:1px solid #BFEED2;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:800;}
.dp-divider{border:none;border-top:1px solid var(--line);margin:16px 0;}
.dp-section-label{font-size:11px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;display:block;}
.dp-notes{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:12px 14px;font-size:13px;color:var(--text);line-height:1.6;margin-bottom:16px;}
.dp-grind select{width:100%;padding:11px 14px;border-radius:12px;border:1.5px solid var(--line);background:#fff;font-size:13px;color:var(--text);outline:none;cursor:pointer;transition:.18s;margin-bottom:16px;}
.dp-qty-row{display:flex;align-items:center;gap:14px;margin-bottom:20px;}
.dp-qty-label{font-size:13px;font-weight:700;color:var(--muted);}
.dp-qty-ctrl{display:flex;align-items:center;border:1.5px solid var(--line);border-radius:999px;overflow:hidden;}
.dp-qty-btn{width:34px;height:34px;border:none;background:transparent;font-size:18px;font-weight:700;color:var(--brown);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;}
.dp-qty-btn:hover{background:var(--panel);}
.dp-qty-val{width:40px;text-align:center;font-size:15px;font-weight:800;color:var(--text);background:#fff;border:none;border-left:1px solid var(--line);border-right:1px solid var(--line);padding:0;-moz-appearance:textfield;}
.dp-qty-val::-webkit-outer-spin-button,.dp-qty-val::-webkit-inner-spin-button{-webkit-appearance:none;}
.dp-add-btn{display:block;width:100%;padding:14px;border-radius:999px;border:none;background:var(--brown);color:#fff;font-weight:800;font-size:15px;cursor:pointer;transition:.18s;}
.dp-add-btn:hover{background:var(--brown-2);}
.dp-add-btn.added{background:#1E6B3A;}
.dp-recipe{margin-top:20px;text-align:center;}
.dp-recipe img{max-width:100%;border-radius:14px;border:1px solid var(--line);}
.prod-img{cursor:pointer;}
@media(max-width:500px){.detail-panel{width:100%;right:-100%;}}

/* ── STOCK BADGES ── */
.stock-badge{display:inline-block;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;margin-top:2px;}
.stock-badge.in-stock{background:#E8FFF2;color:#1E6B3A;border:1px solid #BFEED2;}
.stock-badge.low-stock{background:#FFF8E1;color:#B45309;border:1px solid #FCD34D;}
.stock-badge.sold-out{background:#FFF0F0;color:#c0392b;border:1px solid #f5a0a0;}
.prod-btn.sold-out-btn{background:#ccc;cursor:not-allowed;}
.prod-btn.sold-out-btn:hover{background:#ccc;}

/* ── GRIND HELP BTN ── */
.grind-help-btn{background:none;border:1.5px solid var(--line);border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:800;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;margin-left:6px;vertical-align:middle;transition:.18s;flex-shrink:0;}
.grind-help-btn:hover{border-color:var(--brown);color:var(--brown);}

/* ── GRIND GUIDE POPUP ── */
.grind-backdrop{display:none;position:fixed;inset:0;background:rgba(20,10,5,0.45);backdrop-filter:blur(2px);z-index:600;align-items:center;justify-content:center;padding:20px;}
.grind-backdrop.open{display:flex;}
.grind-box{background:#fff;border-radius:18px;max-width:360px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,0.2);overflow:hidden;}
.grind-box-head{background:var(--brown);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;}
.grind-box-head h3{color:#fff;font-size:15px;font-weight:800;}
.grind-box-x{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:#fff;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.18s;}
.grind-box-x:hover{background:rgba(255,255,255,0.3);}
.grind-list{padding:18px 20px;display:flex;flex-direction:column;gap:12px;}
.grind-row{display:flex;align-items:center;gap:12px;}
.grind-dot{width:18px;height:18px;border-radius:4px;flex-shrink:0;}
.grind-row b{font-size:13px;color:var(--brown);min-width:90px;}
.grind-row span{font-size:12px;color:var(--muted);}
.grind-box-foot{padding:14px 20px;background:var(--panel);border-top:1px solid var(--line);text-align:center;}
.grind-box-foot button{padding:9px 24px;border-radius:999px;border:none;background:var(--brown);color:#fff;font-weight:700;font-size:13px;cursor:pointer;transition:.18s;}
.grind-box-foot button:hover{background:var(--brown-2);}

/* ── HELP POPUP ── */
.help-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:700;}
.help-overlay.open{display:block;}
.help-popup{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(.9);width:90%;max-width:420px;background:#fff;border-radius:18px;z-index:701;overflow:hidden;opacity:0;pointer-events:none;transition:.25s;}
.help-popup.open{opacity:1;transform:translate(-50%,-50%) scale(1);pointer-events:auto;}
.help-header{background:var(--brown);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;}
.help-header h3{font-size:15px;font-weight:800;}
.help-header button{background:none;border:none;color:#fff;font-size:18px;cursor:pointer;}
.help-content{padding:20px;color:var(--text);line-height:1.7;font-size:14px;}
.help-content ul{padding-left:20px;margin:12px 0;}
.help-dismiss{width:100%;margin-top:12px;padding:12px;border:none;border-radius:999px;background:var(--panel);color:var(--brown);font-weight:700;cursor:pointer;transition:.2s;}
.help-dismiss:hover{background:var(--line);}
.dp-help-btn{display:block;width:100%;margin-top:10px;padding:11px;border:1.5px solid var(--line);border-radius:999px;background:#fff;color:var(--muted);font-weight:700;font-size:13px;cursor:pointer;transition:.18s;text-align:center;}
.dp-help-btn:hover{border-color:var(--brown);color:var(--brown);}

/* ── FOOTER ── */
footer{ background:var(--brown);color:rgba(255,255,255,0.8);margin-top:auto; }
.footer-top{ display:grid;grid-template-columns:2fr 1fr 1fr;gap:40px;padding:48px 5%;max-width:1200px;margin:0 auto; }
.footer-brand h2{ color:#fff;font-size:20px;font-weight:800;margin-bottom:8px; }
.footer-brand p{ font-size:13px;line-height:1.7;opacity:.8; }
.footer-col h4{ color:#fff;font-size:14px;font-weight:800;margin-bottom:14px; }
.footer-col a{ display:block;color:rgba(255,255,255,0.7);text-decoration:none;font-size:13px;margin-bottom:8px;transition:.18s; }
.footer-col a:hover{ color:#fff; }
.footer-bottom{ border-top:1px solid rgba(255,255,255,0.15);text-align:center;padding:16px;font-size:12px;opacity:.6; }

/* ── RTL SUPPORT ── */
[dir="rtl"] nav{ flex-direction:row-reverse; }
[dir="rtl"] .sec-head{ flex-direction:row-reverse; }
[dir="rtl"] .footer-top{ direction:rtl; }
[dir="rtl"] .footer-col a{ text-align:right; }
[dir="rtl"] .brand{ flex-direction:row-reverse; }
[dir="rtl"] header{ flex-direction:row-reverse; }

@media(max-width:1000px){
  .prod-grid{ grid-template-columns:repeat(2,1fr); }
  .features{ grid-template-columns:repeat(2,1fr); }
  .footer-top{ grid-template-columns:1fr 1fr; }
}
@media(max-width:650px){
  header{ padding:14px 20px;flex-wrap:wrap;gap:10px; }
  nav a:not(.nav-cart):not(.lang-btn){ display:none; }
  .prod-grid{ grid-template-columns:repeat(2,1fr); }
  .cat-row{ grid-template-columns:repeat(2,1fr); }
  .banner{ flex-direction:column;text-align:center; }
  .hero h2{ font-size:28px; }
  .footer-top{ grid-template-columns:1fr; }
}
</style>
</head>
<body>

<?php include "welcome_banner.php"; ?>
<?php include "cookie_banner.php"; ?>

<!-- HEADER -->
<header>
  <a class="brand" href="code.php">
    <div class="logo"><img src="images/Brew&Bean3.jpg" alt="Logo"></div>
    <div class="brand-text">
      <h1>Brew & Bean</h1>
      <p data-en="Premium Coffee" data-ar="قهوة متميزة">Premium Coffee</p>
    </div>
  </a>
  <nav>
    <a href="code.php" class="active" data-en="Home" data-ar="الرئيسية">Home</a>
    <a href="products.php" data-en="Products" data-ar="المنتجات">Products</a>
    <a href="my_orders.php" data-en="My Orders" data-ar="طلباتي">My Orders</a>
    <a href="contact-page.php" data-en="Contact" data-ar="تواصل معنا">Contact</a>
    <a href="ShoppingCart.php" class="nav-cart" data-en="🛒 Cart" data-ar="🛒 السلة">🛒 Cart</a>
    <!-- Language Toggle -->
    <div class="lang-toggle">
      <button class="lang-btn active" id="btnEN" onclick="setLang('en')">EN</button>
      <button class="lang-btn" id="btnAR" onclick="setLang('ar')">AR</button>
    </div>

    <a href="login.php" class="nav-logout-right" data-en="Logout" data-ar="log out">Logout</a>

  </nav>
</header>

<!-- HERO -->
<div class="hero">
  <div class="hero-bg" id="heroBg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h2 data-en="Premium Coffee,<br>Delivered to You" data-ar="قهوة متميزة،<br>توصل إليك">Premium Coffee,<br>Delivered to You</h2>
    <p data-en="Discover specialty coffee beans and professional brewing tools curated for every coffee lover."
       data-ar="اكتشف حبوب القهوة المتخصصة وأدوات التحضير الاحترافية المختارة لكل محب للقهوة.">
      Discover specialty coffee beans and professional brewing tools curated for every coffee lover.
    </p>
    <div class="hero-btns">
      <a href="products.php" class="btn-primary" data-en="Shop Now →" data-ar="تسوق الآن ←">Shop Now →</a>
      <a href="contact-page.php" class="btn-outline" data-en="Contact Us" data-ar="تواصل معنا">Contact Us</a>
    </div>
  </div>
</div>

<!-- CATEGORIES -->
<section>
  <div class="sec-head">
    <div>
      <p class="section-title" data-en="Browse Categories" data-ar="تصفح التصنيفات">Browse Categories</p>
      <p class="section-sub" data-en="Find everything you need for your perfect cup" data-ar="كل ما تحتاجه لفنجانك المثالي">Find everything you need for your perfect cup</p>
    </div>
    <a class="view-all" href="products.php" data-en="View All →" data-ar="عرض الكل ←">View All →</a>
  </div>
  <div class="cat-row">
    <?php foreach ($categories as $cat):
      $cid = (int)$cat['category_id'];
      $img = $cat_image_map[$cat['category_name']] ?? ($cat_images[$cid] ?? '');
    ?>
<a class="cat-card" href="products.php?category_id=<?php echo $cat['category_id']; ?>">      <?php if ($img): ?>
        <img class="cat-img" src="images/<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($cat['category_name']); ?>" onerror="this.style.display='none'">
      <?php else: ?>
        <div class="cat-img" style="display:flex;align-items:center;justify-content:center;font-size:40px;">&#9749;</div>
      <?php endif; ?>
      <div class="cat-label"><?php echo htmlspecialchars($cat['category_name']); ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<?php if (!empty($featured)): ?>
<section>
  <div class="sec-head">
    <div>
      <p class="section-title" data-en="Featured Products" data-ar="منتجات مميزة">Featured Products</p>
      <p class="section-sub" data-en="Latest additions to our collection" data-ar="أحدث إضافات لمجموعتنا">Latest additions to our collection</p>
    </div>
    <a class="view-all" href="products.php" data-en="View All →" data-ar="عرض الكل ←">View All →</a>
  </div>
  <div class="prod-grid">
    <?php foreach ($featured as $p):
      $stock = (int)($p['stock'] ?? 0);
      $soldOut = ($stock === 0);
    ?>
    <div class="prod-card"
         data-name="<?php echo htmlspecialchars($p['product_name']); ?>"
         data-image="<?php echo htmlspecialchars($p['image']); ?>"
         data-price="<?php echo $p['price']; ?>"
         data-stock="<?php echo $stock; ?>"
         data-desc="<?php echo htmlspecialchars($p['description'] ?? ''); ?>"
         data-isbean="<?php echo ($p['category_id'] == 1) ? '1' : '0'; ?>"
         data-method="<?php echo htmlspecialchars($p['brewing_method'] ?? ''); ?>">
      <img class="prod-img" src="images/<?php echo htmlspecialchars($p['image']); ?>"
           alt="<?php echo htmlspecialchars($p['product_name']); ?>"
           onerror="this.src='images/no-product.jpeg'"
           <?php if(!$soldOut): ?>onclick="openDetail(this.closest('.prod-card'))"<?php else: ?>style="opacity:.5;cursor:default"<?php endif; ?>>
      <div class="prod-body">
        <span class="prod-cat"><?php echo htmlspecialchars($p['category_name'] ?? ''); ?></span>
        <p class="prod-name"><?php echo htmlspecialchars($p['product_name']); ?></p>
        <p class="prod-price"><?php echo number_format($p['price'], 2); ?> SAR</p>
        <?php if($soldOut): ?>
          <span class="stock-badge sold-out">Sold Out</span>
        <?php elseif($stock <= 5): ?>
          <span class="stock-badge low-stock">Only <?php echo $stock; ?> left</span>
        <?php else: ?>
          <span class="stock-badge in-stock"><?php echo $stock; ?> in stock</span>
        <?php endif; ?>
      </div>
      <button class="prod-btn<?php echo $soldOut ? ' sold-out-btn' : ''; ?>" data-en="<?php echo $soldOut ? 'Sold Out' : 'Add to Cart'; ?>" data-ar="<?php echo $soldOut ? 'نفد المخزون' : 'أضف للسلة'; ?>"
        <?php if(!$soldOut): ?>onclick="addToCart('<?php echo addslashes($p['product_name']); ?>',<?php echo $p['price']; ?>,this)"<?php else: ?>disabled<?php endif; ?>>
        <?php echo $soldOut ? 'Sold Out' : 'Add to Cart'; ?>
      </button>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- BY CATEGORY -->
<?php foreach ($by_category as $group): ?>
<section>
  <div class="sec-head">
    <div>
      <p class="section-title"><?php echo htmlspecialchars($group['name']); ?></p>
      <p class="section-sub" data-en="Top picks in this category" data-ar="أبرز المنتجات في هذا التصنيف">Top picks in this category</p>
    </div>
    <a class="view-all" href="products.php" data-en="View All →" data-ar="عرض الكل ←">View All →</a>
  </div>
  <div class="prod-grid">
    <?php foreach ($group['products'] as $p):
      $stock = (int)($p['stock'] ?? 0);
      $soldOut = ($stock === 0);
    ?>
    <div class="prod-card"
         data-name="<?php echo htmlspecialchars($p['product_name']); ?>"
         data-image="<?php echo htmlspecialchars($p['image']); ?>"
         data-price="<?php echo $p['price']; ?>"
         data-stock="<?php echo $stock; ?>"
         data-desc="<?php echo htmlspecialchars($p['description'] ?? ''); ?>"
         data-isbean="<?php echo ($p['category_id'] == 1) ? '1' : '0'; ?>"
         data-method="<?php echo htmlspecialchars($p['brewing_method'] ?? ''); ?>">
      <img class="prod-img" src="images/<?php echo htmlspecialchars($p['image']); ?>"
           alt="<?php echo htmlspecialchars($p['product_name']); ?>"
           onerror="this.src='images/no-product.jpeg'"
           <?php if(!$soldOut): ?>onclick="openDetail(this.closest('.prod-card'))"<?php else: ?>style="opacity:.5;cursor:default"<?php endif; ?>>
      <div class="prod-body">
        <p class="prod-name"><?php echo htmlspecialchars($p['product_name']); ?></p>
        <p class="prod-price"><?php echo number_format($p['price'], 2); ?> SAR</p>
        <?php if($soldOut): ?>
          <span class="stock-badge sold-out">Sold Out</span>
        <?php elseif($stock <= 5): ?>
          <span class="stock-badge low-stock">Only <?php echo $stock; ?> left</span>
        <?php else: ?>
          <span class="stock-badge in-stock"><?php echo $stock; ?> in stock</span>
        <?php endif; ?>
      </div>
      <button class="prod-btn<?php echo $soldOut ? ' sold-out-btn' : ''; ?>" data-en="<?php echo $soldOut ? 'Sold Out' : 'Add to Cart'; ?>" data-ar="<?php echo $soldOut ? 'نفد المخزون' : 'أضف للسلة'; ?>"
        <?php if(!$soldOut): ?>onclick="addToCart('<?php echo addslashes($p['product_name']); ?>',<?php echo $p['price']; ?>,this)"<?php else: ?>disabled<?php endif; ?>>
        <?php echo $soldOut ? 'Sold Out' : 'Add to Cart'; ?>
      </button>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<!-- BANNER -->
<section>
  <div class="banner">
    <div>
      <h2 data-en="Ready to Brew?" data-ar="مستعد للتحضير؟">Ready to Brew?</h2>
      <p data-en="Explore our full collection of specialty coffee tools and beans."
         data-ar="استكشف مجموعتنا الكاملة من أدوات وحبوب القهوة المتخصصة.">
        Explore our full collection of specialty coffee tools and beans.
      </p>
    </div>
    <a href="products.php" class="banner-btn" data-en="View All Products →" data-ar="عرض جميع المنتجات ←">View All Products →</a>
  </div>
</section>

<!-- FEATURES -->
<section>
  <div class="sec-head">
    <div>
      <p class="section-title" data-en="Why Brew & Bean?" data-ar="لماذا Brew & Bean؟">Why Brew & Bean?</p>
      <p class="section-sub" data-en="We care about your coffee experience" data-ar="نهتم بتجربة قهوتك">We care about your coffee experience</p>
    </div>
  </div>
  <div class="features">
    <div class="feat">
      <div class="feat-circle">&#128737;</div>
      <h4 data-en="Secure Payment" data-ar="دفع آمن">Secure Payment</h4>
      <p data-en="100% secure checkout and safe transactions" data-ar="دفع آمن 100% ومعاملات موثوقة">100% secure checkout and safe transactions</p>
    </div>
    <div class="feat">
      <div class="feat-circle">&#128666;</div>
      <h4 data-en="Fast Delivery" data-ar="توصيل سريع">Fast Delivery</h4>
      <p data-en="Quick & reliable shipping to your door" data-ar="شحن سريع وموثوق حتى بابك">Quick & reliable shipping to your door</p>
    </div>
    <div class="feat">
      <div class="feat-circle">&#128260;</div>
      <h4 data-en="Easy Returns" data-ar="إرجاع سهل">Easy Returns</h4>
      <p data-en="Hassle-free 14-day return policy" data-ar="سياسة إرجاع 14 يوم بدون تعقيد">Hassle-free 14-day return policy</p>
    </div>
    <div class="feat">
      <div class="feat-circle">&#127911;</div>
      <h4 data-en="Customer Support" data-ar="خدمة العملاء">Customer Support</h4>
      <p data-en="Our team is always here to help you" data-ar="فريقنا دائماً هنا لمساعدتك">Our team is always here to help you</p>
    </div>
  </div>
</section>

<!-- DETAIL BACKDROP -->
<div class="detail-backdrop" id="detailBackdrop" onclick="closeDetail()"></div>

<!-- DETAIL PANEL -->
<div class="detail-panel" id="detailPanel">
  <div class="dp-header">
    <span class="dp-header-title">Product Details</span>
    <button class="dp-close" onclick="closeDetail()">&#10005;</button>
  </div>
  <div class="dp-body">
    <div class="dp-img-wrap"><img id="dpImg" src="" alt=""></div>
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
      <button class="dp-add-btn" id="dpAddBtn" onclick="addFromPanel()">Add to Cart</button>
      <button class="dp-help-btn" onclick="openHelp()">Help ?</button>
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
      <div class="grind-row"><b>Whole Bean</b><span>Freshest — grind just before brewing</span></div>
      <div class="grind-row"><b>Coarse</b><span>French Press &amp; Cold Brew</span></div>
      <div class="grind-row"><b>Medium</b><span>Pour-over, V60 &amp; Drip</span></div>
      <div class="grind-row"><b>Fine</b><span>Aeropress &amp; Moka Pot</span></div>
      <div class="grind-row"><b>Espresso</b><span>Espresso machines only</span></div>
    </div>
    <div class="grind-box-foot">
      <button onclick="closeGrindHelp()">Got it &#10003;</button>
    </div>
  </div>
</div>

<!-- HELP POPUP -->
<div class="help-overlay" id="helpOverlay" onclick="closeHelp()"></div>
<div class="help-popup" id="helpPopup">
  <div class="help-header">
    <h3>Help &amp; Information</h3>
    <button onclick="closeHelp()">&#10005;</button>
  </div>
  <div class="help-content">
    <p><strong>How to use:</strong></p>
    <ul>
      <li>Click on any product image to view details.</li>
      <li>Select quantity before adding to cart.</li>
      <li>Coffee beans allow grind selection.</li>
      <li>Use the cart page to complete your order.</li>
    </ul>
    <p>If you need more support contact Brew &amp; Bean.</p>
    <button class="help-dismiss" onclick="closeHelp()">Got it &#10003;</button>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-top">
    <div class="footer-brand">
      <h2>Brew & Bean</h2>
      <p data-en="Your destination for specialty coffee beans and professional brewing tools. We curate the best for every coffee lover."
         data-ar="وجهتك لحبوب القهوة المتخصصة وأدوات التحضير الاحترافية. نختار الأفضل لكل محب للقهوة.">
        Your destination for specialty coffee beans and professional brewing tools.
      </p>
    </div>
    <div class="footer-col">
      <h4 data-en="Quick Links" data-ar="روابط سريعة">Quick Links</h4>
      <a href="code.php" data-en="Home" data-ar="الرئيسية">Home</a>
      <a href="products.php" data-en="Products" data-ar="المنتجات">Products</a>
      <a href="ShoppingCart.php" data-en="Cart" data-ar="السلة">Cart</a>
      <a href="my_orders.php" data-en="My Orders" data-ar="طلباتي">My Orders</a>
      <a href="contact-page.php" data-en="Contact" data-ar="تواصل معنا">Contact</a>
    </div>
    <div class="footer-col">
      <h4 data-en="Categories" data-ar="التصنيفات">Categories</h4>
      <?php foreach ($categories as $cat): ?>
      <a href="products.php"><?php echo htmlspecialchars($cat['category_name']); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="footer-bottom">
    <span data-en="© 2026 Brew & Bean — Premium Coffee. All rights reserved."
          data-ar="© 2026 Brew & Bean — قهوة متميزة. جميع الحقوق محفوظة.">
      © 2026 Brew & Bean — Premium Coffee. All rights reserved.
    </span>
  </div>
</footer>

<script>
/* ── HERO IMAGE LOAD ANIMATION ── */
(function(){
  const bg = document.getElementById('heroBg');
  const img = new Image();
  img.src = bg ? getComputedStyle(bg).backgroundImage.replace(/url\(["']?|["']?\)/g,'') : '';
  img.onload = function(){ if(bg) bg.classList.add('loaded'); };
  if(bg) bg.classList.add('loaded'); // instant fallback
})();

/* ── ADD TO CART ── */
function addToCart(name, price, btn) {
  // Update localStorage for UI state
  let cart = JSON.parse(localStorage.getItem('cart') || '[]');
  const existing = cart.find(i => i.name === name);
  if (existing) existing.quantity += 1;
  else cart.push({ name, price: parseFloat(price), quantity: 1 });
  localStorage.setItem('cart', JSON.stringify(cart));

  // Sync to database
  const fd = new FormData();
  fd.append('name', name);
  fd.append('price', price);
  fd.append('quantity', 1);
  fetch('add_to_cart.php', {method:'POST', body:fd}).catch(()=>{});

  const lang = localStorage.getItem('lang') || 'en';
  const original = btn.dataset[lang] || btn.textContent;
  btn.textContent = lang === 'ar' ? '✓ أضيف!' : '✓ Added!';
  btn.style.background = '#1E6B3A';
  setTimeout(() => {
    btn.textContent = original;
    btn.style.background = '';
  }, 1500);
}

/* ── LANGUAGE SWITCHER ── */
function setLang(lang) {
  localStorage.setItem('lang', lang);
  const isAR = lang === 'ar';

  document.documentElement.lang = lang;
  document.documentElement.dir  = isAR ? 'rtl' : 'ltr';
  document.body.style.fontFamily = isAR ? "'Arial', sans-serif" : "Arial, sans-serif";

  document.getElementById('btnEN').classList.toggle('active', !isAR);
  document.getElementById('btnAR').classList.toggle('active',  isAR);

  document.querySelectorAll('[data-en][data-ar]').forEach(el => {
    const val = isAR ? el.dataset.ar : el.dataset.en;
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.placeholder = val;
    else el.innerHTML = val;
  });
}

/* تطبيق اللغة المحفوظة عند تحميل الصفحة */
(function(){
  const saved = localStorage.getItem('lang') || 'en';
  setLang(saved);
})();

/* ── DETAIL PANEL ── */
let _activePanel = null;

function getBeanNotes(name){
  const n=name.toLowerCase();
  if(n.includes('red fruit'))     return 'Red Berries · Hibiscus · Tropical Citrus';
  if(n.includes('watermelon'))    return 'Watermelon · Green Apple · Jasmine';
  if(n.includes('passion fruit')) return 'Passion Fruit · Mango · Bright Citrus';
  if(n.includes('cotton candy'))  return 'Cotton Candy · White Peach · Floral';
  if(n.includes('coconut'))       return 'Coconut · Lemon Zest · Sweet Candy';
  if(n.includes('chocolate'))     return 'Dark Chocolate · Caramel · Roasted Hazelnut';
  if(n.includes('haraaz')||n.includes('yemeni')) return 'Dried Grape · Raisins · Dark Spice';
  if(n.includes('guji'))          return 'Blueberry · Jasmine · Black Tea';
  if(n.includes('blend'))         return 'Balanced · Caramel · Smooth Finish';
  if(n.includes('colombia')||n.includes('colombian')) return 'Red Apple · Caramel · Citrus Brightness';
  if(n.includes('ethiopia')||n.includes('ethiopian')) return 'Jasmine · Wild Berries · Bright Acidity';
  if(n.includes('brazil')||n.includes('brazilian'))   return 'Chocolate · Hazelnut · Brown Sugar';
  if(n.includes('indonesia'))     return 'Dark Chocolate · Earthy · Low Acidity';
  if(n.includes('guatemala'))     return 'Caramel · Dark Chocolate · Hazelnut';
  if(n.includes('india')||n.includes('nasla')) return 'Spice · Milk Chocolate · Woody Notes';
  if(n.includes('uganda'))        return 'Citrus · Brown Sugar · Milk Chocolate';
  return 'Rich · Smooth · Complex Finish';
}

function getBeanOrigin(name){
  const n=name.toLowerCase();
  if(n.includes('colombian')||n.includes('colombia')) return 'Colombia';
  if(n.includes('brazilian')||n.includes('brazil'))   return 'Brazil';
  if(n.includes('yemeni')||n.includes('haraaz'))      return 'Yemen';
  if(n.includes('ethiopia')||n.includes('guji'))      return 'Ethiopia';
  if(n.includes('indonesia'))  return 'Indonesia';
  if(n.includes('guatemala'))  return 'Guatemala';
  if(n.includes('uganda'))     return 'Uganda';
  if(n.includes('india')||n.includes('nasla')) return 'India';
  return 'Multi-Origin';
}

function openDetail(card){
  const name   = card.dataset.name;
  const image  = card.dataset.image;
  const price  = parseFloat(card.dataset.price);
  const desc   = card.dataset.desc;
  const isBean = card.dataset.isbean === '1';
  const method = card.dataset.method || '';
  _activePanel = {name, price};

  document.getElementById('dpImg').src = 'images/' + image;
  document.getElementById('dpImg').onerror = function(){ this.src='images/no-product.jpeg'; };
  document.getElementById('dpName').textContent = name;

  /* badges */
  const b = document.getElementById('dpBadges');
  if(isBean){
    b.innerHTML = `<span class="dp-badge origin">&#127758; ${getBeanOrigin(name)}</span><span class="dp-badge">${method}</span>`;
  } else {
    b.innerHTML = `<span class="dp-badge">&#9874; ${method} Tool</span>`;
  }

  /* stars */
  document.getElementById('dpStars').textContent = price > 150 ? '★★★★★' : '★★★★☆';

  /* price + optional discount */
  const hasDiscount = (name.length % 3 === 0);
  const oldPrice = hasDiscount ? Math.round(price * 1.18) : null;
  document.getElementById('dpPrice').textContent = price.toFixed(2) + ' SAR';
  const oldEl = document.getElementById('dpOldPrice');
  const tagEl = document.getElementById('dpDiscountTag');
  if(oldPrice){
    oldEl.textContent = oldPrice + ' SAR'; oldEl.style.display = 'inline';
    tagEl.textContent = Math.round((1 - price/oldPrice)*100) + '% OFF'; tagEl.style.display = 'inline';
  } else {
    oldEl.style.display = 'none'; tagEl.style.display = 'none';
  }

  /* notes */
  if(isBean){
    document.getElementById('dpNotesLabel').textContent = 'Flavor Notes';
    document.getElementById('dpNotes').textContent = getBeanNotes(name);
  } else {
    document.getElementById('dpNotesLabel').textContent = 'About This Tool';
    document.getElementById('dpNotes').textContent = desc || 'High-quality coffee accessory for precision and daily use.';
  }

  document.getElementById('dpGrindSection').style.display = isBean ? 'block' : 'none';
  document.getElementById('dpRecipe').style.display       = isBean ? 'block' : 'none';
  document.getElementById('dpQty').value = 1;
  const btn = document.getElementById('dpAddBtn');
  btn.textContent = 'Add to Cart'; btn.classList.remove('added');

  document.getElementById('detailPanel').classList.add('open');
  document.getElementById('detailBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';

  if(window._helpTimer) clearTimeout(window._helpTimer);
  window._helpTimer = setTimeout(openHelp, 2000);
}

function closeDetail(){
  document.getElementById('detailPanel').classList.remove('open');
  document.getElementById('detailBackdrop').classList.remove('open');
  document.body.style.overflow = '';
  if(window._helpTimer){ clearTimeout(window._helpTimer); window._helpTimer = null; }
}

function changeDetailQty(d){
  const i = document.getElementById('dpQty');
  i.value = Math.max(1, (parseInt(i.value)||1) + d);
}

function addFromPanel(){
  if(!_activePanel) return;
  const qty = Math.max(1, parseInt(document.getElementById('dpQty').value)||1);
  let cart = JSON.parse(localStorage.getItem('cart')||'[]');
  const ex = cart.find(i=>i.name===_activePanel.name);
  if(ex) ex.quantity += qty;
  else cart.push({name:_activePanel.name, price:_activePanel.price, quantity:qty});
  localStorage.setItem('cart', JSON.stringify(cart));
  const btn = document.getElementById('dpAddBtn');
  btn.textContent = '✓ Added!'; btn.classList.add('added');
  setTimeout(()=>{ btn.textContent='Add to Cart'; btn.classList.remove('added'); }, 1800);
}

/* ── HELP POPUP ── */
function openHelp(){
  document.getElementById('helpOverlay').classList.add('open');
  document.getElementById('helpPopup').classList.add('open');
}
function closeHelp(){
  document.getElementById('helpOverlay').classList.remove('open');
  document.getElementById('helpPopup').classList.remove('open');
}

/* ── GRIND GUIDE POPUP ── */
function openGrindHelp(){
  document.getElementById('grindBackdrop').classList.add('open');
}
function closeGrindHelp(){
  document.getElementById('grindBackdrop').classList.remove('open');
}

</script>

</body>
<script src="accessibility.js"></script>
</html>
