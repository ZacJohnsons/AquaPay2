<?php
// Include database connection
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
        die("Error: Client ID is missing.");
    }
    
    $client_id = $_POST['client_id']; 
    echo "Received Client ID: " . $client_id . "<br>"; // Debugging line

    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $meter_no = $_POST['meter_no'];

    // Check if client_id exists
    $check_sql = "SELECT * FROM client_information WHERE client_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $client_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows == 0) {
        die("Error: Client ID does not exist in database.");
    }
    echo "Client ID found in database.<br>"; // Debugging line

    $check_stmt->close();

    // Prepare SQL query to update client details
    $sql = "UPDATE client_information SET name = ?, email = ?, address = ?, meter_no = ? WHERE client_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $name, $email, $address, $meter_no, $client_id);

        if ($stmt->execute()) {
            header("Location: ../Admindashboard.php?success=Client updated successfully");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
}
?>
