<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['loggedInUser']) || !isset($_SESSION['client_id'])) {
    header("Location: AdminLogin.php");
    exit();
}

$loggedInUser = $_SESSION['loggedInUser'];
$user_id = $_SESSION['client_id'];

// Fetch user information (name, email & profile image) using client_id
$queryUser = "SELECT client_id, name, meter_no, status, email, profile_image, address FROM client_information WHERE client_id = '$user_id'";
$resultUser = mysqli_query($conn, $queryUser);

if ($resultUser && mysqli_num_rows($resultUser) > 0) {
    $userData = mysqli_fetch_assoc($resultUser);
    $name = $userData['name'];
    $email = $userData['email'];
    $profileImage = !empty($userData['profile_image']) ? $userData['profile_image'] : 'default.png';
    $client_id = $userData['client_id'];
    $address = $userData['address'];
    $meter_no = $userData['meter_no'];
    $status = $userData['status'];
} else {
    $name = "Unknown";
    $email = "No email found";
    $profileImage = "default.png";
    $client_id = "Unknown";
    $address = "Not provided";
    $meter_no = "Unknown";
    $status = "Unknown";
}

// Fetch total transactions (payments made by this user)
$totalTransactions = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE client_id = '$user_id'")
)['count'];

// Fetch total receipts (tokens issued to this user)
$totalReceipts = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as count FROM tokens WHERE client_id = '$user_id'")
)['count'];

// Fetch total amount paid by this user
$totalAmount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE client_id = '$user_id'")
)['total'] ?? 0;

// Fetch receipts
$receiptHTML = "";
$sql = "SELECT t.token_id, t.token_value, t.units, t.issue_date, p.amount 
        FROM tokens t
        JOIN payments p ON p.client_id = t.client_id
        WHERE t.client_id = ? 
        ORDER BY t.issue_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $receiptHTML .= "<table border='1' width='100%' cellpadding='8'>
    <tr>
        <th>Receipt No</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>";

    while ($row = $result->fetch_assoc()) {
        $receipt_no = "RCPT-" . str_pad($row['token_id'], 6, "0", STR_PAD_LEFT);
        $receiptHTML .= "<tr>
            <td>$receipt_no</td>
            <td>{$row['issue_date']}</td>
            <td>
                <a href='includes/view_receipt.php?token_id={$row['token_id']}' target='_blank'>View</a> |
                <a href='includes/delete_receipt.php?token_id=" . $row['token_id'] . "' onclick=\"return confirm('Are you sure you want to delete this receipt?');\">Delete</a>            
            </td>
        </tr>";
    }

    $receiptHTML .= "</table>";
} else {
    $receiptHTML = "<p>No receipts found yet.</p>";
}

$stmt->close();
?>

