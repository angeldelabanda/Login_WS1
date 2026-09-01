<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

// Count visible students
$user_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

$user_count = $user_result->fetch_assoc()['total'];

// Count visible events
$event_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM events
     WHERE status = 'visible'"
);

$event_count = $event_result->fetch_assoc()['total'];

// Count registrations
$registration_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM registrations"
);

$registration_count = $registration_result->fetch_assoc()['total'];

$section = $_GET['section'] ?? 'dashboard';

$error = "";
$success = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['remove_event'])
) {
    $event_id = intval($_POST['event_id'] ?? 0);

    if ($event_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE events
             SET status = 'hidden'
             WHERE id = ?"
        );

        $stmt->bind_param("i", $event_id);

        if ($stmt->execute()) {
            $success = "Event removed from the dashboard. 🌸";
        } else {
            $error = "Unable to remove the event.";
        }

        $stmt->close();
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['add_event'])
) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');

    if (
        empty($title) ||
        empty($description) ||
        empty($category) ||
        empty($event_date) ||
        empty($start_time) ||
        empty($end_time) ||
        empty($location) ||
        empty($organizer)
    ) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO events
            (
                title,
                description,
                category,
                event_date,
                start_time,
                end_time,
                location,
                organizer
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssssss",
            $title,
            $description,
            $category,
            $event_date,
            $start_time,
            $end_time,
            $location,
            $organizer
        );

        if ($stmt->execute()) {
            $success = "Event added successfully! 🌸";
            $section = "events";
        } else {
            $error = "Unable to add the event. Please try again.";
        }

        $stmt->close();
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
                    <span class="icon">📊</span>
                    <span>Dashboard</span>
                </a>

                <a
                    href="admin_dashboard.php?section=add-event"
                    class="sidebar-link <?php echo $section === 'add-event' ? 'active' : ''; ?>"
                >
                    <span class="icon">📅</span>
                    <span>Add Event</span>
                </a>

                <a
                    href="admin_dashboard.php?section=events"
                    class="sidebar-link <?php echo $section === 'events' ? 'active' : ''; ?>"
                >
                    <span class="icon">📝</span>
                    <span>Manage Events</span>
                </a>

                <a
                    href="admin_dashboard.php?section=users"
                    class="sidebar-link <?php echo $section === 'users' ? 'active' : ''; ?>"
                >
                    <span class="icon">👥</span>
                    <span>Manage Users</span>
                </a>

            </nav>

            <div class="sidebar-bottom">

                <a
                    href="logout.php"
                    class="sidebar-link logout-link"
                >
                    <span class="icon">🚪</span>
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

                </div>


                <div class="admin-profile">

                    👩‍💼

                    <span>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>

                </div>

            </div>

            <?php if ($section === 'add-event'): ?>

                <section class="content-card">

                    <h2>📅 Add New Event</h2>

                    <p class="section-description">
                        Create a new campus event for students.
                    </p>


                    <?php if (!empty($error)): ?>

                        <div class="message error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>

                    <?php endif; ?>


                    <form
                        method="POST"
                        action="admin_dashboard.php?section=add-event"
                    >

                        <input
                            type="hidden"
                            name="add_event"
                            value="1"
                        >

                        <div class="form-group">

                            <label for="title">
                                Event Title
                            </label>

                            <input
                                type="text"
                                id="title"
                                name="title"
                                placeholder="Enter event title"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="description">
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                placeholder="Describe the event..."
                                required
                            ></textarea>

                        </div>

                        <div class="form-group">

                            <label for="category">
                                Category
                            </label>

                            <select
                                id="category"
                                name="category"
                                required
                            >

                                <option value="">
                                    Select a category
                                </option>

                                <option value="Academic">
                                    Academic
                                </option>

                                <option value="Workshop">
                                    Workshop
                                </option>

                                <option value="Sports">
                                    Sports
                                </option>

                                <option value="Social">
                                    Social
                                </option>

                                <option value="Arts">
                                    Arts & Culture
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="event_date">
                                    Event Date
                                </label>

                                <input
                                    type="date"
                                    id="event_date"
                                    name="event_date"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="location">
                                    Location
                                </label>

                                <input
                                    type="text"
                                    id="location"
                                    name="location"
                                    placeholder="e.g. Room 204"
                                    required
                                >

                            </div>

                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="start_time">
                                    Start Time
                                </label>

                                <input
                                    type="time"
                                    id="start_time"
                                    name="start_time"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="end_time">
                                    End Time
                                </label>

                                <input
                                    type="time"
                                    id="end_time"
                                    name="end_time"
                                    required
                                >

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="organizer">
                                Organizer
                            </label>

                            <input
                                type="text"
                                id="organizer"
                                name="organizer"
                                placeholder="e.g. IT Department"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="submit-button"
                        >
                            + Add Event
                        </button>

                    </form>

                </section>

            <?php elseif ($section === 'events'): ?>

                <div class="section-header">

                    <div>

                        <h2>📝 Manage Events</h2>

                        <p class="section-description">
                            View and manage all campus events.
                        </p>

                    </div>


                    <a
                        href="admin_dashboard.php?section=add-event"
                        class="quick-button"
                    >
                        + Add Event
                    </a>

                </div>


                <?php
                $events_result = $conn->query(
                    "SELECT
                        id,
                        title,
                        description,
                        category,
                        event_date,
                        start_time,
                        end_time,
                        location,
                        organizer
                     FROM events
                     WHERE status = 'visible'
                     ORDER BY event_date ASC, start_time ASC"
                );
                ?>


                <section class="events-card">

                    <?php if (!empty($success)): ?>

                        <div class="message success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>

                    <?php endif; ?>

                    <?php if (!empty($error)): ?>

                        <div class="message error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>

                    <?php endif; ?>


                    <?php if (
                        $events_result &&
                        $events_result->num_rows > 0
                    ): ?>

                        <div class="event-table-container">

                            <table class="event-table">

                                <thead>

                                    <tr>
                                        <th>Event</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Location</th>
                                        <th>Organizer</th>
                                        <th>Action</th>
                                    </tr>

                                </thead>


                                <tbody>

                                    <?php while (
                                        $event = $events_result->fetch_assoc()
                                    ): ?>

                                        <tr>

                                            <td>

                                                <strong>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $event['title']
                                                    );
                                                    ?>
                                                </strong>

                                                <small>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $event['description']
                                                    );
                                                    ?>
                                                </small>

                                            </td>

                                            <td>

                                                <span class="category-badge">
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $event['category']
                                                    );
                                                    ?>
                                                </span>

                                            </td>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $event['event_date']
                                                );
                                                ?>
                                            </td>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $event['start_time']
                                                );
                                                ?>

                                                <?php
                                                echo htmlspecialchars(
                                                    $event['end_time']
                                                );
                                                ?>
                                            </td>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $event['location']
                                                );
                                                ?>
                                            </td>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $event['organizer']
                                                );
                                                ?>
                                            </td>

                                            <td>

                                                <form
                                                    method="POST"
                                                    action="admin_dashboard.php?section=events"
                                                    onsubmit="return confirm('Remove this event from the dashboard?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="remove_event"
                                                        value="1"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="event_id"
                                                        value="<?php echo $event['id']; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="remove-button"
                                                    >
                                                        Remove
                                                    </button>

                                                </form>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                </tbody>

                            </table>

                        </div>


                    <?php else: ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                📅
                            </div>

                            <h3>
                                No Events Yet
                            </h3>

                            <p>
                                There are currently no campus events.
                            </p>

                            <a
                                href="admin_dashboard.php?section=add-event"
                                class="quick-button"
                            >
                                + Create Your First Event
                            </a>

                        </div>

                    <?php endif; ?>

                </section>

