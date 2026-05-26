<?php
// Include database connection
include 'db.php';

if (isset($_GET['token_id'])) {
    $token_id = $_GET['token_id'];

    // Prepare SQL query to delete the client
    $sql = "DELETE FROM tokens WHERE token_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token_id);

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
