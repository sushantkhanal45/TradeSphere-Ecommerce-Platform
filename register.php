<?php
include "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $passwordRaw = $_POST['password'];

    $safeName = $conn->real_escape_string($name);
    $safeEmail = $conn->real_escape_string($email);
    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

    $check = $conn->query("SELECT * FROM users WHERE email='$safeEmail'");
    if ($check && $check->num_rows > 0) {
        $error = "An account with this email already exists.";
    } else {
        if ($conn->query("INSERT INTO users (name, email, password) VALUES ('$safeName', '$safeEmail', '$password')")) {
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
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
</head>
<body>

<div class="form-page">
    <div class="form-card">
        <h2>Create Account</h2>
        <p class="helper">Register to start buying and selling on TradeSphere.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
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
                <input type="password" name="password" placeholder="Create a password" required>
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

</body>
</html>