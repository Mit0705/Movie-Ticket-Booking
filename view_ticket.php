<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=login_required");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $userInfo['username'] ?? 'User';
$email    = $userInfo['email']    ?? 'unknown@email.com';

$bookings = [];
$error_message = '';
$search_message = '';

date_default_timezone_set('Asia/Kolkata');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_GET['print_ticket'])) {
    $ticket_id = (int)$_GET['print_ticket'];

    $stmt = $conn->prepare("
        SELECT b.id, b.seats, b.total_amount, b.booking_date,
               m.title AS movie_title,
               s.show_time
        FROM bookings b
        JOIN movies m ON b.movie_id = m.id
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("<h2 style='color:red; text-align:center; padding:100px;'>Ticket not found!</h2>");
    }

    $showDate = (new DateTime($ticket['booking_date']))->format('d M Y');
    $showTime = date('h:i A', strtotime($ticket['show_time']));
    $barcode = "TKT" . str_pad($ticket['id'], 6, '0', STR_PAD_LEFT);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Ticket #<?php echo $ticket['id']; ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
            .ticket {
                max-width: 800px; margin: 20px auto; background: white; padding: 40px; border: 4px dashed #8B0000;
                border-radius: 15px; box-shadow: 0 0 30px rgba(0,0,0,0.2); text-align: center;
            }
            .header { font-size: 42px; color: #8B0000; margin-bottom: 20px; font-weight: bold; }
            .info { font-size: 22px; line-height: 2.2; }
            .info strong { color: #333; }
            .seats { background: #222; color: #0f0; padding: 20px; font-size: 28px; border-radius: 10px; margin: 20px 0; font-weight: bold; }
            .footer { margin-top: 40px; font-size: 16px; color: #555; }
            .barcode { margin-top: 30px; font-size: 40px; letter-spacing: 10px; color: #333; }
            @media print {
                body { background: white; margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="ticket">
            <h1 class="header">MOVIE TICKET</h1>
            <div class="info">
                <p><strong>Ticket ID:</strong> #<?php echo $ticket['id']; ?></p>
                <p><strong>Movie:</strong> <?php echo htmlspecialchars($ticket['movie_title']); ?></p>
                <p><strong>Date:</strong> <?php echo $showDate; ?></p>
                <p><strong>Time:</strong> <?php echo $showTime; ?></p>
                <div class="seats"><p>Seat No. :</p><?php echo htmlspecialchars($ticket['seats']); ?></div>
                <p><strong>Total Amount:</strong> ₹<?php echo number_format($ticket['total_amount']); ?></p>
                <p><strong>Booked By:</strong> <?php echo htmlspecialchars($username); ?><br>(<?php echo htmlspecialchars($email); ?>)</p>
            </div>
            <div class="footer">
                Thank you for booking! Please show this ticket at the cinema entrance.<br>
                Enjoy your movie!
            </div>
        </div>

        <div class="no-print" style="text-align:center; margin:30px;">
            <button onclick="window.print()" style="padding:15px 30px; font-size:18px; background:#8B0000; color:white; border:none; border-radius:8px; cursor:pointer;">
                Print / Save as PDF
            </button>
            <button onclick="window.close()" style="padding:15px 30px; font-size:18px; margin-left:15px; background:#666; color:white; border:none; border-radius:8px; cursor:pointer;">
                Close
            </button>
        </div>
    </body>
    </html>
    <?php
    exit;
}
try {
    $sql = "
        SELECT b.id, m.title AS movie_title, s.show_time, b.booking_date, b.seats, b.total_amount
        FROM bookings b
        JOIN movies m ON b.movie_id = m.id
        JOIN showtimes s ON b.showtime_id = s.id
        WHERE b.user_id = ?
    ";
    if (!empty($search)) {
        $sql .= " AND m.title LIKE ?";
    }
    $sql .= " ORDER BY b.booking_date DESC, s.show_time DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $searchParam = "%" . $search . "%";
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $searchParam, PDO::PARAM_STR);
    } else {
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bookings as &$booking) {
        if (!empty($booking['booking_date']) && !empty($booking['show_time'])) {
            try {
                $dateOnly = (new DateTime($booking['booking_date']))->format('Y-m-d');
                $fullDateTime = $dateOnly . ' ' . $booking['show_time'];
                $dt = new DateTime($fullDateTime);
                $booking['show_time_formatted'] = $dt->format('M j, Y - g:i A');
            } catch (Exception $e) {
                $booking['show_time_formatted'] = 'Invalid Date/Time';
            }
        } else {
            $booking['show_time_formatted'] = 'N/A';
       
        }
    }
    unset($booking);

    if (!empty($search)) {
        $count = count($bookings);
        $search_message = $count == 0 
            ? "No bookings found for: " . htmlspecialchars($search)
            : "$count booking" . ($count > 1 ? "s" : "") . " found";
    }

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    $error_message = 'Could not load bookings. Please try again later.';
}
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Tickets - Movie Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: url('images/background.png') center/cover no-repeat; display: flex; min-height: 100vh; }
        .container { display: flex; width: 100%; }
        .sidebar { width: 250px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px; border-radius: 0 15px 15px 0; box-shadow: 4px 0 15px rgba(0,0,0,0.3); color: white; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; }
        .sidebar button { width: 100%; padding: 14px; margin: 10px 0; border: none; border-radius: 10px; font-weight: bold; color: white; cursor: pointer; transition: 0.3s; }
        .sidebar button:hover { transform: scale(1.05); }
        .index { background: #17e8c2; }
        .dashboard { background: #33ff00; }
        .book-ticket { background: #ffc107; color: black; }
        .view-ticket { background: #e81755; }
        .logout { background: #9805f4; }

        .content { flex: 1; padding: 20px; }
        .header, .header2 { text-align: center; color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .header { background: #8b00008e; }
        .header2 { background: #3103ffbc; }
        .movie-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .search-bar { text-align: center; margin-bottom: 20px; }
        .search-bar input { padding: 12px; width: 320px; border: 1px solid #ddd; border-radius: 5px; }
        .search-bar button { padding: 12px 25px; background: #8B0000; color: white; border: none; border-radius: 5px; margin-left: 10px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: center; }
        th { background: #8B0000; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }

        .btn-download {
            background: #ff0000ff; color: white; padding: 10px 18px; text-decoration: none; border-radius: 8px;
            font-weight: bold; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-download:hover { background: #aa0000ff; }
        .btn-cancel { color: #d9534f; text-decoration: underline; margin-left: 15px; font-weight: bold; }
        .btn-cancel:hover { color: #a94442; }
        .past { color: #999; font-style: italic; }
        .no-tickets { text-align: center; font-size: 18px; color: #333; margin: 30px 0; }
        .search-message { text-align: center; font-weight: bold; color: blue; margin: 15px 0; }
        .not-found { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2>Bookings</h2>
            <form action="index.php"><button class="index">Home</button></form>
            <form action="dashboard.php"><button class="dashboard">Dashboard</button></form>
            <form action="book_ticket.php"><button class="book-ticket">Book Ticket</button></form>
            <form action="view_ticket.php"><button class="view-ticket">View Tickets</button></form>
            <form action="logout.php"><button class="logout">Logout</button></form>
        </div>

        <div class="content">
            <div class="header"><h2>Online Movie Ticket Booking</h2></div>
            <div class="header2"><h2>Your Bookings</h2></div>

            <div class="movie-card">
                <div class="search-bar">
                    <form method="get">
                        <input type="text" name="search" placeholder="Search by movie title..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>

                <?php if ($error_message): ?>
                    <p style="color:red; text-align:center;"><?php echo htmlspecialchars($error_message); ?></p>
                <?php elseif (empty($bookings) && empty($search)): ?>
                    <p class="no-tickets">No tickets booked yet. <a href="book_ticket.php">Book Now!</a></p>
                <?php else: ?>
                    <?php if ($search_message): ?>
                        <div class="<?php echo strpos($search_message, 'No') === 0 ? 'not-found' : 'search-message'; ?>">
                            <?php echo $search_message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($bookings)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Movie Title</th>
                                    <th>Show Date & Time</th>
                                    <th>Seats</th>
                                    <th>Total Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $now = new DateTime();
                                foreach ($bookings as $row):
                                    $dateOnly = (new DateTime($row['booking_date']))->format('Y-m-d');
                                    $fullDateTime = $dateOnly . ' ' . $row['show_time'];
                                    try {
                                        $showDateTime = new DateTime($fullDateTime);
                                    } catch (Exception $e) {
                                        $showDateTime = $now;
                                    }
                                    $isPast = $showDateTime < $now;
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['movie_title']); ?></td>
                                        <td><?php echo $row['show_time_formatted']; ?></td>
                                        <td><?php echo htmlspecialchars($row['seats']); ?></td>
                                        <td>₹<?php echo number_format($row['total_amount']); ?></td>
                                        <td>
                                            <a href="?print_ticket=<?php echo $row['id']; ?>" target="_blank" class="btn-download">
                                                Download
                                            </a>
                                            <?php if ($isPast): ?>
                                                <span class="past">Expired</span>
                                            <?php else: ?>
                                                <a href="cancel_ticket.php?id=<?php echo $row['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to cancel this ticket?');" 
                                                   class="btn-cancel">Cancel</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>