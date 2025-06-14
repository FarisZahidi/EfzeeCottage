<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: homepage.php');
    exit();
}

// Database connection
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }

        .nav-link {
            color: #fff;
        }

        .nav-link:hover {
            background-color: #495057;
        }

        .nav-link.active {
            background-color: #0d6efd;
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
                    <a href="admin_amenities.php" class="nav-link mb-2">
                        <i class="fas fa-concierge-bell me-2"></i> Amenities
                    </a>
                    <a href="admin_payments.php" class="nav-link mb-2">
                        <i class="fas fa-money-bill me-2"></i> Payments
                    </a>
                    <a href="admin_users.php" class="nav-link mb-2">
                        <i class="fas fa-users me-2"></i> Users
                    </a>
                    <!-- <a href="admin_reviews.php" class="nav-link mb-2">
                        <i class="fas fa-star me-2"></i> Reviews
                    </a> -->
                    <a href="logout.php" class="nav-link mt-4 text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <?php
                    // Get total bookings
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings");
                    $stmt->execute();
                    $bookings_count = $stmt->get_result()->fetch_assoc()['count'];

                    // Get total revenue
                    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
                    $stmt->execute();
                    $total_revenue = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

                    // Get total users
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'guest'");
                    $stmt->execute();
                    $users_count = $stmt->get_result()->fetch_assoc()['count'];

                    // Get average rating
                    $stmt = $conn->prepare("SELECT AVG(rating) as avg FROM reviews");
                    $stmt->execute();
                    $avg_rating = number_format($stmt->get_result()->fetch_assoc()['avg'] ?? 0, 1);
                    ?>

                    <!-- Bookings Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total
                                            Bookings</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $bookings_count; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total
                                            Revenue</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">RM
                                            <?php echo number_format($total_revenue, 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Users
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Card -->
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Average
                                            Rating</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $avg_rating; ?> /
                                            5.0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="row">
                    <!-- Recent Bookings -->
                    <div class="col-12 col-xl-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $conn->prepare("SELECT b.*, u.name as user_name, h.name as homestay_name 
                                                   FROM bookings b 
                                                   JOIN users u ON b.user_id = u.user_id 
                                                   JOIN homestays h ON b.homestay_id = h.homestay_id 
                                                   ORDER BY b.homestay_id DESC LIMIT 5");
                                $stmt->execute();
                                $recent_bookings = $stmt->get_result();

                                while ($booking = $recent_bookings->fetch_assoc()): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <div class="font-weight-bold">
                                                <?php echo htmlspecialchars($booking['homestay_name']); ?>
                                            </div>
                                            <div class="small text-gray-600">
                                                By <?php echo htmlspecialchars($booking['user_name']); ?> •
                                                <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> -
                                                <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-<?php
                                        echo match ($booking['status']) {
                                            'confirmed' => 'success',
                                            'pending' => 'warning',
                                            'cancelled' => 'danger'
                                        };
                                        ?>"><?php echo ucfirst($booking['status']); ?></span>
                                    </div>
                                <?php endwhile; ?>
                                <a href="admin_bookings.php" class="btn btn-primary btn-sm mt-3">View All Bookings</a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="col-12 col-xl-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Reviews</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt = $conn->prepare("SELECT r.*, u.name as user_name, h.name as homestay_name 
                                                   FROM reviews r 
                                                   JOIN bookings b ON r.booking_id = b.booking_id 
                                                   JOIN users u ON b.user_id = u.user_id 
                                                   JOIN homestays h ON b.homestay_id = h.homestay_id 
                                                   ORDER BY r.created_at DESC LIMIT 5");
                                $stmt->execute();
                                $recent_reviews = $stmt->get_result();

                                while ($review = $recent_reviews->fetch_assoc()): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <div class="font-weight-bold">
                                                <?php echo htmlspecialchars($review['homestay_name']); ?>
                                            </div>
                                            <div class="text-warning">
                                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="small text-gray-600">By
                                            <?php echo htmlspecialchars($review['user_name']); ?>
                                        </div>
                                        <div class="mt-1"><?php echo htmlspecialchars($review['comment']); ?></div>
                                    </div>
                                <?php endwhile; ?>
                                <a href="admin_reviews.php" class="btn btn-primary btn-sm mt-3">View All Reviews</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>