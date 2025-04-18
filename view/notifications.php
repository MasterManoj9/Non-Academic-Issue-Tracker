<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the current user
$sql = "SELECT n.*, t.description AS ticket_description 
        FROM Notifications n 
        JOIN Tickets t ON n.ticket_id = t.ticket_id 
        WHERE n.user_id = ? 
        ORDER BY n.sent_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Non-Academic Issue Tracker</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <img src="logo.jpg" alt="Logo" class="logo">
            <h1>Non-Academic Issue Tracker</h1>
        </header>
        <nav>
            <p>
                <a href="index.php">Back to Home</a> | 
                <a href="logout.php">Logout</a>
            </p>
        </nav>

        <h2>Your Notifications</h2>
        <?php if ($notifications_result->num_rows > 0): ?>
            <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                <div class="notification">
                    <p><strong>Message:</strong> <?php echo htmlspecialchars($notification['message']); ?></p>
                    <p><strong>Ticket Description:</strong> <?php echo htmlspecialchars($notification['ticket_description']); ?></p>
                    <p><strong>Sent At:</strong> <?php echo htmlspecialchars($notification['sent_at']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notifications found.</p>
        <?php endif; ?>

        <footer>
            <p>© 2025 Non-Academic Issue Tracker. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>