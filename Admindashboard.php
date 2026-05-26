<?php
session_start();
include 'includes/db.php'; // Database connection

if (!isset($_SESSION['loggedInUser']) || !isset($_SESSION['admin_id'])) {
    header("Location: AdminLogin.php");
    exit();
}

// Device Restriction (block mobile/tablet access)
$userAgent = $_SERVER['HTTP_USER_AGENT'];
function isMobileDevice($userAgent) {
    return preg_match('/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent);
}

if (isMobileDevice($userAgent)) {
    // Redirect or show a simple warning
    header("Location: not-allowed.html"); // You can create this page
    exit();
}

$loggedInUser = $_SESSION['loggedInUser']; 
$admin_id = $_SESSION['admin_id'];
$user_id = $admin_id;
$user_type = 'admin';
require_once 'includes/thought_helpers.php';

// Fetch the admin's username, role, and profile image from the database
$query = "SELECT username, role, profile_image FROM admin_users WHERE admin_id = '$admin_id'";
$result = mysqli_query($conn, $query);

// Check if the query returns a result
if ($row = mysqli_fetch_assoc($result)) {
    $username = $row['username'];
    $role = $row['role'];
    $profileImage = $row['profile_image'];
} else {
    echo "Error fetching user data.";
    exit();
}

// Fetch counts for dashboard
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM client_information"))['count'];
$totalAdmins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM admin_users"))['count'];
$totalTokens = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tokens"))['count'];
$totalPayments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM payments"))['count'];

// Fetch clients from DB
$clientsQuery = "SELECT * FROM client_information";
$clientsResult = mysqli_query($conn, $clientsQuery);

// Fetch payments from DB
$paymentsQuery = "SELECT * FROM payments";
$paymentsResult = mysqli_query($conn, $paymentsQuery);

// Fetch tokens from DB
$tokensQuery = "SELECT * FROM tokens";
$tokensResult = mysqli_query($conn, $tokensQuery);

// Fetch system logs from DB
$logsQuery = "SELECT * FROM system_log";
$logsResult = mysqli_query($conn, $logsQuery);

// Fetch admins
$adminsQuery = "SELECT * FROM admin_users";
$adminsResult = mysqli_query($conn, $adminsQuery);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaPay Dashboard</title>
    <!-- favicon -->
    <link rel="icon" type="image/png" href="images/icon.png">
    <!-- Bootstrap CSS (Include in <head>) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!--Styling-->
    <link rel="stylesheet" href="styles/style4.css">
    <link rel="stylesheet" href="styles/style.css">
    <!-- FontAwesome for icons -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">

    <!-- Dashboard grid layout -->
    <style>
        /* Welcome header */
        .dash-header { margin-bottom: 16px; }
        .dash-welcome { font-size: 14px; color: #344055; margin: 0 0 2px; }
        .dash-welcome strong { color: #023e8a; }

        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .stat-card { margin: 0 !important; }
        .stat-card .card-body { padding: 14px 16px; }

        /* Charts grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 14px;
            margin-bottom: 20px;
        }
        .chart-card { margin: 0; }
        .chart-card .card-body { padding: 14px 16px; }
        .chart-label { font-size: 12px; font-weight: 600; color: #0077b6; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
        .chart-card--wide { grid-column: 1 / -1; }

        /* FAQ wrapper */
        .faq-wrapper { margin-bottom: 20px; }
        .faq-wrapper .card-body { padding: 16px 20px; }
        .faq-wrapper h2 { font-size: 1rem; }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="left">
            <img src="images/icon.png" alt="Logo" class="logo"> 
            <strong style="color: white;">AQUAPAY</strong>
        </div>
        <div class="right">
            <!-- Notification Dropdown -->
            <div class="notification-wrapper" style="position:relative; display:inline-block; margin-right:18px;">
                <a href="javascript:void(0)" id="notification-link">
                    <i class="fas fa-bell" style="font-size: 24px; color: #007bff; cursor:pointer;"></i>
                    <?php
                        // Determine user identity for notification check
                        if (isset($_SESSION['admin_id'])) {
                            // Fetch unread count for this specific user from thoughts_receivers
                            $countQuery = "SELECT COUNT(*) AS count 
                                        FROM thoughts_receivers 
                                        WHERE receiver_id = '$user_id' 
                                        AND receiver_type = '$user_type' 
                                        AND is_read = 0";

                            $resultCount = mysqli_query($conn, $countQuery);
                            $countData = mysqli_fetch_assoc($resultCount);
                            $newCount = $countData['count'] ?? 0;

                            if ($newCount > 0) {
                                echo '<span class="badge bg-red" style="position:absolute;top:-6px;right:-10px;">' . $newCount . '</span>';
                            }
                        }
                    ?>
                </a>
            </div>

             <!-- Profile Icon -->
            <div class="profile-dropdown" id="profileDropdownWrap">
                <strong><?php echo htmlspecialchars($loggedInUser); ?></strong>
                <?php if (!empty($profileImage)) { ?>
                    <img src="<?php echo $profileImage; ?>" alt="Profile Image" class="profile-icon" id="profile-toggle">
                <?php } else { ?>
                    <i class="fas fa-user-circle profile-icon" id="profile-toggle"></i>
                <?php } ?>
                <div class="dropdown-content" id="profileDropdownMenu">
                    <a class="dropdown-item" id="my-profile" href="javascript:void(0);"><i class="fas fa-user fa-sm"></i> My Profile</a>
                    <a class="dropdown-item" href="includes/logout.php"><i class="fas fa-sign-out-alt fa-sm"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3>Admin Panel</h3>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="code.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="view-clients">Clients</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="view-payments">Payment History</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="view-tokens">Token Management</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="view-logs">System Log</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="view-admins">Admins</a></li>
            </ul>
        </aside>

        <!-- Main Dashboard Content -->
        <div class="dashboard-content" id="dashboard-content">
            <div class="overview">

                <!-- Welcome + title -->
                <div class="dash-header">
                    <p class="dash-welcome">Hello, welcome back <strong><?php echo htmlspecialchars($loggedInUser); ?></strong>!</p>
                    <h1>Dashboard Overview</h1>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="card-title"><i class="fas fa-users"></i> Total Clients</p>
                            <p class="card-text"><?php echo $totalUsers; ?></p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="card-title"><i class="fas fa-user-shield"></i> Total Admins</p>
                            <p class="card-text"><?php echo $totalAdmins; ?></p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="card-title"><i class="fas fa-credit-card"></i> Total Payments</p>
                            <p class="card-text"><?php echo $totalPayments; ?></p>
                        </div>
                    </div>
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="card-title"><i class="fas fa-ticket-alt"></i> Tokens Issued</p>
                            <p class="card-text"><?php echo $totalTokens; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-card card">
                        <div class="card-body">
                            <p class="chart-label">Overview (Bar)</p>
                            <canvas id="waterUsageChart" style="height:220px;"></canvas>
                        </div>
                    </div>
                    <div class="chart-card card">
                        <div class="card-body">
                            <p class="chart-label">Distribution (Pie)</p>
                            <canvas id="pieChart" style="height:220px;"></canvas>
                        </div>
                    </div>
                    <div class="chart-card chart-card--wide card">
                        <div class="card-body">
                            <p class="chart-label">Trends (Line)</p>
                            <canvas id="lineChart" style="height:200px;"></canvas>
                        </div>
                    </div>

                <!-- FAQ Section -->
                <div id="faq" class="faq-wrapper">
                    <div class="card">
                        <div class="card-body">
                                <h2 class="mt-3 text-center">Community Thoughts & FAQs</h2>
                                <ul id="faq-list" style="list-style:none; padding:0;">
                                    <?php renderThoughtFeed($conn, $user_id, $user_type); ?>
                                </ul>
                                <form action="includes/post_thought.php" method="POST" class="mt-3" style="position:relative;">
                                    <textarea name="content" class="form-control mb-2" placeholder="Share your thought or ask a question..." required
                                        style="padding-right:40px;"></textarea>

                                    <button type="submit" style="position:absolute;
                                        bottom:12px;right:12px;background:none;border:none;color:#007bff;font-size:18px;cursor:pointer;strong:
                                        20px;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Client Section-->
            <section id="clients-section" class="section collapsed">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <!-- Add Client Button (Left) -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#clientModal">Add Client</button>

                    <!-- Search Bar (Right) -->
                    <div class="d-flex">
                        <select id="clientSearchKey" class="form-control w-auto">
                            <option value="client_id">Client ID</option>
                            <option value="name">Name</option>
                            <option value="meter_no">Meter Number</option>
                        </select>
                        <input type="text" id="clientSearchInput" class="form-control w-auto ml-2" placeholder="Search">
                        <button class="btn btn-link ml-2" onclick="searchTable('clients-table', 'clientSearchKey', 'clientSearchInput')">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <table class="table table-striped mt-3" id="clients-table">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Meter Number</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($client = mysqli_fetch_assoc($clientsResult)) { ?>
                            <tr>
                                <td data-key="client_id"><?php echo $client['client_id']; ?></td>
                                <td data-key="name"><?php echo $client['name']; ?></td>
                                <td><?php echo $client['email']; ?></td>
                                <td data-key="meter_no"><?php echo $client['meter_no']; ?></td>
                                <td><?php echo $client['address']; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Action
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="actionDropdown">
                                            <a class="dropdown-item" data-toggle="modal" data-target="#viewclientModal" data-client-id="<?php echo $client['client_id']; ?>" onclick="loadClientData(<?php echo $client['client_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a class="dropdown-item" data-toggle="modal" data-target="#editclientModal" data-client-id="<?php echo $client['client_id']; ?>" onclick="loadEditClientData(<?php echo $client['client_id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a class="dropdown-item text-danger" href="includes/delete_client.php?client_id=<?php echo $client['client_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>

            <!-- Admin Section-->
            <section id="admins-section" class="section collapsed">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <!-- Add admin Button (Left) -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#adminModal">Add Admin</button>

                    <!-- Search Bar (Right) -->
                    <div class="d-flex">
                        <select id="adminSearchKey" class="form-control w-auto">
                            <option value="admin_id">Admin ID</option>
                            <option value="username">Username</option>
                            <option value="role">Role</option>
                        </select>
                        <input type="text" id="adminSearchInput" class="form-control w-auto ml-2" placeholder="Search">
                        <button class="btn btn-link ml-2" onclick="searchTable('admins-table', 'adminSearchKey', 'adminSearchInput')">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <table class="table table-striped mt-3" id="admins-table">
                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin = mysqli_fetch_assoc($adminsResult)) { ?>
                            <tr>
                                <td data-key="admin_id"><?php echo $admin['admin_id']; ?></td>
                                <td data-key="username"><?php echo $admin['username']; ?></td>
                                <td data-key="role"><?php echo $admin['role']; ?></td>
                                <td><?php echo $admin['email']; ?></td>
                                
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Action
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="actionDropdown">
                                            <a class="dropdown-item" data-toggle="modal" data-target="#viewadminModal" data-admin-id="<?php echo $admin['admin_id']; ?>" onclick="loadAdminData(<?php echo $admin['admin_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a class="dropdown-item" data-toggle="modal" data-target="#editadminModal" data-admin-id="<?php echo $admin['admin_id']; ?>" onclick="loadEditAdminData(<?php echo $admin['admin_id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a class="dropdown-item text-danger" href="includes/delete_admin.php?admin_id=<?php echo $admin['admin_id']; ?>" onclick="return confirm('Are you sure you want to delete this admin?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>

            <!--Payments Section -->
            <section id="payments-section" class="section collapsed">
                <!-- Search Bar -->
                <div class="d-flex justify-content-end mb-3">
                    <select id="paymentSearchKey" class="form-control w-auto">
                        <option value="client_id">Client ID</option>
                        <option value="payment_date">Payment Date</option>
                    </select>
                    <input type="text" id="paymentSearchInput" class="form-control w-auto ml-2" placeholder="Search">
                    <button class="btn btn-link ml-2" onclick="searchTable('payments-table', 'paymentSearchKey', 'paymentSearchInput')"><i class="fas fa-search"></i></button>
                </div>

                <table class="table table-striped mt-3" id="payments-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Client ID</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = mysqli_fetch_assoc($paymentsResult)) { ?>
                            <tr>
                                <td><?php echo $payment['payment_id']; ?></td>
                                <td data-key="client_id"><?php echo $payment['client_id']; ?></td>
                                <td><?php echo $payment['amount']; ?></td>
                                <td data-key="payment_date"><?php echo $payment['payment_date']; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Action
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="actionDropdown">
                                            <a class="dropdown-item text-danger" href="includes/delete_payments.php?payment_id=<?php echo $payment['payment_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>

            <!--Token Section -->
            <section id="tokens-section" class="section collapsed">
                <!-- Search Bar -->
                <div class="d-flex justify-content-end mb-3">
                    <select id="tokenSearchKey" class="form-control w-auto">
                        <option value="client_id">Client ID</option>
                        <option value="token">Token</option>
                        <option value="issue_date">Issue Date</option>
                    </select>
                    <input type="text" id="tokenSearchInput" class="form-control w-auto ml-2" placeholder="Search">
                    <button class="btn btn-link ml-2" onclick="searchTable('tokens-table', 'tokenSearchKey', 'tokenSearchInput')"><i class="fas fa-search"></i></button>
                </div>

                <table class="table table-striped mt-3" id="tokens-table">
                    <thead>
                        <tr>
                            <th>Token ID</th>
                            <th>Client ID</th>
                            <th>Token</th>
                            <th>Units</th>
                            <th>Issue Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($token = mysqli_fetch_assoc($tokensResult)) { ?>
                            <tr>
                                <td><?php echo $token['token_id']; ?></td>
                                <td data-key="client_id"><?php echo $token['client_id']; ?></td>
                                <td data-key="token"><?php echo $token['token_value']; ?></td>
                                <td><?php echo $token['units']; ?></td>
                                <td data-key="issue_date"><?php echo $token['issue_date']; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Action
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="actionDropdown">
                                            <a class="dropdown-item text-danger" href="includes/delete_token.php?token_id=<?php echo $token['token_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>

            <!--Logs-section-->
            <section id="logs-section" class="section collapsed">
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>User Email</th>
                            <th>Action Performed</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>log_time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = mysqli_fetch_assoc($logsResult)) { ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo $log['user_email']; ?></td>
                                <td><?php echo $log['action']; ?></td>
                                <td><?php echo $log['status']; ?></td>
                                <td><?php echo $log['message']; ?></td>
                                <td><?php echo $log['log_time']; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Action
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="actionDropdown">
                                            <a class="dropdown-item text-danger" href="includes/delete_logs.php?log_id=<?php echo $log['log_id']; ?>" onclick="return confirm('Are you sure you want to delete this log?');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>
            
            <!-- Profile Section -->
            <section id="profile-section" style="section collapsed">
                <div class="container1 ">
                    <div class="row">
                        <!-- Left Section: User Information -->
                        <div class="col-md-6 text-center">
                            <div class="profile-image ms-4">
                                <!-- Display profile image or default icon -->
                                <?php if (!empty($profileImage)) { ?>
                                        <img src="<?php echo $profileImage; ?>" alt="Profile Image" 
                                        class="img-fluid rounded-circle" 
                                        style="width: 150px; height: 150px; object-fit: cover; object-position: center;">
                                <?php } else { ?>
                                    <i class="fa fa-user-circle" style="font-size: 150px; color: gray;"></i>
                                <?php } ?>
                            </div>
                            <div class="user-info">
                                <p><strong>Username:</strong> <span><?php echo $username; ?></span></p>
                                <p><strong>Role:</strong> <span><?php echo $role; ?></span></p>
                            </div>
                        </div>

                        <!-- Right Section: Profile Image and Update Info Toggle -->
                        <div class="col-md-6 border-start">
                            <div class="toggle-buttons text-center mb-3">
                                <button class="btn btn-primary me-2" onclick="showSection('profile_image-section')">Profile Image</button>
                                <button class="btn btn-secondary" onclick="showSection('update-info-section')">Update Information</button>
                            </div>
                            <!-- Profile Image Section -->
                            <div id="profile_image-section" class="section-content text-center">
                                <h3>Profile Image</h3>
                                <div class="profile_image">
                                    <?php
                                        $file_ext = isset($data['filename']) ? strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION)) : '';
                                        if (!empty($data['filename']) && in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
                                            echo "<img src='uploads/profile/{$data['filename']}' class='file-view' id='profile_image-img' alt='Profile Image'>";
                                        } else {
                                            echo "<i class='fa fa-user-circle' id='default-profile_image' style='font-size: 150px; color: gray;'></i>";
                                        }
                                    ?>
                                    <input type="file" id="profile_image-input" style="display: none;" accept="image/*">
                                    <button class="btn btn-success mt-2" id="upload-profile_image">Upload</button>
                                    <button class="btn btn-danger mt-2" id="delete-profile_image">Delete</button>
                                </div>
                            </div>
                            <!-- Update Information Section -->
                            <div id="update-info-section" class="section-content" style="display: none;">
                                <h3>Update Information</h3>
                                <form id="update-info-form" method="POST" action="includes/update_profile.php">
                                    <div class="mb-2">
                                        <label for="new-username" class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" id="new-username" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="old-password" class="form-label">Old Password</label>
                                        <input type="password" name="old_password" class="form-control" id="old-password" required>
                                    </div>
                                    <div class="mb-2">
                                        <label for="new-password" class="form-label">New Password</label required>
                                        <input type="password" name="new_password" class="form-control" id="new-password">
                                    </div>
                                    <div class="mb-2">
                                        <label for="confirm-password" class="form-label">Confirm New Password</label required>
                                        <input type="password"name="confirm_password" class="form-control" id="confirm-password">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- add client -->
    <div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Client</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="includes/save_client.php" method="POST">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="client_id">Client ID</label>
                            <input type="text" class="form-control" name="client_id" id="client_id" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" name="password" id="password" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="meter_no">Meter Number</label>
                            <input type="text" class="form-control" name="meter_no" id="meter_no" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <input type="text" class="form-control" name="address" id="address" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal fade" id="editclientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Client</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="includes/edit_client.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="client_id" id="edit_client_id">
                        <div class="form-group">
                            <label for="edit_name">Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_address">Address</label>
                            <input type="text" class="form-control" name="address" id="edit_address" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_meter_no">Meter Number</label>
                            <input type="text" class="form-control" name="meter_no" id="edit_meter_no"  required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- view Client Modal -->
    <div class="modal fade" id="viewclientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Client</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="user-info">
                        <p><strong>Client Number:</strong> <span id="clientId"></span></p>
                    </div>
                    <div class="user-info">
                        <p><strong>Username:</strong> <span id="clientName"></span></p>
                    </div>
                    <div class="user-info">
                        <p><strong>Email:</strong> <span id="clientEmail"></span></p>
                    </div>
                    <div class="user-info">
                        <p><strong>Address:</strong> <span id="clientAddress"></span></p>
                    </div>
                    <div class="user-info">
                        <p><strong>Meter Number:</strong> <span id="clientMeterNo"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 AquaPay | All Rights Reserved. Zac</p>
    </footer>


    <!-- Bootstrap JS (for modal functionality) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Bootstrap JS (Required for dropdown) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="styles/script.js"></script>

    <script>
        // ── Profile dropdown: keep open while moving mouse to menu ──
        (function() {
            var wrap = document.getElementById('profileDropdownWrap');
            var menu = document.getElementById('profileDropdownMenu');
            if (!wrap || !menu) return;
            var leaveTimer;
            wrap.addEventListener('mouseenter', function() {
                clearTimeout(leaveTimer);
                menu.style.display = 'block';
            });
            wrap.addEventListener('mouseleave', function() {
                leaveTimer = setTimeout(function() { menu.style.display = ''; }, 150);
            });
            menu.addEventListener('mouseenter', function() {
                clearTimeout(leaveTimer);
                menu.style.display = 'block';
            });
            menu.addEventListener('mouseleave', function() {
                leaveTimer = setTimeout(function() { menu.style.display = ''; }, 150);
            });
        })();

        function checkScreenSize() {
            const minWidth = 1024; // Set your desktop-only width threshold
            if (window.innerWidth < minWidth) {
            window.location.href = "not-allowed.html"; // Same block page as PHP
            }
        }

        // Run on load
        window.onload = checkScreenSize;

        // Also run on resize
        window.onresize = checkScreenSize;

        // Function to show/hide sections
        function toggleCommentBox(id) {
            var el = document.getElementById('comment-box-' + id);
            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        }

        // Bar chart for water usage
        const ctx = document.getElementById('waterUsageChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Users', 'Admins','Tokens', 'Payments'], // X-axis labels
                datasets: [{
                    label: 'Total Count',
                    data: [<?php echo $totalUsers; ?>, <?php echo $totalAdmins; ?>, <?php echo $totalTokens; ?>, <?php echo $totalPayments; ?>], // Data for each category
                    backgroundColor: 'rgba(22, 149, 149, 0.6)', // Bar color
                    borderColor: 'rgb(14, 99, 99)', // Border color
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { 
                        beginAtZero: true 
                    }
                }
            }
        });

        // Pie chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Users', 'Admins', 'Tokens', 'Payments'], 
                datasets: [{
                    label: 'Distribution',
                    data: [<?php echo $totalUsers; ?>, <?php echo $totalAdmins; ?>, <?php echo $totalTokens; ?>, <?php echo $totalPayments; ?>], // Pie chart data
                    backgroundColor: ['#22A99F', '#FFB55D', '#FF6B6B'],
                    borderColor: ['#16A9A3', '#FF9F1C', '#FF3D29'], 
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });

        // Line chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['Users', 'Admins', 'Tokens', 'Payments'], // X-axis labels
                datasets: [{
                    label: 'Line Chart',
                    data: [<?php echo $totalUsers; ?>, <?php echo $totalAdmins; ?>, <?php echo $totalTokens; ?>, <?php echo $totalPayments; ?>], // Pie chart data
                    fill: false,
                    borderColor: '#007bff',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // function to view notification details
        document.getElementById("notification-link").addEventListener("click", function(e) {
            e.preventDefault();

            // Show FAQ section
            document.getElementById("faq").style.display = "block";
            document.getElementById("faq").scrollIntoView({ behavior: "smooth" });

            // AJAX call to mark thoughts as read
            fetch('includes/mark_thought_read.php', {
                method: 'POST'
            }).then(response => {
                if (response.ok) {
                    let badge = this.querySelector(".badge");
                    if (badge) badge.remove();
                }
            });
        });
    </script>
</body>
</html>
