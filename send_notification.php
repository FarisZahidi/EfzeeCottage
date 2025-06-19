<?php
require_once 'config.php';

class NotificationService {
    private $conn;
    private $mailer;

    public function __construct($conn) {
        $this->conn = $conn;

        // Initialize PHPMailer
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com'; // Update with your SMTP host
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'your-email@gmail.com'; // Update with your email
        $this->mailer->Password = 'your-app-password'; // Update with your app password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        $this->mailer->setFrom('your-email@gmail.com', 'EFZEE COTTAGE'); // Update with your email
    }

    public function sendBookingStatusNotification($booking_id, $status) {
        try {
            // Get booking and user details
            $stmt = $this->conn->prepare("SELECT b.*, u.email, u.name, h.name as homestay_name 
                                        FROM bookings b 
                                        JOIN users u ON b.user_id = u.user_id 
                                        JOIN homestays h ON b.homestay_id = h.homestay_id 
                                        WHERE b.booking_id = ?");
            $stmt->bind_param('i', $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $booking = $result->fetch_assoc();

            if (!$booking) {
                throw new Exception('Booking not found');
            }

            // Create notification record
            $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                        VALUES (?, 'booking_status', ?, ?)");
            $title = "Booking Status Updated";
            $message = "Your booking for {$booking['homestay_name']} has been {$status}.";
            $stmt->bind_param('iss', $booking['user_id'], $title, $message);
            $stmt->execute();

            // Create email notification record
            $stmt = $this->conn->prepare("INSERT INTO email_notifications (user_id, subject, content) 
                                        VALUES (?, ?, ?)");
            $subject = "Booking Status Update - EFZEE COTTAGE";
            $content = $this->getBookingStatusEmailTemplate($booking, $status);
            $stmt->bind_param('iss', $booking['user_id'], $subject, $content);
            $stmt->execute();

            // Send email
            $this->mailer->addAddress($booking['email'], $booking['name']);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $content;
            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            error_log('Error sending booking notification: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPaymentReceivedNotification($payment_id) {
        try {
            // Get payment and booking details
            $stmt = $this->conn->prepare("SELECT p.*, b.user_id, u.email, u.name, h.name as homestay_name 
                                        FROM payments p 
                                        JOIN bookings b ON p.booking_id = b.booking_id 
                                        JOIN users u ON b.user_id = u.user_id 
                                        JOIN homestays h ON b.homestay_id = h.homestay_id 
                                        WHERE p.payment_id = ?");
            $stmt->bind_param('i', $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            // Create notification record
            $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                        VALUES (?, 'payment_received', ?, ?)");
            $title = "Payment Received";
            $message = "Your payment of RM{$payment['amount']} for {$payment['homestay_name']} has been received.";
            $stmt->bind_param('iss', $payment['user_id'], $title, $message);
            $stmt->execute();

            // Create email notification record
            $stmt = $this->conn->prepare("INSERT INTO email_notifications (user_id, subject, content) 
                                        VALUES (?, ?, ?)");
            $subject = "Payment Received - EFZEE COTTAGE";
            $content = $this->getPaymentReceivedEmailTemplate($payment);
            $stmt->bind_param('iss', $payment['user_id'], $subject, $content);
            $stmt->execute();

            // Send email
            $this->mailer->addAddress($payment['email'], $payment['name']);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $content;
            $this->mailer->send();

            return true;
        } catch (Exception $e) {
            error_log('Error sending payment notification: ' . $e->getMessage());
            return false;
        }
    }

    private function getBookingStatusEmailTemplate($booking, $status) {
        return "<div style='font-family: Arial, sans-serif;'>
                    <h2>Booking Status Update</h2>
                    <p>Dear {$booking['name']},</p>
                    <p>Your booking for {$booking['homestay_name']} has been <strong>{$status}</strong>.</p>
                    <p>Booking Details:</p>
                    <ul>
                        <li>Check-in: {$booking['check_in_date']}</li>
                        <li>Check-out: {$booking['check_out_date']}</li>
                        <li>Total Guests: {$booking['total_guests']}</li>
                        <li>Total Amount: RM{$booking['total_price']}</li>
                    </ul>
                    <p>Thank you for choosing EFZEE COTTAGE!</p>
                </div>";
    }

    private function getPaymentReceivedEmailTemplate($payment) {
        return "<div style='font-family: Arial, sans-serif;'>
                    <h2>Payment Received</h2>
                    <p>Dear {$payment['name']},</p>
                    <p>We have received your payment of <strong>RM{$payment['amount']}</strong> for your booking at {$payment['homestay_name']}.</p>
                    <p>Payment Details:</p>
                    <ul>
                        <li>Payment Method: {$payment['payment_method']}</li>
                        <li>Payment Date: {$payment['payment_date']}</li>
                        <li>Status: {$payment['status']}</li>
                    </ul>
                    <p>Thank you for your payment!</p>
                </div>";
    }
}