<?php
if (isset($_GET['token'])) {
    include("includes/db.php");  

    $token = $_GET['token'];

    $query = "SELECT * FROM client_information WHERE reset_token = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        if (isset($_POST['submit'])) {
            $newPassword = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            
                $update = $conn->prepare("UPDATE client_information SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
                $update->bind_param("ss", $hashedPassword, $token);
                $update->execute();

                echo "<div class='alert alert-success text-center mt-3'>✅ Password reset successful. Redirecting to login...</div>";

                echo "<script>
                        setTimeout(function(){
                            window.location.href = 'AdminLogin.php';
                        }, 3000);
                      </script>";
            } else {
                echo "<div class='alert alert-danger text-center mt-3'>❌ Passwords do not match. Please try again.</div>";
            }
        }
    } else {
        echo "<div class='alert alert-danger text-center mt-3'>❌ Invalid or expired token.</div>";
    }
} else {
    echo "<div class='alert alert-danger text-center mt-3'>❌ Token is missing from the URL.</div>";
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaPay Admin Password Reset</title>
    <link rel="icon" type="image/png" href="images/icon.png">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/style3.css">
</head>

<body class="background-radial-gradient">

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6">
                <!-- Password Reset Form -->
                <div class="card p-4 shadow-sm">
                    <div class="card-body">
                        <h3 class="text-center mb-4">Reset Your Password</h3>
                        <form action="" method="POST">
                            <div class="form-outline mb-4">
                                <input type="password" id="password" name="password" class="form-control form-control-lg" required />
                                <label class="form-label" for="password">New Password</label>
                            </div>
                            <div class="form-outline mb-4">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control form-control-lg" required />
                                <label class="form-label" for="confirm_password">Confirm Password</label>
                            </div>
                            <div class="pt-1 mb-4">
                                <button class="btn btn-dark btn-lg btn-block" type="submit" name="submit">Reset Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
