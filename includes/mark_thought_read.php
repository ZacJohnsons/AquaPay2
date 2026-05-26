<?php
session_start();
include 'db.php';

if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $user_type = 'client';
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $user_type = 'admin';
} else {
    http_response_code(403);
    exit;
}

$query = "UPDATE thoughts_receivers 
          SET is_read = 1, read_at = NOW()
          WHERE receiver_id = ? AND receiver_type = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $user_id, $user_type);
$stmt->execute();

http_response_code(200);
?>