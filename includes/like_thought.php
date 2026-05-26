<?php
session_start();
include 'db.php';

if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $redirect = "../userdashboard.php";
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $redirect = "../Admindashboard.php";
} else {
    header("Location: AdminLogin.php");
    exit();
}

$thought_id = $_POST['thought_id'];
$exists = mysqli_query($conn, "SELECT * FROM thought_likes WHERE thought_id='$thought_id' AND user_id='$user_id'");
if (mysqli_num_rows($exists) == 0) {
    mysqli_query($conn, "INSERT INTO thought_likes (thought_id, user_id) VALUES ('$thought_id', '$user_id')");
}
header("Location: $redirect");
exit();
?>