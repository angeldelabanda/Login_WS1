<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "dessert_shop";

$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
$conn->select_db($database);

$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$columns = [];
$result = $conn->query("SHOW COLUMNS FROM users");

while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (!in_array('email', $columns)) {
    $conn->query("ALTER TABLE users ADD email VARCHAR(100) NULL UNIQUE AFTER username");
}

if (!in_array('role', $columns)) {
    $conn->query("ALTER TABLE users ADD role VARCHAR(20) NOT NULL DEFAULT 'customer' AFTER password");
}

if (!in_array('created_at', $columns)) {
    $conn->query("ALTER TABLE users ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER role");
}
?>
