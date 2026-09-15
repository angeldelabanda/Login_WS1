<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Student';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register_event'])) {
    $event_id = intval($_POST['event_id']);

    $stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND status = 'visible'");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event_result = $stmt->get_result();

    if ($event_result->num_rows === 0) {
        $error = "This event is no longer available.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
        $registration_result = $stmt->get_result();

        if ($registration_result->num_rows > 0) {
            $error = "You are already registered for this event.";
        } else {
            $stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $event_id);

            if ($stmt->execute()) {
                $success = "You successfully registered for the event!";
            } else {
                $error = "Unable to register for the event.";
            }
        }
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['unregister_event'])) {
    $event_id = intval($_POST['event_id']);

    $stmt = $conn->prepare("DELETE FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $user_id, $event_id);

    if ($stmt->execute()) {
        $success = "You have been unregistered from the event.";
    } else {
        $error = "Unable to unregister from the event.";
    }

    $stmt->close();
}

$result = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status = 'visible'");
$available_events = $result->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$my_registrations = $result->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id
    WHERE r.user_id = ?
    AND e.status = 'visible'
    AND e.event_date >= CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming_registrations = $result->fetch_assoc()['total'];
$stmt->close();

$events = $conn->query("
    SELECT *
    FROM events
    WHERE status = 'visible'
    ORDER BY event_date ASC, start_time ASC
");

$stmt = $conn->prepare("
    SELECT e.*
    FROM registrations r
    INNER JOIN events e ON r.event_id = e.id
    WHERE r.user_id = ?
    AND e.status = 'visible'
    ORDER BY e.event_date ASC, e.start_time ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$registered_events = $stmt->get_result();

$registered_ids = [];

while ($registered_row = $registered_events->fetch_assoc()) {
    $registered_ids[] = $registered_row['id'];
}

$stmt->execute();
$registered_events = $stmt->get_result();
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
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Student Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
            </div>
        </header>

        <?php if ($success !== ""): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <section id="dashboard" class="dashboard-section">

            <div class="section-title">
                <h2>Overview</h2>
                <p>Here's what's happening around campus.</p>
            </div>

            <div class="stats-container">

                <div class="stat-card">
                    <h3>Available Events</h3>
                    <p><?php echo $available_events; ?></p>
                </div>

                <div class="stat-card">
                    <h3>My Registrations</h3>
                    <p><?php echo $my_registrations; ?></p>
                </div>

                <div class="stat-card">
                    <h3>Upcoming Events</h3>
                    <p><?php echo $upcoming_registrations; ?></p>
                </div>

            </div>

        </section>

        <section id="events" class="content-section dashboard-section">

            <div class="section-title">
                <h2>Campus Events</h2>
                <p>Discover events happening around campus.</p>
            </div>

            <div class="event-filters">
                <input type="text" id="eventSearch" placeholder="🔍 Search events...">

                <select id="categoryFilter">
                    <option value="all">All Categories</option>

                    <?php
                    $category_result = $conn->query("
                        SELECT DISTINCT category
                        FROM events
                        WHERE status = 'visible'
                        AND category IS NOT NULL
                        AND category != ''
                        ORDER BY category ASC
                    ");
                    ?>

                    <?php while ($category = $category_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($category['category']); ?>">
                            <?php echo htmlspecialchars($category['category']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="events-grid" id="eventsGrid">

                <?php if ($events->num_rows > 0): ?>

                    <?php while ($event = $events->fetch_assoc()): ?>

                        <div class="event-card"
                            data-title="<?php echo htmlspecialchars(strtolower($event['title'])); ?>"
                            data-category="<?php echo htmlspecialchars(strtolower($event['category'])); ?>">

                            <div class="event-category">
                                <?php echo htmlspecialchars($event['category']); ?>
                            </div>

                            <h3>
                                <?php echo htmlspecialchars($event['title']); ?>
                            </h3>

                            <p class="event-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </p>

                            <div class="event-details">

                                <p>
                                    <strong>📅 Date:</strong>
                                    <?php echo date("F j, Y", strtotime($event['event_date'])); ?>
                                </p>

                                <p>
                                    <strong>🕐 Time:</strong>
                                    <?php echo date("g:i A", strtotime($event['start_time'])); ?>
                                    -
                                    <?php echo date("g:i A", strtotime($event['end_time'])); ?>
                                </p>

                                <p>
                                    <strong>Location:</strong>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </p>

                                <p>
                                    <strong>Organizer:</strong>
                                    <?php echo htmlspecialchars($event['organizer']); ?>
                                </p>

                            </div>

                            <?php if (in_array($event['id'], $registered_ids)): ?>

                                <button class="registered-button" disabled>
                                    Registered ✓
                                </button>

                            <?php else: ?>

                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="register_event" class="register-button">
                                        Register
                                    </button>
                                </form>

                            <?php endif; ?>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-message">
                        <p>No events are currently available.</p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

        <section id="registrations" class="content-section dashboard-section">

            <div class="section-title">
                <h2>My Registrations</h2>
                <p>Events you have registered for.</p>
            </div>

            <div class="registrations-container">

                <?php if ($registered_events->num_rows > 0): ?>

                    <?php while ($event = $registered_events->fetch_assoc()): ?>

                        <div class="registration-card">

                            <div>
                                <h3>
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </h3>

                                <p>
                                    <?php echo htmlspecialchars($event['category']); ?>
                                </p>
                            </div>

                            <div class="registration-details">

                                <span>
                                    📅 <?php echo date("F j, Y", strtotime($event['event_date'])); ?>
                                </span>

                                <span>
                                    🕐 <?php echo date("g:i A", strtotime($event['start_time'])); ?>
                                    -
                                    <?php echo date("g:i A", strtotime($event['end_time'])); ?>
                                </span>

                                <span>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </span>

                            </div>

                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" name="unregister_event" class="unregister-button">
                                    Unregister
                                </button>
                            </form>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-message">
                        <p>You haven't registered for any events yet.</p>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>

<script>
const searchInput = document.getElementById("eventSearch");
const categoryFilter = document.getElementById("categoryFilter");
const eventCards = document.querySelectorAll(".event-card");

function filterEvents() {
    const searchText = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value.toLowerCase();

    eventCards.forEach(function(card) {
        const title = card.dataset.title;
        const category = card.dataset.category;

        const matchesSearch = title.includes(searchText);
        const matchesCategory = selectedCategory === "all" || category === selectedCategory;

        if (matchesSearch && matchesCategory) {
            card.style.display = "";
        } else {
            card.style.display = "none";
        }
    });
}

searchInput.addEventListener("input", filterEvents);
categoryFilter.addEventListener("change", filterEvents);

function showSection(sectionId) {
    const sections = document.querySelectorAll(".dashboard-section");

    sections.forEach(function(section) {
        section.style.display = "none";
    });

    document.getElementById(sectionId).style.display = "block";
}

showSection("dashboard");
</script>

</body>
</html>