<?php
session_start();
require_once 'config.php';
require_once 'send_notification.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review']);
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homestay_id = filter_input(INPUT_POST, 'homestay_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user']['user_id'];

    if (!$homestay_id || !$rating || !$comment) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
        exit;
    }

    // Check if user has completed a booking for this homestay
    $bookingCheck = $conn->prepare("SELECT booking_id FROM bookings WHERE user_id = ? AND homestay_id = ? AND status = 'completed' LIMIT 1");
    $bookingCheck->bind_param('ii', $user_id, $homestay_id);
    $bookingCheck->execute();
    $bookingResult = $bookingCheck->get_result();

    if ($bookingResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You can only review homestays you have stayed at']);
        exit;
    }

    $booking = $bookingResult->fetch_assoc();
    $booking_id = $booking['booking_id'];

    // Check if user has already reviewed this booking
    $reviewCheck = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? LIMIT 1");
    $reviewCheck->bind_param('i', $booking_id);
    $reviewCheck->execute();

    if ($reviewCheck->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted a review for this booking']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert the review
        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, homestay_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param('iiiis', $booking_id, $user_id, $homestay_id, $rating, $comment);
        $stmt->execute();
        $review_id = $stmt->insert_id;

        // Update homestay rating
        $update_rating = "UPDATE homestays h 
                          SET rating = (SELECT AVG(rating) FROM reviews WHERE homestay_id = h.homestay_id) 
                          WHERE homestay_id = ?";
        $stmt = $conn->prepare($update_rating);
        $stmt->bind_param('i', $homestay_id);
        $stmt->execute();

        // Get homestay name for notification
        $query = "SELECT name FROM homestays WHERE homestay_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $homestay_id);
        $stmt->execute();
        $homestay = $stmt->get_result()->fetch_assoc();

        // Create notification for admin
        $notification = new NotificationService($conn);
        
        // Prepare notification data
        $notification_data = [
            'review_id' => $review_id,
            'booking_id' => $booking_id,
            'homestay_name' => $homestay['name'],
            'guest_name' => $_SESSION['user']['name'],
            'rating' => $rating,
            'comment' => $comment
        ];

        // Send notification to admin
        $notification->sendNewReviewNotification($notification_data);

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Review submitted successfully',
            'review_id' => $review_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        error_log('Error submitting review: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit review: ' . $e->getMessage()
        ]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();