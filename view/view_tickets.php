<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch tickets created by the user
$sql = "SELECT t.ticket_id, t.category, t.description, t.priority, t.status, t.created_at, 
               u.sap_id AS assigned_to_sap_id
        FROM Tickets t
        LEFT JOIN Users u ON t.assigned_to = u.user_id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Tickets - Non-Academic Issue Tracker</title>
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
                <a href="notifications.php">Notifications</a> | 
                <a href="logout.php">Logout</a>
            </p>
        </nav>

        <h2>Your Tickets</h2>
        <?php if ($tickets_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Ticket ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created At</th>
                </tr>
                <?php while ($ticket = $tickets_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ticket['ticket_id']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['category']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['description']); ?></td>
                        <td class="priority-<?php echo strtolower($ticket['priority']); ?>">
                            <?php echo htmlspecialchars($ticket['priority']); ?>
                        </td>
                        <td class="status-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                            <?php echo htmlspecialchars($ticket['status']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['assigned_to_sap_id'] ?? 'Unassigned'); ?></td>
                        <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No tickets found.</p>
        <?php endif; ?>

        <footer>
            <p>© 2025 Non-Academic Issue Tracker. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>