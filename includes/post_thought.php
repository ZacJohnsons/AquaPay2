<?php
session_start();
include 'db.php';

// 1. Identify the logged-in user
if (isset($_SESSION['client_id'])) {
    $user_id = $_SESSION['client_id'];
    $username = $_SESSION['loggedInUser'];
    $user_type = 'client';
    $redirect = "../userdashboard.php#faq";
} elseif (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $username = $_SESSION['loggedInUser'];
    $user_type = 'admin';
    $redirect = "../Admindashboard.php#faq";
} else {
    header("Location: AdminLogin.php");
    exit();
}

// 2. Get and sanitize the thought content
$content = mysqli_real_escape_string($conn, $_POST['content']);

// 3. Insert the thought
$query = "INSERT INTO thoughts (sender_id, sender_type, username, content, created_at) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $user_id, $user_type, $username, $content);
$stmt->execute();
$thought_id = $conn->insert_id; // Capture the new thought's ID

// 4. Insert into thoughts_receiver for everyone except the sender
if ($user_type === 'client') {
    // Sender is a client
    $exclude_client_id = $user_id;

    // Fetch all other clients
    $clients = mysqli_query($conn, "SELECT client_id FROM client_information WHERE client_id != '$exclude_client_id'");

    // Fetch all admins
    $admins = mysqli_query($conn, "SELECT admin_id FROM admin_users");

} else {
    // Sender is an admin
    $exclude_admin_id = $user_id;

    // Fetch all other admins
    $admins = mysqli_query($conn, "SELECT admin_id FROM admin_users WHERE admin_id != '$exclude_admin_id'");

    // Fetch all clients
    $clients = mysqli_query($conn, "SELECT client_id FROM client_information");
}

// Insert into thoughts_receiver for admins
while ($admin = mysqli_fetch_assoc($admins)) {
    $receiver_id = $admin['admin_id'];
    $receiver_type = 'admin';
    $is_read = 0;
    $insertReceiver = $conn->prepare("INSERT INTO thoughts_receivers (thought_id, receiver_id, receiver_type, is_read, read_at) VALUES (?, ?, ?, ?, 0)");
    $insertReceiver->bind_param("issi", $thought_id, $receiver_id, $receiver_type, $is_read);
    $insertReceiver->execute();
}

// Insert into thoughts_receiver for clients
while ($client = mysqli_fetch_assoc($clients)) {
    $receiver_id = $client['client_id'];
    $receiver_type = 'client';
    $is_read = 0;
    $insertReceiver = $conn->prepare("INSERT INTO thoughts_receivers (thought_id, receiver_id, receiver_type, is_read, read_at) VALUES (?, ?, ?, ?, 0)");
    $insertReceiver->bind_param("iiss", $thought_id, $receiver_id, $receiver_type, $is_read);
    $insertReceiver->execute();
}

// 5. Redirect back to the dashboard
header("Location: $redirect");
exit();
?>
