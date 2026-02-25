<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);

    if ($admin && $password === $admin['password']) {
    $_SESSION['admin_id'] = $admin['id'];
    header("Location: dashboard.php");
    exit;
} else {
    $error = "Invalid username or password";
}

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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
            margin-top: calc(50vh - 150px); 
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
        }
        .login-container input[type="text"],
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
    </style>
</head>
<body>
    <header>
        <h1>Movie Ticket Booking System</h1>
        <nav>
            <a href="../index.php">Home</a> |
            <a href="admin/login.php">Admin</a> |
            <a href="../login.php">Login</a> |
            <a href="../register.php">Register</a>
        </nav>
    </header>
    <div class="login-container">
        <h1 style="text-align: center">Admin Login</h1>
        <form action="login.php" method="post">
            Username:<input type="text" name="username" required>
            Password:<input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>