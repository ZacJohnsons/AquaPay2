<?php
header('Content-Type: application/json');
include 'db.php';

// Get POSTed JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if token is provided
if (!isset($data['token']) || empty($data['token'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No token provided'
    ]);
    exit;
}

$token_value = mysqli_real_escape_string($conn, $data['token']);

// Step 1: Check if the token exists
$sql = "SELECT token_id, units, status, expiry_date FROM tokens WHERE token_value='$token_value' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token'
    ]);
    exit;
}

$row = mysqli_fetch_assoc($result);

// Step 2: Check if token is already used
if ($row['status'] === 'used') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Token already used'
    ]);
    exit;
}

// Step 3: Check if token is expired
$currentDate = date('Y-m-d H:i:s');
if ($row['expiry_date'] < $currentDate) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Token expired'
    ]);
    exit;
}

// Step 4: Fetch units and mark token as used
$units = floatval($row['units']);
$token_id = $row['token_id'];

$updateSql = "UPDATE tokens SET status='used' WHERE token_id='$token_id'";
if (!mysqli_query($conn, $updateSql)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update token status'
    ]);
    exit;
}

// Step 5: Return success with units
echo json_encode([
    'status' => 'success',
    'message' => 'Token validated successfully',
    'units' => $units
]);

$conn->close();
?>
