<?php
session_start();
if (isset($_POST['confirm_logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: welcome.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logout - Brew & Bean</title>
<style>
:root{
  --brown:#4A2C1D;
  --brown-2:#6A3F28;
  --bg:#EFE6DA;
  --line:#E2D2BE;
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:Arial,sans-serif;
  background:var(--bg);
  min-height:100vh;
  display:flex;align-items:center;justify-content:center;
}
.overlay{
  position:fixed;inset:0;
  background:rgba(20,10,5,0.55);
  backdrop-filter:blur(3px);
  display:flex;align-items:center;justify-content:center;
  padding:20px;
}
.modal{
  background:#fff;
  border-radius:22px;
  padding:36px 40px;
  max-width:400px;width:100%;
  text-align:center;
  box-shadow:0 24px 60px rgba(0,0,0,0.2);
  animation:pop .25s cubic-bezier(.4,0,.2,1);
}
@keyframes pop{
  from{transform:scale(.88);opacity:0}
  to{transform:scale(1);opacity:1}
}
.icon{
  width:72px;height:72px;
  background:rgba(74,44,29,0.1);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;
  font-size:30px;
}
h2{
  font-size:22px;font-weight:800;
  color:var(--brown);margin-bottom:10px;
}
p{
  font-size:14px;color:#6B5A50;
  line-height:1.6;margin-bottom:28px;
}
.btns{display:flex;gap:12px;}
.btn-cancel{
  flex:1;padding:13px;
  border-radius:999px;
  border:1.5px solid var(--line);
  background:#fff;color:var(--brown);
  font-weight:700;font-size:14px;
  cursor:pointer;transition:.18s;
  text-decoration:none;
  display:flex;align-items:center;justify-content:center;
}
.btn-cancel:hover{background:var(--bg);}
.btn-logout{
  flex:1;padding:13px;
  border-radius:999px;
  border:none;
  background:var(--brown);color:#fff;
  font-weight:700;font-size:14px;
  cursor:pointer;transition:.18s;
}
.btn-logout:hover{background:var(--brown-2);}
</style>
</head>
<body>

<div class="overlay">
  <div class="modal">
    <div class="icon">&#128682;</div>
    <h2>Logging Out</h2>
    <p>Are you sure you want to logout from your account?</p>
    <div class="btns">
      <a class="btn-cancel" href="javascript:history.back()">Cancel</a>
      <form method="POST" style="flex:1">
        <button class="btn-logout" type="submit" name="confirm_logout" style="width:100%">Yes, Logout</button>
      </form>
    </div>
  </div>
</div>

<script>
// Auto-focus the cancel button for keyboard accessibility
document.querySelector('.btn-cancel').focus();
// Allow Escape key to go back
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') history.back();
});
</script>
<script src="accessibility.js"></script>
</body>
</html>
