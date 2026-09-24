<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Admin Dashboard | Campus Hub</title>
    <link rel="stylesheet" href="dashboardstyle.css">
</head>

<body>

    <div class="admin-layout">

        <aside class="sidebar">

            <div class="sidebar-logo">
                <img src="images/logo.png" alt="Campus Hub Logo">
                <h2>Campus Hub</h2>
                <p>Admin Panel</p>
            </div>

            <nav class="sidebar-nav">

                <a
                    href="admin_dashboard.php"
                    class="sidebar-link"
                >
                    <span>Dashboard</span>
                </a>

                <a
                    href="admin_dashboard.php?section=add-event"
                    class="sidebar-link"
                >
                    <span>Add Event</span>
                </a>

                <a
                    href="admin_dashboard.php?section=events"
                    class="sidebar-link"
                >
                    <span>Manage Events</span>
                </a>

                <a
                    href="admin_dashboard.php?section=users"
                    class="sidebar-link"
                >
                    <span>Manage Users</span>
                </a>

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

            <div class="top-bar">

                <div>
                    <p class="welcome-small">
                        Welcome back! 🌸
                    </p>

                    <h1>Admin Dashboard</h1>

                    <h1> WAAAAAAAH HINDI MAKITA HUHUHU </h1>
                </div>

            </div>

        </main>

    </div>

</body>

</html>