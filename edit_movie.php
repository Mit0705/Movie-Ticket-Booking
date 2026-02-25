<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: dashboard.php");
    exit();
}

$sql = "SELECT * FROM movies WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$movie = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$movie) {
    header("Location: dashboard.php");
    exit();
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? '';
    $release_date = $_POST['release_date'] ?? '';

    if (!empty($title) && !empty($release_date)) {
        $current_date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        $selected_date = new DateTime($release_date, new DateTimeZone('Asia/Kolkata'));

        if ($selected_date < $current_date) {
            $errorMessage = "Cannot select a release date in the past.";
        } else {
            $sql = "UPDATE movies SET title = ?, release_date = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $title, $release_date, $id);
            if (mysqli_stmt_execute($stmt)) {
                $successMessage = "Movie updated successfully.";
                header("Location: dashboard.php");
                exit();
            } else {
                $errorMessage = "Database error: " . mysqli_stmt_error($stmt);
            }
        }
    } else {
        $errorMessage = "Please provide both title and release date.";
    }
}

$minDate = date('Y-m-d');

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Movie</title>
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
        .movie-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .movie-table table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .movie-table th,
        .movie-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            font-size: 14px;
        }
        .movie-table th {
            background-color: #8b0000;
            color: white;
        }
        .movie-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        label {
            font-weight: bold;
        }
        input, textarea, select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const releaseDateInput = document.getElementById('release_date');
            const updateMinDate = () => {
                const now = new Date();
                const offset = 5.5 * 60;
                const localTime = new Date(now.getTime() + offset * 60 * 1000);
                const minDate = localTime.toISOString().slice(0, 10);
                releaseDateInput.setAttribute('min', minDate);
            };
            updateMinDate();
            setInterval(updateMinDate, 86400000);
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Edit Movie</h2>
            <form action="/Online_movie_ticket_booking/index.php" method="get">
                <button class="index">Home</button>
            </form>
            <form action="dashboard.php" method="get">
                <button class="dashboard">Dashboard</button>
            </form>
            <form action="add_show_time.php" method="get">
                <button class="add_show_time">Add Show Date&Time</button>
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
                <h2>Edit Movie</h2>
            </div>
            <div class="movie-card">
                <div class="movie-table">
                    <form method="POST">
                        <label for="title">Title:</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required>
                        <label for="release_date">Release Date:</label>
                        <input type="date" name="release_date" id="release_date" value="<?php echo htmlspecialchars($movie['release_date']); ?>" min="<?php echo $minDate; ?>" required>
                        <button type="submit">Update Movie</button>
                    </form>
                    <?php if ($successMessage): ?>
                        <div class="success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>