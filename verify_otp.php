<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];
$safeEmail = $conn->real_escape_string($email);

$error = "";
$success = "";

if (isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);

    $result = $conn->query("SELECT * FROM users WHERE email='$safeEmail'");
    $user = $result ? $result->fetch_assoc() : null;

    if (!$user) {
        $error = "User not found.";
    } else {
        $expiresAt = strtotime($user['otp_expires_at']);

        if (time() > $expiresAt) {
            $error = "OTP has expired. Please register again or request a new OTP.";
        } elseif (!password_verify($otp, $user['email_otp'])) {
            $error = "Invalid OTP.";
        } else {
            $conn->query("
                UPDATE users
                SET is_verified=1, email_otp=NULL, otp_expires_at=NULL
                WHERE email='$safeEmail'
            ");

            unset($_SESSION['pending_email']);
            $_SESSION['verification_success'] = "Email verified successfully. You can now log in.";
            header("Location: login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Verify Your Email</h2>
        <p class="helper">Enter the 6-digit OTP sent to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>OTP Code</label>
                <input type="text" name="otp" maxlength="6" placeholder="Enter 6-digit OTP" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="verify_otp" class="btn btn-primary">Verify OTP</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>