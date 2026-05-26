<?php
// Include database connection
include 'db.php';

if (isset($_GET['client_id'])) {
    $client_id = $_GET['client_id'];

    // Prepare SQL query to delete the client
    $sql = "DELETE FROM client_information WHERE client_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $client_id);

        if ($stmt->execute()) {
            header("Location: ../Admindashboard.php?success=Client deleted successfully");
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
