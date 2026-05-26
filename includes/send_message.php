<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$mailConfig = require __DIR__ . '/mail_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $messageBody = htmlspecialchars($_POST['message']);

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $mailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailConfig['username'];
        $mail->Password   = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['secure'];
        $mail->Port       = $mailConfig['port'];

        $mail->setFrom($email, $name);
        $mail->addAddress($mailConfig['support_inbox']); 

        // Attach image if available
        if (!empty($_FILES['image']['tmp_name'])) {
            $mail->addAttachment($_FILES['image']['tmp_name'], $_FILES['image']['name']);
        }

        $mail->isHTML(false);
        $mail->Subject = "Contact Form: " . $subject;
        $mail->Body    = "You have received a message from $name <$email>:\n\n$messageBody";

        $mail->send();
        header("Location: ../userdashboard.php?popup=✅ Email sent successfully!");
        exit;
    } catch (Exception $e) {
        header("Location: ../userdashboard.php?popup=❌ Email failed. {$mail->ErrorInfo}");
        exit;
    }
} else {
    header("Location: ../userdashboard.php?popup=❌ Invalid request.");
    exit;
}
?>
