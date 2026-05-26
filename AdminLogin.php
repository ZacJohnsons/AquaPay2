<?php
session_start(); // Start session for user login
include 'includes/db.php'; // Include the database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture the entered username and password
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // User-entered password

    // Check if the username matches an admin
    $sql_admin = "SELECT * FROM admin_users WHERE username = '$username'";
    $result_admin = mysqli_query($conn, $sql_admin);

    if ($result_admin && mysqli_num_rows($result_admin) == 1) {
        $row = mysqli_fetch_assoc($result_admin);
        
        // Verify the password for admin (plain text password comparison)
        if ($password == $row['password']) {
            // Set session for admin login
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['loggedInUser'] = $row['role']; // Admin role
            header("Location: Admindashboard.php"); // Redirect to admin panel
            exit();
        } else {
            $error = "Invalid admin username or password.";
        }
    }

    // Check if the username matches a client
    $sql_client = "SELECT * FROM client_information WHERE name = '$username'";
    $result_client = mysqli_query($conn, $sql_client);

    if ($result_client && mysqli_num_rows($result_client) == 1) {
        $row = mysqli_fetch_assoc($result_client);

        // Verify the password for client (hashed password comparison)
        if (password_verify($password, $row['password'])) {
            // Set session for client login
            $_SESSION['client_id'] = $row['client_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['loggedInUser'] = $row['name']; // For client dashboard
            $_SESSION['role'] = 'client'; // Client role

            header("Location: userdashboard.php"); // Redirect to client/user dashboard
            exit();
        } else {
            $error = "Invalid client username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaPay Admin Login</title>
    <link rel="icon" type="image/png" href="images/icon.png">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/style1.css">
</head>

<body class="background-radial-gradient">

    <section class="vh-100 login-section">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col col-xl-10">
                    <div class="card login-card">
                        <div class="row g-0">
                            
                            <!-- Left Section -->
                            <div class="col-md-6 col-lg-5 d-none d-md-block position-relative">
                                <img src="images/tap2.jpg" alt="AquaPay Login" class="img-fluid left-image">
                                
                                <!-- Overlay with Caption -->
                                <div class="image-overlay position-absolute w-100 h-100 d-flex justify-content-center align-items-center">
                                    <span class="caption-text">Purchase & Use</span>
                                </div>
                            </div>

                            <!-- Right Section -->
                            <div class="col-md-6 col-lg-7 d-flex align-items-center">
                                <div class="card-body p-4 p-lg-5 text-black">
                                    <form method="POST" action=" ">
                                        
                                        <div class="d-flex align-items-center mb-3 pb-1">
                                            <!--<i class="fas fa-tint fa-2x me-3" style="color: #008888;"></i>-->
                                            <span class="h1 fw-bold mb-0">AquaPay</span>
                                            <img src="images/icon.png" alt="Logo" class="logo"> 
                                        </div>

                                        <h5 class="fw-normal mb-3 pb-3">Sign into your account</h5>

                                        <?php if (isset($error)): ?>
                                            <div class="error-message"><?php echo $error; ?></div>
                                        <?php endif; ?>

                                        <div class="form-outline mb-4">
                                            <input type="text" id="username" name="username" class="form-control form-control-lg" required />
                                            <label class="form-label" for="username">Username</label>
                                        </div>

                                        <div class="form-outline mb-4 position-relative">
                                            <input type="password" id="password" name="password" class="form-control form-control-lg" required />
                                            <label class="form-label" for="password">Password</label>
                                            
                                            <!-- Show Password Toggle with Checkbox (Square) -->
                                            <div class="d-flex align-items-center mt-3">
                                                <label for="showPassword" class="form-label mb-0">Show Password</label>
                                                <input type="checkbox" id="showPassword" class="me-2" onclick="togglePasswordVisibility()" />
                                            </div>
                                        </div>
                                        <div class="pt-1 mb-4">
                                            <button class="btn btn-dark btn-lg btn-block" type="submit">Login</button>
                                        </div>
                                        
                                        <div class="text-center">
                                            <a href="forgot_password.html" class="text-muted">Forgot Password?</a>
                                        </div>                          
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <script>
        // Show password toggle
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById('password');
            var showPasswordCheckbox = document.getElementById('showPassword');

            if (showPasswordCheckbox.checked) {
                passwordInput.type = "text";  // Show password
            } else {
                passwordInput.type = "password";  // Hide password
            }
        }
    </script>
</body>
</html>
