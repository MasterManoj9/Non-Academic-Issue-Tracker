<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch tickets based on the user's role
if ($role === 'admin') {
    // For admins: Fetch all tickets
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

    // Fetch data for pie chart (tickets by category)
    $category_sql = "SELECT category, COUNT(*) as count 
                     FROM Tickets 
                     GROUP BY category";
    $category_result = $conn->query($category_sql);
    $categories = [];
    $category_counts = [];
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = ucfirst($row['category']);
        $category_counts[] = $row['count'];
    }

    // Fetch data for bar graph (average time to resolve by category)
    $time_sql = "SELECT t.category, 
                        AVG(TIMESTAMPDIFF(HOUR, t.created_at, n.sent_at)) as avg_time 
                 FROM Tickets t
                 JOIN Notifications n ON t.ticket_id = n.ticket_id
                 WHERE t.status = 'Resolved' 
                 AND n.message LIKE '%status updated to Resolved'
                 GROUP BY t.category";
    $time_result = $conn->query($time_sql);
    $time_categories = [];
    $avg_times = [];
    while ($row = $time_result->fetch_assoc()) {
        $time_categories[] = ucfirst($row['category']);
        $avg_times[] = round($row['avg_time'], 2); // Round to 2 decimal places
    }
} elseif (in_array($role, ['staff', 'maintenance', 'warden', 'rector'])) {
    // For staff: Fetch tickets assigned to them
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
} else {
    // For students/faculty: Fetch tickets they created
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
}

// Handle ticket assignment (for admins)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ticket']) && $role === 'admin') {
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

        header("Location: track_ticket.php?message=Ticket assigned successfully");
        exit;
    } else {
        echo "Error assigning ticket: " . $conn->error;
    }
    $stmt->close();
}

// Handle status update (for admins and staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $ticket_id = $_POST['ticket_id'];
    $status = $_POST['status'];

    if ($role === 'admin') {
        $sql = "UPDATE Tickets SET status = ? WHERE ticket_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $ticket_id);
    } else {
        $sql = "UPDATE Tickets SET status = ? WHERE ticket_id = ? AND assigned_to = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $status, $ticket_id, $user_id);
    }

    if ($stmt->execute()) {
        // Insert a notification for the ticket creator (if updated by staff)
        if (in_array($role, ['staff', 'maintenance', 'warden', 'rector'])) {
            $message = "Ticket #$ticket_id status updated to $status";
            $sql = "INSERT INTO Notifications (ticket_id, user_id, message) VALUES (?, (SELECT user_id FROM Tickets WHERE ticket_id = ?), ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iis", $ticket_id, $ticket_id, $message);
            $stmt->execute();
        }

        header("Location: track_ticket.php?message=Ticket status updated successfully");
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
    <title>Track Tickets - Non-Academic Issue Tracker</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Include Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>Non-Academic Issue Tracker</h1>
        <nav>
            <p>Welcome, <?php echo htmlspecialchars($role); ?>! 
                <a href="raise_ticket.php">Raise a Ticket</a> | 
                <a href="notifications.php">Notifications</a> | 
                <a href="logout.php">Logout</a>
            </p>
        </nav>

        <h2><?php echo $role === 'admin' ? 'All Tickets' : ($role === 'student' || $role === 'faculty' ? 'Your Tickets' : 'Your Assigned Tickets'); ?></h2>
        <?php if (isset($_GET['message'])): ?>
            <p class="message"><?php echo htmlspecialchars($_GET['message']); ?></p>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <!-- Charts Section -->
            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                <!-- Pie Chart: Tickets by Category -->
                <div style="flex: 1; min-width: 300px;">
                    <h3 style="text-align: center;">Tickets by Category</h3>
                    <canvas id="categoryPieChart"></canvas>
                </div>
                <!-- Bar Graph: Average Time to Resolve by Category -->
                <div style="flex: 1; min-width: 300px;">
                    <h3 style="text-align: center;">Average Time to Resolve (Hours) by Category</h3>
                    <canvas id="timeBarGraph"></canvas>
                </div>
            </div>

            <!-- JavaScript to Render Charts -->
            <script>
                // Pie Chart: Tickets by Category
                const categoryCtx = document.getElementById('categoryPieChart').getContext('2d');
                new Chart(categoryCtx, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($categories); ?>,
                        datasets: [{
                            data: <?php echo json_encode($category_counts); ?>,
                            backgroundColor: [
                                '#FF6384', // Red
                                '#36A2EB', // Blue
                                '#FFCE56', // Yellow
                                '#4BC0C0', // Teal
                                '#9966FF'  // Purple
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
                                text: 'Distribution of Tickets by Category'
                            }
                        }
                    }
                });

                // Bar Graph: Average Time to Resolve by Category
                const timeCtx = document.getElementById('timeBarGraph').getContext('2d');
                new Chart(timeCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($time_categories); ?>,
                        datasets: [{
                            label: 'Average Time (Hours)',
                            data: <?php echo json_encode($avg_times); ?>,
                            backgroundColor: '#36A2EB',
                            borderColor: '#1a73e8',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Average Time (Hours)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Category'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Average Time to Resolve by Category'
                            }
                        }
                    }
                });
            </script>
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
                    <th>Assigned To</th>
                    <th>Created At</th>
                    <?php if ($role === 'admin'): ?>
                        <th>Assign</th>
                    <?php endif; ?>
                    <?php if ($role === 'admin' || in_array($role, ['staff', 'maintenance', 'warden', 'rector'])): ?>
                        <th>Update Status</th>
                    <?php endif; ?>
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
                        <td><?php echo htmlspecialchars($ticket['created_by'] ?? ($role === 'admin' ? 'Unknown' : 'You')); ?></td>
                        <td><?php echo htmlspecialchars($ticket['assigned_to_sap_id'] ?? 'Unassigned'); ?></td>
                        <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                        <?php if ($role === 'admin'): ?>
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
                                    <button type="submit" name="assign_ticket">Assign</button>
                                </form>
                            </td>
                        <?php endif; ?>
                        <?php if ($role === 'admin' || in_array($role, ['staff', 'maintenance', 'warden', 'rector'])): ?>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                                    <select name="status" required>
                                        <option value="Received" <?php echo $ticket['status'] === 'Received' ? 'selected' : ''; ?>>Received</option>
                                        <option value="In Progress" <?php echo $ticket['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Solution Proposed" <?php echo $ticket['status'] === 'Solution Proposed' ? 'selected' : ''; ?>>Solution Proposed</option>
                                        <option value="Resolved" <?php echo $ticket['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                    <button type="submit" name="update_status">Update</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No tickets found.</p>
        <?php endif; ?>
    </div>
</body>
</html>