<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$email = $_SESSION['email'] ?? "test@gmail.com";
$username = $_SESSION['username'] ?? 'User';
$successMessage = "";
$notFoundMessage = "";
$oneFoundMessage = $_SESSION['oneFoundMessage'] ?? "";

if (isset($_GET['search']) || isset($_POST['movie'])) {
    $_SESSION['oneFoundMessage'] = "";
}

$conn = new mysqli("localhost", "root", "", "ticket_booking");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
date_default_timezone_set('Asia/Kolkata');
$currentDateTime = new DateTime();
$currentDate = $currentDateTime->format('Y-m-d');
$currentTime = $currentDateTime->format('H:i:s');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$movies = [];
$sql = "SELECT id, title FROM movies";
if (!empty($search)) {
    $sql .= " WHERE title LIKE '%" . $conn->real_escape_string($search) . "%'";
}
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $movies[$row['id']] = $row['title'];
    }
    if (count($movies) === 1 && empty($_POST['movie']) && empty($_SESSION['oneFoundMessage'])) {
        $selectedMovie = key($movies);
        $_SESSION['oneFoundMessage'] = "One movie found: " . htmlspecialchars($movies[$selectedMovie]);
        $oneFoundMessage = $_SESSION['oneFoundMessage'];
    }
} else if (!empty($search)) {
    $notFoundMessage = "No movies found for the search term: " . htmlspecialchars($search);
}
$result->free();

$showtimes = [];
$result = $conn->query("SELECT id, show_time FROM showtimes ORDER BY show_time");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $showtimes[$row['id']] = $row['show_time'];
    }
}
$result->free();

$bookedSeats = [];
$selectedMovie = $_POST['movie'] ?? (isset($selectedMovie) ? $selectedMovie : 0);
$selectedShowtime = $_POST['showtime_id'] ?? 0;
$selectedDate = $_POST['booking_date'] ?? '';

