<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

// Get date from request
$date = $_GET['date'] ?? date('Y-m-d');

// Validate date
if (!strtotime($date)) {
    http_response_code(400);
    exit('Invalid date');
}

try {
    // Get bookings for the specified date
    $query = "SELECT b.*, 
                     h.name as homestay_name,
                     u.name as guest_name,
                     u.email as guest_email,
                     u.phone as guest_phone,
                     p.payment_id,
                     p.amount as payment_amount,
                     p.payment_method,
                     p.status as payment_status,
                     p.payment_date,
                     pr.file_path as receipt_path
              FROM bookings b 
              JOIN homestays h ON b.homestay_id = h.homestay_id 
              JOIN users u ON b.user_id = u.user_id 
              LEFT JOIN payments p ON b.booking_id = p.booking_id 
              LEFT JOIN payment_receipts pr ON p.payment_id = pr.payment_id
              WHERE ? BETWEEN b.check_in_date AND b.check_out_date
              ORDER BY b.check_in_date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '<div class="alert alert-info">No bookings found for this date.</div>';
        exit;
    }
?>

<div class="date-bookings">
    <h5>Bookings for <?php echo date('F j, Y', strtotime($date)); ?></h5>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Homestay</th>
                    <th>Guest</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $result->fetch_assoc()): 
                    $check_in = date('M j, Y', strtotime($booking['check_in_date']));
                    $check_out = date('M j, Y', strtotime($booking['check_out_date']));
                    
                    // Generate status badge classes
                    $status_class = match($booking['status']) {
                        'confirmed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    };
                    
                    $payment_status_class = match($booking['payment_status'] ?? 'pending') {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'secondary'
                    };
                ?>
                <tr>
                    <td>#<?php echo $booking['booking_id']; ?></td>
                    <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($booking['guest_name']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                    </td>
                    <td><?php echo $check_in; ?></td>
                    <td><?php echo $check_out; ?></td>
                    <td>
                        <select class="form-select form-select-sm status-select" 
                                onchange="updateBookingStatus(<?php echo $booking['booking_id']; ?>, this.value)">
                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </td>
                    <td>
                        <?php if ($booking['payment_id']): ?>
                            <select class="form-select form-select-sm payment-status-select"
                                    onchange="updatePaymentStatus(<?php echo $booking['payment_id']; ?>, this.value)">
                                <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $booking['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $booking['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                            <?php if ($booking['receipt_path']): ?>
                                <a href="#" onclick="viewReceipt('<?php echo htmlspecialchars($booking['receipt_path']); ?>')" class="btn btn-sm btn-link">
                                    <i class="fas fa-receipt"></i> View Receipt
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">No Payment</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="showBookingDetails(<?php echo $booking['booking_id']; ?>)">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function viewReceipt(path) {
    // Check if it's a PDF
    if (path.toLowerCase().endsWith('.pdf')) {
        window.open(path, '_blank');
    } else {
        // Show image in a modal
        const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
        document.getElementById('receiptImage').src = path;
        modal.show();
    }
}
</script>

<?php
} catch (Exception $e) {
    error_log('Error fetching date bookings: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Failed to fetch bookings. Please try again later.</div>';
}

// Close connection
$conn->close();