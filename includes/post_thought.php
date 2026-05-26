<?php
session_start();
include 'db.php';
require_once 'thought_helpers.php';

if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $username = $_SESSION['loggedInUser'];
    $user_type = 'client';
    $redirect = '../userdashboard.php#faq';
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $username = $_SESSION['loggedInUser'];
    $user_type = 'admin';
    $redirect = '../Admindashboard.php#faq';
} else {
    header('Location: AdminLogin.php');
    exit();
}

$content = trim($_POST['content'] ?? '');
if ($content === '') {
    header("Location: $redirect");
    exit();
}

$stmt = $conn->prepare(
    'INSERT INTO thoughts (sender_id, sender_type, username, content, created_at) VALUES (?, ?, ?, ?, NOW())'
);
$stmt->bind_param('ssss', $user_id, $user_type, $username, $content);
$stmt->execute();
$thought_id = (int) $conn->insert_id;
$stmt->close();

distributeThoughtToReceivers($conn, $thought_id, $user_id, $user_type);

header("Location: $redirect");
exit();
