<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

$conn = new mysqli("localhost", "root", "", "brew_bean");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fullName = $_POST['fullName'];
$Email     = $_POST['Email'];
$Subject   = $_POST['Subject'];
$Message   = $_POST['Message'];

$sql = "INSERT INTO contact_messages (fullName, Email, Subject, Message)
        VALUES ('$fullName', '$Email', '$Subject', '$Message')";

if ($conn->query($sql) === TRUE) {
    echo "Saved";
} else {
    echo $conn->error;
}$conn->close();

}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact & Support - Brew & Bean</title>
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

/* ── PAGE HERO ── */
.page-hero{
  background:linear-gradient(135deg, var(--brown) 0%, var(--brown-2) 100%);
  color:#fff;
  text-align:center;
  padding:56px 40px;
}
.page-hero h2{font-size:32px;font-weight:800;margin-bottom:10px;}
.page-hero p{font-size:16px;opacity:.85;}

/* ── MAIN ── */
main{
  flex:1;
  width:90%;
  max-width:1100px;
  margin:40px auto;
}

/* ── CARD ── */
.card{
  background:#fff;
  border:1px solid var(--line);
  border-radius:var(--radius);
  padding:28px;
  box-shadow:0 10px 28px rgba(46,36,32,0.08);
}
.card-title{
  display:flex;align-items:center;gap:10px;
  font-size:18px;font-weight:800;color:var(--brown);
  margin-bottom:22px;padding-bottom:14px;
  border-bottom:1px solid var(--line);
}
.card-title .icon{
  width:36px;height:36px;background:rgba(74,44,29,0.1);
  border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;
}

/* ── TOP GRID (info + form) ── */
.top-grid{
  display:grid;
  grid-template-columns:1fr 1.4fr;
  gap:20px;
  margin-bottom:20px;
  align-items:start;
}

/* ── CONTACT INFO ── */
.info-list{display:flex;flex-direction:column;gap:12px;}
.info-item{
  display:flex;align-items:flex-start;gap:12px;
  background:var(--panel);
  border:1px solid var(--line);
  border-radius:12px;
  padding:14px 16px;
}
.info-icon{
  width:38px;height:38px;flex-shrink:0;
  background:rgba(74,44,29,0.1);
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;
}
.info-text strong{
  display:block;font-size:12px;color:var(--muted);
  font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;
}
.info-text span{font-size:14px;font-weight:600;color:var(--text);}

