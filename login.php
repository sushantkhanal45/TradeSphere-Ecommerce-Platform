<?php
session_start();
include "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    $user = $result ? $result->fetch_assoc() : null;

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['email'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Login</h2>
        <p class="helper">Login to continue buying, selling, and managing your activity.</p>

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
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>

        <p class="form-note">
            Don’t have an account? <a href="register.php">Create one here</a>
        </p>
    </div>
</div>

</body>
</html>