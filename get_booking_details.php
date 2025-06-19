<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

// Get booking ID
$booking_id = $_GET['id'] ?? 0;

// Get booking details with related information
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
          WHERE b.booking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    http_response_code(404);
    exit('Booking not found');
}

// Format dates
$check_in = date('M j, Y', strtotime($booking['check_in_date']));
$check_out = date('M j, Y', strtotime($booking['check_out_date']));
$payment_date = $booking['payment_date'] ? date('M j, Y', strtotime($booking['payment_date'])) : 'Not paid';

// Generate status badge class
$status_class = match($booking['status']) {
    'confirmed' => 'success',
    'pending' => 'warning',
    'cancelled' => 'danger',
    default => 'secondary'
};

// Generate payment status badge class
$payment_status_class = match($booking['payment_status'] ?? 'pending') {
    'completed' => 'success',
    'pending' => 'warning',
    'failed' => 'danger',
    'refunded' => 'info',
    default => 'secondary'
};
?>

<div class="booking-details">
    <div class="row">
        <div class="col-md-6">
            <h5>Booking Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Booking ID:</th>
                    <td>#<?php echo $booking['booking_id']; ?></td>
                </tr>
                <tr>
                    <th>Homestay:</th>
                    <td><?php echo htmlspecialchars($booking['homestay_name']); ?></td>
                </tr>
                <tr>
                    <th>Check-in:</th>
                    <td><?php echo $check_in; ?></td>
                </tr>
                <tr>
                    <th>Check-out:</th>
                    <td><?php echo $check_out; ?></td>
                </tr>
                <tr>
                    <th>Total Guests:</th>
                    <td><?php echo $booking['total_guests']; ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <select class="form-select form-select-sm d-inline-block w-auto" 
                                onchange="updateBookingStatus(<?php echo $booking_id; ?>, this.value)">
                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="col-md-6">
            <h5>Guest Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Name:</th>
                    <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($booking['guest_email']); ?></td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td><?php echo htmlspecialchars($booking['guest_phone']); ?></td>
                </tr>
            </table>

            <h5 class="mt-4">Payment Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Amount:</th>
                    <td>RM <?php echo number_format($booking['total_price'], 2); ?></td>
                </tr>
                <tr>
                    <th>Payment Method:</th>
                    <td><?php echo $booking['payment_method'] ? ucfirst($booking['payment_method']) : 'Not selected'; ?></td>
                </tr>
                <tr>
                    <th>Payment Date:</th>
                    <td><?php echo $payment_date; ?></td>
                </tr>
                <tr>
                    <th>Payment Status:</th>
                    <td>
                        <select class="form-select form-select-sm d-inline-block w-auto"
                                onchange="updatePaymentStatus(<?php echo $booking['payment_id']; ?>, this.value)"
                                <?php echo !$booking['payment_id'] ? 'disabled' : ''; ?>>
                            <option value="pending" <?php echo ($booking['payment_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo ($booking['payment_status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo ($booking['payment_status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="refunded" <?php echo ($booking['payment_status'] ?? '') === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php if ($booking['receipt_path']): ?>
            <div class="mt-3">
                <h6>Payment Receipt</h6>
                <div class="receipt-preview">
                    <?php if (pathinfo($booking['receipt_path'], PATHINFO_EXTENSION) === 'pdf'): ?>
                        <a href="<?php echo htmlspecialchars($booking['receipt_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> View Receipt
                        </a>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($booking['receipt_path']); ?>" class="img-fluid" style="max-height: 200px;">
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateBookingStatus(bookingId, status) {
    fetch('update_booking_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `booking_id=${bookingId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh calendar events
            calendar.refetchEvents();
            // Show success message
            alert('Booking status updated successfully');
        } else {
            alert('Failed to update booking status: ' + data.message);
        }
    });
}

function updatePaymentStatus(paymentId, status) {
    fetch('update_payment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `payment_id=${paymentId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment status updated successfully');
        } else {
            alert('Failed to update payment status: ' + data.message);
        }
    });
}
</script>