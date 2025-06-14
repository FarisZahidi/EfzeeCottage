<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cozyhomestay';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit a review']);
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homestay_id = filter_input(INPUT_POST, 'homestay_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];

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

    // Insert the review
    $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, homestay_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param('iiiis', $booking_id, $user_id, $homestay_id, $rating, $comment);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();