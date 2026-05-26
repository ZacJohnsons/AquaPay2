<?php
// Include database connection
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $client_id = $_POST['client_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $meter_no = $_POST['meter_no'];
    $address = $_POST['address'];
    $password = $_POST['password'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Prepare SQL query to insert data (include password)
    $sql = "INSERT INTO client_information (client_id, name, email, meter_no, address, password) VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind all 6 parameters, including the hashed password
        $stmt->bind_param("ssssss", $client_id, $name, $email, $meter_no, $address, $hashed_password);

        if ($stmt->execute()) {
            // Client added successfully, now insert notification
            $notification_message = "A new client has been added: $name";
            $notification_type = "Client"; // Type of notification

            // Insert notification into the notifications table
            $insert_notification = "INSERT INTO notifications (notification_type, message) VALUES ('$notification_type', '$notification_message')";
            mysqli_query($conn, $insert_notification); // Execute notification insertion

            // Redirect back with success message
            header("Location: ../Admindashboard.php?success=Client added successfully");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    // Close database connection
    $conn->close();
}
?>
