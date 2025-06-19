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
$review_id = $_POST['review_id'] ?? 0;
$admin_response = $_POST['response'] ?? '';
$status = $_POST['status'] ?? 'approved'; // approved or hidden

// Validate input
if ($review_id <= 0 || empty($admin_response)) {
    echo json_encode(['success' => false, 'message' => 'Invalid response data']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get review details
    $query = "SELECT r.*, u.email, u.name as guest_name, h.name as homestay_name 
              FROM reviews r 
              JOIN users u ON r.user_id = u.user_id 
              JOIN homestays h ON r.homestay_id = h.homestay_id 
              WHERE r.review_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $review_id);
    $stmt->execute();
    $review = $stmt->get_result()->fetch_assoc();

    if (!$review) {
        throw new Exception('Review not found');
    }

    // Update review with admin response
    $update_query = "UPDATE reviews 
                     SET admin_response = ?, 
                         status = ?, 
                         updated_at = NOW(), 
                         responded_by = ? 
                     WHERE review_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ssii', 
        $admin_response, 
        $status,
        $_SESSION['user']['user_id'],
        $review_id
    );
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No changes made to review');
    }

    // Create notification
    $notification = new NotificationService($conn);
    
    // Prepare notification data
    $notification_data = [
        'review_id' => $review_id,
        'guest_email' => $review['email'],
        'guest_name' => $review['guest_name'],
        'homestay_name' => $review['homestay_name'],
        'rating' => $review['rating'],
        'comment' => $review['comment'],
        'admin_response' => $admin_response,
        'status' => $status
    ];

    // Send email notification to guest
    $notification->sendReviewResponseNotification($notification_data);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Review response submitted successfully',
        'review_id' => $review_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log('Error submitting review response: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit review response: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();