/* ── FORM ── */
.form-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
.form-group label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
.form-group input,
.form-group select,
.form-group textarea{
  padding:10px 14px;
  border-radius:10px;
  border:1.5px solid var(--line);
  background:#fff;
  font-size:14px;
  color:var(--text);
  outline:none;
  transition:.18s;
  font-family:inherit;
  resize:vertical;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{border-color:var(--brown-2);}

.submit-btn{
  width:100%;padding:13px;
  border-radius:999px;border:none;
  background:var(--brown);color:#fff;
  font-weight:800;font-size:15px;
  cursor:pointer;transition:.18s;
  margin-top:4px;
}
.submit-btn:hover{background:var(--brown-2);}

.success-msg{
  display:none;
  background:#E8FFF2;border:1px solid #BFEED2;
  color:#1E6B3A;font-weight:700;font-size:13px;
  padding:12px 16px;border-radius:10px;
  margin-top:12px;text-align:center;
}
.success-msg.show{display:block;}

/* ── FAQ ── */
.faq-list{display:flex;flex-direction:column;gap:10px;}

.faq-item{
  border:1.5px solid var(--line);
  border-radius:14px;
  overflow:hidden;
  transition:.18s;
}
.faq-item.open{border-color:rgba(74,44,29,0.3);}

.faq-question{
  width:100%;
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 18px;
  background:#fff;
  border:none;cursor:pointer;
  font-size:14px;font-weight:700;
  color:var(--brown);
  text-align:left;
  transition:.18s;
}
.faq-question:hover{background:var(--panel);}
.faq-item.open .faq-question{background:rgba(74,44,29,0.06);}

.faq-arrow{
  font-size:12px;color:var(--muted);
  transition:transform .22s;flex-shrink:0;
}
.faq-item.open .faq-arrow{transform:rotate(180deg);}

.faq-answer{
  display:none;
  padding:0 18px 16px;
  background:#fff;
}
.faq-answer ul{
  padding-left:18px;
  display:flex;flex-direction:column;gap:6px;
  margin-top:4px;
}
.faq-answer li{font-size:13px;color:var(--muted);line-height:1.6;}

/* ── FOOTER ── */
footer{text-align:center;padding:18px;background:#fff;border-top:1px solid var(--line);color:var(--muted);font-size:13px;margin-top:auto;}

@media(max-width:860px){
  .top-grid{grid-template-columns:1fr;}
  .form-grid{grid-template-columns:1fr;}
  .form-group.full{grid-column:1;}
}
@media(max-width:580px){
  header{padding:14px 18px;}
  nav a:not(.nav-cart){display:none;}
  .page-hero{padding:36px 20px;}
  .page-hero h2{font-size:24px;}
  main{width:95%;}
}
</style>
</head>
<body>

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
    <a href="contact-page.php" class="active">Contact</a>
    <a href="ShoppingCart.php" class="nav-cart">&#128722; Cart</a>
          <a href="login.php" class="nav-logout-right" data-en="Logout" data-ar="log out">Logout</a>

  </nav>
</header>

<!-- PAGE HERO -->
<div class="page-hero">
  <h2>Contact &amp; Support</h2>
  <p>We're here to help you brew the perfect cup &#9749;</p>
</div>

<!-- MAIN -->
<main>

  <div class="top-grid">

    <!-- Contact Info -->
    <div class="card">
      <div class="card-title">
        <div class="icon">&#128222;</div>
        Get in Touch
      </div>
      <div class="info-list">
        <div class="info-item">
          <div class="info-icon">&#9993;</div>
          <div class="info-text">
            <strong>Email</strong>
            <span>support@brewandbean.com</span>
          </div>
        </div>
        <div class="info-item">
          <div class="info-icon">&#128241;</div>
          <div class="info-text">
            <strong>Phone</strong>
            <span>+966 5XXXXXXXX</span>
          </div>
        </div>
        <div class="info-item">
          <div class="info-icon">&#128205;</div>
          <div class="info-text">
            <strong>Location</strong>
            <span>Dammam, Saudi Arabia</span>
          </div>
        </div>
        <div class="info-item">
          <div class="info-icon">&#128336;</div>
          <div class="info-text">
            <strong>Working Hours</strong>
            <span>Sun – Thu &nbsp;9AM – 6PM</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Form -->
    <div class="card">
      <div class="card-title">
        <div class="icon">&#128140;</div>
        Send Us a Message
      </div>
<form method="POST">
          <div class="form-grid">
          <div class="form-group">
            <label>Full Name</label>
<input type="text" name="fullName" placeholder="Your name" required>
          </div>
          <div class="form-group">
            <label>Email Address</label>
<input type="email" name="Email" placeholder="your@email.com" required>
          </div>
          <div class="form-group full">
            <label>Subject</label>
<select name="Subject" required>
              <option value="">Select a subject…</option>
              <option>Order Issue</option>
              <option>Product Question</option>
              <option>Brewing Help</option>
              <option>Technical Problem</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group full">
            <label>Message</label>
<textarea name="Message" rows="5" placeholder="Write your message here…" required></textarea>  
          </div>
        </div>
        <button class="submit-btn" type="submit">Send Message &#8594;</button>
        <div class="success-msg" id="successMsg">&#10003; Your message has been sent successfully!</div>
      </form>
    </div>

  </div>

  <!-- FAQ -->
  <div class="card">
    <div class="card-title">
      <div class="icon">&#10067;</div>
      Frequently Asked Questions
    </div>
    <div class="faq-list">

      <div class="faq-item">
        <button class="faq-question">
          &#128203;&nbsp; Orders
          <span class="faq-arrow">&#9660;</span>
        </button>
        <div class="faq-answer">
          <ul>
            <li>Can I modify my order? — Yes, before it is processed for shipping.</li>
            <li>Can I cancel my order? — Yes, as long as it hasn't shipped yet.</li>
            <li>How do I track my order? — Check the My Orders page after logging in.</li>
          </ul>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question">
          &#127807;&nbsp; Products
          <span class="faq-arrow">&#9660;</span>
        </button>
        <div class="faq-answer">
          <ul>
            <li>Are the beans freshly roasted? — Yes, every batch is roasted to order.</li>
            <li>Do you sell brewing tools? — Yes, for all methods: V60, Chemex, Espresso and more.</li>
            <li>What brewing methods do you support? — Filter Coffee, Espresso, Cold Brew and more.</li>
          </ul>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question">
          &#128666;&nbsp; Shipping &amp; Delivery
          <span class="faq-arrow">&#9660;</span>
        </button>
        <div class="faq-answer">
          <ul>
            <li>How long does delivery take? — 2–5 business days within Saudi Arabia.</li>
            <li>Is there free shipping? — Yes, on all orders.</li>
            <li>Do you ship internationally? — Currently within Saudi Arabia only.</li>
          </ul>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question">
          &#128260;&nbsp; Returns &amp; Refunds
          <span class="faq-arrow">&#9660;</span>
        </button>
        <div class="faq-answer">
          <ul>
            <li>What is the return policy? — 14-day return on unused items.</li>
            <li>How do I request a return? — Contact us via email or phone.</li>
            <li>When do I get my refund? — Within 3–7 business days after approval.</li>
          </ul>
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question">
          &#9749;&nbsp; Brewing Guide
          <span class="faq-arrow">&#9660;</span>
        </button>
        <div class="faq-answer">
          <ul>
            <li>How do I choose the right beans? — Start with your brewing method, then pick a roast level.</li>
            <li>Is it beginner friendly? — Absolutely, our tools come with step-by-step guidance.</li>
            <li>Can I get a personalised recommendation? — Use the Recommend feature on the Products page.</li>
          </ul>
        </div>
      </div>

    </div>
  </div>

</main>

  <!-- Our Location Map -->
  <div class="card" style="margin-top:20px;">
    <div class="card-title">
      <div class="icon">&#128205;</div>
      Our Location
    </div>
    <div style="border-radius:12px;overflow:hidden;border:1px solid var(--line);">
      <iframe
        src="https://www.google.com/maps?q=Dammam,Saudi+Arabia&output=embed"
        width="100%"
        height="360"
        style="display:block;border:none;"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </div>

</main>

<!-- FOOTER -->
<footer>&copy; 2026 Brew &amp; Bean &mdash; Premium Coffee</footer>

<script>
function sendMessage(){
  document.getElementById('successMsg').classList.add('show');
  return false;
}

document.querySelectorAll('.faq-question').forEach(btn => {
  btn.addEventListener('click', () => {
    const item   = btn.parentElement;
    const answer = btn.nextElementSibling;
    const isOpen = item.classList.contains('open');

    document.querySelectorAll('.faq-item').forEach(i => {
      i.classList.remove('open');
      i.querySelector('.faq-answer').style.display = 'none';
    });

    if(!isOpen){
      item.classList.add('open');
      answer.style.display = 'block';
    }
  });
});
</script>
<script src="accessibility.js"></script>
</body>
</html>
