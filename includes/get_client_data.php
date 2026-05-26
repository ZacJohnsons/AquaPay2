<?php
// Include your database connection file
include 'db.php';

if (isset($_GET['client_id'])) {
    $client_id = $_GET['client_id'];

    // Query to fetch client information
    $sql = "SELECT client_id, name, email, address, meter_no FROM client_information WHERE client_id = '$client_id'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Client not found']);
    }
}
?>
