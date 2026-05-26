<?php
session_start();
include 'db.php'; // Ensure database connection

if (!isset($_SESSION['client_id'])) {
    header("Location: AdminLogin.php");
    exit();
}

$user_id = $_SESSION['client_id'];

// Check if a file is uploaded
if (isset($_FILES['profile_image'])) {
    $target_dir = "../upload/"; // Ensure correct directory format
    $imageFileType = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));

    // Allowed file types
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (!in_array($imageFileType, $allowed_types)) {
        echo "Only JPG, JPEG, and PNG files are allowed.";
        exit();
    }

    // Generate a unique file name to prevent conflicts
    $unique_name = time() . "_" . uniqid() . "." . $imageFileType;
    $target_file = $target_dir . $unique_name;

    // Get the current profile image from the database
    $query = "SELECT profile_image FROM client_information WHERE client_id='$user_id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $current_image = $row['profile_image'];

    // Delete the old profile image if it exists
    if (!empty($current_image) && file_exists("../" . $current_image)) {
        unlink("../" . $current_image);
    }

    // Move uploaded file and update database
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
        // Store only the relative path
        $new_profile_image = "upload/" . $unique_name;
        $update_query = "UPDATE client_information SET profile_image='$new_profile_image' WHERE client_id='$user_id'";

        if (mysqli_query($conn, $update_query)) {
            echo "Profile image updated successfully!";
        } else {
            echo "Error updating profile image in the database.";
        }
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file selected.";
}
?>

