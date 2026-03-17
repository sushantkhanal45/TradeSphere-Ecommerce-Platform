<?php
session_start();
include "../config/db.php";

$error = "";

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
            $error = "Please verify this admin email before logging in.";
        } elseif (!isset($user['role']) || $user['role'] !== 'admin') {
            $error = "You are not authorized as admin.";
        } else {
            session_regenerate_id(true);
            $_SESSION['admin'] = $user['email'];
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];

            header("Location: dashboard.php");
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
    <title>Admin Login - TradeSphere</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .password-wrap{position:relative;}
        .password-wrap input{padding-right:50px;}
        .toggle-eye{
            position:absolute;
            right:14px;
            top:50%;
            transform:translateY(-50%);
            cursor:pointer;
            user-select:none;
            font-size:18px;
        }
    </style>
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Admin Login</h2>
        <p class="helper">Login to manage TradeSphere.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter admin email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrap">
                    <input type="password" id="adminPassword" name="password" placeholder="Enter password" required>
                    <span class="toggle-eye" onclick="togglePassword('adminPassword', this)">👁</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login as Admin</button>
            </div>
        </form>
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