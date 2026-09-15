<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

require 'db.php';

$user_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

$user_count = $user_result->fetch_assoc()['total'];

$event_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM events
     WHERE status = 'visible'"
);

$event_count = $event_result->fetch_assoc()['total'];

$registration_result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM registrations"
);

$registration_count = $registration_result->fetch_assoc()['total'];

$section = $_GET['section'] ?? 'dashboard';

$error = "";
$success = "";

$selected_event = null;
$registrants_result = null;

if ($section === 'registrations') {
    $event_id = intval($_GET['id'] ?? 0);

    if ($event_id > 0) {
        $stmt = $conn->prepare(
            "SELECT
                id,
                title,
                event_date,
                start_time,
                end_time,
                location,
                organizer
             FROM events
             WHERE id = ?"
        );

        $stmt->bind_param("i", $event_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $selected_event = $result->fetch_assoc();

        $stmt->close();

        if ($selected_event) {
            $stmt = $conn->prepare(
                "SELECT
                    u.id,
                    u.username,
                    u.email,
                    r.registered_at
                 FROM registrations r
                 INNER JOIN users u ON r.user_id = u.id
                 WHERE r.event_id = ?
                 AND u.role = 'student'
                 ORDER BY r.registered_at ASC"
            );

            $stmt->bind_param("i", $event_id);
            $stmt->execute();

            $registrants_result = $stmt->get_result();
        }
    }
}

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

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['update_event'])
) {
    $event_id = intval($_GET['id'] ?? 0);

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');

    if (
        $event_id <= 0 ||
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
            "UPDATE events
             SET
                title = ?,
                description = ?,
                category = ?,
                event_date = ?,
                start_time = ?,
                end_time = ?,
                location = ?,
                organizer = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "ssssssssi",
            $title,
            $description,
            $category,
            $event_date,
            $start_time,
            $end_time,
            $location,
            $organizer,
            $event_id
        );

        if ($stmt->execute()) {
            $success = "Event updated successfully! 🌸";
            $section = "events";
        } else {
            $error = "Unable to update event.";
        }

        $stmt->close();
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['create_handler'])
) {
    $username = trim($_POST['handler_username'] ?? '');
    $email = trim($_POST['handler_email'] ?? '');
    $password = $_POST['handler_password'] ?? '';

    if (
        empty($username) ||
        empty($email) ||
        empty($password)
    ) {
        $error = "Please fill in all handler account fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE username = ? OR email = ?"
        );

        $check->bind_param(
            "ss",
            $username,
            $email
        );

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or email is already registered.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "handler";

            $stmt = $conn->prepare(
                "INSERT INTO users
                (username, email, password, role)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $username,
                $email,
                $hashedPassword,
                $role
            );

            if ($stmt->execute()) {
                $success = "Handler account created successfully! 🌸";
                $section = "users";
            } else {
                $error = "Unable to create handler account.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['delete_handler'])
) {
    $handler_id = intval($_POST['handler_id'] ?? 0);

    if ($handler_id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM users
             WHERE id = ?
             AND role = 'handler'"
        );

        $stmt->bind_param(
            "i",
            $handler_id
        );

        if ($stmt->execute()) {
            $success = "Handler account deleted successfully. 🌸";
            $section = "users";
        } else {
            $error = "Unable to delete handler account.";
        }

        $stmt->close();
    } else {
        $error = "Invalid handler account.";
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['remove_student'])
) {
    $student_id = intval($_POST['student_id'] ?? 0);

    if ($student_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE users
             SET status = 'hidden'
             WHERE id = ?
             AND role = 'student'"
        );

        $stmt->bind_param(
            "i",
            $student_id
        );

        if ($stmt->execute()) {
            $success = "Student account removed successfully. 🌸";
            $section = "users";
        } else {
            $error = "Unable to remove student account.";
        }

        $stmt->close();
    } else {
        $error = "Invalid student account.";
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

                                                <div class="event-actions">

                                                    <a
                                                        href="admin_dashboard.php?section=edit-event&id=<?php echo $event['id']; ?>"
                                                        class="edit-button"
                                                    >
                                                        Edit
                                                    </a>

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

                                                    <a
                                                        href="admin_dashboard.php?section=registrations&id=<?php echo $event['id']; ?>"
                                                        class="view-button"
                                                    >
                                                        View Registrations
                                                    </a>

                                                </div>

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

            <?php elseif ($section === 'registrations'): ?>

                <?php if ($selected_event): ?>

                    <div class="section-header">

                        <div>

                            <h2>👥 Event Registrations</h2>

                            <p class="section-description">
                                Students registered for this event.
                            </p>

                        </div>

                        <a
                            href="admin_dashboard.php?section=events"
                            class="quick-button"
                        >
                            ← Back to Events
                        </a>

                    </div>

                    <section class="content-card">

                        <h2>
                            <?php echo htmlspecialchars($selected_event['title']); ?>
                        </h2>

                        <p class="section-description">
                            📅
                            <?php
                            echo date(
                                "F j, Y",
                                strtotime($selected_event['event_date'])
                            );
                            ?>

                            &nbsp; | &nbsp;

                            🕐
                            <?php
                            echo date(
                                "g:i A",
                                strtotime($selected_event['start_time'])
                            );
                            ?>

                            -

                            <?php
                            echo date(
                                "g:i A",
                                strtotime($selected_event['end_time'])
                            );
                            ?>

                            &nbsp; | &nbsp;

                            📍
                            <?php
                            echo htmlspecialchars(
                                $selected_event['location']
                            );
                            ?>
                        </p>

                    </section>

                    <section class="users-card">

                        <?php if (
                            $registrants_result &&
                            $registrants_result->num_rows > 0
                        ): ?>

                            <div class="user-table-container">

                                <table class="user-table">

                                    <thead>

                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Registered At</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php while (
                                            $registrant = $registrants_result->fetch_assoc()
                                        ): ?>

                                            <tr>

                                                <td>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $registrant['id']
                                                    );
                                                    ?>
                                                </td>

                                                <td>

                                                    <strong>
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $registrant['username']
                                                        );
                                                        ?>
                                                    </strong>

                                                </td>

                                                <td>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $registrant['email']
                                                    );
                                                    ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    echo date(
                                                        "F j, Y g:i A",
                                                        strtotime(
                                                            $registrant['registered_at']
                                                        )
                                                    );
                                                    ?>
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
                                    No Registrations Yet
                                </h3>

                                <p>
                                    No students have registered for this event yet.
                                </p>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            Event Not Found
                        </h3>

                        <p>
                            The event you are trying to view does not exist.
                        </p>

                        <a
                            href="admin_dashboard.php?section=events"
                            class="quick-button"
                        >
                            Back to Events
                        </a>

                    </div>

                <?php endif; ?>

            <?php elseif ($section === 'users'): ?>

                <?php
                $users_result = $conn->query(
                    "SELECT
                        id,
                        username,
                        email,
                        role
                     FROM users
                     WHERE role = 'student'
                     AND status = 'visible'
                     ORDER BY id DESC"
                );

                $handlers_result = $conn->query(
                    "SELECT
                        id,
                        username,
                        email,
                        role
                     FROM users
                     WHERE role = 'handler'
                     ORDER BY id DESC"
                );
                ?>

                <section class="content-card">

                    <h2>📝 Create Handler Account</h2>

                    <p class="section-description">
                        Create an account for a staff member who can manage campus events.
                    </p>

                    <form
                        method="POST"
                        action="admin_dashboard.php?section=users"
                    >

                        <input
                            type="hidden"
                            name="create_handler"
                            value="1"
                        >

                        <div class="form-group">

                            <label for="handler_username">
                                Username
                            </label>

                            <input
                                type="text"
                                id="handler_username"
                                name="handler_username"
                                placeholder="Enter handler username"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="handler_email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="handler_email"
                                name="handler_email"
                                placeholder="Enter handler email"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label for="handler_password">
                                Password
                            </label>

                            <input
                                type="password"
                                id="handler_password"
                                name="handler_password"
                                placeholder="Enter handler password"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="submit-button"
                        >
                            Create Handler
                        </button>

                    </form>

                </section>

                <section class="users-card">

                    <div class="section-header">

                        <div>

                            <h2>🧑‍💼 Handler Accounts</h2>

                            <p class="section-description">
                                View and manage the handler accounts created by the admin.
                            </p>

                        </div>

                    </div>

                    <?php if (
                        $handlers_result &&
                        $handlers_result->num_rows > 0
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
                                        $handler = $handlers_result->fetch_assoc()
                                    ): ?>

                                        <tr>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $handler['id']
                                                );
                                                ?>
                                            </td>

                                            <td>

                                                <strong>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $handler['username']
                                                    );
                                                    ?>
                                                </strong>

                                            </td>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $handler['email']
                                                );
                                                ?>
                                            </td>

                                            <td>

                                                <span class="role-badge">
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $handler['role']
                                                    );
                                                    ?>
                                                </span>

                                            </td>

                                            <td>

                                                <form
                                                    method="POST"
                                                    action="admin_dashboard.php?section=users"
                                                    onsubmit="return confirm('Delete this handler account?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="delete_handler"
                                                        value="1"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="handler_id"
                                                        value="<?php echo $handler['id']; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="remove-button"
                                                    >
                                                        Delete
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
                                🧑‍💼
                            </div>

                            <h3>
                                No Handlers Yet
                            </h3>

                            <p>
                                There are currently no handler accounts.
                            </p>

                        </div>

                    <?php endif; ?>

                </section>

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

                                                <form
                                                    method="POST"
                                                    action="admin_dashboard.php?section=users"
                                                    onsubmit="return confirm('Remove this student account from the dashboard?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="remove_student"
                                                        value="1"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="student_id"
                                                        value="<?php echo $user['id']; ?>"
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

            <?php elseif ($section === 'edit-event'): ?>

                <?php
                $event_id = intval($_GET['id'] ?? 0);

                $stmt = $conn->prepare(
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
                     WHERE id = ?"
                );

                $stmt->bind_param("i", $event_id);
                $stmt->execute();

                $result = $stmt->get_result();
                $event = $result->fetch_assoc();

                $stmt->close();
                ?>

                <?php if ($event): ?>

                    <section class="content-card">

                        <h2>✏️ Edit Event</h2>

                        <p class="section-description">
                            Update the details of this campus event.
                        </p>

                        <?php if (!empty($error)): ?>

                            <div class="message error">
                                <?php echo htmlspecialchars($error); ?>
                            </div>

                        <?php endif; ?>

                        <form
                            method="POST"
                            action="admin_dashboard.php?section=edit-event&id=<?php echo $event['id']; ?>"
                        >

                            <input
                                type="hidden"
                                name="update_event"
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
                                    value="<?php echo htmlspecialchars($event['title']); ?>"
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
                                    required
                                ><?php echo htmlspecialchars($event['description']); ?></textarea>

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

                                    <option
                                        value="Academic"
                                        <?php echo $event['category'] === 'Academic' ? 'selected' : ''; ?>
                                    >
                                        Academic
                                    </option>

                                    <option
                                        value="Workshop"
                                        <?php echo $event['category'] === 'Workshop' ? 'selected' : ''; ?>
                                    >
                                        Workshop
                                    </option>

                                    <option
                                        value="Sports"
                                        <?php echo $event['category'] === 'Sports' ? 'selected' : ''; ?>
                                    >
                                        Sports
                                    </option>

                                    <option
                                        value="Social"
                                        <?php echo $event['category'] === 'Social' ? 'selected' : ''; ?>
                                    >
                                        Social
                                    </option>

                                    <option
                                        value="Arts"
                                        <?php echo $event['category'] === 'Arts' ? 'selected' : ''; ?>
                                    >
                                        Arts & Culture
                                    </option>

                                    <option
                                        value="Other"
                                        <?php echo $event['category'] === 'Other' ? 'selected' : ''; ?>
                                    >
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
                                        value="<?php echo htmlspecialchars($event['event_date']); ?>"
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
                                        value="<?php echo htmlspecialchars($event['location']); ?>"
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
                                        value="<?php echo htmlspecialchars($event['start_time']); ?>"
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
                                        value="<?php echo htmlspecialchars($event['end_time']); ?>"
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
                                    value="<?php echo htmlspecialchars($event['organizer']); ?>"
                                    required
                                >

                            </div>

                            <button
                                type="submit"
                                class="submit-button"
                            >
                                Save Changes
                            </button>

                            <a
                                href="admin_dashboard.php?section=events"
                                class="quick-button"
                            >
                                Cancel
                            </a>

                        </form>

                    </section>

                <?php else: ?>

                    <div class="empty-state">

                        <h3>
                            Event Not Found
                        </h3>

                        <a
                            href="admin_dashboard.php?section=events"
                            class="quick-button"
                        >
                            Back to Events
                        </a>

                    </div>

                <?php endif; ?>

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