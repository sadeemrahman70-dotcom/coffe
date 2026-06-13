<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['choice'] ?? '';

    if ($choice === 'accepted') {
        setcookie('cookie_consent', 'accepted', time() + (30 * 24 * 60 * 60), '/');
    }
}
?>