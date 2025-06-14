<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: deepseek.php');
    exit();
}

// Database connection
require_once 'config.php';

// Handle payment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_payment_status') {
        $payment_id = $_POST['payment_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE payment_id = ?");
        $stmt->bind_param("si", $status, $payment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Payment status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update payment status.";
        }
        
        header('Location: admin_payments.php');
        exit();
    }
}

// Get payment statistics
$stats = $conn->query("SELECT 
    COUNT(*) as total_payments,
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_payments
FROM payments");
$payment_stats = $stats->fetch_assoc();

// Get all payments with related booking and user information
$query = "SELECT p.*, b.check_in_date, b.check_out_date, u.name as user_name, h.name as homestay_name 
          FROM payments p 
          JOIN bookings b ON p.booking_id = b.booking_id 
          JOIN users u ON b.user_id = u.user_id 
          JOIN homestays h ON b.homestay_id = h.homestay_id 
          ORDER BY p.payment_date DESC";
$payments = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - EFZEE COTTAGE</title>
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
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
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
                <a href="admin.php" class="nav-link mb-2">
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
                <a href="admin_payments.php" class="nav-link active mb-2">
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
            <h2 class="mb-4">Payment Management</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Revenue</h5>
                            <h3>RM <?php echo number_format($payment_stats['total_revenue'] ?? 0, 2); ?></h3>

                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Completed Payments</h5>
                            <h3><?php echo $payment_stats['completed_payments']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Pending Payments</h5>
                            <h3><?php echo $payment_stats['pending_payments']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Transactions</h5>
                            <h3><?php echo $payment_stats['total_payments']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Guest</th>
                                    <th>Homestay</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Stay Period</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $payment['payment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['homestay_name']); ?></td>
                                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <?php 
                                                $status_class = [
                                                    'pending' => 'warning',
                                                    'completed' => 'success',
                                                    'failed' => 'danger',
                                                    'refunded' => 'info'
                                                ][$payment['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?> status-badge">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <?php 
                                                echo date('M d', strtotime($payment['check_in_date'])) . ' - ' . 
                                                     date('M d, Y', strtotime($payment['check_out_date'])); 
                                            ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $payment['payment_id']; ?>">
                                                <i class="fas fa-edit"></i> Update Status
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="updateStatusModal<?php echo $payment['payment_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Payment Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="admin_payments.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_payment_status">
                                                        <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Payment Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="pending" <?php echo $payment['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="completed" <?php echo $payment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="failed" <?php echo $payment['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                                <option value="refunded" <?php echo $payment['status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>