<?php
session_start();
include 'db.php';

if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $username = $_SESSION['loggedInUser'];
    $redirect = "../userdashboard.php#faq";
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $username = $_SESSION['loggedInUser'];
    $redirect = "../Admindashboard.php#faq";
} else {
    // Not logged in
    header("Location: AdminLogin.php");
    exit();
}

$thought_id = $_POST['thought_id'];
$comment = $_POST['comment'];
mysqli_query($conn, "INSERT INTO thought_comments (thought_id, user_id, username, comment) VALUES ('$thought_id', '$user_id', '$username', '".mysqli_real_escape_string($conn, $comment)."')");
header("Location: $redirect");
exit();
?>