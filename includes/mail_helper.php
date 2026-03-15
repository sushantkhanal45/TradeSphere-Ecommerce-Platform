<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendMailMessage($toEmail, $toName, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hellwrld0045@gmail.com';
        $mail->Password   = '0123456789abcde#';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('hellwrld0045@gmail.com', 'TradeSphere');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendOtpEmail($email, $name, $otp) {
    $subject = "TradeSphere Email Verification OTP";
    $body = "
        <h2>TradeSphere Email Verification</h2>
        <p>Hello <strong>{$name}</strong>,</p>
        <p>Your OTP for verifying your TradeSphere account is:</p>
        <h1 style='letter-spacing: 4px;'>{$otp}</h1>
        <p>This OTP will expire in 10 minutes.</p>
    ";

    return sendMailMessage($email, $name, $subject, $body);
}

function sendResetEmail($email, $name, $resetLink) {
    $subject = "TradeSphere Password Reset";
    $body = "
        <h2>TradeSphere Password Reset</h2>
        <p>Hello <strong>{$name}</strong>,</p>
        <p>Click the link below to reset your password:</p>
        <p><a href='{$resetLink}'>{$resetLink}</a></p>
        <p>This link will expire in 30 minutes.</p>
    ";

    return sendMailMessage($email, $name, $subject, $body);
}
?>