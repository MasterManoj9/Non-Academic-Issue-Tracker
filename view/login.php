<?php
session_start();
include 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sap_id = $_POST['sap_id'];
    $password = $_POST['password'];

    $sql = "SELECT user_id, role, password FROM Users WHERE sap_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sap_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Invalid SAP ID or password.";
        }
    } else {
        $error_message = "Invalid SAP ID or password.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NMIMS Issue Tracker</title>
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

        .login-left {
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

        .login-right {
            width: 60%;
            margin-left: 40%;
            padding: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
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

        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }

            .login-left {
                width: 100%;
                position: static;
                height: auto;
                padding: 20px;
            }

            .login-right {
                width: 100%;
                margin-left: 0;
                padding: 20px;
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 25px;
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
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .login-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .signup-link a {
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

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-left">
        <div class="logo-container">
            <img src="logo.jpg" alt="NMIMS Logo">
            <h2>Issue Tracker</h2>
        </div>
        <div class="welcome-text">
            <h1>Welcome Back!</h1>
            <p>Login to access your account</p>
        </div>
    </div>

    <div class="login-right">
        <div class="login-container">
            <h2>Login to Your Account</h2>
            <p>Enter your credentials below</p>
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <?php if (isset($_GET['message'])): ?>
                <p class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="sap_id">SAP ID</label>
                    <input type="text" name="sap_id" id="sap_id" required placeholder="Enter your SAP ID">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="login-button">Login</button>
                <div class="signup-link">
                    New to NMIMS? <a href="signup.php">Create an account</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>