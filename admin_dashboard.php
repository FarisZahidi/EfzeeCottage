<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get booking statistics
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
FROM bookings";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get unread notifications
$notifications_query = "SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 5";
$notifications = $conn->query($notifications_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EfzeeCottage</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css' rel='stylesheet'>
    
    <style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #34495e;
        --accent-color: #3498db;
    }

    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .sidebar {
        background-color: var(--primary-color);
        min-height: 100vh;
        padding: 20px 0;
    }

    .sidebar .nav-link {
        color: #ecf0f1;
        padding: 10px 20px;
        margin: 5px 0;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        background-color: var(--accent-color);
        color: white;
    }

    .sidebar .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .main-content {
        padding: 20px;
    }

    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-card i {
        font-size: 2rem;
        margin-bottom: 15px;
    }

    .calendar-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 20px;
    }

    .status-select,
    .payment-status-select {
        width: auto;
        display: inline-block;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        padding: 3px 6px;
        border-radius: 50%;
        background: #e74c3c;
        color: white;
        font-size: 0.7rem;
    }

    #bookingCalendar {
        height: 600px;
    }

    .modal-xl {
        max-width: 90%;
    }

    .receipt-preview img {
        max-width: 100%;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar">
            <h4 class="text-white mb-4 px-3">EfzeeCottage</h4>
            <nav class="nav flex-column">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a class="nav-link" href="admin_bookings.php">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a class="nav-link" href="admin_homestays.php">
                    <i class="fas fa-house"></i> Homestays
                </a>
                <a class="nav-link" href="admin_payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a class="nav-link" href="admin_reviews.php">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a class="nav-link" href="admin_discounts.php">
                    <i class="fas fa-tag"></i> Discounts
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">
            <div id="alertContainer"></div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-calendar-alt text-primary"></i>
                        <h3><?php echo $stats['total_bookings']; ?></h3>
                        <p class="mb-0">Total Bookings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-check-circle text-success"></i>
                        <h3><?php echo $stats['confirmed_bookings']; ?></h3>
                        <p class="mb-0">Confirmed Bookings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-clock text-warning"></i>
                        <h3><?php echo $stats['pending_bookings']; ?></h3>
                        <p class="mb-0">Pending Bookings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <i class="fas fa-times-circle text-danger"></i>
                        <h3><?php echo $stats['cancelled_bookings']; ?></h3>
                        <p class="mb-0">Cancelled Bookings</p>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="calendar-container">
                <div id="bookingCalendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent"></div>
        </div>
    </div>
</div>

<!-- Date Bookings Modal -->
<div class="modal fade" id="dateBookingsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bookings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dateBookingsContent"></div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="receiptImage" src="" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.js'></script>
<script src="assets/js/admin-calendar.js"></script>

</body>
</html>