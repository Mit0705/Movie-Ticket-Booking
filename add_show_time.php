<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

$admin_id = (int)$_SESSION['admin_id'];
$email = $_SESSION['email'] ?? "admin@gmail.com";
$username = $_SESSION['username'] ?? 'Admin';

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $movie_id = $_POST['movie_id'] ?? '';
    $show_time = $_POST['show_time'] ?? '';

    if (!empty($movie_id) && !empty($show_time)) {
        $check = $conn->prepare("SELECT id FROM showtimes WHERE movie_id = ? AND show_time = ?");
        $check->bind_param("is", $movie_id, $show_time);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errorMessage = "This show time already exists for the selected movie.";
        } else {
            $stmt = $conn->prepare("INSERT INTO showtimes (movie_id, show_time) VALUES (?, ?)");
            $stmt->bind_param("is", $movie_id, $show_time);

            if ($stmt->execute()) {
                $successMessage = "Show time added successfully.";
            } else {
                $errorMessage = "Database error: " . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
    } else {
        $errorMessage = "Please select a movie and enter a valid show time.";
    }
}

$movies = $conn->query("SELECT id, title FROM movies");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Show Time</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-image: url("images/background.png");
            background-size: cover;
            background-position: center;
            display: flex;
            min-height: 100vh;
        }
        .container {
            display: flex;
            width: 100%;
        }
        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 0 15px 15px 0;
            box-shadow: 4px 0 15px rgba(0,0,0,0.3);
            color: #fff;
        }
        .sidebar button {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            margin: 12px 0;
            padding: 14px;
            font-weight: bold;
            color: #fff;
            transition: 0.3s;
        }
        .sidebar button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }
        .sidebar .index { background-color: #17e8c2ff; color: #fff; }
        .sidebar .dashboard { background-color: #33ff00ff; color: #fff; }
        .sidebar .add_show_time { background-color: #ff9100ff; color: #fff; }
        .sidebar .add_movie { background-color: #ffc107; color: #fff; }
        .sidebar .view_users { background-color: #5107ffff; color: #fff; }
        .sidebar .logout { background-color: #9805f4; color: #fff; }
        .content {
            flex: 1;
            padding: 20px;
        }
        .header {
            text-align: center;
            background-color: #8b00008e;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .form-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: auto;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        select, input[type="time"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button.submit-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        button.submit-btn:hover {
            background-color: #257237ff;
        }
        .success {
            color: green;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        h2{
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Add Show Time</h2>
            <form action="/Online_movie_ticket_booking/index.php" method="get">
                <button class="index">Home</button>
            </form>
            <form action="dashboard.php" method="get">
                <button class="dashboard">Dashboard</button>
            </form>
            <form action="add_show_time.php" method="get">
                <button class="add_show_time">Add Show Time</button>
            </form>
            <form action="add_movie.php" method="get">
                <button class="add_movie">Add Movie</button>
            </form>
            <form action="view_users.php" method="get">
                <button class="view_users">View Users</button>
            </form>
            <form action="logout.php" method="get">
                <button class="logout">Logout</button>
            </form>
        </div>
        <div class="content">
            <div class="header">
                <h2>Add Show Time</h2>
            </div>
            <div class="form-card">
                <form method="post">
                    <label for="movie_id">Select Movie:</label>
                    <select name="movie_id" id="movie_id" required>
                        <option value="">-- Select a Movie --</option>
                        <?php if ($movies && $movies->num_rows > 0): ?>
                            <?php while ($movie = $movies->fetch_assoc()): ?>
                                <option value="<?= $movie['id'] ?>"><?= htmlspecialchars($movie['title']) ?></option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">No movies found</option>
                        <?php endif; ?>
                    </select>
                    <label for="show_time">Select Show Time:</label>
                    <input type="time" name="show_time" id="show_time" required>
                    <button type="submit" class="submit-btn">Add Show Time</button>
                </form>
                <?php if ($successMessage): ?>
                    <div class="success"><?= htmlspecialchars($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="error"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
