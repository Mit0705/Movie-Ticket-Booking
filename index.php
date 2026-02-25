<?php
session_start();

date_default_timezone_set('Asia/Kolkata');

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Movie Ticket Booking</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
        }
        header {
            background-color: #8b0000;
            color: white;
            text-align: center;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        header h1 {
            font-size: 2rem;
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
        .main-content {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .main-content h2 {
            color: #a52a2a;
            margin-bottom: 20px;
        }
        .main-content p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .hero {
            position: relative;
            height: 400px;
            background: url('images/background.png') no-repeat center center/cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #8b0000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #a52a2a;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
        .modal-content h3 {
            color: #a52a2a;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .modal-content p {
            margin-bottom: 20px;
            font-size: 1rem;
        }
        .modal-content .modal-btn {
            padding: 10px 20px;
            background-color: #8b0000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 1rem;
        }
        .modal-content .modal-btn:hover {
            background-color: #a52a2a;
        }
        @media (max-width: 768px) {
            .main-content {
                margin: 10px;
                padding: 15px;
            }
            .hero {
                height: 250px;
            }
            nav a {
                margin: 0 10px;
                font-size: 1rem;
            }
            .modal-content {
                width: 95%;
                padding: 15px;
            }
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
    <div class="main-content">
        <div class="hero">
            <div class="hero-overlay">Book Your Favorite Movies Now!</div>
        </div>
        <h2>Welcome to Movie Ticket Booking System</h2>
        <p>Explore the latest movies and book your tickets online very easy.</p>
        <a href="#" class="btn" id="bookNowBtn">Book Now</a>
    </div>
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <h3>Login Required</h3>
            <p>You must be logged in to book tickets. Please log in to continue.</p>
            <button class="modal-btn" id="modalLoginBtn">Go to Login</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookNowBtn = document.getElementById('bookNowBtn');
            const loginModal = document.getElementById('loginModal');
            const modalLoginBtn = document.getElementById('modalLoginBtn');
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

            bookNowBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (isLoggedIn) {
                    window.location.href = 'book_ticket.php';
                } else {
                    loginModal.style.display = 'flex';
                }
            });

            modalLoginBtn.addEventListener('click', function() {
                loginModal.style.display = 'none';
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 300);
            });
        });
    </script>
</body>
</html>