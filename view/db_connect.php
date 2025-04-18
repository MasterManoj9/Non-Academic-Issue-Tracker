<?php
$host = 'localhost';
$username = 'root'; // Default XAMPP username
$password = '';     // Default XAMPP password (empty)
$database = 'non_acd_issue'; // Updated database name

// Create a connection
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to avoid encoding issues
$conn->set_charset("utf8mb4");
?>