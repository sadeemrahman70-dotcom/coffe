<?php
session_start();
include_once "db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[A-Za-z]{2,}$/", $email)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } else {

        $safe_email = mysqli_real_escape_string($conn, $email);

        $result = mysqli_query($conn,
            "SELECT * FROM admin WHERE email='$safe_email' LIMIT 1"
        );

        if ($result && mysqli_num_rows($result) > 0) {

            $admin = mysqli_fetch_assoc($result);

            if ($password === $admin['password']) {

                $_SESSION["role"]      = "admin";
                $_SESSION["email"]     = $email;
                $_SESSION["full_name"] = $admin["full_name"] ?? "Admin";

                header("Location: admin_dashboard.php");
                exit;

            } else {
                $error = "Incorrect password.";
            }

        } else {
            $error = "No admin account found with this email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign In - Brew & Bean</title>
<style>
:root{
  --brown:#4A2C1D;
  --brown-2:#6A3F28;
  --gold:#C8A96E;
  --bg:#EFE6DA;
  --panel:#F8F1E7;
  --line:#E2D2BE;
  --text:#2E2420;
  --muted:#6B5A50;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Arial,sans-serif;
  min-height:100vh;
  display:flex;
  background:var(--bg);
}

/* LEFT HERO */
.hero{
  flex:1;
  background:linear-gradient(160deg,#2E1A0E 0%,var(--brown) 50%,var(--brown-2) 100%);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  padding:60px 48px;
  position:relative;overflow:hidden;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:url('images/BEAN.jpg') center/cover no-repeat;
  opacity:.18;
}
.hero-content{position:relative;z-index:1;text-align:center;color:#fff;}
.hero-logo{
  width:90px;height:90px;border-radius:50%;
  overflow:hidden;border:3px solid rgba(255,255,255,0.4);
  background:#fff;margin:0 auto 22px;
  box-shadow:0 8px 28px rgba(0,0,0,0.3);
}
.hero-logo img{width:100%;height:100%;object-fit:cover;display:block;}
.hero h1{font-size:34px;font-weight:800;margin-bottom:10px;}
.hero p{font-size:15px;opacity:.8;line-height:1.7;max-width:300px;}

.admin-badge{
  margin-top:36px;
  background:rgba(200,169,110,0.15);
  border:1.5px solid rgba(200,169,110,0.4);
  border-radius:14px;
  padding:18px 24px;
  display:flex;flex-direction:column;gap:10px;
  width:100%;max-width:300px;
}
.admin-badge-item{
  display:flex;align-items:center;gap:12px;
  color:#fff;font-size:13px;
}
.admin-badge-item span:first-child{font-size:18px;flex-shrink:0;}

/* RIGHT FORM */
.form-side{
  width:480px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  padding:40px 48px;background:#fff;
}
.form-wrap{width:100%;max-width:360px;}

.form-header{margin-bottom:28px;}
.form-header h2{font-size:26px;font-weight:800;color:var(--brown);margin-bottom:6px;}
.form-header p{font-size:14px;color:var(--muted);line-height:1.5;}

.admin-tag{
  display:inline-flex;align-items:center;gap:6px;
  background:#FFF3E0;border:1px solid #FFCC80;
  color:#8A4500;border-radius:999px;
  padding:4px 12px;font-size:12px;font-weight:700;
  margin-bottom:16px;
}

.error-box{
  background:#FFF0F0;border:1px solid #f5a0a0;
  border-radius:12px;padding:12px 16px;
  display:flex;align-items:center;gap:10px;
  font-size:13px;color:#c0392b;margin-bottom:20px;
  animation:shake .3s;
}
@keyframes shake{
  0%,100%{transform:translateX(0)}
  25%{transform:translateX(-6px)}
  75%{transform:translateX(6px)}
}

.field{margin-bottom:18px;}
.field label{display:block;font-size:13px;font-weight:700;color:var(--text);margin-bottom:7px;}
.input-wrap{position:relative;}
.input-ico{
  position:absolute;left:14px;top:50%;
  transform:translateY(-50%);
  font-size:15px;pointer-events:none;
}
.field input{
  width:100%;padding:13px 14px 13px 42px;
  border-radius:12px;border:1.5px solid var(--line);
  background:#fafafa;font-size:14px;color:var(--text);
  outline:none;transition:.2s;
}
.field input:focus{
  border-color:var(--brown);background:#fff;
  box-shadow:0 0 0 3px rgba(74,44,29,0.1);
}
.toggle-pw{
  position:absolute;right:14px;top:50%;
  transform:translateY(-50%);
  background:none;border:none;color:var(--muted);
  font-size:14px;cursor:pointer;padding:0;
}

.btn-login{
  width:100%;padding:14px;border-radius:999px;border:none;
  background:var(--brown);color:#fff;
  font-weight:800;font-size:15px;
  cursor:pointer;transition:.2s;margin-top:4px;
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-login:hover{background:var(--brown-2);transform:translateY(-1px);}
.btn-login:disabled{opacity:.6;cursor:not-allowed;transform:none;}

.back-link{
  display:flex;align-items:center;justify-content:center;gap:6px;
  margin-top:24px;font-size:13px;color:var(--muted);
  text-decoration:none;
}
.back-link:hover{color:var(--brown);}

@media(max-width:860px){
  .hero{display:none;}
  .form-side{width:100%;padding:40px 28px;}
}
@media(max-width:420px){
  .form-side{padding:30px 20px;}
}
</style>
</head>
<body>

<!-- HERO -->
<div class="hero">
  <div class="hero-content">
    <div class="hero-logo">
      <img src="images/Brew&Bean3.jpg" alt="Brew & Bean">
    </div>
    <h1>Brew &amp; Bean</h1>
    <p>Admin portal for managing your coffee store.</p>

    <div class="admin-badge">
      <div class="admin-badge-item">
        <span>&#128201;</span>
        <span>Manage products &amp; inventory</span>
      </div>
      <div class="admin-badge-item">
        <span>&#128666;</span>
        <span>Track &amp; update orders</span>
      </div>
      <div class="admin-badge-item">
        <span>&#128101;</span>
        <span>View customer accounts</span>
      </div>
    </div>
  </div>
</div>

<!-- FORM -->
<div class="form-side">
  <div class="form-wrap">

    <div class="admin-tag">&#128737; Admin Portal</div>

    <div class="form-header">
      <h2>Admin Sign In</h2>
      <p>Access restricted to authorized administrators only</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <span>&#9888;</span>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="loginAdmin.php" id="adminForm" novalidate>

      <div class="field">
        <label for="email">Admin Email</label>
        <div class="input-wrap">
          <span class="input-ico">&#9993;</span>
          <input type="email" id="email" name="email"
                 placeholder="admin@example.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 required autocomplete="email">
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="input-ico">&#128274;</span>
          <input type="password" id="password" name="password"
                 placeholder="Enter admin password"
                 required autocomplete="current-password">
          <button type="button" class="toggle-pw" onclick="togglePw()">&#128065;</button>
        </div>
      </div>
<a class="forgot" href="forgot_password.php">Forgot password?</a>
      <button class="btn-login" type="submit" id="loginBtn">
        Sign In as Admin &#8594;
      </button>

    </form>

    <a class="back-link" href="welcome.php">&#8592; Back to role selection</a>

  </div>
</div>

<script>
function togglePw() {
  const inp = document.getElementById('password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

document.getElementById('adminForm').addEventListener('submit', function(e) {
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  if (!email || !pass) { e.preventDefault(); return; }
  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '&#8987; Signing in…';
});

document.getElementById('email').focus();
</script>
<script src="accessibility.js"></script>

</body>
</html>
