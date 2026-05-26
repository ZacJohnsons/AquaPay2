<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require '../includes/db.php';

$mailConfig = require __DIR__ . '/mail_config.php';

if (isset($_POST['submit'])) {
    $email = htmlspecialchars($_POST['email']);

    // Use MySQLi Prepared Statement
    $stmt = $conn->prepare("SELECT * FROM client_information WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Save token + expiry using mysqli
        $update = $conn->prepare("UPDATE client_information SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        $resetLink = "http://localhost/AquaPay/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['secure'];
            $mail->Port = $mailConfig['port'];

            $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
            $mail->addAddress($email); 
            $mail->isHTML(true);
            $mail->Subject = "AquaPay Password Reset";
            $mail->Body = "
                <p>Hi,</p>
                <p>We received a request to reset your password. Click the link below to set a new password:</p>
                <a href='$resetLink'>$resetLink</a>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, just ignore this email.</p>
            ";

            $mail->send();

            // Log the reset request
            $log = $conn->prepare("INSERT INTO system_log (user_email, action, status, message) VALUES (?, 'Password Reset Request', 'Success', 'Reset link sent to user.')");
            $log->bind_param("s", $email);
            $log->execute();

            header("Location: ../reset_password.php?token=$token");
            exit;
        } catch (Exception $e) {
            $error = $mail->ErrorInfo;
            $log = $conn->prepare("INSERT INTO system_log (user_email, action, status, message) VALUES (?, 'Password Reset Request', 'Failed', ?)");
            $log->bind_param("ss", $email, $error);
            $log->execute();

            header("Location: ../forgot_password.php?popup=❌ Failed to send email.");
            exit;
        }
    } else {
        header("Location: ../forgot_password.php?popup=❌ Email not found.");
        exit;
    }
}
?>
