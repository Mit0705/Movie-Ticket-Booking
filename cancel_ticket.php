<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$conn = new mysqli("localhost", "root", "", "ticket_booking");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $ticket_id = (int)$_GET['id'];
    $user_id   = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $ticket_id, $user_id);
        if ($stmt->execute()) {
            header("Location: view_ticket.php?msg=Ticket cancelled successfully");
            exit();
        } else {
            echo "Error cancelling ticket: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Prepare failed: " . $conn->error;
    }
} else {
    echo "Invalid request. No ticket ID provided.";
}
$conn->close();
?>