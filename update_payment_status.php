<?php
session_start();
require_once 'config.php';
require_once 'send_notification.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$payment_id = $_POST['payment_id'] ?? 0;
$status = $_POST['status'] ?? '';

// Validate status
$valid_statuses = ['pending', 'completed', 'failed', 'refunded'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current payment and booking details
    $query = "SELECT p.*, b.booking_id, b.check_in_date, b.check_out_date, b.total_price,
                     u.email, u.name as guest_name, h.name as homestay_name
              FROM payments p
              JOIN bookings b ON p.booking_id = b.booking_id
              JOIN users u ON b.user_id = u.user_id
              JOIN homestays h ON b.homestay_id = h.homestay_id
              WHERE p.payment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        throw new Exception('Payment not found');
    }

    // Update payment status
    $update_query = "UPDATE payments SET status = ?, updated_at = NOW() WHERE payment_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $status, $payment_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No changes made to payment status');
    }

    // If payment is completed, update booking status to confirmed
    if ($status === 'completed') {
        $update_booking = "UPDATE bookings SET status = 'confirmed', updated_at = NOW() WHERE booking_id = ?";
        $stmt = $conn->prepare($update_booking);
        $stmt->bind_param('i', $payment['booking_id']);
        $stmt->execute();
    }

    // Create notification
    $notification = new NotificationService($conn);
    
    // Prepare notification data
    $notification_data = [
        'payment_id' => $payment_id,
        'booking_id' => $payment['booking_id'],
        'guest_email' => $payment['email'],
        'guest_name' => $payment['guest_name'],
        'homestay_name' => $payment['homestay_name'],
        'check_in_date' => date('M j, Y', strtotime($payment['check_in_date'])),
        'check_out_date' => date('M j, Y', strtotime($payment['check_out_date'])),
        'amount' => $payment['amount'],
        'payment_method' => $payment['payment_method'],
        'status' => $status
    ];

    // Send email notification
    $notification->sendPaymentStatusNotification($notification_data);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully',
        'new_status' => $status
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log('Error updating payment status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update payment status: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();