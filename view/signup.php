<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sap_id = $_POST['sap_id'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate email
    if (!preg_match('/[a-zA-Z0-9._%+-]+@nmims\.edu\.in$/', $email)) {
        $error_message = "Please use your NMIMS email address (e.g., username@nmims.edu.in).";
    }
    // Validate passwords match
    elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    }
    else {
        // Check if the SAP ID already exists
        $sql = "SELECT * FROM Users WHERE sap_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $sap_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "SAP ID already exists.";
        } else {
            // Hash the password and insert the new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (sap_id, email, role, password) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $sap_id, $email, $role, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php?message=Registration successful. Please login.");
                exit;
            } else {
                $error_message = "Error during registration: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - NMIMS Issue Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
        }

        .signup-left {
            width: 40%;
            background: #2c3e50;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: fixed;
            height: 100vh;
        }

        .signup-right {
            width: 60%;
            margin-left: 40%;
            padding: 40px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 350px;
        }

        .logo-container img {
            width: 250px;
            height: auto;
            display: block;
            margin: 0 auto 15px;
            object-fit: contain;
        }

        .logo-container h2 {
            color: #2c3e50;
            font-size: 1.5em;
            margin-top: 15px;
        }

        .welcome-text {
            text-align: center;
            margin-top: 40px;
            color: white;
        }

        .welcome-text h1 {
            font-size: 2em;
            margin-bottom: 15px;
        }

        .welcome-text p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .signup-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
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
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        .signup-button {
            width: 100%;
            padding: 14px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .signup-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }

            .signup-left {
                width: 100%;
                position: static;
                height: auto;
                padding: 20px;
            }

            .signup-right {
                width: 100%;
                margin-left: 0;
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="signup-left">
        <div class="logo-container">
            <img src="logo.jpg" alt="NMIMS Logo">
            <h2>Issue Tracker</h2>
        </div>
        <div class="welcome-text">
            <h1>Welcome to NMIMS</h1>
            <p>Create your account to get started</p>
        </div>
    </div>

    <div class="signup-right">
        <div class="signup-container">
            <div class="signup-header">
                <h2>Create Your Account</h2>
                <p>Join the NMIMS community</p>
            </div>
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="sap_id">SAP ID</label>
                        <input type="text" name="sap_id" id="sap_id" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" id="role" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                            <option value="rector">Rector</option>
                            <option value="warden">Warden</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="email">University Email</label>
                        <input type="email" name="email" id="email" required pattern="[a-zA-Z0-9._%+-]+@nmims\.edu\.in">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="8">
                    </div>
                </div>
                <button type="submit" class="signup-button">Create Account</button>
                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>