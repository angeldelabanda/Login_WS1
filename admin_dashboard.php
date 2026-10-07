<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

$section = $_GET['section'] ?? 'dashboard';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'add_event') {
    $section = 'add-event';

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $status = trim($_POST['status'] ?? 'visible');
    $imagePath = null;

    if (
        $title === '' ||
        $description === '' ||
        $category === '' ||
        $eventDate === '' ||
        $startTime === '' ||
        $endTime === '' ||
        $location === '' ||
        $organizer === ''
    ) {
        $error = "Please fill in all required event details.";
    } elseif ($endTime <= $startTime) {
        $error = "End time must be later than start time.";
    } elseif (!in_array($status, ['visible', 'hidden'], true)) {
        $error = "Please choose a valid status.";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed. Please try again.";
            } else {
                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];
                $mimeType = mime_content_type($_FILES['image']['tmp_name']);

                if (!array_key_exists($mimeType, $allowedTypes)) {
                    $error = "Please upload a JPG, PNG, GIF, or WEBP image.";
                } else {
                    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'events';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = uniqid('event_', true) . '.' . $allowedTypes[$mimeType];
                    $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $imagePath = 'images/events/' . $fileName;
                    } else {
                        $error = "Could not save the uploaded image.";
                    }
                }
            }
        }

        if ($error === "") {
            $stmt = $conn->prepare(
                "INSERT INTO events
                    (title, description, category, event_date, start_time, end_time, location, organizer, image, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssssssss",
                $title,
                $description,
                $category,
                $eventDate,
                $startTime,
                $endTime,
                $location,
                $organizer,
                $imagePath,
                $status
            );

            if ($stmt->execute()) {
                $success = "Event added successfully.";
            } else {
                $error = "Could not add event. Please try again.";
            }

            $stmt->close();
        }
    }
}

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
                    class="sidebar-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>"
                >
                    <span>Dashboard</span>
                </a>

                <a
                    href="admin_dashboard.php?section=add-event"
                    class="sidebar-link <?php echo $section === 'add-event' ? 'active' : ''; ?>"
                >
                    <span>Add Event</span>
                </a>

                <a
                    href="admin_dashboard.php?section=events"
                    class="sidebar-link <?php echo $section === 'events' ? 'active' : ''; ?>"
                >
                    <span>Manage Events</span>
                </a>

                <a
                    href="admin_dashboard.php?section=users"
                    class="sidebar-link <?php echo $section === 'users' ? 'active' : ''; ?>"
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

            <?php if ($section === 'add-event'): ?>

            <section class="admin-section">
                <h1>Add Event</h1>
                <h4>Create a campus event for students to view and register.</h4>

                <?php if (!empty($error)): ?>
                    <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <p class="alert alert-success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>

                <form class="event-form" action="admin_dashboard.php?section=add-event" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_event">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="event_date">Event Date</label>
                            <input type="date" id="event_date" name="event_date" required>
                        </div>

                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required>
                        </div>

                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" id="end_time" name="end_time" required>
                        </div>

                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" required>
                        </div>

                        <div class="form-group">
                            <label for="organizer">Organizer</label>
                            <input type="text" id="organizer" name="organizer" required>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="visible">Visible</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="image">Image</label>
                            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                    </div>

                    <button type="submit" class="primary-button">Add Event</button>
                </form>
            </section>

            <?php else: ?>

            <div class="top-bar">

                <div>  

                    <h1> Welcome back, Admin! 🌸</h1>
                    <h4>Manage your events and users efficiently (Add events is partially done).</h4>

                    <div class="card-container">
                        <div class="card">
                            <p> Total Events 🌷</p>
                        </div>

                        <div class="card">
                            <p> Total Users🌷</p>
                        </div>

                        <div class="card">
                            <p> Total Registrations 🌷</p>
                        </div>
                    </div>

                </div>

                <div class = "current-events">
                    <h2> -------- CURRENT EVENTS --------</h2>

                    <div class="event-container">
                        <div class="event-card">
                            <p> Event 1 🌷</p>
                            <p> Click here to view details</p>
                        </div>

                        <div class="event-card">
                            <p> Event 2 🌷</p>
                            <p> Click here to view details</p>
                        </div>

                        <div class="event-card">
                            <p> Event 3 🌷</p>
                            <p> Click here to view details</p>
                        </div>
                    </div>
                </div>

            </div>

            <?php endif; ?>

        </main>

    </div>

</body>

</html>
