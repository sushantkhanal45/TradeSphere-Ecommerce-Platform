<?php
session_start();
include "config/db.php";
include "includes/mail_helper.php";

$error = "";
$success = "";

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];
$safeEmail = $conn->real_escape_string($email);

/* VERIFY OTP */
if (isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);

    $result = $conn->query("SELECT * FROM users WHERE email='$safeEmail' LIMIT 1");
    $user = $result ? $result->fetch_assoc() : null;

    if (!$user) {
        $error = "User not found.";
    } elseif (empty($user['otp_expires_at']) || strtotime($user['otp_expires_at']) < time()) {
        $error = "OTP has expired.";
    } elseif (empty($user['email_otp']) || !password_verify($otp, $user['email_otp'])) {
        $error = "Invalid OTP.";
    } else {
        $conn->query("
            UPDATE users
            SET is_verified = 1,
                email_otp = NULL,
                otp_expires_at = NULL
            WHERE email='$safeEmail'
        ");

        unset($_SESSION['pending_email']);
        $_SESSION['login_success'] = "Email verified successfully. You can now login.";

        header("Location: login.php");
        exit();
    }
}

/* RESEND OTP */
if (isset($_POST['resend_otp'])) {
    $result = $conn->query("SELECT name FROM users WHERE email='$safeEmail' LIMIT 1");
    $user = $result ? $result->fetch_assoc() : null;

    if ($user) {
        $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date("Y-m-d H:i:s", time() + 600);

        $conn->query("
            UPDATE users
            SET email_otp='$otpHash',
                otp_expires_at='$expiresAt'
            WHERE email='$safeEmail'
        ");

        sendOtpEmail($email, $user['name'], $otp);
        $success = "A new OTP has been sent to your email.";
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Email Verification</h2>
        <p class="helper">Enter the OTP sent to your email.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Enter OTP</label>
                <input type="text" name="otp" maxlength="6" placeholder="6 digit OTP" required>
            </div>

            <button type="submit" name="verify_otp" class="btn btn-primary">
                Verify OTP
            </button>
        </form>

        <br>

        <form method="POST">
            <button type="submit" name="resend_otp" class="btn">
                Resend OTP
            </button>
        </form>

        <br>

        <a href="register.php" class="btn btn-secondary">
            Back to Register
        </a>
    </div>
</div>

</body>
</html>