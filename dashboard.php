<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'] ?? 'student';

require 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <p>This is your student dashboard.</p>
    <p> Dashboard is still under construction. Please check back later for more features and updates.</p>
    <a href="logout.php">Logout</a>
</body>
</html>
