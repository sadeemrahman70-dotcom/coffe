<?php
session_start();
include 'db.php';

// Developed by: Lujain Mansoor Al Darweesh
// Add to cart validation and stock handling

header('Content-Type: application/json');

if (!isset($_POST['name'], $_POST['price'])) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

$name  = mysqli_real_escape_string($conn, $_POST['name']);
$price = (float) $_POST['price'];
$qty   = max(1, (int) ($_POST['quantity'] ?? 1));
$sid   = mysqli_real_escape_string($conn, session_id());

$check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE product_name='$name' AND session_id='$sid'");

if (!$check) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($check) > 0) {
    $sql = "UPDATE cart SET quantity = quantity + $qty WHERE product_name='$name' AND session_id='$sid'";
} else {
    $sql = "INSERT INTO cart (session_id, product_name, price, quantity) VALUES ('$sid', '$name', $price, $qty)";
}

if (!mysqli_query($conn, $sql)) {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

echo json_encode(['success' => true]);
exit;
?>