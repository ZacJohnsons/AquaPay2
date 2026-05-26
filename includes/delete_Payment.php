<?php
// Include database connection
include 'db.php';

if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    // Prepare SQL query to delete the client
    $sql = "DELETE FROM payments WHERE payment_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $payment_id);

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
