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

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE (username = ? OR email = ?) AND role = 'customer'");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = 'customer';
            header("Location: dashboard.php");
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
        <img src="images/Logo.png" alt="Logo" class="logo">
        <h2>Login</h2>

        <!-- Display error message if login fails -->
        <?php if (!empty($error)): ?>
            <p style="color: red; text-align: center; margin-bottom: 15px;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color: green; text-align: center; margin-bottom: 15px;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

    <div class="login-form">
        <!-- Send POST request to index.php -->
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
    </section>
</body>
</html>