if ($selectedMovie && $selectedShowtime && $selectedDate) {
    $stmt = $conn->prepare("SELECT seats FROM bookings WHERE movie_id=? AND showtime_id=? AND booking_date=?");
    $stmt->bind_param("iis", $selectedMovie, $selectedShowtime, $selectedDate);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $seatsArr = explode(",", $row['seats']);
        foreach ($seatsArr as $s) {
            $bookedSeats[] = (int)trim($s);
        }
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm'])) {
    $movie_id = (int)$_POST['movie'];
    $showtime_id = (int)$_POST['showtime_id'];
    $booking_date = $_POST['booking_date'] ?? '';
    $seats = $_POST['seats'] ?? '';
    $seatCount = $seats ? count(explode(',', $seats)) : 0;
    $total_amount = $seatCount * 200;

    if ($movie_id <= 0) {
        $successMessage = "Please select a movie.";
    } else if ($showtime_id <= 0) {
        $successMessage = "Please select a showtime.";
    } else if (empty($booking_date)) {
        $successMessage = "Please select a booking date.";
    } else if ($seatCount == 0) {
        $successMessage = "Please select at least one seat.";
    } else {
        $showtime = $showtimes[$showtime_id] ?? '';
        $selectedDateTimeStr = "$booking_date $showtime";
        $selectedDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $selectedDateTimeStr);

        if (!$selectedDateTime) {
            $successMessage = "Invalid date or time format.";
        } else if ($selectedDateTime < $currentDateTime) {
            $successMessage = "Cannot book a showtime that has already passed.";
        } else {
            $conflict = false;
            $stmt = $conn->prepare("SELECT seats FROM bookings WHERE movie_id=? AND showtime_id=? AND booking_date=?");
            $stmt->bind_param("iis", $movie_id, $showtime_id, $booking_date);
            $stmt->execute();
            $res = $stmt->get_result();
            $alreadyBooked = [];
            while ($row = $res->fetch_assoc()) {
                $alreadyBooked = array_merge($alreadyBooked, explode(",", $row['seats']));
            }
            $stmt->close();

            $alreadyBooked = array_map('trim', $alreadyBooked);
            $selectedSeatsArr = explode(",", $seats);
            foreach ($selectedSeatsArr as $seat) {
                if (in_array($seat, $alreadyBooked)) {
                    $conflict = true;
                    break;
                }
            }

            if ($conflict) {
                $successMessage = "Those seats are already booked. Please choose different seats.";
            } else {
                $sql = "INSERT INTO bookings (user_id, movie_id, showtime_id, booking_date, seats, total_amount) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $successMessage = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("iiissd", $user_id, $movie_id, $showtime_id, $booking_date, $seats, $total_amount);
                    if ($stmt->execute()) {
                        $successMessage = "Booking successful!<br>
                                           Movie: " . htmlspecialchars($movies[$movie_id]) . "<br>
                                           Date: " . htmlspecialchars($booking_date) . "<br>
                                           Show Time: " . htmlspecialchars($showtimes[$showtime_id]) . "<br>
                                           Seat No: " . htmlspecialchars($seats) . "<br>
                                           Total: ₹" . $total_amount;
                    } else {
                        $successMessage = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Online Movie Ticket Booking</title>
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
        .sidebar .book-ticket { background-color: #ffc107; color: #fff; }
        .sidebar .view-ticket { background-color: #e81755; color: #fff; }
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
        .header2 {
            text-align: center;
            background-color: #3103ffbc;
            color: white;
            padding: 5px 10px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .movie-card {
            background-color: #ffffffac;
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
        select, input[type="date"] {
            width: 100%;
            margin: 10px 0;
            padding: 5px;
            border-radius: 5px;
        }
        .seats {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 5px;
            margin: 5px 0;
        }
        .seat {
            width: 30px;
            height: 30px;
            background-color: #e0ffff;
            border: 1px solid #ccc;
            cursor: pointer;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
        }
        .seat.booked {
            background-color: gray;
            cursor: not-allowed;
            color: white;
        }
        .seat.selected { 
            background-color: #00ff7f; 
        }
        button {
            background-color: #8b0000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { 
            background-color: #a52a2a; 
        }
        .success { 
            color: green; 
            font-weight: bold; 
            margin-top: 10px; 
        }
        .not-found {
            color: red;
            font-weight: bold;
            margin-top: 10px;
            text-align: center;
        }
        .one-found {
            color: blue;
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
            <h2>Ticket Booking</h2>
            <form action="index.php" method="get"><button class="index">Home</button></form>
            <form action="dashboard.php" method="get"><button class="dashboard">Dashboard</button></form>
            <form action="book_ticket.php" method="get"><button class="book-ticket">Book Ticket</button></form>
            <form action="view_ticket.php" method="get"><button class="view-ticket">View Ticket</button></form>
            <form action="logout.php" method="get"><button class="logout">Logout</button></form>
        </div>
        <div class="content">
            <div class="header">
                <h2>Book Online Movie Ticket</h2>
            </div>
            <div class="header2"><h2>Book Movie Ticket</h2></div>
            <div class="movie-card">
                <div class="search-bar">
                    <form method="get" action="book_ticket.php">
                        <input type="text" name="search" placeholder="Search by movie title..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <?php if ($notFoundMessage): ?>
                    <div class="not-found"><?php echo $notFoundMessage; ?></div>
                <?php else: ?>
                    <?php if ($oneFoundMessage): ?>
                        <div class="one-found"><?php echo $oneFoundMessage; ?></div>
                    <?php endif; ?>
                    <form method="post" action="" id="booking_form">
                        <select name="movie" id="movie_select" required onchange="this.form.submit()">
                            <option value="">Select Movie</option>
                            <?php foreach ($movies as $id => $title): ?>
                                <option value="<?php echo $id; ?>" <?php if ($selectedMovie == $id) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="booking_date" id="booking_date" value="<?php echo htmlspecialchars($selectedDate); ?>" min="<?php echo $currentDate; ?>" required onchange="this.form.submit()" />
                        <select name="showtime_id" required onchange="this.form.submit()">
                            <option value="">Select Show Time</option>
                            <?php
                            if ($selectedDate) {
                                foreach ($showtimes as $id => $time) {
                                    $showDateTimeStr = "$selectedDate $time";
                                    $showDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $showDateTimeStr);
                                    if ($showDateTime && $showDateTime >= $currentDateTime) {
                                        echo "<option value='$id' " . ($selectedShowtime == $id ? "selected" : "") . ">" . htmlspecialchars($time) . "</option>";
                                    }
                                }
                            } else {
                                foreach ($showtimes as $id => $time) {
                                    echo "<option value='$id' " . ($selectedShowtime == $id ? "selected" : "") . ">" . htmlspecialchars($time) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <div class="seats" id="seats">
                            <?php
                            for ($i = 1; $i <= 50; $i++) {
                                $isBooked = in_array($i, $bookedSeats);
                                $class = $isBooked ? 'seat booked' : 'seat';
                                $content = $isBooked ? "X" : $i;
                                echo "<div class='$class' data-seat='$i'>$content</div>";
                            }
                            ?>
                        </div>
                        <input type="hidden" name="seats" id="selectedSeats" />
                        <div style="display: flex; justify-content: center; margin-top:15px;">
                            <button type="submit" name="confirm">Confirm Booking</button>
                        </div>
                        <?php if ($successMessage): ?>
                            <div class="success"><?php echo $successMessage; ?></div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        const form = document.getElementById('booking_form');
        const movieSelect = document.getElementById('movie_select');

        if (form && movieSelect) {
            <?php if ($selectedMovie && count($movies) === 1 && empty($_POST['movie']) && !isset($_SESSION['form_submitted'])): ?>
                document.addEventListener('DOMContentLoaded', function () {
                    form.submit();
                    <?php $_SESSION['form_submitted'] = true; ?>
                });
            <?php else: ?>
                <?php unset($_SESSION['form_submitted']); ?>
            <?php endif; ?>
        }

        const seats = document.querySelectorAll('.seat');
        const selectedSeatsInput = document.getElementById('selectedSeats');
        seats.forEach(seat => {
            seat.addEventListener('click', () => {
                if (!seat.classList.contains('booked')) {
                    seat.classList.toggle('selected');
                    updateSelectedSeats();
                }
            });
        });
        function updateSelectedSeats() {
            const selected = document.querySelectorAll('.seat.selected');
            const seatsArray = Array.from(selected).map(seat => seat.getAttribute('data-seat'));
            selectedSeatsInput.value = seatsArray.join(',');
        }
    </script>
</body>
</html>