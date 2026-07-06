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
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1"); //prepared statement to prevent SQL injection    
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
        body{
            margin:0;
            min-height:100vh;
            background:linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #38bdf8 100%);
            font-family:"Segoe UI", Arial, sans-serif;
        }

        .admin-login-wrap{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:30px 16px;
        }

        .admin-login-card{
            width:100%;
            max-width:470px;
            background:rgba(255,255,255,0.98);
            border-radius:24px;
            box-shadow:0 18px 50px rgba(0,0,0,0.22);
            padding:34px 28px;
        }

        .admin-top-badge{
            width:72px;
            height:72px;
            border-radius:50%;
            background:#0f172a;
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:30px;
            margin:0 auto 18px;
            box-shadow:0 12px 30px rgba(15,23,42,0.28);
        }

        .admin-login-card h2{
            text-align:center;
            margin:0 0 10px 0;
            font-size:30px;
            color:#111827;
        }

        .admin-login-card .helper{
            text-align:center;
            color:#6b7280;
            margin-bottom:24px;
            line-height:1.6;
        }

        .password-wrap{
            position:relative;
        }

        .password-wrap input{
            padding-right:50px;
        }

        .toggle-eye{
            position:absolute;
            right:14px;
            top:50%;
            transform:translateY(-50%);
            cursor:pointer;
            user-select:none;
            font-size:18px;
        }

        .admin-login-footer{
            text-align:center;
            margin-top:18px;
            color:#6b7280;
            font-size:14px;
        }

        .admin-login-footer a{
            color:#0ea5e9;
            text-decoration:none;
            font-weight:600;
        }

        .admin-login-footer a:hover{
            text-decoration:underline;
        }
    </style>
</head>
<body>

<div class="admin-login-wrap">
    <div class="admin-login-card">
        <div class="admin-top-badge">🛡</div>

        <h2>Admin Login</h2>
        <p class="helper">
            Access the TradeSphere admin dashboard to manage users, products,
            and marketplace activity securely.
        </p>

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
                <button type="submit" class="btn btn-primary" style="width:100%;">Login as Admin</button>
            </div>
        </form>

        <div class="admin-login-footer">
            Back to <a href="../index.php">TradeSphere Home</a>
        </div>
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