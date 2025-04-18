<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'maintenance', 'warden', 'rector'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch tickets assigned to the current staff member
$sql = "SELECT t.ticket_id, t.category, t.description, t.priority, t.status, t.created_at, 
               u.sap_id AS created_by
        FROM Tickets t
        JOIN Users u ON t.user_id = u.user_id
        WHERE t.assigned_to = ?
        ORDER BY t.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_result = $stmt->get_result();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticket_id = $_POST['ticket_id'];
    $status = $_POST['status'];

    $sql = "UPDATE Tickets SET status = ? WHERE ticket_id = ? AND assigned_to = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $ticket_id, $user_id);

    if ($stmt->execute()) {
        // Insert a notification for the ticket creator
        $message = "Ticket #$ticket_id status updated to $status";
        $sql = "INSERT INTO Notifications (ticket_id, user_id, message) VALUES (?, (SELECT user_id FROM Tickets WHERE ticket_id = ?), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $ticket_id, $ticket_id, $message);
        $stmt->execute();

        header("Location: staff_tickets.php?message=Ticket status updated successfully");
        exit;
    } else {
        echo "Error updating status: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Assigned Tickets - Non-Academic Issue Tracker</title>
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

        <h2>Your Assigned Tickets</h2>
        <?php if (isset($_GET['message'])): ?>
            <p class="message"><?php echo htmlspecialchars($_GET['message']); ?></p>
        <?php endif; ?>

        <?php if ($tickets_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Ticket ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Update Status</th>
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
                        <td><?php echo htmlspecialchars($ticket['created_by']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                                <select name="status" required>
                                    <option value="Received" <?php echo $ticket['status'] === 'Received' ? 'selected' : ''; ?>>Received</option>
                                    <option value="In Progress" <?php echo $ticket['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Solution Proposed" <?php echo $ticket['status'] === 'Solution Proposed' ? 'selected' : ''; ?>>Solution Proposed</option>
                                    <option value="Resolved" <?php echo $ticket['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                                <button type="submit" name="update_status"><i class="fas fa-sync-alt"></i> Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No tickets assigned to you.</p>
        <?php endif; ?>

        <footer>
            <p>© 2025 Non-Academic Issue Tracker. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>