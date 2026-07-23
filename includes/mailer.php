<?php
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetEmail(string $recipientEmail, string $recipientName, string $resetLink): bool
{
    $mail = new PHPMailer(true);
    try {
        // Server basic configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        
        $senderEmail = "tehjingjing2006@gmail.com";
        $mail->Username = $senderEmail;
        $mail->Password = "lzyc kgof mzzw uvra";

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'socket' => [
                'bindto' => '0:0'
            ]
        ];

        // Email sender & recipient
        $mail->setFrom($senderEmail, 'School Portal System');
        $mail->addAddress($recipientEmail, $recipientName);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Student Portal';
        $mail->Body = "
            <h2>Password Reset Request</h2>
            <p>Hi {$recipientName},</p>
            <p>You requested a password reset for your student account.</p>
            <p>This link is valid for 1 hour only:</p>
            <p><a href='$resetLink'>$resetLink</a></p>
            <p>If you did not request this, ignore this email.</p>
        ";
        $mail->AltBody = "Copy this link to reset your password: $resetLink";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errMsg = "PHPMailer Info: ".$mail->ErrorInfo." | PHP Exception: ".$e->getMessage();
        error_log($errMsg);
        $GLOBALS['mailDebug'] = $errMsg;
        return false;
    }
}
