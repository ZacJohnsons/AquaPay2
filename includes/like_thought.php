<?php
session_start();
include 'db.php';

if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $user_type = 'client';
    $redirect = '../userdashboard.php#faq';
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $user_type = 'admin';
    $redirect = '../Admindashboard.php#faq';
} else {
    header('Location: AdminLogin.php');
    exit();
}

$thought_id = (int) ($_POST['thought_id'] ?? 0);
if ($thought_id <= 0) {
    header("Location: $redirect");
    exit();
}

$like_key = $user_type . ':' . $user_id;

$check = $conn->prepare('SELECT 1 FROM thought_likes WHERE thought_id = ? AND user_id = ? LIMIT 1');
$check->bind_param('is', $thought_id, $like_key);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if (!$exists) {
    $insert = $conn->prepare('INSERT INTO thought_likes (thought_id, user_id) VALUES (?, ?)');
    $insert->bind_param('is', $thought_id, $like_key);
    $insert->execute();
    $insert->close();
}

header("Location: $redirect");
exit();
