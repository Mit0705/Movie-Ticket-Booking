<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    header("Location: view_users.php?msg=User+deleted+successfully");
    exit();
}

$users = [];
$error_message = '';
$search_message = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT id, username, email FROM users";
    if (!empty($search)) {
        $searchParam = '%' . mysqli_real_escape_string($conn, $search) . '%';
        $sql .= " WHERE username LIKE '$searchParam' OR email LIKE '$searchParam'";
    }
    $sql .= " ORDER BY id ASC";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        mysqli_free_result($result);

        if (!empty($search)) {
            $user_count = count($users);
            if ($user_count === 0) {
                $search_message = "No users found for the search term: " . htmlspecialchars($search);
            } elseif ($user_count === 1) {
                $search_message = "One user found: " . htmlspecialchars($users[0]['username']);
            } else {
                $number_words = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'];
                $count_word = ($user_count <= 10) ? $number_words[$user_count] : $user_count;
                $search_message = "$count_word users found";
            }
        }
    } else {
        throw new Exception('Query failed: ' . mysqli_error($conn));
    }
} catch (Exception $e) {
    error_log('DB error in view_users.php: ' . $e->getMessage());
    $error_message = 'Could not retrieve users right now. Please try later.';
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - Online Movie Ticket Booking</title>
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
            text-decoration: none;
            font-weight: bold;
            text-decoration: underline;
        }
        .movie-table a:hover {
            text-decoration: underline;
        }
        .message {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-top: 10px;
        }
        .search-message {
            color: blue;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        .not-found {
            color: red;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        .no-tickets {
            text-align: center;
            font-weight: bold;
            color: #333;
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
            <h2>View Users</h2>
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
                <h2>Registered Users</h2>
            </div>
            <div class="movie-card">
                <div class="search-bar">
                    <form method="get" action="view_users.php">
                        <input type="text" name="search" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <div class="movie-table">
                    <?php if (!empty($error_message)): ?>
                        <p style="color:red;"><?php echo htmlspecialchars($error_message); ?></p>
                    <?php elseif (empty($users) && empty($search)): ?>
                        <p class="no-tickets">No users found</p>
                    <?php else: ?>
                        <?php if ($search_message): ?>
                            <div class="<?php echo $user_count === 0 ? 'not-found' : 'search-message'; ?>">
                                <?php echo $search_message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($users)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $row): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <a href="view_users.php?delete_id=<?php echo $row['id']; ?>"
                                                   class="delete"
                                                   onclick="return confirm('Are you sure you want to delete this user?');">
                                                   Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if (isset($_GET['msg'])): ?>
                    <div class="message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
