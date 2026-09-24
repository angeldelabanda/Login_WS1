<?php

session_start();

if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] !== 'handler'
) {
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

    <title>Handler Dashboard - Campus Hub</title>

    <link rel="stylesheet" href="handlerstyle.css">
</head>

<body>

    <div class="handler-layout">

        <aside class="sidebar">

            <div class="sidebar-logo">
                <img src="images/logo.png" alt="Campus Hub Logo">
            </div>

            <div class="sidebar-header">
                <h2>Campus Hub</h2>
                <p>Handler Panel</p>
            </div>
        

            <nav>

                <a
                    href="#"
                    class="sidebar-link"
                >
                    <span>Dashboard</span>
                </a>

                <a
                    href="#"
                    class="sidebar-link"
                >
                    <span>Add Event</span>
                </a>

                <a
                    href="#"
                    class="sidebar-link"
                >
                    <span>Manage Events</span>
                </a>

            </nav>

            <div class="sidebar-bottom">

                <a href="logout.php">
                 Logout
                </a>

            </div>

        </aside>

        <main class="main-content">

            <h1> WELCOME, HANDLER! </h1>

        </main>

    </div>

</body>

</html>