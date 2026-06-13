<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['customer_id']) && !empty($_SESSION['just_logged_in']) && empty($_SESSION['banner_dismissed'])):
    $banner_name = htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'there');
?>
<div id="welcome-banner">
  <span>&#9749; Welcome back, <strong><?php echo $banner_name; ?></strong>! Ready to brew something great?</span>
  <button onclick="dismissBanner()" title="Close">&times;</button>
</div>
<style>
#welcome-banner{
  width:100%;
  background:linear-gradient(90deg,#4A2C1D,#6A3F28);
  color:#fff;
  padding:10px 20px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:12px;
  font-size:14px;
  font-family:Arial,sans-serif;
  position:relative;
  z-index:999;
  box-shadow:0 2px 8px rgba(0,0,0,0.2);
}
#welcome-banner strong{
  font-weight:700;
  text-decoration:underline;
  text-underline-offset:2px;
}
#welcome-banner button{
  position:absolute;
  right:16px;
  top:50%;
  transform:translateY(-50%);
  background:none;
  border:none;
  color:#fff;
  font-size:18px;
  cursor:pointer;
  opacity:.7;
  line-height:1;
  padding:0 4px;
}
#welcome-banner button:hover{opacity:1;}
</style>
<script>
function dismissBanner(){
  fetch('dismiss_banner.php').finally(function(){
    var b = document.getElementById('welcome-banner');
    if(b) b.style.display = 'none';
  });
}
</script>
<?php endif; ?>