<?php
if (isset($_GET['popup'])) {
    $popup = htmlspecialchars($_GET['popup'], ENT_QUOTES, 'UTF-8');
    echo "<script>window.onload = function() { showPopup('$popup'); };</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaPay Client Portal</title>
    <!--Favicon-->
    <link rel="icon" type="image/png" href="images/icon.png">
    <!--styling-->
    <link rel="stylesheet" href="styles/style5.css">
    <!-- FontAwesome for icons -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">

</head>
<body>
    <!-- Topbar -->
    <div class="top-bar" id="topbar">
        <div class="left">
            <button class="navbar-toggler" id="sidebarToggle">
                <i class="fas fa-bars"></i> 
            </button>
        </div>
        <div class="right">
            <!-- Notification Icon -->
            <div class="notification-wrapper" style="position:relative; display:inline-block; margin-right:18px;">
                <a href="#faq" id="notification-link">
                    <i class="fas fa-bell" style="font-size: 24px; color: #007bff; cursor:pointer;"></i>
                    <?php
                        // Determine user identity for notification check
                        if (isset($_SESSION['client_id'])) {
                            $user_id = $_SESSION['client_id'];
                            $user_type = 'client';

                            // Fetch unread count for this specific user from thoughts_receiver
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

            <span>Hi, <?php echo htmlspecialchars($loggedInUser); ?></span>
            <div class="profile-dropdown">
                <?php if (!empty($profileImage)) { ?>
                    <img src="<?php echo $profileImage; ?>" alt="Profile Image" class="profile-icon" id="profile-toggle" style="width: 30px; height: 30px; border-radius: 50%;">
                <?php } else { ?>
                    <i class="fas fa-user-circle profile-icon" id="profile-toggle"></i>
                <?php } ?>
                <div class="dropdown-content" id="profile-dropdown">
                    <a class="dropdown-item" id="my-profile" href="javascript:void(0);">My Profile</a>
                    <a class="dropdown-item" href="includes/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <aside class="sidebar" id="sidebar">
        <div class="d-flex align-items-center mb-3">
            <img src="images/icon.png" alt="logo" class="logo"/>
            <h4 style="color: white;">Aquapay</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
            <li class="nav-item"><a class="nav-link" href="code.php">Dashboard</a></li>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fas fa-money-bill-wave me-2"></i> Payments</a>
                <ul class="dropdown-content list-unstyled ps-3">
                    <li><a class="nav-link" href="#" id="view-make-payment"><i class="fas fa-money-bill me-2"></i> Make Payments</a></li>
                    <li><a class="nav-link" href="#" id="view-transactions"><i class="fas fa-history me-2"></i> Transactions</a></li>
                    <li><a class="nav-link" href="#" id="view-print"><i class="fas fa-print me-2"></i>Receipt</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="view-meter-info"><i class="fas fa-info-circle me-2"></i> Meter Info</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#"><i class="fas fa-question-circle me-2"></i> Contact Us</a>
                <ul class="dropdown-content list-unstyled ps-3">
                    <li><a class="nav-link" href="#" id="view-contact"><i class="fas fa-envelope me-2"></i> Contact us</a></li>
                    <li><a class="nav-link" href="#" id="view-faq"><i class="fas fa-question me-2"></i> FAQ</a></li>
                </ul>
            </li>
        </ul>
    </aside>
    <div class="overlay" id="overlay"></div>

    <!-- Main Container -->
    <div class="main-container" id="main-container">
        <div class="hero-section text-dark" style="background: #d5d9deff; min-height: 100vh; padding-top: 40px;">
            <div class="container">
                <div class="row align-items-center mb-4">
                    
                    <div class="col-md-6">
                        <h4 style="font-weight:600;">Hello, <?php echo htmlspecialchars($loggedInUser); ?> Welcome Back !</h4>
                    </div>
                   
                    <div class="col-md-6 text-end">
                        <?php
                            // Example: $units = ...; $status = ...;
                            $accountStatus = ($status === 'active' && $units > 0) ? 'Active' : 'Inactive';
                            $statusColor = ($accountStatus === 'Active') ? 'success' : 'danger';
                        ?>
                        <span class="badge bg-<?php echo $statusColor; ?>" style="font-size:1rem;">
                            <?php echo $accountStatus; ?>
                        </span>
                    </div>
                </div>
        
                <div class="row mb-3">
                    <div class="col-12">
                        <h1 class="display-5 mb-2" style="font-weight:700;">Welcome to AquaPay</h1>
                        <p class="lead mb-2" style="font-size:1.1rem;">Manage your water bills easily and securely from anywhere, anytime.</p>
                    </div>
                </div>
        
                <div class="dashboard-cards d-flex justify-content-center align-items-center mb-4" style="gap: 32px;">
                    <div class="card shadow-sm dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Transactions</h6>
                            <p class="card-text display-6">
                                <?php echo $totalTransactions ?? 0; ?>
                            </p>
                        </div>
                    </div>
                    <div class="card shadow-sm dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Receipts</h6>
                            <p class="card-text display-6">
                                <?php echo $totalReceipts ?? 0; ?>
                            </p>
                        </div>
                    </div>
                    <div class="card shadow-sm dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Total Amount</h6>
                            <p class="card-text display-6">
                                UGX <?php echo number_format($totalAmount); ?>
                            </p>
                        </div>
                    </div>
                    <div class="card shadow-sm dashboard-card">
                        <div class="card-body text-center">
                            <h6 class="card-title">Meter Status</h6>
                            <?php
                                $accountStatus = ($status === 'active' && $units > 0) ? 'Active' : 'Inactive';
                                $statusColor = ($accountStatus === 'Active') ? 'success' : 'danger';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?>" style="font-size:1.1rem;">
                                <?php echo $accountStatus; ?>
                            </span>
                        </div>
                    </div>
                </div>
        
                <!-- Charts: Pie and Bar -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">Transactions Overview</h6>
                                <canvas id="transactionsPieChart" height="180"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title">Receipts Overview</h6>
                                <canvas id="receiptsBarChart" height="180"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div id="faq" class="row mb-4 justify-content-center">
                    <div class="col-lg-12 col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-body text-start">
                                <h2 class="mt-3 text-center">Community Thoughts & FAQs</h2>
                                <ul id="faq-list" style="list-style:none; padding:0;">
                                    <?php
                                    $thoughts = mysqli_query($conn, "
                                        SELECT t.*, 
                                            (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) AS likes,
                                            (SELECT profile_image FROM client_information WHERE client_id = t.sender_id LIMIT 1) AS client_profile,
                                            (SELECT profile_image FROM admin_users WHERE admin_id = t.sender_id LIMIT 1) AS admin_profile,
                                            tr.is_read
                                        FROM thoughts t
                                        JOIN thoughts_receivers tr ON tr.thought_id = t.id
                                        WHERE tr.receiver_id = '$user_id' AND tr.receiver_type = '$user_type'

                                        UNION

                                        SELECT t.*, 
                                            (SELECT COUNT(*) FROM thought_likes WHERE thought_id = t.id) AS likes,
                                            (SELECT profile_image FROM client_information WHERE client_id = t.sender_id LIMIT 1) AS client_profile,
                                            (SELECT profile_image FROM admin_users WHERE admin_id = t.sender_id LIMIT 1) AS admin_profile,
                                            NULL AS is_read
                                        FROM thoughts t
                                        WHERE t.sender_id = '$user_id' AND t.sender_type = '$user_type'

                                        ORDER BY created_at DESC
                                    ");

                                    while ($row = mysqli_fetch_assoc($thoughts)) {
                                        $profileImage = !empty($row['client_profile']) ? $row['client_profile'] : (!empty($row['admin_profile']) ? $row['admin_profile'] : 'default.png');
                                        echo '<li class="thought-card" style="border-bottom:1px solid #eee; margin-bottom:14px; padding-bottom:10px;">';
                                        echo '<div style="display:flex;align-items:flex-start;position:relative;">';
                                        // Profile image
                                        echo '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile" style="width:38px;height:38px;border-radius:50%;margin-right:10px;">';
                                        // Name and text
                                        echo '<div style="flex:1;">';
                                        echo '<span style="font-weight:600;vertical-align:middle;">' . htmlspecialchars($row['username']) . '</span>';
                                        echo '<div style="margin:2px 0 0 0;">' . nl2br(htmlspecialchars($row['content'])) . '</div>';
                                        echo '</div>';
                                        // Date on the extreme right
                                        echo '<div style="position:absolute;top:0;right:0;font-size:13px;color:#888;">' . date('M d, Y H:i', strtotime($row['created_at'])) . '</div>';
                                        echo '</div>';
                                        // Like and comment icons (left aligned)
                                        echo '<div style="margin-left:48px;margin-top:4px;text-align:left;">';
                                        echo '<form method="POST" action="includes/like_thought.php" style="display:inline;">
                                                <input type="hidden" name="thought_id" value="' . $row['id'] . '">
                                                <button type="submit" style="border:none;background:none;color:#007bff;cursor:pointer;padding-right:6px;">
                                                    <i class="fas fa-thumbs-up"></i>
                                                    <span style="font-size:12px; color:#000;">' . $row['likes'] . '</span>
                                                </button>
                                            </form>';
                                        echo '<button type="button" class="comment-toggle-btn" style="border:none;background:none;color:#007bff;cursor:pointer;padding-left:2px;" onclick="toggleCommentBox(' . $row['id'] . ')">
                                                <i class="fas fa-comment"></i>
                                            </button>';
                                        echo '</div>';
                                        // Comments
                                        $comments = mysqli_query($conn, "SELECT * FROM thought_comments WHERE thought_id = {$row['id']} ORDER BY created_at ASC");
                                        echo '<div style="margin-left:48px;margin-top:6px;">';
                                        while ($c = mysqli_fetch_assoc($comments)) {
                                            echo '<div style="display:flex;align-items:center;margin-bottom:4px;">';
                                            echo '<div style="border-radius:8px;padding:4px 10px;">';
                                            echo '<strong>' . htmlspecialchars($c['username']) . ':</strong> ' . htmlspecialchars($c['comment']);
                                            echo '<span style="font-size:11px; color:#aaa; margin-left:8px;">' . date('M d, H:i', strtotime($c['created_at'])) . '</span>';
                                            echo '</div></div>';
                                        }
                                        // Hidden comment box
                                        echo '<div id="comment-box-' . $row['id'] . '" style="display:none;margin-top:6px;">';
                                        echo '<form method="POST" action="includes/comment_thought.php" style="position:relative;">';
                                        echo '<input type="hidden" name="thought_id" value="' . $row['id'] . '">';

                                        echo '<input type="text" name="comment" placeholder="Add a comment..." required 
                                                style="width:100%; padding:6px 36px 6px 10px; border-radius:4px; border:1px solid #ccc;">';

                                        echo '<button type="submit" style="position:absolute;top:50%;right:6px;transform:translateY(-50%);background:none;border:none;color:#007bff;font-size:16px;cursor:pointer;strong: 20px;
                                            ">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>';

                                        echo '</form>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</li>';
                                    }
                                    ?>
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
        </div>

        <!-- Profile Section -->
        <section id="profile-section" class="aqua-section" style="display: none;">
            <div class="container1 ">
                <div class="row">
                    <!-- Left Section: User Information -->
                    <div class="col-md-6 text-center">
                        <div class="profile-image ms-4">
                            <!-- Display profile image or default icon -->
                            <?php if (!empty($profileImage)) { ?>
                                    <img src="<?php echo $profileImage; ?>" alt="Profile Image" 
                                    class="img-fluid rounded-circle" 
                                    style="width: 180px; height: 180px; object-fit: cover; object-position: center;">
                            <?php } else { ?>
                                <i class="fa fa-user-circle" style="font-size: 150px; color: gray;"></i>
                            <?php } ?>
                        </div>
                        <div class="user-info">
                            <p><strong>Client Number:</strong> <span><?php echo $client_id; ?></span></p>    
                            <p><strong>Username:</strong> <span><?php echo $name; ?></span></p>
                            <p><strong>Email:</strong> <span><?php echo $email; ?></span></p>
                            <p><strong>Address:</strong> <span><?php echo $address; ?></span></p>
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

        <!-- Make Payment Section -->
        <section id="make-payment" class="aqua-section" style="display: none;">
        <h2>Make a Payment</h2>
            <form action="includes/pay_now.php" method="POST">
                <label>Phone Number:</label>
                <input type="text" name="phone" required placeholder="e.g. 0771234567">
                <label>Amount (UGX):</label>
                <input type="number" name="amount" required min="1000" step="100">
                <button type="submit">Pay Now</button>
            </form>
        </section>

        <!-- Transactions Section -->
        <section id="transactions" class="aqua-section" style="display: none;">
            <h2>Payment History</h2>
            <table>
                <thead>
                    <tr><th>Date of Payment</th><th>Amount Paid</th><th>Status</th></tr>
                </thead>
                <tbody id="transaction-list">
                    <?php
                    $sql_payments = "SELECT payment_date, amount, payment_status FROM payments WHERE client_id = ? ORDER BY payment_date DESC";
                    $stmt_payments = $conn->prepare($sql_payments);
                    $stmt_payments->bind_param("i", $client_id);
                    $stmt_payments->execute();
                    $result_payments = $stmt_payments->get_result();

                    if ($result_payments->num_rows > 0) {
                        while ($row = $result_payments->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['payment_date']) . "</td>
                                <td>UGX " . number_format($row['amount']) . "</td>
                                <td>" . htmlspecialchars($row['payment_status']) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No payment history found.</td></tr>";
                    }
                    $stmt_payments->close();
                    ?>
                </tbody>
            </table>
        </section>

        <!-- Print Receipt Section -->
        <section id="print-receipt" class="aqua-section" style="display: none;">
            <h2>Receipts</h2>
            <div id="receipt-list">
                <?php echo $receiptHTML; ?>
                <div style="margin-top: 20px;">
                    <!-- <button onclick="window.print()">Print Selected</button> -->
                </div>
            </div> 
        </section>

        <!-- Meter Info Section -->
        <section id="meter-info" class="aqua-section" style="display: none;">
            <h2>Meter Information</h2>
            <p>Meter No: <span id="meter-number"><?php echo htmlspecialchars($meter_no); ?></span></p>
            <p>Status: <span id="meter-status"><?php echo htmlspecialchars($status); ?></span></p>
            <p>Owner: <span id="meter-owner"><?php echo htmlspecialchars($name); ?></span></p>
            <p>Address: <span id="meter-address"><?php echo htmlspecialchars($address); ?></span></p>
        </section>

        <!-- Contact Us Section -->
        <section id="contact-us" class="aqua-section" style="display: none;">
            <h2>Contact Us</h2>
            <div class="contact-wrapper">
                <form action="includes/send_message.php" method="POST" enctype="multipart/form-data" class="contact-form">
                    <input type="text" name="name" placeholder="Your name" required>
                    <input type="email" name="email" placeholder="Your email" required>
                    <input type="text" name="subject" placeholder="Subject" required>
                    <textarea name="message" placeholder="Your message" required></textarea>
                    <label for="image">Attach image (optional):</label>
                    <input type="file" name="image" accept="image/*">
                    <button type="submit">Send Message</button>
                </form>

                <div class="contact-info">
                    <p><strong>Email:</strong> support@aquapay.com</p>
                    <p><strong>Phone:</strong> +256 783 953 940</p>
                    <p><strong>Address:</strong> Plot 10, Gulu City, Uganda</p>
                    <a href="https://wa.me/256787638998" target="_blank">
                        <img src="images/whatsaap.jpeg" alt="Chat on WhatsApp" style="width: 50px; margin-top: 10px;">
                    </a>
                </div>
            </div>
        </section>


        <!-- FAQ Section -->
        <section id="faq1" class="aqua-section" style="display: none;">
            <div class="container py-5">
                <!-- About AquaPay Section -->
                <div class="row align-items-center">
                    <div class="col-md-6 mb-4">
                        <h2>What is AquaPay?</h2>
                        <p style="font-size: 1.1rem; line-height: 1.7;">
                            AquaPay is a smart platform designed to simplify and digitalize your water bill payments.
                            Pay securely through Mobile Money, receive instant payment tokens, and track your transactions in real-time.
                            AquaPay makes water management as easy as paying for electricity (like Yaka).
                        </p>
                    </div>

                    <div class="col-md-6 mb-4">
                        <h2>Key Features</h2>
                        <ul style="font-size: 1.1rem; line-height: 2;">
                            <li>Instant Payment Token Generation</li>
                            <li>Real-Time Transaction Tracking</li>
                            <li>Secure Mobile Money and Bank Payments</li>
                            <li>Accessible Anywhere, Anytime</li>
                            <li>Transparent and Low Transaction Fees</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Token Popup Container -->
    <div id="token-popup" class="token-popup">
        <div class="popup-content">
            <span class="close-popup" onclick="closePopup()">&times;</span>
            <p id="popup-message">Token or error message will appear here</p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" id="footer">
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
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

    function toggleCommentBox(id) {
        var el = document.getElementById('comment-box-' + id);
            if (el.style.display === 'none' || el.style.display === '') {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Pie chart: Payments vs Receipts
        var ctxPie = document.getElementById('transactionsPieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Payments', 'Receipts'],
                datasets: [{
                    data: [<?php echo $totalTransactions ?? 0; ?>, <?php echo $totalReceipts ?? 0; ?>],
                    backgroundColor: ['#007bff', '#28a745']
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    
        // Bar chart: Payments vs Receipts
        var ctxBar = document.getElementById('receiptsBarChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Payments', 'Receipts'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $totalTransactions ?? 0; ?>, <?php echo $totalReceipts ?? 0; ?>],
                    backgroundColor: ['#007bff', '#28a745']
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileToggle = document.getElementById('profile-toggle');
            const dropdownContent = document.getElementById('profile-dropdown');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            profileToggle.addEventListener('click', () => {
                dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.right')) {
                    dropdownContent.style.display = 'none';
                }
            });

            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });

            overlay.addEventListener('click', function () {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });


        document.addEventListener("DOMContentLoaded", function () {
            const dashboardOverview = document.querySelector(".hero-section");
            const sections = document.querySelectorAll(".aqua-section");

            function toggleSections(sectionId) {
                // Hide all other sections
                sections.forEach(section => section.style.display = "none");
                // Hide dashboard
                dashboardOverview.style.display = "none";
                // Show the target section
                const target = document.getElementById(sectionId);
                if (target) target.style.display = "block";
            }

            // Show Dashboard when dashboard link is clicked
            document.querySelector(".nav-link[href='code.php']").addEventListener("click", function (e) {
                e.preventDefault();
                dashboardOverview.style.display = "flex";
                sections.forEach(section => section.style.display = "none");
            });

            // Payment submenu
            document.getElementById("view-make-payment").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("make-payment");
            });

            document.getElementById("view-transactions").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("transactions");
            });

            document.getElementById("view-print").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("print-receipt");
            });

            // Meter Info
            document.getElementById("view-meter-info").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("meter-info");
            });

            // Contact submenu
            document.getElementById("view-contact").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("contact-us");
            });

            document.getElementById("view-faq").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("faq1");
            });  
            
            // Profile section under profile dropdown
            document.getElementById("my-profile").addEventListener("click", function (e) {
                e.preventDefault();
                toggleSections("profile-section");
            });
        });

        //My profile nav toggles 
        function showSection(section) {
        // Hide all sections
        const sections = document.querySelectorAll('.section-content');
        sections.forEach((section) => {
            section.style.display = 'none';
        });

        // Remove active class from all navbar buttons
        const buttons = document.querySelectorAll('.nav-btn');
        buttons.forEach((btn) => {
            btn.classList.remove('active');
        });

        // Show the clicked section
        document.getElementById(section).style.display = 'block';

        // Add active class to the clicked button
        const activeBtn = document.querySelector(`.nav-btn[onclick="showSection('${section}')"]`);
        activeBtn.classList.add('active');
        }


        // profile image upload functionality
        document.addEventListener("DOMContentLoaded", function () {
            const profileImg = document.getElementById("profile_image-img");
            const defaultProfileIcon = document.getElementById("default-profile_image");
            const fileInput = document.getElementById("profile_image-input");
            const uploadButton = document.getElementById("upload-profile_image");
            let selectedFile = null;
        
            // Clicking the profile image or default icon opens the file input
            [profileImg, defaultProfileIcon].forEach(element => {
                if (element) element.addEventListener("click", () => fileInput.click());
            });
        
            // When a file is selected, preview it only
            fileInput.addEventListener("change", function () {
                if (fileInput.files.length === 0) return; // No file selected
        
                const file = fileInput.files[0];
                const allowedTypes = ["image/jpeg", "image/png", "image/jpg"];
                if (!allowedTypes.includes(file.type)) {
                    alert("Only JPG, JPEG, and PNG files are allowed.");
                    fileInput.value = '';
                    return;
                }
        
                selectedFile = file; // Save for upload
        
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (profileImg) {
                        profileImg.src = e.target.result;
                    } else {
                        const newImg = document.createElement("img");
                        newImg.src = e.target.result;
                        newImg.className = "file-view";
                        newImg.id = "profile_image-img";
                        defaultProfileIcon.replaceWith(newImg);
                        // Re-attach click event for new image
                        newImg.addEventListener("click", () => fileInput.click());
                    }
                };
                reader.readAsDataURL(file);
            });
        
            // Only upload when Upload button is clicked
            uploadButton.addEventListener("click", function () {
                if (!selectedFile) {
                    alert("Please select an image first.");
                    return;
                }
                let formData = new FormData();
                formData.append("profile_image", selectedFile);
        
                fetch("includes/uploadClient_profile.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(result => {
                    alert(result); // Show response message
                    location.reload(); // Refresh page to reflect the new profile image
                })
                .catch(error => {
                    console.error("Upload error:", error);
                });
            });
        });
    

        document.querySelector('input[name="image"]').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert("Only JPG, PNG, and GIF files are allowed.");
                    event.target.value = '';
                } else if (file.size > 2 * 1024 * 1024) {
                    alert("Image must be less than 2MB.");
                    event.target.value = '';
                } else {
                    const preview = document.createElement("img");
                    preview.src = URL.createObjectURL(file);
                    preview.style.maxWidth = "100px";
                    document.querySelector(".contact-form").appendChild(preview);
                }             
                
            }
        });

        function showPopup(message) {
            document.getElementById('popup-message').innerText = message;
            document.getElementById('token-popup').style.display = 'block';
        }

        function closePopup() {
            document.getElementById('token-popup').style.display = 'none';
        }

        document.addEventListener("DOMContentLoaded", function () {
            if (window.location.hash === "#print-receipt") {
                document.getElementById("print-receipt").style.display = "block";
            }
        });
       
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const sidebarLinks = sidebar.querySelectorAll('.nav-link');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    }
                });
            });
        });
    </script>
</body>
</html>