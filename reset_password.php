<?php
include "config/db.php";

$error = "";
$success = "";

if (!isset($_GET['token']) || trim($_GET['token']) === "") {
    die("Invalid reset link.");
}

$token = trim($_GET['token']);
$safeToken = $conn->real_escape_string($token);

$result = $conn->query("SELECT * FROM users WHERE reset_token='$safeToken'");
$user = $result ? $result->fetch_assoc() : null;

if (!$user) {
    die("Invalid or expired reset link.");
}

if (strtotime($user['reset_expires_at']) < time()) {
    die("This reset link has expired.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password === "" || $confirmPassword === "") {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        $conn->query("
            UPDATE users
            SET password='$newHash', reset_token=NULL, reset_expires_at=NULL
            WHERE id=" . (int)$user['id']
        ");

        $success = "Password reset successfully. You can now log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-wrap{position:relative;}
        .password-wrap input{padding-right:50px;}
        .toggle-eye{
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            cursor:pointer; user-select:none; font-size:18px;
        }
    </style>
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Reset Password</h2>
        <p class="helper">Enter your new password below.</p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
            <div style="text-align:center; margin-top:16px;">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrap">
                        <input type="password" id="newPassword" name="password" placeholder="Enter new password" required>
                        <span class="toggle-eye" onclick="togglePassword('newPassword', this)">👁</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="password-wrap">
                        <input type="password" id="confirmNewPassword" name="confirm_password" placeholder="Confirm new password" required>
                        <span class="toggle-eye" onclick="togglePassword('confirmNewPassword', this)">👁</span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<script>
function togglePassword(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈";
    } else {
        input.type = "password";
        icon.textContent = "👁";
    }
}
</script>
</body>
</html>