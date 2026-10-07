<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "campus_hub";

$conn = new mysqli(
    $host,
    $user,
    $password
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query(
    "CREATE DATABASE IF NOT EXISTS `$database`"
);

$conn->select_db($database);

$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,

        username VARCHAR(50) NOT NULL UNIQUE,

        email VARCHAR(100) NOT NULL UNIQUE,

        password VARCHAR(255) NOT NULL,

        role VARCHAR(20) NOT NULL DEFAULT 'student',

        status VARCHAR(20) NOT NULL DEFAULT 'visible',

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$columns = [];

$result = $conn->query(
    "SHOW COLUMNS FROM users"
);

while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (!in_array('email', $columns)) {

    $conn->query(
        "ALTER TABLE users
         ADD email VARCHAR(100) NULL UNIQUE
         AFTER username"
    );
}

if (!in_array('role', $columns)) {

    $conn->query(
        "ALTER TABLE users
         ADD role VARCHAR(20) NOT NULL DEFAULT 'student'
         AFTER password"
    );
}

if (!in_array('status', $columns)) {

    $conn->query(
        "ALTER TABLE users
         ADD status VARCHAR(20) NOT NULL DEFAULT 'visible'
         AFTER role"
    );
}

if (!in_array('created_at', $columns)) {

    $conn->query(
        "ALTER TABLE users
         ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
         AFTER status"
    );
}

$conn->query("
    CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,

        title VARCHAR(150) NOT NULL,

        description TEXT NOT NULL,

        category VARCHAR(50) NOT NULL,

        event_date DATE NOT NULL,

        start_time TIME NOT NULL,

        end_time TIME NOT NULL,

        location VARCHAR(150) NOT NULL,

        organizer VARCHAR(100) NOT NULL,

        image VARCHAR(255) NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        status VARCHAR(20) NOT NULL DEFAULT 'visible'
    )
");

$eventColumns = [];

$eventResult = $conn->query(
    "SHOW COLUMNS FROM events"
);

while ($row = $eventResult->fetch_assoc()) {
    $eventColumns[] = $row['Field'];
}

if (!in_array('image', $eventColumns)) {

    $conn->query(
        "ALTER TABLE events
         ADD image VARCHAR(255) NULL
         AFTER organizer"
    );
}

if (!in_array('created_at', $eventColumns)) {

    $conn->query(
        "ALTER TABLE events
         ADD created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
         AFTER image"
    );
}

if (!in_array('status', $eventColumns)) {

    $conn->query(
        "ALTER TABLE events
         ADD status VARCHAR(20) NOT NULL DEFAULT 'visible'
         AFTER created_at"
    );
}

?>
