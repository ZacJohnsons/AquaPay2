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

$thought_id = (int) ($_POST['thought_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($thought_id <= 0 || $comment === '') {
    header("Location: $redirect");
    exit();
}

$stmt = $conn->prepare(
    'INSERT INTO thought_comments (thought_id, user_id, username, comment) VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('isss', $thought_id, $user_id, $username, $comment);
$stmt->execute();
$stmt->close();

notifyThoughtActivity($conn, $thought_id, $user_id, $user_type);

header("Location: $redirect");
exit();
