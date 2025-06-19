<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get date range from request
$start_date = $_GET['start'] ?? date('Y-m-d');
$end_date = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date range']);
    exit;
}

try {
    // Get bookings within date range with related information
    $query = "SELECT b.booking_id,
                     b.check_in_date,
                     b.check_out_date,
                     b.status as booking_status,
                     b.total_guests,
                     b.total_price,
                     h.name as homestay_name,
                     h.homestay_id,
                     u.name as guest_name,
                     u.email as guest_email,
                     p.status as payment_status,
                     p.payment_method
              FROM bookings b
              JOIN homestays h ON b.homestay_id = h.homestay_id
              JOIN users u ON b.user_id = u.user_id
              LEFT JOIN payments p ON b.booking_id = p.booking_id
              WHERE (b.check_in_date BETWEEN ? AND ?
                     OR b.check_out_date BETWEEN ? AND ?)
              ORDER BY b.check_in_date ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($booking = $result->fetch_assoc()) {
        // Format dates for calendar
        $check_in = date('Y-m-d', strtotime($booking['check_in_date']));
        $check_out = date('Y-m-d', strtotime($booking['check_out_date']));

        // Create event title
        $title = sprintf(
            '%s - %s (%d guests)', 
            $booking['homestay_name'],
            $booking['guest_name'],
            $booking['total_guests']
        );

        // Get status colors
        $status_colors = [
            'confirmed' => '#28a745',
            'pending' => '#ffc107',
            'cancelled' => '#dc3545'
        ];

        $backgroundColor = $status_colors[$booking['booking_status']] ?? '#6c757d';

        // Create event object
        $events[] = [
            'id' => $booking['booking_id'],
            'title' => $title,
            'start' => $check_in,
            'end' => $check_out,
            'backgroundColor' => $backgroundColor,
            'borderColor' => $backgroundColor,
            'extendedProps' => [
                'homestay_id' => $booking['homestay_id'],
                'homestay_name' => $booking['homestay_name'],
                'guest_name' => $booking['guest_name'],
                'guest_email' => $booking['guest_email'],
                'total_guests' => $booking['total_guests'],
                'total_price' => $booking['total_price'],
                'booking_status' => $booking['booking_status'],
                'payment_status' => $booking['payment_status'],
                'payment_method' => $booking['payment_method']
            ]
        ];
    }

    // Set JSON response headers
    header('Content-Type: application/json');
    echo json_encode($events);

} catch (Exception $e) {
    error_log('Error fetching calendar events: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch calendar events'
    ]);
}

// Close connection
$conn->close();