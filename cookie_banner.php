<?php
// If user already accepted cookies, hide banner for 30 days
if (isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'accepted') {
    return;
}
?>

<style>
#cookieBar{
  position:fixed;
  bottom:28px;
  left:50%;
  transform:translateX(-50%) translateY(120px);
  opacity:0;
  transition:transform .4s cubic-bezier(.4,0,.2,1), opacity .4s ease;
  background:#fff;
  border-radius:20px;
  padding:20px 32px;
  display:flex;
  align-items:center;
  gap:16px;
  z-index:9999;
  box-shadow:0 8px 32px rgba(46,36,32,0.18);
  max-width:980px;
  width:min(980px, calc(100% - 48px));
  flex-wrap:wrap;
}

#cookieBar.show{
  transform:translateX(-50%) translateY(0);
  opacity:1;
}

.ck-icon{
  font-size:28px;
  flex-shrink:0;
}

.ck-text{
  flex:1;
  min-width:300px;
  font-size:14px;
  color:#2E2420;
  line-height:1.7;
  margin:0;
}

.ck-btns{
  display:flex;
  gap:10px;
  flex-shrink:0;
}

.ck-decline{
  padding:9px 20px;
  border-radius:999px;
  border:1.5px solid #D5C5B5;
  background:#fff;
  color:#5A4A40;
  font-size:13px;
  font-weight:700;
  cursor:pointer;
  white-space:nowrap;
}

.ck-decline:hover{
  background:#F8F1E7;
  border-color:#4A2C1D;
  color:#4A2C1D;
}

.ck-accept{
  padding:9px 20px;
  border-radius:999px;
  border:none;
  background:#2E1A0E;
  color:#fff;
  font-size:13px;
  font-weight:700;
  cursor:pointer;
  white-space:nowrap;
}

.ck-accept:hover{
  background:#4A2C1D;
}

#ck-toast{
  position:fixed;
  bottom:28px;
  left:50%;
  transform:translateX(-50%) translateY(20px);
  background:#2E2420;
  color:#fff;
  font-size:13px;
  font-family:Arial,sans-serif;
  padding:12px 24px;
  border-radius:999px;
  white-space:nowrap;
  z-index:10000;
  opacity:0;
  transition:opacity .3s ease, transform .3s ease;
  pointer-events:none;
  box-shadow:0 4px 16px rgba(0,0,0,0.3);
}

#ck-toast.show{
  opacity:1;
  transform:translateX(-50%) translateY(0);
}

@media(max-width:480px){
  #cookieBar{
    bottom:16px;
    padding:14px 16px;
    gap:12px;
  }

  .ck-btns{
    width:100%;
    justify-content:flex-end;
  }
}
</style>

<div id="cookieBar">
  <div class="ck-icon">&#127850;</div>

  <p class="ck-text">
    We use cookies to improve your experience on Brew &amp; Bean.
    By continuing, you agree to our use of cookies.
  </p>

  <div class="ck-btns">
    <button class="ck-decline" onclick="cookieChoice('declined')">Decline</button>
    <button class="ck-accept" onclick="cookieChoice('accepted')">Accept All</button>
  </div>
</div>

<div id="ck-toast">
  &#9888; Some features may be limited without cookies.
</div>

<script>
setTimeout(function(){
  document.getElementById('cookieBar').classList.add('show');
}, 800);

function cookieChoice(val){

  if(val === 'accepted'){
    fetch('set_cookie_consent.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'choice=accepted'
    });

    document.cookie = "cookie_consent=accepted; max-age=" + (30*24*60*60) + "; path=/";
    document.getElementById('cookieBar').classList.remove('show');
  }

  if(val === 'declined'){
    document.getElementById('cookieBar').classList.remove('show');

    var t = document.getElementById('ck-toast');
    t.classList.add('show');

    setTimeout(function(){
      t.classList.remove('show');
    }, 6000);
  }
}
</script>
