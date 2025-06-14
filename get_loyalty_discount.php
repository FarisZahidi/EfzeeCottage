<?php
header('Content-Type: application/json');
require 'include/config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode([
        'discount' => 0,
        'tier_name' => '',
        'points' => 0,
        'next_tier' => '',
        'points_needed' => 0
    ]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Check if user has any previous bookings
$stmt = $conn->prepare("
    SELECT COUNT(*) as booking_count
    FROM bookings
    WHERE user_id = ? AND status != 'cancelled'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookingCount = $result->fetch_assoc()['booking_count'];

$currentStatus = [
    'points' => 0,
    'tier_name' => $bookingCount > 0 ? 'Returning Customer' : 'New Customer',
    'discount' => $bookingCount > 0 ? 20.00 : 0.00,
    'discount_type' => 'fixed'
];

// Set next tier information for new customers only
$nextTier = [
    'tier_name' => $bookingCount > 0 ? '' : 'Returning Customer',
    'points_needed' => $bookingCount > 0 ? 0 : 1
];

echo json_encode([
    'discount' => (float) $currentStatus['discount'],
    'tier_name' => $currentStatus['tier_name'] ?? '',
    'points' => (int) $currentStatus['points'],
    'next_tier' => $nextTier['tier_name'] ?? '',
    'points_needed' => (int) $nextTier['points_needed']
]);
?>