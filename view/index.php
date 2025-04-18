<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$sap_id = $_SESSION['sap_id'] ?? 'Unknown'; // Fetch SAP ID from session (set during login)

// Fetch ticket statistics for the logged-in user
// By Category
$category_sql = "SELECT category, COUNT(*) as count FROM Tickets WHERE user_id = ? GROUP BY category";
$stmt = $conn->prepare($category_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$category_result = $stmt->get_result();
$category_data = [];
$category_labels = [];
while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['category'];
    $category_data[] = $row['count'];
}

// By Priority
$priority_sql = "SELECT priority, COUNT(*) as count FROM Tickets WHERE user_id = ? GROUP BY priority";
$stmt = $conn->prepare($priority_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$priority_result = $stmt->get_result();
$priority_data = [];
$priority_labels = [];
while ($row = $priority_result->fetch_assoc()) {
    $priority_labels[] = $row['priority'];
    $priority_data[] = $row['count'];
}

// By Status
$status_sql = "SELECT status, COUNT(*) as count FROM Tickets WHERE user_id = ? GROUP BY status";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$status_result = $stmt->get_result();
$status_data = [];
$status_labels = [];
while ($row = $status_result->fetch_assoc()) {
    $status_labels[] = $row['status'];
    $status_data[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NMIMS Issue Tracker</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            min-height: 100vh;
            padding: 20px 0;
            color: white;
            position: fixed;
        }

        .logo-container {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo-container img {
            width: 150px;
            margin-bottom: 10px;
        }

        .logo-container h3 {
            font-size: 1.2em;
        }

        .user-profile {
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .user-profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid rgba(255,255,255,0.2);
        }

        .user-profile h4 {
            font-size: 1.1em;
            margin-bottom: 5px;
        }

        .user-profile p {
            font-size: 0.9em;
            opacity: 0.8;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            padding: 15px 25px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-item.active {
            background: #3498db;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .ticket-form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #e1e1e1;
            color: #333;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Chart container */
        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .chart-section h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
        }

        .chart-container {
            display: flex;
            flex-direction: row;
            overflow-x: auto;
            gap: 20px;
            padding-bottom: 10px;
        }

        .chart-wrapper {
            flex: 0 0 auto;
            width: 250px;
            background-color: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .chart-wrapper canvas {
            max-width: 100%;
            max-height: 230px;
        }

        .section-links {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-links h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .section-links p {
            margin: 10px 0;
        }

        .section-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .section-links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .chart-wrapper {
                width: 200px;
            }

            .chart-wrapper canvas {
                max-height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="logo.jpg" alt="NMIMS Logo">
            <h3>NMIMS Issue Tracker</h3>
        </div>
        
        <div class="user-profile">
            <img src="user-profile.jpg" alt="User Profile">
            <h4><?php echo htmlspecialchars($role); ?></h4>
            <p><?php echo htmlspecialchars($sap_id); ?></p>
        </div>

        <nav class="nav-menu">
            <a href="index.php" class="nav-item active">
                <span>📊</span> Dashboard
            </a>
            <a href="view_tickets.php" class="nav-item">
                <span>📋</span> My Tickets
            </a>
            <?php if (in_array($role, ['staff', 'maintenance', 'warden', 'rector'])): ?>
                <a href="staff_tickets.php" class="nav-item">
                    <span>📋</span> Assigned Tickets
                </a>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <a href="admin_dashboard.php" class="nav-item">
                    <span>📈</span> Admin Dashboard
                </a>
            <?php endif; ?>
            <a href="notifications.php" class="nav-item">
                <span>🔔</span> Notifications
            </a>
            <a href="logout.php" class="nav-item">
                <span>🚪</span> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Raise and track your non-academic issues</p>
        </div>

        <div class="ticket-form-container">
            <h2>Create New Ticket</h2>
            <?php if (isset($_GET['message'])): ?>
                <p class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></p>
            <?php endif; ?>
            <form action="create_ticket.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Issue Title</label>
                        <input type="text" name="title" id="title" required placeholder="Enter a descriptive title">
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <option value="infrastructure">Infrastructure</option>
                            <option value="hygiene">Hygiene</option>
                            <option value="security">Security</option>
                            <option value="hostel">Hostel</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority Level</label>
                        <select name="priority" id="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" required placeholder="Specify the location">
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="5" required placeholder="Provide detailed information about the issue"></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="file">Attachments</label>
                        <input type="file" name="file" id="file" accept="image/*,video/*,.pdf">
                        <div class="upload-info">
                            Supported formats: Images, Videos, PDF • Max file size: 10MB
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Ticket</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Cancel</button>
                </div>
            </form>
        </div>

        <div class="chart-section">
            <h3>Your Ticket Statistics</h3>
            <?php if (empty($category_data) && empty($priority_data) && empty($status_data)): ?>
                <p style="text-align: center; color: #666;">No tickets available to display statistics.</p>
            <?php else: ?>
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
            <?php endif; ?>
        </div>

        <div class="section-links">
            <h2>Quick Links</h2>
            <p><a href="view_tickets.php">View Your Tickets</a></p>
            <?php if (in_array($role, ['staff', 'maintenance', 'warden', 'rector'])): ?>
                <p><a href="staff_tickets.php">View Your Assigned Tickets</a></p>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
                <p><a href="admin_dashboard.php">View Admin Dashboard</a></p>
            <?php endif; ?>
        </div>
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