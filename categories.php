<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
include "db_connect.php";

$categories = [];
$selected_category = null;
$selected_products = [];
$search_value = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

/* Get categories with products count */
$categories_query = "
    SELECT 
        c.category_id,
        c.category_name,
        c.description,
        COUNT(p.product_id) AS products_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    GROUP BY c.category_id, c.category_name, c.description
    ORDER BY c.category_name ASC
";

$categories_result = mysqli_query($conn, $categories_query);

if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        if ($search_value === '' || stripos($row['category_name'], $search_value) !== false) {
            $categories[] = $row;
        }
    }
} else {
    die("Categories query failed: " . mysqli_error($conn));
}

/* Selected category */
if ($selected_category_id > 0) {
    $category_query = "SELECT * FROM categories WHERE category_id = $selected_category_id LIMIT 1";
    $category_result = mysqli_query($conn, $category_query);

    if ($category_result) {
        $selected_category = mysqli_fetch_assoc($category_result);
    } else {
        die("Selected category query failed: " . mysqli_error($conn));
    }

    if ($selected_category) {
        $products_query = "SELECT * FROM products WHERE category_id = $selected_category_id ORDER BY product_name ASC";
        $products_result = mysqli_query($conn, $products_query);

        if ($products_result) {
            while ($product = mysqli_fetch_assoc($products_result)) {
                $selected_products[] = $product;
            }
        } else {
            die("Products query failed: " . mysqli_error($conn));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Categories - Brew & Bean</title>

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

  .grid{
    display:grid;
    grid-template-columns: 1.3fr 1fr;
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

  .categories-list{
    display:grid;
    gap:12px;
  }

  .category-card{
    background:#fff;
    border:1px solid var(--line);
    border-radius:18px;
    padding:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
  }

  .category-info h3{
    margin:0 0 6px;
    font-size:18px;
  }

  .category-info p{
    margin:0;
    color: var(--muted);
    font-size:14px;
    line-height:1.5;
  }

  .tag{
    display:inline-block;
    padding:8px 12px;
    border-radius:999px;
    border:1px solid var(--line);
    background: rgba(74,44,29,0.07);
    color: var(--brown);
    font-weight:bold;
    font-size:13px;
    margin-top:8px;
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

  .note-box{
    border:1px dashed rgba(74,44,29,0.35);
    border-radius:18px;
    background:#fff;
    padding:14px;
  }

  .note-box h3{
    margin:0 0 10px;
    font-size:16px;
  }

  .note-box p{
    margin:0;
    line-height:1.6;
    color: var(--text);
  }

  .products-preview{
    display:grid;
    grid-template-columns: repeat(2, 1fr);
    gap:12px;
    margin-top:14px;
  }

  .preview-card{
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:12px;
    text-align:center;
  }

  .preview-card img{
    width:100%;
    height:140px;
    object-fit:contain;
    border-radius:12px;
    margin-bottom:10px;
    background:#fff;
  }

  .preview-card h4{
    margin:0 0 6px;
    font-size:14px;
    min-height:36px;
  }

  .preview-card p{
    margin:0;
    color:var(--brown);
    font-weight:bold;
    font-size:14px;
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
    .category-card{ flex-direction:column; align-items:flex-start; }
    .products-preview{ grid-template-columns:1fr; }
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
        <label class="nav-link active" for="productsMenu">
          Manage Products <span class="chev">›</span>
        </label>

        <div class="submenu">
          <a href="add_product.php">Add Product <span>+</span></a>
          <a href="view_products.php">View / Edit Products <span>›</span></a>
          <a class="active" href="categories.php">Categories <span>›</span></a>
        </div>
      </div>

      <a class="nav-link" href="manage_orders.php">Manage Orders <span class="chev">›</span></a>
      <a class="nav-link" href="customers.php">Customers <span class="chev">›</span></a>
      <a class="nav-link" href="logout.php">Logout <span class="chev">⎋</span></a>
    </nav>
  </aside>

  <main class="main">

    <div class="topbar">
      <div class="title">
        <h1>Categories</h1>
        <small>View and manage product categories</small>
      </div>

      <form class="actions" method="get" action="categories.php">
        <input class="search" type="text" name="search" placeholder="Search category..." value="<?php echo htmlspecialchars($search_value); ?>" />
        <button class="btn" type="submit">Search</button>
        <a class="btn" href="admin_dashboard.php">Back to Dashboard</a>
      </form>
    </div>

    <section class="grid">

      <div class="panel">
        <h2>Categories List</h2>

        <div class="categories-list">
          <?php if (count($categories) > 0) { ?>
            <?php foreach ($categories as $category) { ?>
              <div class="category-card">
                <div class="category-info">
                  <h3><?php echo htmlspecialchars($category['category_name']); ?></h3>
                  <p><?php echo !empty($category['description']) ? htmlspecialchars($category['description']) : 'No description available.'; ?></p>
                  <span class="tag"><?php echo $category['products_count']; ?> Products</span>
                </div>
                <div class="mini-actions">
                  <a class="mini-btn" href="categories.php?category_id=<?php echo $category['category_id']; ?>">View</a>
                </div>
              </div>
            <?php } ?>
          <?php } else { ?>
            <p>No categories found.</p>
          <?php } ?>
        </div>
      </div>

      <div class="panel">
        <h2>Category Details</h2>

        <?php if ($selected_category): ?>
          <div class="detail-box">
            <h3><?php echo htmlspecialchars($selected_category['category_name']); ?></h3>

            <div class="detail-row">
              <span class="detail-label">Category Name</span>
              <div class="detail-value"><?php echo htmlspecialchars($selected_category['category_name']); ?></div>
            </div>

            <div class="detail-row">
              <span class="detail-label">Products Count</span>
              <div class="detail-value"><?php echo count($selected_products); ?> Products</div>
            </div>

            <div class="detail-row">
              <span class="detail-label">Description</span>
              <div class="detail-value">
                <?php echo !empty($selected_category['description']) ? htmlspecialchars($selected_category['description']) : 'No description available.'; ?>
              </div>
            </div>
          </div>

          <div class="note-box">
            <h3>Products</h3>

            <?php if (count($selected_products) > 0): ?>
              <div class="products-preview">
                <?php foreach ($selected_products as $product): ?>
                  <div class="preview-card">
                    <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                    <p><?php echo htmlspecialchars($product['price']); ?> SAR</p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p>No products in this category.</p>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <div class="detail-box">
            <h3>Selected Category</h3>

            <div class="detail-row">
              <span class="detail-label">Category Name</span>
              <div class="detail-value">Please select a category</div>
            </div>

            <div class="detail-row">
              <span class="detail-label">Products Count</span>
              <div class="detail-value">0 Products</div>
            </div>

            <div class="detail-row">
              <span class="detail-label">Description</span>
              <div class="detail-value">
                Select any category from the left side to view its details and products.
              </div>
            </div>
          </div>

          <div class="note-box">
            <h3>Notes</h3>
            <p>
              This page shows category information and all products linked to the selected category from the database.
            </p>
          </div>
        <?php endif; ?>

      </div>

    </section>
  </main>
</div>
<script>
  const searchInput   = document.querySelector('.search');
  const categoryCards = document.querySelectorAll('.category-card');
  const listWrap      = document.querySelector('.categories-list');
  const panelTitle    = document.querySelector('.panel h2');

  // رسالة "لا نتائج"
  const noResults = document.createElement('p');
  noResults.id = 'no-results';
  noResults.style.cssText = 'color:var(--muted);text-align:center;padding:20px 0;display:none;';
  noResults.textContent = 'No categories match your search.';
  listWrap.appendChild(noResults);

  function highlight(text, query) {
    if (!query) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp('(' + escaped + ')', 'gi'),
      '<mark style="background:rgba(74,44,29,0.15);border-radius:4px;padding:0 2px;">$1</mark>');
  }

  // حفظ النصوص الأصلية
  categoryCards.forEach(function(card) {
    const h3 = card.querySelector('.category-info h3');
    const p  = card.querySelector('.category-info p');
    h3.dataset.original = h3.textContent;
    p.dataset.original  = p.textContent;
  });

  function updateCount(visible) {
    panelTitle.textContent = visible === categoryCards.length
      ? 'Categories List'
      : 'Categories List (' + visible + ' of ' + categoryCards.length + ')';
  }

  function runSearch() {
    const q     = searchInput.value.trim();
    // تقسيم الكلمات — كل كلمة تُبحث بشكل مستقل
    const words = q.toLowerCase().split(/\s+/).filter(w => w.length > 0);
    let visible = 0;

    categoryCards.forEach(function(card) {
      const h3   = card.querySelector('.category-info h3');
      const p    = card.querySelector('.category-info p');
      const name = h3.dataset.original.toLowerCase();
      const desc = p.dataset.original.toLowerCase();
      const text = name + ' ' + desc;

      // يظهر الكارد إذا تطابقت أي كلمة من كلمات البحث
      const match = words.length === 0 || words.some(w => text.includes(w));

      if (match) {
        card.style.display = '';
        h3.innerHTML = highlight(h3.dataset.original, q);
        p.innerHTML  = highlight(p.dataset.original, q);
        visible++;
      } else {
        card.style.display = 'none';
        h3.innerHTML = h3.dataset.original;
        p.innerHTML  = p.dataset.original;
      }
    });

    noResults.style.display = visible === 0 ? 'block' : 'none';
    updateCount(visible);
  }

  // بحث مباشر أثناء الكتابة
  searchInput.addEventListener('input', runSearch);

  // منع إعادة تحميل الصفحة عند الضغط على Enter
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

  // تشغيل البحث تلقائياً إذا كان هناك قيمة محفوظة
  if (searchInput.value.trim() !== '') runSearch();
</script>
<script src="accessibility.js"></script>
</body>
</html>
