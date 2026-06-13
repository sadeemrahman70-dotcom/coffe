<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome - Brew & Bean</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Arial,sans-serif;
  min-height:100vh;
  display:flex;
}

/* ── LEFT: background photo ── */
.bg-side{
  flex:1;
  position:relative;
  overflow:hidden;
}
.bg-side img{
  width:100%;height:100%;
  object-fit:cover;
  display:block;
}
.bg-overlay{
  position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(46,26,14,0.55) 0%,rgba(74,44,29,0.35) 100%);
}
/* Brand watermark on photo */
.bg-brand{
  position:absolute;
  bottom:40px;left:48px;
  color:#fff;
  text-shadow:0 2px 8px rgba(0,0,0,0.5);
}
.bg-brand h2{font-size:28px;font-weight:800;letter-spacing:-0.5px;}
.bg-brand p{font-size:14px;opacity:.75;margin-top:4px;}

/* ── RIGHT: white panel ── */
.panel{
  width:420px;
  flex-shrink:0;
  background:#fff;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  padding:56px 48px;
}

/* Logo */
.panel-logo{
  width:80px;height:80px;border-radius:50%;
  overflow:hidden;
  border:3px solid #E2D2BE;
  margin-bottom:14px;
  box-shadow:0 4px 16px rgba(0,0,0,0.1);
}
.panel-logo img{width:100%;height:100%;object-fit:cover;display:block;}

.panel-title{
  font-size:26px;font-weight:800;
  color:#4A2C1D;
  margin-bottom:6px;
  letter-spacing:-0.3px;
}
.panel-sub{
  font-size:13px;color:#6B5A50;
  text-align:center;margin-bottom:36px;
  line-height:1.5;
}

/* Two big login buttons */
.login-btns{
  display:flex;gap:16px;
  width:100%;margin-bottom:28px;
}
.login-card{
  flex:1;
  background:#4A2C1D;
  border-radius:14px;
  padding:28px 14px 22px;
  display:flex;flex-direction:column;
  align-items:center;gap:12px;
  text-decoration:none;
  transition:all .22s ease;
  cursor:pointer;
  border:2px solid transparent;
}
.login-card:hover{
  background:#6A3F28;
  transform:translateY(-3px);
  box-shadow:0 8px 24px rgba(74,44,29,0.3);
}
.login-card-icon{
  width:62px;height:62px;
  border-radius:50%;
  background:rgba(255,255,255,0.15);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;
}
.login-card-label{
  font-size:14px;font-weight:800;
  color:#fff;text-align:center;
  line-height:1.3;
}

/* Extra links */
.extra-links{
  width:100%;display:flex;flex-direction:column;gap:10px;
}
.extra-link{
  width:100%;padding:12px 20px;
  border-radius:10px;
  border:1.5px solid #E2D2BE;
  background:#fff;
  color:#4A2C1D;
  font-size:13px;font-weight:600;
  text-align:center;text-decoration:none;
  transition:all .18s;
}
.extra-link:hover{
  background:#EFE6DA;
  border-color:#4A2C1D;
  color:#4A2C1D;
}

/* Footer inside panel */
.panel-footer{
  margin-top:36px;
  font-size:11px;color:#B0A090;
  text-align:center;
}

/* ── Responsive ── */
@media(max-width:700px){
  .bg-side{display:none;}
  .panel{width:100%;padding:40px 28px;}
}
@media(max-width:360px){
  .login-btns{flex-direction:column;}
}
</style>
</head>
<body>

<!-- LEFT: photo background -->
<div class="bg-side">
  <img src="images/BEAN.jpg" alt="Brew & Bean">
  <div class="bg-overlay"></div>
  <div class="bg-brand">
    <h2>Brew &amp; Bean</h2>
    <p>Premium Coffee &amp; Brewing Tools</p>
  </div>
</div>

<!-- RIGHT: white panel -->
<div class="panel">

  <div class="panel-logo">
    <img src="images/Brew&Bean3.jpg" alt="Brew & Bean">
  </div>

  <h1 class="panel-title">Brew &amp; Bean</h1>
  <p class="panel-sub">Coffee Shop Management System</p>

  <!-- Two big buttons -->
  <div class="login-btns">

    <a class="login-card" href="login.php">
      <div class="login-card-icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
      </div>
      <div class="login-card-label">Customer<br>Login</div>
    </a>

    <a class="login-card" href="loginAdmin.php">
      <div class="login-card-icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2L4 6v6c0 5 3.6 9.7 8 11 4.4-1.3 8-6 8-11V6L12 2z"/>
          <polyline points="9 12 11 14 15 10"/>
        </svg>
      </div>
      <div class="login-card-label">Admin<br>Login</div>
    </a>

  </div>

  <!-- Extra links -->
  <div class="extra-links">
    <a class="extra-link" href="register.php">+ Create New Account</a>
  </div>

  <p class="panel-footer">&copy; 2026 Brew &amp; Bean &mdash; All rights reserved</p>

</div>
<script src="accessibility.js"></script>
</body>
</html>
