<?php
/**
 * Contact Form Handler (Local + Live)
 * Uses SMTP via PHPMailer for live server
 * Logs to a file when running locally (XAMPP)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// require 'vendor/autoload.php'; // Composer autoload for PHPMailer
require __DIR__ . '/../vendor/autoload.php';


$receiving_email_address = 'elonnim@outlook.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize form inputs
    $name    = strip_tags(trim($_POST['name']));
    $email   = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = strip_tags(trim($_POST['subject']));
    $message = trim($_POST['message']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please fill in all fields.'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email address.'
        ]);
        exit;
    }

    $email_content = "Name: $name\nEmail: $email\nSubject: $subject\nMessage:\n$message\n";

    // Detect if running locally (XAMPP typically has hostname "localhost")
    $is_local = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

    if ($is_local) {
        // Local environment: log to file
        $log_file = __DIR__ . '/contact_log.txt';
        if (file_put_contents($log_file, $email_content . "\n-----------------\n", FILE_APPEND)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Your message has been logged instead of sent (local test).'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to write log. Check folder permissions.'
            ]);
        }
    } else {
        // Live environment: send email via PHPMailer SMTP
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.office365.com'; // Outlook SMTP
            $mail->SMTPAuth   = true;
            $mail->Username   = 'elonnim@outlook.com';
            $mail->Password   = 'Elon@2024#';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom($email, $name);
            $mail->addAddress($receiving_email_address);

            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();

            echo json_encode([
                'status' => 'success',
                'message' => 'Your message has been sent. Our team will contact you shortly.'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Mailer Error: ' . $mail->ErrorInfo
            ]);
        }
    }

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request.'
    ]);
}
?>
