<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include_once "db_connect.php";

$error   = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    $password  = trim($_POST["password"] ?? "");
    $confirm   = trim($_POST["confirm"] ?? "");

    /* VALIDATION */
    if (!$full_name || !$email || !$password) {
        $error = "Please fill in all required fields.";
    }
    /* EMAIL VALIDATION ) */
    elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[A-Za-z]{2,}$/", $email)) {
        $error = "Please enter a valid email address.";
    }
    /* PASSWORD VALIDATION  */
    elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
        $error = "Password must contain uppercase, lowercase, number, special character, and be at least 8 characters.";
    }
    elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    }
    else {

        $safe_name  = mysqli_real_escape_string($conn, $full_name);
        $safe_email = mysqli_real_escape_string($conn, $email);
        $safe_phone = mysqli_real_escape_string($conn, $phone);
        $safe_pass  = mysqli_real_escape_string($conn, $password);

        // Check email exists in customers OR admin tables
        $check       = mysqli_query($conn, "SELECT customer_id FROM customers WHERE email='$safe_email' LIMIT 1");
        $check_admin = mysqli_query($conn, "SELECT admin_id FROM admin WHERE email='$safe_email' LIMIT 1");

        if (mysqli_num_rows($check) > 0 || mysqli_num_rows($check_admin) > 0) {

            $error = "This email is already registered. Please login instead.";

        } else {

            $insert = mysqli_query($conn,
                "INSERT INTO customers (full_name, email, phone, password)
                 VALUES ('$safe_name','$safe_email','$safe_phone','$safe_pass')"
            );

            if ($insert) {

                $new_id = mysqli_insert_id($conn);

                $_SESSION["role"]        = "user";
                $_SESSION["email"]       = $email;
                $_SESSION["customer_id"] = $new_id;
                $_SESSION["full_name"]   = $full_name;
                unset($_SESSION["just_logged_in"]);
                unset($_SESSION["banner_dismissed"]);
                setcookie('cookie_consent', '', time() - 3600, '/');

                header("Location: code.php");
                exit;

            } else {

                $error = "Registration failed. Please try again.";

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
<title>Create Account - Brew & Bean</title>
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

.hero-steps{
  margin-top:36px;
  display:flex;flex-direction:column;gap:14px;
  width:100%;max-width:300px;
}
.hero-step{
  display:flex;align-items:center;gap:14px;
  color:#fff;font-size:13px;
}
.step-num{
  width:32px;height:32px;border-radius:50%;
  background:rgba(255,255,255,0.2);
  border:1px solid rgba(255,255,255,0.3);
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-size:13px;flex-shrink:0;
}
.step-num.done{background:rgba(255,255,255,0.35);}

/* ── RIGHT FORM ── */
.form-side{
  width:500px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  padding:40px 48px;background:#fff;
  overflow-y:auto;
}
.form-wrap{width:100%;max-width:380px;}

.form-header{margin-bottom:28px;}
.form-header h2{font-size:26px;font-weight:800;color:var(--brown);margin-bottom:6px;}
.form-header p{font-size:14px;color:var(--muted);line-height:1.5;}

/* error / success */
.msg-box{
  border-radius:12px;padding:12px 16px;
  display:flex;align-items:center;gap:10px;
  font-size:13px;margin-bottom:20px;
}
.msg-error{background:#FFF0F0;border:1px solid #f5a0a0;color:#c0392b;}
.msg-success{background:#E8FFF2;border:1px solid #BFEED2;color:#1E6B3A;}

/* fields */
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field{margin-bottom:16px;}
.field label{
  display:block;font-size:13px;font-weight:700;
  color:var(--text);margin-bottom:7px;
}
.req{color:#c0392b;margin-left:2px;}
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
.field input.invalid{border-color:#c0392b;}

.toggle-pw{
  position:absolute;right:14px;top:50%;
  transform:translateY(-50%);
  background:none;border:none;color:var(--muted);
  font-size:14px;cursor:pointer;padding:0;
}

/* password strength */
.strength-bar{
  height:4px;border-radius:2px;
  background:var(--line);margin-top:6px;
  overflow:hidden;
}
.strength-fill{
  height:100%;border-radius:2px;
  width:0%;transition:width .3s,background .3s;
}

/* submit */
.btn-register{
  width:100%;padding:14px;
  border-radius:999px;border:none;
  background:var(--brown);color:#fff;
  font-weight:800;font-size:15px;
  cursor:pointer;transition:.2s;margin-top:4px;
  display:flex;align-items:center;justify-content:center;gap:8px;
}
.btn-register:hover{background:var(--brown-2);transform:translateY(-1px);}
.btn-register:disabled{opacity:.6;cursor:not-allowed;transform:none;}

.divider{
  display:flex;align-items:center;gap:12px;
  margin:20px 0;color:var(--muted);font-size:12px;
}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line);}

.form-footer{
  text-align:center;margin-top:20px;
  font-size:13px;color:var(--muted);
}
.form-footer a{color:var(--brown);font-weight:700;text-decoration:none;}
.form-footer a:hover{text-decoration:underline;}

@media(max-width:860px){
  .hero{display:none;}
  .form-side{width:100%;padding:40px 28px;}
}
@media(max-width:420px){
  .form-side{padding:30px 20px;}
  .row2{grid-template-columns:1fr;}
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
    <p>Join our community of coffee lovers and start shopping today.</p>

    <div class="hero-steps">
      <div class="hero-step">
        <div class="step-num done">1</div>
        <span>Create your free account</span>
      </div>
      <div class="hero-step">
        <div class="step-num">2</div>
        <span>Browse our premium collection</span>
      </div>
      <div class="hero-step">
        <div class="step-num">3</div>
        <span>Fast delivery to your door</span>
      </div>
    </div>
  </div>
</div>

<!-- FORM -->
<div class="form-side">
  <div class="form-wrap">

    <div class="form-header">
      <h2>Create Account &#9749;</h2>
      <p>Sign up to start your coffee journey with Brew &amp; Bean</p>
    </div>

    <?php if ($error): ?>
    <div class="msg-box msg-error">&#9888; <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="regForm" novalidate>

      <div class="field">
        <label>Full Name <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-ico">&#128100;</span>
          <input type="text" name="full_name" id="full_name"
                 placeholder="Your full name"
                 value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                 required>
        </div>
      </div>

      <div class="field">
        <label>Email Address <span class="req">*</span></label>
        <div class="input-wrap">
          <span class="input-ico">&#9993;</span>
          <input type="email" name="email" id="email"
                 placeholder="example@email.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                 required>
        </div>
      </div>

      <div class="field">
        <label>Phone Number</label>
        <div class="input-wrap">
          <span class="input-ico">&#128222;</span>
          <input type="tel" name="phone" id="phone"
                 placeholder="05xxxxxxxx"
                 value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
      </div>

      <div class="row2">
        <div class="field">
          <label>Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-ico">&#128274;</span>
            <input type="password"
			   name="password"
			   id="password"
			   placeholder="Enter your password"
			   pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
			   title="Password must contain uppercase, lowercase, number, special character, and be at least 8 characters."
			   required
			   oninput="checkStrength(this.value)">
            <button type="button" class="toggle-pw" onclick="togglePw('password')">&#128065;</button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        </div>

        <div class="field">
          <label>Confirm Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-ico">&#128275;</span>
            <input type="password" name="confirm" id="confirm"
                   placeholder="Repeat password" required>
            <button type="button" class="toggle-pw" onclick="togglePw('confirm')">&#128065;</button>
          </div>
        </div>
      </div>

      <button class="btn-register" type="submit" id="regBtn">
        Create Account &#8594;
      </button>

    </form>

    <div class="divider">already have an account?</div>

    <div class="form-footer">
      <a href="login.php">&#8592; Back to Sign In</a> &nbsp;|&nbsp; <a href="welcome.php">Role Selection</a>
    </div>

  </div>
</div>

<script>
function togglePw(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

function checkStrength(val) {
  const fill = document.getElementById('strengthFill');
  if (!val) { fill.style.width = '0%'; return; }
  let score = 0;
  if (val.length >= 4)  score++;
  if (val.length >= 8)  score++;
  if (/[0-9]/.test(val)) score++;
  if (/[A-Z]/.test(val)) score++;
  const colors = ['#c0392b','#e67e22','#f1c40f','#2ecc71'];
  const widths  = ['25%','50%','75%','100%'];
  fill.style.width      = widths[score - 1] || '0%';
  fill.style.background = colors[score - 1] || 'transparent';
}

document.getElementById('regForm').addEventListener('submit', function(e) {
  const name  = document.getElementById('full_name').value.trim();
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  const conf  = document.getElementById('confirm').value;
  let ok = true;

  [document.getElementById('full_name'),
   document.getElementById('email'),
   document.getElementById('password'),
   document.getElementById('confirm')].forEach(f => f.classList.remove('invalid'));

  if (!name)  { document.getElementById('full_name').classList.add('invalid'); ok = false; }
  if (!email) { document.getElementById('email').classList.add('invalid');     ok = false; }
  if (pass.length < 4) { document.getElementById('password').classList.add('invalid'); ok = false; }
  if (pass !== conf)   { document.getElementById('confirm').classList.add('invalid');  ok = false; }

  if (!ok) { e.preventDefault(); return; }

  const btn = document.getElementById('regBtn');
  btn.disabled = true;
  btn.innerHTML = '&#8987; Creating account…';
});

document.getElementById('full_name').focus();
</script>
<script src="accessibility.js"></script>

</body>
</html>
