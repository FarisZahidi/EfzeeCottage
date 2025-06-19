<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

class DiscountCodeManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function generateCode($admin_id, $discount_percentage, $valid_days = 30, $max_uses = null) {
        try {
            // Validate admin permissions
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE user_id = ? AND role = 'admin'");
            $stmt->bind_param('i', $admin_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Unauthorized access');
            }

            // Generate unique code
            $code = strtoupper(substr(uniqid(), -6));
            $valid_from = date('Y-m-d');
            $valid_until = date('Y-m-d', strtotime("+{$valid_days} days"));

            // Insert discount code
            $stmt = $this->conn->prepare("INSERT INTO discount_codes 
                                        (code, discount_percentage, valid_from, valid_until, max_uses, created_by) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sdssii', $code, $discount_percentage, $valid_from, $valid_until, $max_uses, $admin_id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'code' => $code,
                    'discount_percentage' => $discount_percentage,
                    'valid_until' => $valid_until,
                    'max_uses' => $max_uses
                ];
            } else {
                throw new Exception('Failed to generate discount code');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function validateCode($code) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM discount_codes 
                                        WHERE code = ? 
                                        AND valid_from <= CURDATE() 
                                        AND (valid_until IS NULL OR valid_until >= CURDATE())
                                        AND (max_uses IS NULL OR current_uses < max_uses)");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $discount = $result->fetch_assoc();
                return [
                    'success' => true,
                    'discount_percentage' => $discount['discount_percentage'],
                    'message' => "Discount of {$discount['discount_percentage']}% applied!"
                ];
            } else {
                throw new Exception('Invalid or expired discount code');
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function applyCode($code, $booking_id) {
        try {
            $this->conn->begin_transaction();

            // Validate code first
            $validation = $this->validateCode($code);
            if (!$validation['success']) {
                throw new Exception($validation['message']);
            }

            // Update booking with discount
            $stmt = $this->conn->prepare("UPDATE bookings 
                                        SET total_price = total_price * (1 - ?/100) 
                                        WHERE booking_id = ?");
            $discount_percentage = $validation['discount_percentage'];
            $stmt->bind_param('di', $discount_percentage, $booking_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to apply discount to booking');
            }

            // Increment usage count
            $stmt = $this->conn->prepare("UPDATE discount_codes 
                                        SET current_uses = current_uses + 1 
                                        WHERE code = ?");
            $stmt->bind_param('s', $code);
            $stmt->execute();

            $this->conn->commit();
            return $validation;

        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function listCodes($admin_id) {
        try {
            // Validate admin permissions
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE user_id = ? AND role = 'admin'");
            $stmt->bind_param('i', $admin_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Unauthorized access');
            }

            // Get all discount codes
            $result = $this->conn->query("SELECT * FROM discount_codes ORDER BY created_at DESC");
            $codes = [];

            while ($row = $result->fetch_assoc()) {
                $codes[] = [
                    'code' => $row['code'],
                    'discount_percentage' => $row['discount_percentage'],
                    'valid_from' => $row['valid_from'],
                    'valid_until' => $row['valid_until'],
                    'max_uses' => $row['max_uses'],
                    'current_uses' => $row['current_uses'],
                    'created_at' => $row['created_at']
                ];
            }

            return ['success' => true, 'codes' => $codes];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discount_manager = new DiscountCodeManager($conn);
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'generate':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                exit;
            }
            $result = $discount_manager->generateCode(
                $_SESSION['user']['user_id'],
                $_POST['discount_percentage'] ?? 0,
                $_POST['valid_days'] ?? 30,
                $_POST['max_uses'] ?? null
            );
            echo json_encode($result);
            break;

        case 'validate':
            $result = $discount_manager->validateCode($_POST['code'] ?? '');
            echo json_encode($result);
            break;

        case 'apply':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Please log in to apply discount code']);
                exit;
            }
            $result = $discount_manager->applyCode(
                $_POST['code'] ?? '',
                $_POST['booking_id'] ?? 0
            );
            echo json_encode($result);
            break;

        case 'list':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                exit;
            }
            $result = $discount_manager->listCodes($_SESSION['user']['user_id']);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}