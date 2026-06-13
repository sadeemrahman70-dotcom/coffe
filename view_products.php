<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
include "db_connect.php";

/* Handle delete */
if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE product_id = $delete_id");
    header("Location: view_products.php");
    exit();
}

/* Handle update */
$updateError = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $product_id = (int) $_POST['product_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = (int) $_POST['category_id'];
    $brewing_method = mysqli_real_escape_string($conn, $_POST['brewing_method']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    if ($price <= 0) {
        $updateError = 'price';
    } elseif ($stock < 0) {
        $updateError = 'stock';
    } else {
        $update_query = "UPDATE products SET
            product_name='$product_name',
            category_id='$category_id',
            brewing_method='$brewing_method',
            price='$price',
            stock='$stock',
            description='$description'
            WHERE product_id=$product_id";
        mysqli_query($conn, $update_query);
        header("Location: view_products.php");
        exit();
    }
}

/* Get categories */
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

/* Get products */
$query = "SELECT products.*, categories.category_name
          FROM products
          LEFT JOIN categories ON products.category_id = categories.category_id
          ORDER BY products.created_at DESC, products.product_id DESC";
$result = mysqli_query($conn, $query);
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Products - Brew & Bean</title>

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
    --shadow: 0 18px 35px rgba(46,36,32,0.12);
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
    flex: 0 0 auto;
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
    margin-top: 8px;
  }

  .nav .has-sub{ position: relative; }
  .nav input[type="checkbox"]{ display:none; }

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

  .title h1{ margin:0; font-size:20px; }
  .title small{ color: var(--muted); }

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
    display:inline-flex;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
  }

  .btn:hover{ transform: translateY(-1px); }

  .btn.primary{
    background: var(--brown);
    border-color: var(--brown);
    color:#fff;
  }

  .btn.danger{
    border-color:#8a2b2b;
    color:#8a2b2b;
    background:#fff;
  }

  .btn.danger:hover{
    background: rgba(138,43,43,0.06);
  }

  .grid{
    display:grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 14px;
    align-items:start;
  }

  .panel{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 10px 16px 16px 16px;
  }

  .panel h2{
    margin: 0 0 8px;
    font-size: 18px;
  }

  .products{
    display:grid;
    gap:12px;
    max-height:550px;
    overflow-y:auto;
    padding-right:5px;
  }

  .badge-new{
    display:inline-block;
    background:#2E7D32;
    color:#fff;
    font-size:10px;
    font-weight:800;
    padding:2px 8px;
    border-radius:999px;
    letter-spacing:.5px;
    vertical-align:middle;
    margin-left:6px;
    animation:pulse-new 1.5s ease-in-out infinite;
  }
  @keyframes pulse-new{
    0%,100%{opacity:1;}
    50%{opacity:.6;}
  }

  .product-card{
    background:#fff;
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 14px;
    display:grid;
    grid-template-columns: 72px 1fr;
    gap: 12px;
    box-shadow: 0 10px 22px rgba(46,36,32,0.06);
  }

  .p-img{
    width:72px;height:72px;
    border-radius:16px;
    border:1px solid var(--line);
    overflow:hidden;
    background: #fafafa;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    color: var(--muted);
  }

  .p-img img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .p-head{
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:flex-start;
    flex-wrap:wrap;
  }

  .p-title{
    margin:0;
    font-size:16px;
    font-weight:800;
  }

  .p-meta{
    margin-top:6px;
    color: var(--muted);
    font-size:13px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .pill{
    display:inline-flex;
    padding:6px 10px;
    border-radius:999px;
    border: 1px solid var(--line);
    background: rgba(74,44,29,0.07);
    color: var(--brown);
    font-weight:700;
    font-size:12px;
  }

  .p-actions{
    margin-top:10px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .edit-panel{
    display:none;
  }

  .edit-panel:target{
    display:block;
  }

  .default-panel{
    display:block;
  }

  .edit-panel:target ~ .default-panel{
    display:none;
  }

  .form{
    display:grid;
    gap: 12px;
  }

  .row2{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }

  .right-side{
    position: sticky;
    top: 18px;
    height: calc(100vh - 36px);
    overflow-y: auto;
  }

  label{
    font-weight:bold;
    font-size: 13px;
    color: var(--muted);
    display:block;
    margin-bottom: 6px;
  }

  input, select, textarea{
    width:100%;
    padding: 12px 12px;
    border-radius: 14px;
    border: 1px solid var(--line);
    outline:none;
    background:#fff;
    font-size: 14px;
  }

  textarea{ min-height: 120px; resize:vertical; }

  .helper{
    color: var(--muted);
    font-size: 13px;
    margin-top: 2px;
  }

  .form-actions{
    display:flex;
    gap: 10px;
    flex-wrap:wrap;
    margin-top: 6px;
  }

  .info-card{
    border: 1px dashed rgba(74,44,29,0.40);
    border-radius: 18px;
    background: #fff;
    padding: 14px;
  }

  .info-card h3{
    margin:0 0 6px;
    font-size: 16px;
  }

  .info-card p{
    margin:0;
    color: var(--muted);
    line-height: 1.55;
  }

  .badge{
    display:inline-block;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid var(--line);
    background: rgba(74,44,29,0.07);
    color: var(--brown);
    font-weight:bold;
    font-size: 13px;
    margin-top: 10px;
  }

  @media (max-width: 1100px){
    .grid{ grid-template-columns: 1fr; }
    .search{ width:100%; max-width:100%; }
  }

  @media (max-width: 780px){
    .layout{ flex-direction:column; }
    .sidebar{ width:100%; }
    .topbar{ flex-direction:column; align-items:stretch; }
    .row2{ grid-template-columns: 1fr; }
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
        <a class="nav-link" href="admin_dashboard.php">
          Dashboard <span class="chev">›</span>
        </a>

        <div class="has-sub">
          <input type="checkbox" id="productsMenu" checked>
          <label class="nav-link active" for="productsMenu">
            Manage Products <span class="chev">›</span>
          </label>

          <div class="submenu">
            <a href="add_product.php">Add Product <span>+</span></a>
            <a class="active" href="view_products.php">View / Edit Products <span>›</span></a>
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
          <h1>Products</h1>
          <small>View and manage store products</small>
        </div>

        <div class="actions">
          <input class="search" id="productSearch" type="text" placeholder="Search products..." />
          <a class="btn" href="admin_dashboard.php">Back to Dashboard</a>
        </div>
      </div>

      <section class="grid">

        <div class="panel">
          <h2>Products List</h2>

          <div class="products">
            <?php if (count($products) > 0) { ?>
              <?php foreach ($products as $product) { ?>
                <div class="product-card">
                  <div class="p-img">
                    <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                  </div>

                  <div>
                    <div class="p-head">
                      <div>
                        <p class="p-title">
                          <?php echo htmlspecialchars($product['product_name']); ?>
                          <?php
                            $created = !empty($product['created_at']) ? strtotime($product['created_at']) : 0;
                            if ($created > 0 && (time() - $created) < 7200):
                          ?>
                            <span class="badge-new">NEW</span>
                          <?php endif; ?>
                        </p>
                        <div class="p-meta">
                          <span class="pill"><?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : 'No Category'; ?></span>
                          <span class="pill"><?php echo htmlspecialchars($product['price']); ?> SAR</span>
                          <span class="pill">Stock: <?php echo htmlspecialchars($product['stock']); ?></span>
                          <span class="pill">ID: <?php echo htmlspecialchars($product['product_id']); ?></span>
                        </div>
                      </div>
                    </div>

                    <div class="p-actions">
                      <a class="btn" href="#view-<?php echo $product['product_id']; ?>">View</a>
                      <a class="btn primary" href="#edit-<?php echo $product['product_id']; ?>">Edit</a>
                      <a class="btn danger" href="view_products.php?delete=<?php echo $product['product_id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                    </div>
                  </div>
                </div>
              <?php } ?>
            <?php } else { ?>
              <p>No products found.</p>
            <?php } ?>
          </div>
        </div>

        <div class="right-side">

          <?php foreach ($products as $product) { ?>
            <div class="panel edit-panel" id="view-<?php echo $product['product_id']; ?>">
              <h2>Product Details</h2>
              <div class="helper">Preview product information (read-only).</div>

              <div class="form">
                <div class="p-img" style="width:140px;height:140px;">
                  <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                </div>

                <div>
                  <label>Product ID</label>
                  <input type="text" value="<?php echo htmlspecialchars($product['product_id']); ?>" readonly>
                </div>

                <div>
                  <label>Product Name</label>
                  <input type="text" value="<?php echo htmlspecialchars($product['product_name']); ?>" readonly>
                </div>

                <div class="row2">
                  <div>
                    <label>Category</label>
                    <input type="text" value="<?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : 'No Category'; ?>" readonly>
                  </div>
                  <div>
                    <label>Brewing Method</label>
                    <input type="text" value="<?php echo htmlspecialchars($product['brewing_method']); ?>" readonly>
                  </div>
                </div>

                <div class="row2">
                  <div>
                    <label>Price (SAR)</label>
                    <input type="text" value="<?php echo htmlspecialchars($product['price']); ?>" readonly>
                  </div>
                  <div>
                    <label>Stock</label>
                    <input type="text" value="<?php echo htmlspecialchars($product['stock']); ?>" readonly>
                  </div>
                </div>

                <div>
                  <label>Description</label>
                  <textarea readonly><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-actions">
                  <a class="btn primary" href="#edit-<?php echo $product['product_id']; ?>">Edit</a>
                  <a class="btn" href="view_products.php">Close</a>
                </div>
              </div>
            </div>

            <div class="panel edit-panel" id="edit-<?php echo $product['product_id']; ?>">
              <h2>Edit Product</h2>
              <div class="helper">Update product details (ID is read-only).</div>

              <form class="form" method="post" action="view_products.php" onsubmit="return validateProduct(this)">
                <div class="p-img" style="width:140px;height:140px;">
                  <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                </div>

                <?php if ($updateError && (int)$_POST['product_id'] === $product['product_id']): ?>
                <div style="background:#fdecea;border:1px solid #f5a0a0;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:700;color:#c0392b;">
                    ✖ <?php echo $updateError === 'price' ? 'Price must be greater than 0.' : 'Stock cannot be negative.'; ?>
                </div>
                <?php endif; ?>

                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

                <div>
                  <label>Product ID</label>
                  <input type="text" value="<?php echo htmlspecialchars($product['product_id']); ?>" readonly>
                </div>

                <div>
                  <label>Product Name</label>
                  <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                </div>

                <div class="row2">
                  <div>
                    <label>Category</label>
                    <select name="category_id" required>
                      <?php
                      mysqli_data_seek($categories_result, 0);
                      while ($cat = mysqli_fetch_assoc($categories_result)) {
                      ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php if ($product['category_id'] == $cat['category_id']) echo 'selected'; ?>>
                          <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>

                  <div>
                    <label>Brewing Method</label>
                    <select name="brewing_method" required>
                      <option value="Filter" <?php if ($product['brewing_method'] == 'Filter') echo 'selected'; ?>>Filter</option>
                      <option value="Espresso" <?php if ($product['brewing_method'] == 'Espresso') echo 'selected'; ?>>Espresso</option>
                      <option value="Filter & Espresso" <?php if ($product['brewing_method'] == 'Filter & Espresso') echo 'selected'; ?>>Filter & Espresso</option>
                    </select>
                  </div>
                </div>

                <div class="row2">
                  <div>
                    <label>Price (SAR)</label>
                    <input type="number" step="0.01" min="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                  </div>
                  <div>
                    <label>Stock</label>
                    <input type="number" min="0" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                  </div>
                </div>

                <div>
                  <label>Description</label>
                  <textarea name="description"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-actions">
                  <button class="btn primary" type="submit" name="update_product">Update Product</button>
                  <a class="btn" href="view_products.php">Cancel</a>
                </div>
              </form>
            </div>
          <?php } ?>

          <div class="panel default-panel">
            <h2>Product Details</h2>
            <div class="info-card">
              <h3>Select a Product</h3>
              <p>Choose any product from the left side to view or edit its details.</p>
              <span class="badge">Dynamic Product Management</span>
            </div>
          </div>

        </div>
      </section>
    </main>
  </div>

<script>
function validateProduct(form) {
    const price = parseFloat(form.querySelector('[name="price"]').value);
    const stock = parseInt(form.querySelector('[name="stock"]').value);

    // remove old inline error if any
    const old = form.querySelector('.js-error');
    if (old) old.remove();

    if (isNaN(price) || price <= 0) {
        showInlineError(form, 'Price must be greater than 0.');
        return false;
    }
    if (isNaN(stock) || stock < 0) {
        showInlineError(form, 'Stock cannot be negative.');
        return false;
    }
    return true;
}

function showInlineError(form, msg) {
    const div = document.createElement('div');
    div.className = 'js-error';
    div.style.cssText = 'background:#fdecea;border:1px solid #f5a0a0;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:700;color:#c0392b;margin-bottom:4px;';
    div.textContent = '✖ ' + msg;
    form.querySelector('.form-actions').before(div);
    div.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>

<script>
  const searchInput   = document.getElementById("productSearch");
  const productCards  = document.querySelectorAll(".product-card");
  const productsWrap  = document.querySelector(".products");
  const panelTitle    = document.querySelector(".panel h2");

  // إنشاء رسالة "لا نتائج"
  const noResults = document.createElement("p");
  noResults.id = "no-results";
  noResults.style.cssText = "color:var(--muted);text-align:center;padding:20px 0;display:none;";
  noResults.textContent = "No products match your search.";
  productsWrap.appendChild(noResults);

  function updateCount(visible) {
    panelTitle.textContent = visible === productCards.length
      ? "Products List"
      : "Products List (" + visible + " of " + productCards.length + ")";
  }

  function runSearch() {
    const q = searchInput.value.toLowerCase().trim();
    let visible = 0;

    productCards.forEach(function(card) {
      // يبحث في الاسم + كل الـ pills (تصنيف، سعر، مخزون)
      const name  = card.querySelector(".p-title").textContent.toLowerCase();
      const pills = Array.from(card.querySelectorAll(".pill"))
                        .map(p => p.textContent.toLowerCase()).join(" ");

      const match = name.includes(q) || pills.includes(q);
      card.style.display = match ? "grid" : "none";
      if (match) visible++;
    });

    noResults.style.display = visible === 0 ? "block" : "none";
    updateCount(visible);
  }

  searchInput.addEventListener("input", runSearch);

  // مسح البحث بضغط Escape
  searchInput.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
      this.value = "";
      runSearch();
      this.blur();
    }
  });
</script>
<script src="accessibility.js"></script>
</body>
</html> 
