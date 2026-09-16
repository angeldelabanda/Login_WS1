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

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['remove_event'])
) {

    $event_id = $_POST['event_id'] ?? '';

    if (!empty($event_id)) {

        $stmt = $conn->prepare(
            "UPDATE events
             SET status = 'hidden'
             WHERE id = ?"
        );

        $stmt->bind_param(
            "i",
            $event_id
        );

        if ($stmt->execute()) {

            $success = "Event removed from the dashboard.";

        } else {

            $error = "Unable to remove event.";

        }

        $stmt->close();

    } else {

        $error = "Invalid event.";

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

        } else {

            $error = "Unable to add event.";

        }

        $stmt->close();
    }
}

$section = $_GET['section'] ?? 'dashboard';

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['update_event'])
) {

    $event_id = $_GET['id'] ?? '';

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');

    if (
        empty($event_id) ||
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
            $section = 'events';

        } else {

            $error = "Unable to update event.";

        }

        $stmt->close();
    }
}



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

    <div class="admin-layout">

        <aside class="sidebar">

            <div class="sidebar-header">
                <h2>🌸 Campus Hub</h2>
                <p>Handler Panel</p>
            </div>

            <nav>

                <a href="handler_dashboard.php?section=dashboard">
                    📊 Dashboard
                </a>

                <a href="handler_dashboard.php?section=add_event">
                    📅 Add Event
                </a>

                <a href="handler_dashboard.php?section=events">
                    📝 Manage Events
                </a>

            </nav>

            <div class="sidebar-bottom">

                <a href="logout.php">
                    🚪 Logout
                </a>

            </div>

        </aside>

        <main class="main-content">

            <?php if (!empty($error)): ?>

                <p class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php endif; ?>


            <?php if (!empty($success)): ?>

                <p class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </p>

            <?php endif; ?>

    <?php if ($section === 'dashboard'): ?>

    <?php
    $event_result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM events
         WHERE status = 'visible'"
    );

    $event_count = $event_result->fetch_assoc()['total'];

    $upcoming_result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM events
         WHERE status = 'visible'
         AND event_date >= CURDATE()"
    );

    $upcoming_count = $upcoming_result->fetch_assoc()['total'];
    ?>

    <div class="top-bar">

        <div>

            <p class="welcome-small">
                Welcome back! 🌸
            </p>

            <h1>
                Handler Dashboard
            </h1>

        </div>

        <div class="admin-profile">

            🧑‍💼

            <span>
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>

        </div>

    </div>

    <p class="subtitle">
        Manage campus events and keep student activities updated.
    </p>

    <section class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                📅
            </div>

            <div>

                <h3>
                    Visible Events
                </h3>

                <p>
                    <?php echo $event_count; ?>
                </p>

            </div>

        </div>

        <div class="stat-card">

            <div class="stat-icon">
                🌷
            </div>

            <div>

                <h3>
                    Upcoming Events
                </h3>


            <?php elseif ($section === 'add_event'): ?>

                <h1>📅 Add Event</h1>

                <div class="content-card">

                    <form method="POST" action="handler_dashboard.php?section=add_event">

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
                placeholder="Enter event description"
                required
            ></textarea>

        </div>


        <div class="form-group">

            <label for="category">
                Category
            </label>

            <input
                type="text"
                id="category"
                name="category"
                placeholder="e.g. Academic, Sports, Club"
                required
            >

        </div>


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


        <div class="form-group">

            <label for="location">
                Location
            </label>

            <input
                type="text"
                id="location"
                name="location"
                placeholder="Enter event location"
                required
            >

        </div>


        <div class="form-group">

            <label for="organizer">
                Organizer
            </label>

            <input
                type="text"
                id="organizer"
                name="organizer"
                placeholder="Enter organizer"
                required
            >

        </div>


        <button type="submit" name="add_event" class="submit-button">
            Add Event
        </button>

    </form>

</div>


            <?php elseif ($section === 'events'): ?>

    <div class="section-header">

        <div>

            <h2>📝 Manage Events</h2>

            <p class="section-description">
                View and manage all campus events.
            </p>

        </div>


        <a
            href="handler_dashboard.php?section=add_event"
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

                                    -

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

                                    <a
                                        href="handler_dashboard.php?section=edit_event&id=<?php echo $event['id']; ?>"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="handler_dashboard.php?section=events"
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
                    href="handler_dashboard.php?section=add_event"
                    class="quick-button"
                >
                    + Create Your First Event
                </a>

            </div>

        <?php endif; ?>

        <?php elseif ($section === 'edit_event'): ?>

    <?php

    $event_id = $_GET['id'] ?? '';

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

        <h2>✏️ Edit Event</h2>

        <section class="content-card">

            <form
                method="POST"
                action="handler_dashboard.php?section=edit_event&id=<?php echo $event['id']; ?>"
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

                    <input
                        type="text"
                        id="category"
                        name="category"
                        value="<?php echo htmlspecialchars($event['category']); ?>"
                        required
                    >

                </div>


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


                <button type="submit" class="submit-button">
                    Save Changes
                </button>

                <a
                    href="handler_dashboard.php?section=events"
                    class="quick-button"
                >
                    Cancel
                </a>

            </form>

        </section>

    <?php else: ?>

        <div class="empty-state">

            <h3>Event Not Found</h3>

            <a
                href="handler_dashboard.php?section=events"
                class="quick-button"
            >
                Back to Events
            </a>

        </div>

    <?php endif; ?>

    </section>

            <?php endif; ?>

        </main>

    </div>

</body>

</html>