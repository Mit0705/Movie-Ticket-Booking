<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $email && $password) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->lastInsertId();
                header("Location: login.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Integrity constraint')) {
                $error = "Username or Email already exists.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background-image: url("images/background.png");
            background-size: cover;
            background-position: center;
            height: 100vh;

            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-container {
            margin-top: calc(50vh - 160px); 
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.25);
            width: 350px;
        }


        header {
            width: 100%;
            background-color: #8b0000;
            color: white;
            text-align: center;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
        }

        nav {
            margin-top: 10px;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 15px;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        nav a:hover {
            color: #ffca28;
            text-decoration: underline;
        }
        .login-container h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .login-container input[type="text"],
        .login-container input[type="email"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
        }
        .login-container input:focus {
            border-color: #667eea;
            box-shadow: 0 0 6px rgba(102,126,234,0.6);
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        .login-container button:hover {
            background: #5645d9;
        }
        .login-container a {
            display: inline-block;
            margin-top: 12px;
            font-size: 14px;
            color: #667eea;
            text-decoration: none;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Movie Ticket Booking System</h1>
        <nav>
            <a href="index.php">Home</a> |
            <a href="admin/login.php">Admin</a> |
            <a href="login.php">Login</a> |
            <a href="register.php">Register</a>
        </nav>
    </header>
    <div class="login-container">
        <h1>Register</h1>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="">
            <label for="username">Username</label>
            <input type="text" name="username" required>

            <label for="email">Email</label>
            <input type="email" name="email" required>

            <label for="password">Password</label>
            <input type="password" name="password" required>

            <button type="submit">Register</button>
            <p>Already have an account? <a href="login.php">Login Here</a></p>
        </form>
    </div>
</body>
</html>