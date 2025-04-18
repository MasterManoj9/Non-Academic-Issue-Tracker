<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raise a Ticket - Non-Academic Issue Tracker</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Non-Academic Issue Tracker</h1>
        <nav>
            <p>Welcome, <?php echo htmlspecialchars($role); ?>! 
                <a href="track_ticket.php">Track Tickets</a> | 
                <a href="notifications.php">Notifications</a> | 
                <a href="logout.php">Logout</a>
            </p>
        </nav>

        <h2>Raise a Ticket</h2>
        <?php if (isset($_GET['message'])): ?>
            <p class="message"><?php echo htmlspecialchars($_GET['message']); ?></p>
        <?php endif; ?>
        <form action="create_ticket.php" method="POST" enctype="multipart/form-data">
            <label>Category: 
                <select name="category" required>
                    <option value="infrastructure">Infrastructure</option>
                    <option value="hygiene">Hygiene</option>
                    <option value="security">Security</option>
                    <option value="hostel">Hostel</option>
                    <option value="other">Other</option>
                </select>
            </label>
            <label>Description: <textarea name="description" required></textarea></label>
            <label>Priority: 
                <select name="priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </label>
            <label>Attach File: <input type="file" name="file"></label>
            <button type="submit">Raise Ticket</button>
        </form>
    </div>
</body>
</html>