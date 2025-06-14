<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: deepseek.php');
    exit();
}

// Database connection
require_once 'config.php';

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $booking_id = $_POST['booking_id'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Booking status updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update booking status.";
        }
        
        header('Location: admin_bookings.php');
        exit();
    }
}

// Get all bookings with related information
$query = "SELECT b.*, u.name as user_name, u.email as user_email, h.name as homestay_name 
          FROM bookings b 
          JOIN users u ON b.user_id = u.user_id 
          JOIN homestays h ON b.homestay_id = h.homestay_id 
          ORDER BY b.homestay_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
                <a href="admin.php" class="nav-link mb-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_bookings.php" class="nav-link active mb-2">
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
            <h2 class="mb-4">Manage Bookings</h2>

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

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Homestay</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $booking['booking_id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['user_name']); ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                        <td>RM <?php echo number_format($booking['total_price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($booking['status']) {
                                                    'confirmed' => 'success',
                                                    'pending' => 'warning',
                                                    'cancelled' => 'danger'
                                                }; 
                                            ?>"><?php echo ucfirst($booking['status']); ?></span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bookingModal<?php echo $booking['booking_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Booking Modal -->
                                            <div class="modal fade" id="bookingModal<?php echo $booking['booking_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Booking Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="admin_bookings.php" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Booking Status</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Booking Details</label>
                                                                    <div class="card">
                                                                        <div class="card-body">
                                                                            <p>
                                                                                <strong>Guest:</strong> <?php echo htmlspecialchars($booking['user_name']); ?>
                                                                                <?php
                                                                                // Get user's phone number
                                                                                $phone_query = "SELECT phone FROM users WHERE user_id = ?";
                                                                                $phone_stmt = $conn->prepare($phone_query);
                                                                                $phone_stmt->bind_param("i", $booking['user_id']);
                                                                                $phone_stmt->execute();
                                                                                $phone_result = $phone_stmt->get_result()->fetch_assoc();
                                                                                $phone = $phone_result['phone'];
                                                                                if ($phone):
                                                                                    // Format phone number for WhatsApp (remove any non-numeric characters)
                                                                                    $whatsapp_number = preg_replace('/[^0-9]/', '', $phone);
                                                                                    if (substr($whatsapp_number, 0, 1) === '0') {
                                                                                        $whatsapp_number = '6' . $whatsapp_number; // Add Malaysia country code
                                                                                    }
                                                                                ?>
                                                                                    <a href="https://wa.me/<?php echo $whatsapp_number; ?>" target="_blank" class="btn btn-sm btn-success ms-2">
                                                                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                                                                    </a>
                                                                                <?php endif; ?>
                                                                            </p>
                                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
                                                                            <p><strong>Phone:</strong> <?php echo $phone ? htmlspecialchars($phone) : 'Not provided'; ?></p>
                                                                            <p><strong>Homestay:</strong> <?php echo htmlspecialchars($booking['homestay_name']); ?></p>
                                                                            <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></p>
                                                                            <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
                                                                            <p><strong>Total Price:</strong> RM <?php echo number_format($booking['total_price'], 2); ?></p>
                                                                            <?php if ($booking['special_requests']): ?>
                                                                                <p><strong>Special Requests:</strong> <?php echo htmlspecialchars($booking['special_requests']); ?></p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
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
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#bookingsTable').DataTable({
            order: [[0, 'desc']]
        });
    });
</script>
</body>
</html>