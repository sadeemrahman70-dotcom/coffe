<?php
include_once "db_connect.php";

$msg = "";
$msgType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email   = trim($_POST["email"] ?? "");
    $newPass = trim($_POST["new_pass"] ?? "");
    $confirm = trim($_POST["confirm"] ?? "");

    if (!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[A-Za-z]{2,}$/", $email)) {

        $msg = "Please enter a valid email address.";
        $msgType = "error";

    } elseif (strlen($newPass) < 6) {

        $msg = "Password must be at least 6 characters.";
        $msgType = "error";

    } elseif ($newPass !== $confirm) {

        $msg = "Passwords do not match.";
        $msgType = "error";

    } else {

        $safe_email = mysqli_real_escape_string($conn, $email);
        $safe_pass  = mysqli_real_escape_string($conn, $newPass);

        // CUSTOMER
        $userRes = mysqli_query($conn,
            "SELECT customer_id FROM customers WHERE email='$safe_email' LIMIT 1"
        );

        if ($userRes && mysqli_num_rows($userRes) > 0) {

            mysqli_query($conn,
                "UPDATE customers SET password='$safe_pass' WHERE email='$safe_email'"
            );

            $msg = "Password updated successfully! <a href='login.php'>Sign in</a>";
            $msgType = "success";

        } else {

            // ADMIN
            $adminRes = mysqli_query($conn,
                "SELECT admin_id FROM admin WHERE email='$safe_email' LIMIT 1"
            );

            if ($adminRes && mysqli_num_rows($adminRes) > 0) {

                mysqli_query($conn,
                    "UPDATE admin SET password='$safe_pass' WHERE email='$safe_email'"
                );

                $msg = "Admin password updated! <a href='loginAdmin.php'>Sign in</a>";
                $msgType = "success";

            } else {

                $msg = "No account found with this email.";
                $msgType = "error";
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

<title>Forgot Password</title>

<style>

:root{
  --brown:#4A2C1D;
  --brown2:#6A3F28;
  --bg:#EFE6DA;
  --line:#E2D2BE;
}

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

body{
  font-family:Arial,sans-serif;
  min-height:100vh;
  display:flex;
  background:var(--bg);
}

/* LEFT SIDE */

.hero{
  flex:1;
  background:linear-gradient(160deg,#2E1A0E 0%,var(--brown) 50%,var(--brown2) 100%);
  display:flex;
  justify-content:center;
  align-items:center;
  color:white;
  text-align:center;
  padding:40px;
}

.hero-content h1{
  font-size:42px;
  margin-bottom:16px;
}

.hero-content p{
  max-width:350px;
  line-height:1.8;
  opacity:.9;
}

/* RIGHT SIDE */

.form-side{
  width:460px;
  background:white;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:40px;
}

.form-wrap{
  width:100%;
  max-width:340px;
}

.form-wrap h2{
  color:var(--brown);
  margin-bottom:10px;
  font-size:30px;
}

.form-wrap p{
  color:#666;
  margin-bottom:25px;
  font-size:14px;
}

.field{
  margin-bottom:18px;
}

.field label{
  display:block;
  margin-bottom:8px;
  font-size:13px;
  font-weight:bold;
}

.field input{
  width:100%;
  padding:14px;
  border-radius:12px;
  border:1.5px solid var(--line);
  font-size:14px;
}

.field input:focus{
  outline:none;
  border-color:var(--brown);
}

/* PASSWORD EYE */

.pass-wrap{
  position:relative;
}

.eye{
  position:absolute;
  right:14px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  font-size:18px;
}

/* BUTTON */

.btn{
  width:100%;
  padding:15px;
  border:none;
  border-radius:999px;
  background:var(--brown);
  color:white;
  font-size:15px;
  font-weight:bold;
  cursor:pointer;
  transition:.2s;
  margin-top:8px;
}

.btn:hover{
  background:var(--brown2);
}

/* MESSAGE */

.message{
  padding:13px;
  border-radius:12px;
  margin-bottom:18px;
  font-size:14px;
}

.success{
  background:#E9F8EC;
  color:#1E6B3A;
}

.error{
  background:#FFECEC;
  color:#C0392B;
}

/* PASSWORD STRENGTH */

.strength{
  width:100%;
  height:8px;
  background:#ddd;
  border-radius:999px;
  margin-top:8px;
  overflow:hidden;
}

.strength-bar{
  height:100%;
  width:0%;
  transition:.3s;
}

/* PASSWORD MATCH */

.match{
  font-size:12px;
  margin-top:7px;
}

/* BACK */

.back{
  display:block;
  text-align:center;
  margin-top:22px;
  color:#666;
  text-decoration:none;
  font-size:13px;
}

.back:hover{
  color:var(--brown);
}

/* MOBILE */

@media(max-width:850px){

  .hero{
    display:none;
  }

  .form-side{
    width:100%;
  }
}

</style>
</head>

<body>

<!-- LEFT -->

<div class="hero">

  <div class="hero-content">

    <h1>Brew & Bean ☕</h1>

    <p>
      Reset your password and continue enjoying your favorite coffee experience.
    </p>

  </div>

</div>

<!-- RIGHT -->

<div class="form-side">

  <div class="form-wrap">

    <h2>Forgot Password</h2>

    <p>Enter your email and create a new password.</p>

    <?php if($msg): ?>

      <div class="message <?php echo $msgType; ?>">

        <?php echo $msg; ?>

      </div>

    <?php endif; ?>

    <form method="POST" action="">

      <!-- EMAIL -->

      <div class="field">

        <label>Email Address</label>

        <input type="email" name="email" required>

      </div>

      <!-- NEW PASSWORD -->

      <div class="field">

        <label>New Password</label>

        <div class="pass-wrap">

          <input type="password"
                 name="new_pass"
                 id="newPass"
                 required>

          <span class="eye" onclick="toggleNewPass()">👁</span>

        </div>

        <div class="strength">

          <div class="strength-bar" id="strengthBar"></div>

        </div>

      </div>

      <!-- CONFIRM PASSWORD -->

      <div class="field">

        <label>Confirm Password</label>

        <div class="pass-wrap">

          <input type="password"
                 name="confirm"
                 id="confirmPass"
                 required>

          <span class="eye" onclick="toggleConfirmPass()">👁</span>

        </div>

        <div class="match" id="matchText"></div>

      </div>

      <button type="submit" class="btn">

        Update Password

      </button>

    </form>

    <a class="back" href="welcome.php">

      ← Back

    </a>

  </div>

</div>

<script>

/* SHOW PASSWORD */

function toggleNewPass(){

  const input = document.getElementById("newPass");

  input.type =
    input.type === "password"
    ? "text"
    : "password";
}

function toggleConfirmPass(){

  const input = document.getElementById("confirmPass");

  input.type =
    input.type === "password"
    ? "text"
    : "password";
}

/* PASSWORD STRENGTH */

const pass = document.getElementById("newPass");
const confirmPass = document.getElementById("confirmPass");

const bar = document.getElementById("strengthBar");
const matchText = document.getElementById("matchText");

pass.addEventListener("input", () => {

    const val = pass.value;

    let strength = 0;

    if(val.length >= 6) strength++;
    if(/[A-Z]/.test(val)) strength++;
    if(/[0-9]/.test(val)) strength++;
    if(/[^A-Za-z0-9]/.test(val)) strength++;

    if(strength == 1){

        bar.style.width = "25%";
        bar.style.background = "red";

    } else if(strength == 2){

        bar.style.width = "50%";
        bar.style.background = "orange";

    } else if(strength == 3){

        bar.style.width = "75%";
        bar.style.background = "#d4b000";

    } else if(strength >= 4){

        bar.style.width = "100%";
        bar.style.background = "green";

    } else {

        bar.style.width = "0%";
    }
});

/* PASSWORD MATCH */

confirmPass.addEventListener("input", () => {

    if(confirmPass.value === pass.value){

        matchText.innerHTML = "Passwords match";
        matchText.style.color = "green";

    } else {

        matchText.innerHTML = "Passwords do not match";
        matchText.style.color = "red";
    }
});

</script>
<script src="accessibility.js"></script>

</body>
</html>