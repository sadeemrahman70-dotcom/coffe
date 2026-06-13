<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
include "db_connect.php";

$message = "";

/* Get categories */
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

/* Add product */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = (int) $_POST['category_id'];
    $brewing_method = mysqli_real_escape_string($conn, $_POST['brewing_method']);
    $price = (float) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $image_name = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $target_path = "images/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_path);
    }

    $stmt = $conn->prepare("INSERT INTO products (product_name, category_id, brewing_method, price, stock, description, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sisdiss", $product_name, $category_id, $brewing_method, $price, $stock, $description, $image_name);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: view_products.php");
        exit();
    } else {
        $message = "Error: " . $stmt->error;
        $stmt->close();
    }

    $categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Add Product - Brew & Bean</title>

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
    --success:#1E6B3A;
    --success-bg:#E8FFF2;
    --danger:#8a2b2b;
    --danger-bg:#fff1f1;
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
    opacity:0.9;
    color: rgba(255,255,255,0.75);
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
  }

  .submenu a:hover{
    background: rgba(255,255,255,0.16);
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
  }

  .btn:hover{ transform: translateY(-1px); }

  .btn.primary{
    background: var(--brown);
    border-color: var(--brown);
    color:#fff;
  }

  .grid{
    display:grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 14px;
  }

  .panel{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 18px;
    padding: 16px;
  }

  .panel h2{
    margin:0 0 12px;
    font-size:18px;
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

  textarea{
    min-height: 120px;
    resize:vertical;
  }

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
    margin-bottom: 12px;
  }

  .info-card h3{
    margin:0 0 6px;
    font-size: 16px;
  }

  .info-card p{
    margin:0;
    color: var(--muted);
    line-height: 1.5;
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

  .message{
    padding:12px 14px;
    border-radius:14px;
    font-weight:600;
    margin-bottom:14px;
  }

  .message.success{
    background: var(--success-bg);
    color: var(--success);
    border:1px solid #BFEED2;
  }

  .message.error{
    background: var(--danger-bg);
    color: var(--danger);
    border:1px solid #f1c4c4;
  }

  .preview-panel{
    display:flex;
    flex-direction:column;
  }

  .preview-card{
    background: var(--panel);
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 28px 20px;
    text-align:center;
    min-height: 640px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
  }

  .preview-image-box{
    width: 100%;
    max-width: 360px;
    height: 360px;
    background: #fff;
    border-radius: 22px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-bottom: 24px;
  }

  .preview-image-box img{
    width:100%;
    height:100%;
    object-fit:contain;
    display:block;
  }

  .preview-name{
    margin: 0 0 12px;
    font-size: 22px;
    font-weight: 800;
    line-height: 1.25;
    color: var(--brown);
  }

  .preview-price{
    margin: 0 0 26px;
    font-size: 18px;
    font-weight: 700;
    color: var(--brown-2);
  }

  .preview-btn{
    border:none;
    background: var(--brown);
    color:#fff;
    padding: 14px 30px;
    border-radius: 999px;
    font-size: 16px;
    font-weight: 700;
    cursor:pointer;
    transition:.18s;
  }

  .preview-btn:hover{
    transform: translateY(-1px);
  }

  .field-error-msg{
    color: #8a2b2b;
    font-size: 12px;
    margin-top: 5px;
    display: none;
  }

  input.field-invalid{
    border-color: #c0392b !important;
    background: #fff8f8 !important;
  }

  @media (max-width: 1100px){
    .grid{ grid-template-columns: 1fr; }
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
            <a class="active" href="add_product.php">Add Product <span>+</span></a>
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
          <h1>Add Product</h1>
          <small>Create a new item (beans, capsules, tools) for Brew & Bean</small>
        </div>

        <div class="actions">
          <a class="btn" href="admin_dashboard.php">Back to Dashboard</a>
        </div>
      </div>

      <?php if (!empty($message)) { ?>
        <div class="message <?php echo (strpos($message, 'successfully') !== false) ? 'success' : 'error'; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php } ?>

      <section class="grid">

        <div class="panel">
          <h2>Product Details</h2>

          <form class="form" action="add_product.php" method="post" enctype="multipart/form-data">
            <div>
              <label>Product Name</label>
              <input type="text" id="inp-name" name="product_name" placeholder="e.g., Colombia Beans 250g">
              <span class="field-error-msg" id="err-name">Product name is required.</span>
            </div>

            <div class="row2">
              <div>
                <label>Category</label>
                <select name="category_id" required>
                  <option value="">Select</option>
                  <?php while ($cat = mysqli_fetch_assoc($categories_result)) { ?>
                    <option value="<?php echo $cat['category_id']; ?>">
                      <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div>
                <label>Brewing Method</label>
                <select name="brewing_method" required>
                  <option value="">Select</option>
                  <option value="Filter">Filter</option>
                  <option value="Espresso">Espresso</option>
                  <option value="Filter & Espresso">Filter & Espresso</option>
                </select>
              </div>
            </div>

            <div class="row2">
              <div>
                <label>Price (SAR)</label>
                <input type="number" step="0.01" id="inp-price" name="price" placeholder="e.g., 65">
                <span class="field-error-msg" id="err-price">Please enter a valid price greater than 0.</span>
              </div>
              <div>
                <label>Stock</label>
                <input type="number" id="inp-stock" name="stock" placeholder="e.g., 20">
                <span class="field-error-msg" id="err-stock">Stock must be 0 or more.</span>
              </div>
            </div>

            <div>
              <label>Description</label>
              <textarea name="description" placeholder="Write product description..."></textarea>
            </div>

            <div>
              <label>Image</label>
              <input type="file" name="image" accept="image/*">
              <div class="helper">Tip: Use JPG/PNG and a clear square image if possible.</div>
            </div>

            <div class="form-actions">
              <button class="btn primary" type="submit" name="add_product">Save Product</button>
              <a class="btn" href="admin_dashboard.php">Cancel</a>
            </div>
          </form>
        </div>

        <div class="panel preview-panel">
          <h2>Product Preview</h2>

          <div class="preview-card">
            <div class="preview-image-box">
             <img src="images/no-product.jpeg" alt="Product Preview">
            </div>

            <h3 class="preview-name">New Product Preview</h3>
<p class="preview-price">Price will appear after saving</p>

            <button class="preview-btn" type="button">Add to Cart</button>
          </div>
        </div>

      </section>

    </main>
  </div>

  <!-- ===== Submit Validation ===== -->
  <script>
    document.querySelector('.form').addEventListener('submit', function(e) {
      let valid = true;

      const nameInp  = document.getElementById('inp-name');
      const priceInp = document.getElementById('inp-price');
      const stockInp = document.getElementById('inp-stock');

      // Name
      if (nameInp.value.trim() === '') {
        nameInp.classList.add('field-invalid');
        document.getElementById('err-name').style.display = 'block';
        valid = false;
      } else {
        nameInp.classList.remove('field-invalid');
        document.getElementById('err-name').style.display = 'none';
      }

      // Price
      const pv = parseFloat(priceInp.value);
      if (isNaN(pv) || pv <= 0) {
        priceInp.classList.add('field-invalid');
        document.getElementById('err-price').style.display = 'block';
        valid = false;
      } else {
        priceInp.classList.remove('field-invalid');
        document.getElementById('err-price').style.display = 'none';
      }

      // Stock
      const sv = parseInt(stockInp.value);
      if (isNaN(sv) || sv < 0) {
        stockInp.classList.add('field-invalid');
        document.getElementById('err-stock').style.display = 'block';
        valid = false;
      } else {
        stockInp.classList.remove('field-invalid');
        document.getElementById('err-stock').style.display = 'none';
      }

      if (!valid) e.preventDefault();
    });

    // Clear errors on input
    document.getElementById('inp-name').addEventListener('input', function() {
      if (this.value.trim()) { this.classList.remove('field-invalid'); document.getElementById('err-name').style.display = 'none'; }
    });
    document.getElementById('inp-price').addEventListener('input', function() {
      if (!isNaN(parseFloat(this.value)) && parseFloat(this.value) > 0) { this.classList.remove('field-invalid'); document.getElementById('err-price').style.display = 'none'; }
    });
    document.getElementById('inp-stock').addEventListener('input', function() {
      if (!isNaN(parseInt(this.value)) && parseInt(this.value) >= 0) { this.classList.remove('field-invalid'); document.getElementById('err-stock').style.display = 'none'; }
    });
  </script>

  <!-- ===== Live Preview Script ===== -->
  <script>
    // Elements - form inputs
    const nameInput    = document.querySelector('input[name="product_name"]');
    const priceInput   = document.querySelector('input[name="price"]');
    const imageInput   = document.querySelector('input[name="image"]');

    // Elements - preview card
    const previewImg   = document.querySelector('.preview-image-box img');
    const previewName  = document.querySelector('.preview-name');
    const previewPrice = document.querySelector('.preview-price');

    const defaultImg  = previewImg.src;  // keep original placeholder

    // Live name update
    nameInput.addEventListener('input', function () {
      previewName.textContent = this.value.trim() !== '' ? this.value : 'New Product Preview';
    });

    // Live price update
    priceInput.addEventListener('input', function () {
      previewPrice.textContent = this.value.trim() !== ''
        ? parseFloat(this.value).toFixed(2) + ' SAR'
        : 'Price will appear after saving';
    });

    // Live image preview
    imageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => { previewImg.src = e.target.result; };
        reader.readAsDataURL(file);
      } else {
        previewImg.src = defaultImg;
      }
    });
  </script>
<script src="accessibility.js"></script>
</body>
</html>
