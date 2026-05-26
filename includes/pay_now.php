<?php

session_start();
require_once('config.php');
include 'db.php';


if(!isset($_SESSION['loggedInUser']) || !isset($_SESSION['client_id'])) {
    header("Location: AdminLogin.php");
    exit();
}

$loggedInUser = $_SESSION['loggedInUser'];
$client_id = $_SESSION['client_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST["amount"];
    $phone = formatPhoneNumber($_POST["phone"]);


    // API parameters
    $paymentApiUrl = "https://daraza.net/api/request_to_pay/"; // i actually just b

    $postData = array(
        "method" => 1,
        "phone" => $phone,
        "amount" => $amount,
        'note' => 'checkout',
    );


    $headers = [
        'Authorization: Api-Key '. API_KEY,
        "Content-Type: application/json",
    ];


    $ch = curl_init($paymentApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    curl_setopt($ch, CURLOPT_TIMECONDITION, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);


    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        // die("Curl error: " . curl_error($ch));
    }

    curl_close($ch);

    $result = json_decode($response, true);


    // die(var_dump($response));

     if ($result && isset($result['status']) && $result['status'] == "success") {
        // Save payment
        $payment_date = date("Y-m-d H:i:s");
        $payment_status = "Completed";
        $status = "active";
    
        $stmt = $conn->prepare("INSERT INTO payments (client_id, payment_date, amount, payment_status, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $client_id, $payment_date, $amount, $payment_status, $status);
        $stmt->execute();
        $stmt->close();
    
        // STEP 1: Generate 20-digit random integer token
        $token_value = '';
        for ($i = 0; $i < 20; $i++) {
            $token_value .= rand(0, 9);
        }

        // Get current year and month
        $current_month = date('Y-m');

        // Count tokens generated this month for this client
        $query = "SELECT COUNT(*) as count FROM tokens WHERE client_id = ? AND DATE_FORMAT(issue_date, '%Y-%m') = ?";
        $stmt_check = $conn->prepare($query);
        $stmt_check->bind_param("is", $client_id, $current_month);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row = $result_check->fetch_assoc();
        $monthly_token_count = $row['count'];
        $stmt_check->close();

        // Determine rate based on number of purchases this month
        if ($monthly_token_count == 0) {
            $rate_per_1000 = 0.5; // First payment this month
        } elseif ($monthly_token_count == 1) {
            $rate_per_1000 = 2.0; // Second payment this month
        } else {
            $rate_per_1000 = 1.2; // Third or more payments this month
        }

        // Calculate units
        $units = ($amount / 1000) * $rate_per_1000;
        $units = number_format($units, 2, '.', '');

        
        // STEP 4: Token details
        $issue_date = date("Y-m-d H:i:s");
        $expiry_date = date("Y-m-d H:i:s", strtotime("+30 days"));
        $token_status = "active";

        // STEP 5: Save to `tokens` table
        $stmt2 = $conn->prepare("INSERT INTO tokens (client_id, token_value, issue_date, expiry_date, status, units) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("issssd", $client_id, $token_value, $issue_date, $expiry_date, $token_status, $units);
        $stmt2->execute();
        $stmt2->close();
    
        // Redirect with popup message
        $message = "Payment Successful. Your token is: $token_value";
        header("Location: ../userdashboard.php?popup=" . urlencode($message));
        exit();
    
    } else {


        $error = "Payment Failed: " . ($result['message'] ?? 'Unknown error.');
        header("Location: ../userdashboard.php?popup=" . urlencode($error));
        exit();
    }
    
 }
?>
