<?php
session_start();
include "config/db.php";
include "includes/mail_helper.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $passwordRaw = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($name === "" || $email === "" || $passwordRaw === "" || $confirmPassword === "") {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($passwordRaw !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($passwordRaw) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safeEmail = $conn->real_escape_string($email);

        $check = $conn->query("SELECT * FROM users WHERE email='$safeEmail'");

        if ($check && $check->num_rows > 0) {
            $existing = $check->fetch_assoc();

            if ((int)$existing['is_verified'] === 1) {
                $error = "An account with this email already exists.";
            } else {
                $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
                $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                $expiresAt = date("Y-m-d H:i:s", time() + 600);

                $conn->query("
                    UPDATE users
                    SET name='$safeName',
                        password='" . password_hash($passwordRaw, PASSWORD_DEFAULT) . "',
                        email_otp='$otpHash',
                        otp_expires_at='$expiresAt'
                    WHERE email='$safeEmail'
                ");

                $mailSent = sendOtpEmail($email, $name, $otp);

                if ($mailSent) {
                    $_SESSION['pending_email'] = $email;
                    header("Location: verify_otp.php");
                    exit();
                } else {
                    $error = "Could not send OTP email. Please try again.";
                }
            }
        } else {
            $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);
            $otp = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expiresAt = date("Y-m-d H:i:s", time() + 600);

            $sql = "
                INSERT INTO users (name, email, password, is_verified, email_otp, otp_expires_at)
                VALUES ('$safeName', '$safeEmail', '$passwordHash', 0, '$otpHash', '$expiresAt')
            ";

            if ($conn->query($sql)) {
                $mailSent = sendOtpEmail($email, $name, $otp);

                if ($mailSent) {
                    $_SESSION['pending_email'] = $email;
                    header("Location: verify_otp.php");
                    exit();
                } else {
                    $error = "Registration saved, but OTP email could not be sent.";
                }
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .password-wrap{
            position: relative;
        }

        .password-wrap input{
            padding-right: 50px;
        }

        .toggle-eye{
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            font-size: 18px;
        }
    </style>
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Create Account</h2>
        <p class="helper">Register to start buying and selling on TradeSphere.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" id="registerPassword" name="password" placeholder="Create a password" required>
                    <span class="toggle-eye" onclick="togglePassword('registerPassword', this)">👁</span>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-wrap">
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm password" required>
                    <span class="toggle-eye" onclick="togglePassword('confirmPassword', this)">👁</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>

        <p style="margin-top:16px; text-align:center;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
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