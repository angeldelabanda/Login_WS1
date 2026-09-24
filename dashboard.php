<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit();
}

require 'db.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Campus Hub</title>
    <link rel="stylesheet" href="studentstyle.css">
</head>
<body>

<div class="dashboard-container">

    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="images/Logo.png" alt="Campus Hub Logo">
            <h2>Campus Hub</h2>
        </div>

        <nav class="sidebar-nav">
            <a href="#" onclick="showSection('dashboard'); return false;">Dashboard</a>
            <a href="#" onclick="showSection('events'); return false;">Events</a>
            <a href="#" onclick="showSection('registrations'); return false;">My Registrations</a>
        </nav>
        <div class="sidebar-bottom">

                <a
                    href="logout.php"
                    class="sidebar-link logout-link"
                >
                    <span>Logout</span>
                </a>

            </div>

    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Student Dashboard</h1>
                <p>Welcome back, Student!</p>
            </div>
        </header>
    </main>

</div>

</body>
</html>