<?php elseif ($section === 'users'): ?>

    <?php
    $users_result = $conn->query(
        "SELECT id, username, email, role
         FROM users
         WHERE role = 'student'
         ORDER BY id DESC"
    );
    ?>


    <div class="section-header">

        <div>

            <h2>👥 Manage Users</h2>

            <p class="section-description">
                View and manage registered student accounts.
            </p>

        </div>

    </div>


    <section class="users-card">

        <?php if (!empty($success)): ?>

            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>

        <?php endif; ?>


        <?php if (!empty($error)): ?>

            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <?php if (
            $users_result &&
            $users_result->num_rows > 0
        ): ?>

            <div class="user-table-container">

                <table class="user-table">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php while (
                            $user = $users_result->fetch_assoc()
                        ): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $user['id']
                                    );
                                    ?>
                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $user['username']
                                        );
                                        ?>
                                    </strong>

                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $user['email']
                                    );
                                    ?>
                                </td>

                                <td>

                                    <span class="role-badge">
                                        <?php
                                        echo htmlspecialchars(
                                            $user['role']
                                        );
                                        ?>
                                    </span>

                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="remove-button"
                                        onclick="alert('User removal will be added next!');"
                                    >
                                        Remove
                                    </button>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


        <?php else: ?>

            <div class="empty-state">

                <div class="empty-icon">
                    👥
                </div>

                <h3>
                    No Students Yet
                </h3>

                <p>
                    There are currently no registered student accounts.
                </p>

            </div>

        <?php endif; ?>

    </section>

<?php elseif ($section === 'dashboard'): ?>

                <p class="subtitle">
                    Manage campus events and student activities from here.
                </p>

                <section class="stats">

                    <div class="stat-card">

                        <div class="stat-icon">
                            👩‍🎓
                        </div>

                        <div>

                            <h3>
                                Students
                            </h3>

                            <p>
                                <?php echo $user_count; ?>
                            </p>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            📅
                        </div>

                        <div>

                            <h3>
                                Events
                            </h3>

                            <p>
                                <?php echo $event_count; ?>
                            </p>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon">
                            💗
                        </div>

                        <div>

                            <h3>
                                Registrations
                            </h3>

                            <p>
                                <?php echo $registration_count; ?>
                            </p>

                        </div>

                    </div>

                </section>

                <section class="welcome-card">

                    <h2>
                        Welcome to Campus Hub! 🌷
                    </h2>

                    <p>
                        Use the sidebar to manage events, students,
                        and campus activities.
                    </p>


                    <div class="quick-actions">

                        <a
                            href="admin_dashboard.php?section=add-event"
                            class="quick-button"
                        >
                            📅 Add Event
                        </a>

                        <a
                            href="admin_dashboard.php?section=events"
                            class="quick-button"
                        >
                            📝 Manage Events
                        </a>

                        <a
                            href="admin_dashboard.php?section=users"
                            class="quick-button"
                        >
                            👥 Manage Users
                        </a>

                    </div>

                </section>

            <?php endif; ?>

        </main>

    </div>

</body>

</html>