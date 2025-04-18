<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all tickets
$sql = "SELECT t.ticket_id, t.category, t.description, t.priority, t.status, t.created_at, 
               u1.sap_id AS created_by, u2.sap_id AS assigned_to_sap_id
        FROM Tickets t
        LEFT JOIN Users u1 ON t.user_id = u1.user_id
        LEFT JOIN Users u2 ON t.assigned_to = u2.user_id
        ORDER BY t.created_at DESC";
$tickets_result = $conn->query($sql);

// Fetch staff for assignment dropdown
$staff_sql = "SELECT user_id, sap_id, role FROM Users WHERE role IN ('staff', 'maintenance', 'warden', 'rector')";
$staff_result = $conn->query($staff_sql);

// Fetch ticket statistics for all tickets
// By Category
$category_sql = "SELECT category, COUNT(*) as count FROM Tickets GROUP BY category";
$category_result = $conn->query($category_sql);
$category_data = [];
$category_labels = [];
while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['category'];
    $category_data[] = $row['count'];
}

// By Priority
$priority_sql = "SELECT priority, COUNT(*) as count FROM Tickets GROUP BY priority";
$priority_result = $conn->query($priority_sql);
$priority_data = [];
$priority_labels = [];
while ($row = $priority_result->fetch_assoc()) {
    $priority_labels[] = $row['priority'];
    $priority_data[] = $row['count'];
}

// By Status
$status_sql = "SELECT status, COUNT(*) as count FROM Tickets GROUP BY status";
$status_result = $conn->query($status_sql);
$status_data = [];
$status_labels = [];
while ($row = $status_result->fetch_assoc()) {
    $status_labels[] = $row['status'];
    $status_data[] = $row['count'];
}

// Handle ticket assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $assigned_to = $_POST['assigned_to'];

    $sql = "UPDATE Tickets SET assigned_to = ? WHERE ticket_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assigned_to, $ticket_id);

    if ($stmt->execute()) {
        // Insert a notification for the assigned staff
        $message = "Ticket #$ticket_id assigned to you";
        $sql = "INSERT INTO Notifications (ticket_id, user_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $ticket_id, $assigned_to, $message);
        $stmt->execute();

        header("Location: admin_dashboard.php?message=Ticket assigned successfully");
        exit;
    } else {
        echo "Error assigning ticket: " . $conn->error;
    }
    $stmt->close();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticket_id = $_POST['ticket_id'];
    $status = $_POST['status'];

    $sql = "UPDATE Tickets SET status = ? WHERE ticket_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $ticket_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?message=Ticket status updated successfully");
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
    <title>Admin Dashboard - Non-Academic Issue Tracker</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <h2>Admin Dashboard</h2>
        <?php if (isset($_GET['message'])): ?>
            <p class="message"><?php echo htmlspecialchars($_GET['message']); ?></p>
        <?php endif; ?>

        <div class="chart-section">
            <h3>All Ticket Statistics</h3>
            <div class="chart-container">
                <!-- Pie Chart: Tickets by Category -->
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
                <!-- Pie Chart: Tickets by Priority -->
                <div class="chart-wrapper">
                    <canvas id="priorityChart"></canvas>
                </div>
                <!-- Pie Chart: Tickets by Status -->
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <?php if ($tickets_result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Ticket ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Assigned To</th>
                    <th>Created At</th>
                    <th>Assign</th>
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
                        <td><?php echo htmlspecialchars($ticket['created_by'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($ticket['assigned_to_sap_id'] ?? 'Unassigned'); ?></td>
                        <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                                <select name="assigned_to" required>
                                    <option value="">Select Staff</option>
                                    <?php
                                    $staff_result->data_seek(0); // Reset the staff result pointer
                                    while ($staff = $staff_result->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($staff['user_id']); ?>" <?php echo $staff['user_id'] == $ticket['assigned_to'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staff['sap_id'] . " (" . $staff['role'] . ")"); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <button type="submit" name="assign_ticket"><i class="fas fa-user-plus"></i> Assign</button>
                            </form>
                        </td>
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
            <p>No tickets found.</p>
        <?php endif; ?>

        <footer>
            <p>© 2025 Non-Academic Issue Tracker. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Pie Chart: Tickets by Category
        const categoryChart = new Chart(document.getElementById('categoryChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    label: 'Tickets by Category',
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: [
                        '#1a73e8', // Blue
                        '#34c759', // Green
                        '#ff9500', // Orange
                        '#ff3b30', // Red
                        '#5856d6'  // Purple
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Tickets by Category'
                    }
                }
            }
        });

        // Pie Chart: Tickets by Priority
        const priorityChart = new Chart(document.getElementById('priorityChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($priority_labels); ?>,
                datasets: [{
                    label: 'Tickets by Priority',
                    data: <?php echo json_encode($priority_data); ?>,
                    backgroundColor: [
                        '#2ecc71', // Green (Low)
                        '#e67e22', // Orange (Medium)
                        '#e74c3c'  // Red (High)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Tickets by Priority'
                    }
                }
            }
        });

        // Pie Chart: Tickets by Status
        const statusChart = new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    label: 'Tickets by Status',
                    data: <?php echo json_encode($status_data); ?>,
                    backgroundColor: [
                        '#666',    // Gray (Received)
                        '#e67e22', // Orange (In Progress)
                        '#3498db', // Blue (Solution Proposed)
                        '#2ecc71'  // Green (Resolved)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Tickets by Status'
                    }
                }
            }
        });
    </script>
</body>
</html>