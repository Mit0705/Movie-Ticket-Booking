<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT * FROM movies";
if (!empty($search)) {
    $sql .= " WHERE title LIKE '%$search%'";
}

$movies = mysqli_query($conn, $sql);

$current_date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
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
        .sidebar .index { background-color: #17e8c2ff; }
        .sidebar .dashboard { background-color: #33ff00ff; }
        .sidebar .add_show_time { background-color: #ff9100ff; }
        .sidebar .add_movie { background-color: #ffc107; }
        .sidebar .view_users { background-color: #5107ffff; }
        .sidebar .logout { background-color: #9805f4; }

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
        .movie-table a {
            margin: 0 5px;
            color: #3300ffff;
            text-decoration: underline;
            font-weight: bold;
        }
        .status-expired {
            color: red;
            font-weight: bold;
        }
        .status-active {
            color: green;
            font-weight: bold;
        }
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }
        .search-bar input[type="text"] {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .search-bar button {
            padding: 10px 20px;
            margin-left: 10px;
            background-color: #8b0000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .search-bar button:hover {
            background-color: #a30000;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="sidebar">
            <h2>Admin Dashboard</h2>

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
                <h2>Movies</h2>
            </div>

            <div class="movie-card">

                <div class="search-bar">
                    <form method="get" action="dashboard.php">
                        <input type="text" name="search" placeholder="Search by movie title..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>

                <div class="movie-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Sr No.</th>
                                <th>Title</th>
                                <th>Release Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $sn = 1;
                            while ($movie = mysqli_fetch_assoc($movies)) {
                                $release_date = new DateTime($movie['release_date'], new DateTimeZone('Asia/Kolkata'));
                                $status = ($release_date < $current_date) ? 'Expired' : 'Active';
                                $status_class = ($release_date < $current_date) ? 'status-expired' : 'status-active';
                            ?>

                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                <td><?php echo htmlspecialchars($movie['release_date']); ?></td>
                                <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                                <td>
                                    <a href="edit_movie.php?id=<?php echo $movie['id']; ?>">Edit Date</a>
                                    <a href="delete_movie.php?id=<?php echo $movie['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>

                            <?php } ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
