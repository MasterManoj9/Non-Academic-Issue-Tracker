<?php
include 'db_connect.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token exists and update the user's email_verified status
    $sql = "UPDATE Users SET email_verified = TRUE, verification_token = NULL WHERE verification_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "Email verified successfully! You can now <a href='login.php'>log in</a>.";
    } else {
        echo "Invalid or expired verification token.";
    }
    $stmt->close();
} else {
    echo "No verification token provided.";
}
$conn->close();
?>