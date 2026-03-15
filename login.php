<?php
session_start();
include "config/db.php";

$error = "";
$success = "";

if (isset($_SESSION['login_success'])) {
    $success = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === "" || $password === "") {
        $error = "Please fill in all fields.";
    } else {
        $safeEmail = $conn->real_escape_string($email);

        $result = $conn->query("SELECT * FROM users WHERE email='$safeEmail'");
        $user = $result ? $result->fetch_assoc() : null;

        if (!$user) {
            $error = "Invalid email or password.";
        } elseif (!password_verify($password, $user['password'])) {
            $error = "Invalid email or password.";
        } elseif ((int)$user['is_verified'] !== 1) {
            $error = "Please verify your email before logging in.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = $user['email'];

            header("Location: index.php");
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
    <title>Login - TradeSphere</title>
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

        .forgot-link{
            text-align: right;
            margin-top: 8px;
            margin-bottom: 18px;
        }

        .forgot-link a{
            text-decoration: none;
            color: #0ea5e9;
            font-size: 14px;
        }

        .forgot-link a:hover{
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Login</h2>
        <p class="helper">Login to continue buying, selling, and managing your activity.</p>

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

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                    <span class="toggle-eye" onclick="togglePassword('loginPassword', this)">👁</span>
                </div>
            </div>

            <div class="forgot-link">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>

        </form>

        <p style="margin-top:16px; text-align:center;">
            Don’t have an account? <a href="register.php">Create one here</a>
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