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
$booking_id = $_POST['booking_id'] ?? 0;
$status = $_POST['status'] ?? '';

// Validate status
$valid_statuses = ['pending', 'confirmed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current booking details
    $query = "SELECT b.*, u.email, u.name as guest_name, h.name as homestay_name 
              FROM bookings b 
              JOIN users u ON b.user_id = u.user_id 
              JOIN homestays h ON b.homestay_id = h.homestay_id 
              WHERE b.booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    // Update booking status
    $update_query = "UPDATE bookings SET status = ?, updated_at = NOW() WHERE booking_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $status, $booking_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No changes made to booking status');
    }

    // Create notification
    $notification = new NotificationService($conn);
    
    // Prepare notification data
    $notification_data = [
        'booking_id' => $booking_id,
        'guest_email' => $booking['email'],
        'guest_name' => $booking['guest_name'],
        'homestay_name' => $booking['homestay_name'],
        'check_in_date' => date('M j, Y', strtotime($booking['check_in_date'])),
        'check_out_date' => date('M j, Y', strtotime($booking['check_out_date'])),
        'total_price' => $booking['total_price'],
        'status' => $status
    ];

    // Send email notification
    $notification->sendBookingStatusNotification($notification_data);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully',
        'new_status' => $status
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log('Error updating booking status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update booking status: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();