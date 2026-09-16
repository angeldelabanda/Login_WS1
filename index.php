<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if (isset($_GET['registered'])) {
    $success = "Account created successfully. Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            if ($row['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($row['role'] === 'handler') {
                header("Location: handler_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }

        exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="loginstyle.css">
</head>
<body>
    <section class="login-card">
        <img src="images/logo.png" alt="Logo" class="logo">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <p style="color: red; text-align: center; margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: green; text-align: center; margin-bottom: 15px;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

    <div class="login-form">
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="username">Username or Email:</label>
                <input type="text" id="username" name="username" placeholder="Enter username or email..." required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter password..." required>
            </div>
            <button type="submit">Login</button>
            <p>Don't have an account? <a href="register.php">Sign Up</a>.</p>
        </form>
    </div>
    <div class="demo-accounts">
    <h3>Demo Accounts</h3>
    <p class="demo-note">Temporary credentials for checking the system:</p>

    <div class="account-card">
        <strong>Admin Account</strong>
        <p>Username: admin</p>
        <p>Password: admin123</p>
    </div>

    <div class="account-card">
        <strong>Handler Account</strong>
        <p>Username: handler1</p>
        <p>Password: handler12345</p>
        <p>You can also create an account on Admin Dashboard.</p>
    </div>

    <div class="account-card">
        <strong>Student Account</strong>
        <p>Username: gela</p>
        <p>Password: 123456</p>
    </div>
</div>
    </section>
</body>
</html>
