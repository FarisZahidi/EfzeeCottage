<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to make a payment']);
    exit;
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';

    if (!$booking_id || !$amount || $payment_method !== 'qr_code') {
        echo json_encode(['success' => false, 'message' => 'Invalid payment information']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create payment record
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status, payment_date) VALUES (?, ?, ?, 'pending', CURDATE())");
        $stmt->bind_param('ids', $booking_id, $amount, $payment_method);
        $stmt->execute();
        $payment_id = $conn->insert_id;

        // Handle receipt upload if provided
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['receipt'];
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG and PDF files are allowed.');
            }

            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/receipts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $filename = 'receipt_' . $payment_id . '_' . time() . '.' . $file_type;
            $file_path = $upload_dir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Save receipt record
                $stmt = $conn->prepare("INSERT INTO payment_receipts (payment_id, file_path, file_type) VALUES (?, ?, ?)");
                $stmt->bind_param('iss', $payment_id, $file_path, $file_type);
                $stmt->execute();

                // Create notification for admin
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                      SELECT user_id, 'admin_alert', 'New Payment Receipt', 
                                      'A new payment receipt has been uploaded for booking #' || ? 
                                      FROM users WHERE role = 'admin' LIMIT 1");
                $stmt->bind_param('i', $booking_id);
                $stmt->execute();
            } else {
                throw new Exception('Failed to upload receipt file.');
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Payment recorded successfully. Please wait for admin approval.',
            'payment_id' => $payment_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}