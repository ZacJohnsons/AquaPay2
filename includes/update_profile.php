<?php
session_start();
include 'db.php'; // Ensure this file contains the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Fetch current admin data
    $admin_id = $_SESSION['admin_id']; // Ensure admin_id is stored in session
    $query = "SELECT * FROM admin_users WHERE admin_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if (!$admin) {
        echo "Admin not found!";
        exit();
    }

    // Verify old password
    if ($old_password !== $admin['password']) { // Update this check if passwords are hashed
        echo "<script>alert('Old password is incorrect!'); window.location.href='../Admindashboard.php';</script>";
        exit();
    }

    // Validate new password
    if ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match!'); window.location.href='../Admindashboard.php';</script>";
        exit();
    }

    // Hash new password before saving
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update username and password
    $update_query = "UPDATE admin_users SET username = ?, password = ? WHERE admin_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssi", $username, $hashed_password, $admin_id);

    if ($update_stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location.href='../Admindashboard.php';</script>";
    } else {
        echo "<script>alert('error updating profile!'); window.location.href='../Admindashboard.php';</script>";
    }
}
?>
