<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];

    // Insert ticket into the database with assigned_to as NULL
    $sql = "INSERT INTO Tickets (user_id, category, description, priority, assigned_to) VALUES (?, ?, ?, ?, NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $category, $description, $priority);

    if ($stmt->execute()) {
        $ticket_id = $conn->insert_id;

        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'mp4', 'pdf'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = uniqid() . '.' . $file_ext;
                $file_path = 'uploads/' . $new_file_name;
                if (move_uploaded_file($file_tmp, $file_path)) {
                    $file_type = in_array($file_ext, ['jpg', 'jpeg', 'png']) ? 'photo' : ($file_ext === 'mp4' ? 'video' : 'document');
                    $sql = "INSERT INTO Attachments (ticket_id, file_url, file_type) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $ticket_id, $file_path, $file_type);
                    $stmt->execute();
                }
            }
        }

        header("Location: index.php?message=Ticket created successfully");
        exit;
    } else {
        echo "Error creating ticket: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>