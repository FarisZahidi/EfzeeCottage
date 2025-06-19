<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get booking statistics
$stats = $conn->query("SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings
FROM bookings");
$booking_stats = $stats->fetch_assoc();

// Get recent bookings for calendar
$bookings_query = "SELECT b.*, h.name as homestay_name, u.name as guest_name, p.status as payment_status 
                  FROM bookings b 
                  JOIN homestays h ON b.homestay_id = h.homestay_id 
                  JOIN users u ON b.user_id = u.user_id 
                  LEFT JOIN payments p ON b.booking_id = p.booking_id 
                  ORDER BY b.check_in_date DESC";
$bookings = $conn->query($bookings_query);

// Get unread notifications
$notifications = null;
try {
    // Check if notifications table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->num_rows > 0) {
        $notifications_query = "SELECT * FROM notifications WHERE read_status = 0 ORDER BY created_at DESC LIMIT 5";
        $notifications = $conn->query($notifications_query);
    } else {
        error_log('Notifications table does not exist. Please run fix_notifications.sql');
    }
} catch (Exception $e) {
    error_log('Error checking/fetching notifications: ' . $e->getMessage());
    // Continue without notifications
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--primary-color);
        }

        .nav-link {
            color: #fff;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .nav-link.active {
            background-color: var(--secondary-color);
        }

        .stats-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 15px;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .calendar-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .booking-list {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-confirmed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .notifications-dropdown {
            min-width: 300px;
            padding: 0;
        }

        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <h3 class="text-white mb-4">Admin Panel</h3>
            <div class="nav flex-column">
                <a href="admin.php" class="nav-link active mb-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_bookings.php" class="nav-link mb-2">
                    <i class="fas fa-calendar-alt me-2"></i> Bookings
                </a>
                <a href="admin_homestays.php" class="nav-link mb-2">
                    <i class="fas fa-home me-2"></i> Homestays
                </a>
                <a href="admin_payments.php" class="nav-link mb-2">
                    <i class="fas fa-money-bill me-2"></i> Payments
                </a>
                <a href="admin_users.php" class="nav-link mb-2">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <a href="admin_reviews.php" class="nav-link mb-2">
                    <i class="fas fa-star me-2"></i> Reviews
                </a>
                <a href="logout.php" class="nav-link mt-4 text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Dashboard</h2>
                <div class="dropdown">
                    <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifications && $notifications->num_rows > 0): ?>
                            <span class="notification-badge"><?php echo $notifications->num_rows; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu notifications-dropdown">
                        <?php 
                        if ($notifications instanceof mysqli_result && $notifications->num_rows > 0): 
                            try {
                                while ($notification = $notifications->fetch_assoc()): ?>
                                    <div class="notification-item">
                                        <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                        <p class="mb-0 small"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                <?php endwhile;
                            } catch (Exception $e) {
                                error_log('Error processing notifications: ' . $e->getMessage());
                                echo '<div class="dropdown-item text-muted">Unable to load notifications</div>';
                            }
                        else: ?>
                            <div class="dropdown-item text-muted">No new notifications</div>
                            <?php if (!$notifications instanceof mysqli_result): ?>
                                <!-- Admin alert about missing notifications table -->
                                <div class="dropdown-item text-danger border-top mt-2 pt-2">
                                    <small><i class="fas fa-exclamation-triangle me-1"></i>Notifications system needs setup</small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Bookings</h5>
                            <h3><?php echo $booking_stats['total_bookings']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Confirmed</h5>
                            <h3><?php echo $booking_stats['confirmed_bookings']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Pending</h5>
                            <h3><?php echo $booking_stats['pending_bookings']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Cancelled</h5>
                            <h3><?php echo $booking_stats['cancelled_bookings']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="calendar-container mb-4">
                <div id="calendar"></div>
            </div>

            <!-- Recent Bookings -->
            <div class="booking-list">
                <h4 class="mb-4">Recent Bookings</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Guest</th>
                                <th>Homestay</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                                    <td><?php echo $booking['check_in_date']; ?></td>
                                    <td><?php echo $booking['check_out_date']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['payment_status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst($booking['payment_status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewBooking(<?php echo $booking['booking_id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $booking['booking_id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingModalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: [
                <?php 
                $bookings->data_seek(0);
                while ($booking = $bookings->fetch_assoc()): 
                    $color = '';
                    switch($booking['status']) {
                        case 'confirmed': $color = '#2ecc71'; break;
                        case 'pending': $color = '#f1c40f'; break;
                        case 'cancelled': $color = '#e74c3c'; break;
                    }
                ?>
                {
                    title: '<?php echo addslashes($booking['guest_name']) . " - " . addslashes($booking['homestay_name']); ?>',
                    start: '<?php echo $booking['check_in_date']; ?>',
                    end: '<?php echo $booking['check_out_date']; ?>',
                    backgroundColor: '<?php echo $color; ?>',
                    extendedProps: {
                        booking_id: <?php echo $booking['booking_id']; ?>
                    }
                },
                <?php endwhile; ?>
            ],
            eventClick: function(info) {
                viewBooking(info.event.extendedProps.booking_id);
            }
        });
        calendar.render();
    });

    function viewBooking(bookingId) {
        // Load booking details via AJAX
        fetch(`get_booking_details.php?id=${bookingId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('bookingModalContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('bookingModal')).show();
            });
    }

    function updateStatus(bookingId) {
        // Implement status update logic
    }

    // Mark notifications as read when clicked
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            // Implement notification read status update
        });
    });
</script>

</body>
</html>