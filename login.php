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

        /* Reject if this email belongs to admin */
        $adminCheck = mysqli_query($conn,
            "SELECT admin_id FROM admin WHERE email='$safe_email' LIMIT 1"
        );
        if ($adminCheck && mysqli_num_rows($adminCheck) > 0) {
            $error = "This is an admin account. Please use the <a href='loginAdmin.php' style='color:#c0392b;font-weight:700;'>Admin Login</a> page.";
        } else {

            $userResult = mysqli_query($conn,
                "SELECT * FROM customers WHERE email='$safe_email' LIMIT 1"
            );

            if ($userResult && mysqli_num_rows($userResult) > 0) {

                $user = mysqli_fetch_assoc($userResult);

                if ($password == $user['password']) {

                    $_SESSION["customer_id"]    = $user["customer_id"];
                    $_SESSION["full_name"]      = $user["full_name"];
                    $_SESSION["role"]           = "user";
                    $_SESSION["email"]          = $email;
                    $_SESSION["just_logged_in"] = true;
                    unset($_SESSION["banner_dismissed"]);

                    header("Location: code.php");
                    exit;

                } else {

                    $error = "Incorrect password.";

                }

            } else {

                $error = "Account not found. Don't have one? <a href='register.php' style='color:#c0392b;font-weight:700;'>Create account</a>";

            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In - Brew & Bean</title>
<style>
:root{
  --brown:#4A2C1D;
  --brown-2:#6A3F28;
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

/* ── LEFT HERO ── */
.hero{
  flex:1;
  background:linear-gradient(160deg, #2E1A0E 0%, var(--brown) 50%, var(--brown-2) 100%);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  padding:60px 48px;
  position:relative;
  overflow:hidden;
}
.hero::before{
  content:'';
  position:absolute;inset:0;
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
.hero h1{font-size:34px;font-weight:800;margin-bottom:10px;letter-spacing:-0.5px;}
.hero p{font-size:15px;opacity:.8;line-height:1.7;max-width:320px;}
.hero-badges{
  display:flex;flex-direction:column;gap:12px;
  margin-top:40px;width:100%;max-width:320px;
}
.hero-badge{
  background:rgba(255,255,255,0.1);
  border:1px solid rgba(255,255,255,0.2);
  border-radius:14px;padding:14px 18px;
  display:flex;align-items:center;gap:12px;
  color:#fff;font-size:13px;
  backdrop-filter:blur(4px);
}
.hero-badge span:first-child{font-size:20px;flex-shrink:0;}
.hero-badge div b{display:block;font-weight:700;margin-bottom:2px;}
.hero-badge div small{opacity:.7;font-size:12px;}

/* ── RIGHT FORM PANEL ── */
.form-side{
  width:480px;
  flex-shrink:0;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:40px 48px;
  background:#fff;
}
.form-wrap{width:100%;max-width:360px;}

.form-header{margin-bottom:32px;}
.form-header h2{
  font-size:26px;font-weight:800;
  color:var(--brown);margin-bottom:6px;
}
.form-header p{font-size:14px;color:var(--muted);line-height:1.5;}

/* ── ERROR ── */
.error-box{
  background:#FFF0F0;border:1px solid #f5a0a0;
  border-radius:12px;padding:12px 16px;
  display:flex;align-items:center;gap:10px;
  font-size:13px;color:#c0392b;
  margin-bottom:20px;
  animation:shake .3s;
}
@keyframes shake{
  0%,100%{transform:translateX(0)}
  25%{transform:translateX(-6px)}
  75%{transform:translateX(6px)}
}

/* ── FIELDS ── */
.field{margin-bottom:18px;}
.field label{
  display:block;
  font-size:13px;font-weight:700;
  color:var(--text);margin-bottom:7px;
}
.input-wrap{position:relative;}
.input-ico{
  position:absolute;left:14px;top:50%;
  transform:translateY(-50%);
  font-size:15px;pointer-events:none;
}
.field input{
  width:100%;
  padding:13px 14px 13px 42px;
  border-radius:12px;
  border:1.5px solid var(--line);
  background:#fafafa;
  font-size:14px;color:var(--text);
  outline:none;transition:.2s;
}
.field input:focus{
  border-color:var(--brown);
  background:#fff;
  box-shadow:0 0 0 3px rgba(74,44,29,0.1);
}
.field-row{
  display:flex;justify-content:space-between;
  align-items:center;margin-bottom:7px;
}
.field-row label{margin:0;}
.forgot{font-size:12px;color:var(--brown-2);text-decoration:none;font-weight:600;}
.forgot:hover{text-decoration:underline;}

/* password toggle */
.toggle-pw{
  position:absolute;right:14px;top:50%;
  transform:translateY(-50%);
  background:none;border:none;
  color:var(--muted);font-size:14px;
  cursor:pointer;padding:0;
  display:flex;align-items:center;
}

/* ── SUBMIT ── */
.btn-login{
  width:100%;padding:14px;
  border-radius:999px;border:none;
  background:var(--brown);color:#fff;
  font-weight:800;font-size:15px;
  cursor:pointer;transition:.2s;
  margin-top:4px;
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-login:hover{background:var(--brown-2);transform:translateY(-1px);}
.btn-login:disabled{opacity:.6;cursor:not-allowed;transform:none;}

/* ── DIVIDER ── */
.divider{
  display:flex;align-items:center;gap:12px;
  margin:22px 0;color:var(--muted);font-size:12px;
}
.divider::before,.divider::after{
  content:'';flex:1;
  height:1px;background:var(--line);
}

/* ── SOCIAL BUTTONS ── */
.social-btns{display:flex;gap:12px;}
.btn-social{
  flex:1;
  padding:11px 14px;
  border-radius:12px;
  border:1.5px solid var(--line);
  background:#fff;
  display:flex;align-items:center;justify-content:center;gap:8px;
  font-size:13px;font-weight:700;color:var(--text);
  cursor:pointer;transition:.18s;
}
.btn-social:hover{background:var(--bg);border-color:var(--brown);}
.btn-apple{background:#000;color:#fff;border-color:#000;}
.btn-apple:hover{background:#222;border-color:#222;}

/* Google icon */
.google-icon{width:18px;height:18px;flex-shrink:0;}
/* Apple icon */
.apple-icon{width:16px;height:16px;flex-shrink:0;fill:#fff;}

/* ── FOOTER NOTE ── */
.form-footer{
  text-align:center;
  margin-top:24px;
  font-size:13px;color:var(--muted);
}
.form-footer a{
  color:var(--brown);font-weight:700;
  text-decoration:none;
}
.form-footer a:hover{text-decoration:underline;}


/* ── RESPONSIVE ── */
@media(max-width:860px){
  .hero{display:none;}
  .form-side{width:100%;padding:40px 28px;}
}
@media(max-width:420px){
  .form-side{padding:30px 20px;}
  .social-btns{flex-direction:column;}
}
</style>
</head>
<body>

<!-- LEFT HERO -->
<div class="hero">
  <div class="hero-content">
    <div class="hero-logo">
      <img src="images/Brew&Bean3.jpg" alt="Brew & Bean">
    </div>
    <h1>Brew &amp; Bean</h1>
    <p>Your destination for specialty coffee beans and professional brewing tools.</p>

    <div class="hero-badges">
      <div class="hero-badge">
        <span>&#9749;</span>
        <div>
          <b>Premium Coffee</b>
          <small>Specialty beans from around the world</small>
        </div>
      </div>
      <div class="hero-badge">
        <span>&#128666;</span>
        <div>
          <b>Fast Delivery</b>
          <small>Quick & reliable shipping to your door</small>
        </div>
      </div>
      <div class="hero-badge">
        <span>&#128737;</span>
        <div>
          <b>Secure Shopping</b>
          <small>100% safe and trusted checkout</small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- RIGHT FORM PANEL -->
<div class="form-side">
  <div class="form-wrap">

    <div class="form-header">
      <h2>Welcome back &#9749;</h2>
      <p>Sign in to your account to continue shopping</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <span>&#9888;</span>
      <?php echo $error; ?>
    </div>
    <?php endif; ?>


    <form method="POST" action="login.php" id="loginForm" novalidate>

      <div class="field">
        <label for="email">Email Address</label>
        <div class="input-wrap">
          <span class="input-ico">&#9993;</span>
          <input type="email" id="email" name="email"
                 placeholder="example@email.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 required autocomplete="email">
        </div>
      </div>

      <div class="field">
        <div class="field-row">
          <label for="password">Password</label>
          <a class="forgot" href="forgot_password.php">Forgot password?</a>
        </div>
        <div class="input-wrap">
          <span class="input-ico">&#128274;</span>
          <input type="password"
		   id="password"
		   name="password"
		   placeholder="Enter your password"
		   required
		   autocomplete="current-password">
          <button type="button" class="toggle-pw" onclick="togglePassword()" title="Show/hide password">
            &#128065;
          </button>
        </div>
      </div>

      <button class="btn-login" type="submit" id="loginBtn">
        Sign In &#8594;
      </button>

    </form>

    <div class="divider">or continue with</div>

    <!-- SOCIAL BUTTONS -->
    <div class="social-btns">

      <!-- Google -->
      <button class="btn-social" onclick="socialLogin('Google')">
        <svg class="google-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Google
      </button>

      <!-- Apple -->
      <button class="btn-social btn-apple" onclick="socialLogin('Apple')">
        <svg class="apple-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.7 9.05 7.4c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.32 2.99-2.54 4zm-3.07-17.6c.05 2.02-1.48 3.68-3.42 3.8-.22-1.96 1.57-3.74 3.42-3.8z"/>
        </svg>
        Apple
      </button>

    </div>

    <div class="form-footer">
      Don't have an account?
      <a href="register.php">Create one for free</a>
    </div>

    <div class="form-footer" style="margin-top:10px;">
      <a href="welcome.php">&#8592; Back to role selection</a>
    </div>

  </div>
</div>

<script>
/* ── PASSWORD TOGGLE ── */
function togglePassword() {
  const inp = document.getElementById('password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

/* ── SOCIAL BUTTONS ── */
function socialLogin(provider) {
  const btn = event.currentTarget;
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.style.opacity = '0.7';
  btn.innerHTML = '<span>&#8987;</span> Connecting…';

  // Simulate loading (replace with real OAuth when ready)
  setTimeout(() => {
    alert(provider + ' Sign-In coming soon!\n\nPlease use email & password for now.');
    btn.disabled = false;
    btn.style.opacity = '';
    btn.innerHTML = orig;
  }, 800);
}

/* ── FORM VALIDATION ── */
document.getElementById('loginForm').addEventListener('submit', function(e) {
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();
  const btn      = document.getElementById('loginBtn');

  if (!email || !password) {
    e.preventDefault();
    return;
  }

  // Loading state
  btn.disabled = true;
  btn.innerHTML = '&#8987; Signing in…';
  btn.style.opacity = '0.8';
});

/* ── AUTO-FOCUS ── */
document.getElementById('email').focus();

/* ── ENTER KEY NAVIGATION ── */
document.getElementById('email').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('password').focus();
  }
});
</script>
<script src="accessibility.js"></script>
</body>
</html>
