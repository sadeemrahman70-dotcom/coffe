<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['banner_dismissed'] = true;
unset($_SESSION['just_logged_in']);
http_response_code(200);
