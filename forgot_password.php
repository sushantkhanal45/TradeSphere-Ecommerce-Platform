<?php
include "config/db.php";
include "includes/mail_helper.php";

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);

    if ($email === "") {
        $error = "Please enter your email address.";
    } else {
        $safeEmail = $conn->real_escape_string($email);

        $result = $conn->query("SELECT * FROM users WHERE email='$safeEmail'");
        $user = $result ? $result->fetch_assoc() : null;

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date("Y-m-d H:i:s", time() + 1800);

            $safeToken = $conn->real_escape_string($token);

            $conn->query("
                UPDATE users
                SET reset_token='$safeToken',
                    reset_expires_at='$expiresAt'
                WHERE email='$safeEmail'
            ");

            $resetLink = "http://localhost/TradeSphere/reset_password.php?token=" . urlencode($token);

            sendResetEmail($user['email'], $user['name'], $resetLink);
        }

        $success = "If that email exists, password reset instructions have been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Forgot Password</h2>
        <p class="helper">
            Enter your email address and we will send password reset instructions.
        </p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </div>
        </form>

        <p style="margin-top:16px; text-align:center;">
            <a href="login.php">Back to Login</a>
        </p>
    </div>
</div>

</body>
</html>