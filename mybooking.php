<?php
session_start();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cozyhomestay';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: homepage.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = $_POST['booking_id'];
    
    // Get booking details to check cancellation eligibility
    $check_query = "SELECT check_in_date, status FROM bookings WHERE booking_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $booking_check = $check_stmt->get_result()->fetch_assoc();
    
    if ($booking_check) {
        $check_in_date = new DateTime($booking_check['check_in_date']);
        $current_date = new DateTime();
        $hours_difference = ($check_in_date->getTimestamp() - $current_date->getTimestamp()) / 3600;
        
        if ($hours_difference >= 24 && $booking_check['status'] !== 'cancelled') {
            $update_query = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $booking_id, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success_message'] = "Booking cancelled successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to cancel booking.";
            }
        } else {
            $_SESSION['error_message'] = "Booking can only be cancelled at least 24 hours before check-in date.";
        }
    }
    
    header("Location: mybooking.php");
    exit();
}

// Get user's booking count for loyalty discount
$loyalty_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = ? AND status != 'cancelled'";
$loyalty_stmt = $conn->prepare($loyalty_query);
$loyalty_stmt->bind_param("i", $user_id);
$loyalty_stmt->execute();
$loyalty_result = $loyalty_stmt->get_result()->fetch_assoc();
$has_previous_booking = $loyalty_result['booking_count'] > 0;

$query = "SELECT b.*, h.name as homestay_name, h.address, h.price_per_night,
          ANY_VALUE(p.payment_method) as payment_method, 
          ANY_VALUE(p.status) as payment_status, 
          ANY_VALUE(p.payment_date) as payment_date,
          GROUP_CONCAT(DISTINCT a.name) as amenity_names,
          GROUP_CONCAT(DISTINCT a.price) as amenity_prices,
          ANY_VALUE(cl.current_tier) as current_tier,
          ANY_VALUE(lt.discount_percentage) as discount_percentage,
          (SELECT COUNT(*) FROM bookings WHERE user_id = b.user_id AND status != 'cancelled') as user_booking_count
          FROM bookings b
          LEFT JOIN homestays h ON b.homestay_id = h.homestay_id
          LEFT JOIN payments p ON b.booking_id = p.booking_id
          LEFT JOIN booking_amenities ba ON b.booking_id = ba.booking_id
          LEFT JOIN amenities a ON ba.amenity_id = a.amenity_id
          LEFT JOIN customer_loyalty cl ON b.user_id = cl.user_id
          LEFT JOIN loyalty_tiers lt ON cl.current_tier = lt.tier_id
          WHERE b.user_id = ?
          GROUP BY b.booking_id
          ORDER BY b.check_in_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - EFZEE COTTAGE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --text-color: #333;
            --text-light: #fff;
            --transition: all 0.3s ease;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f6f1;
            color: #333;
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(44, 62, 80, 0.9);
            z-index: 1000;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            padding: 1rem 5%;
            background-color: rgba(44, 62, 80, 0.95);
            box-shadow: var(--shadow);
        }

        .nav-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--light-color);
            letter-spacing: 1px;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--light-color);
            font-weight: 400;
            position: relative;
            padding: 0.5rem 0;
            transition: var(--transition);
            text-decoration: none;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--secondary-color);
            transition: var(--transition);
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }

        .nav-links a.active {
            color: var(--secondary-color);
            font-weight: 600;
        }

        .nav-button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .nav-button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .nav-button.logout {
            background-color: var(--accent-color);
        }

        .nav-button.logout:hover {
            background-color: #c0392b;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 20px; /* Added top padding for fixed navbar */
        }
        
        /* Page Title */
        .page-title {
            text-align: center;
            margin: 30px 0;
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            position: relative;
            font-size: 2.5rem;
        }
        
        .page-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 3px;
            background: var(--secondary-color);
            margin: 15px auto;
            border-radius: 2px;
        }
        
        /* Booking Cards */
        .booking-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid #eae0d5;
        }
        
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        .booking-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .booking-header h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 600;
            margin-right: 15px;
            flex: 1;
        }
        
        .booking-status {
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .status-confirmed {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .status-cancelled {
            background-color: var(--danger-color);
            color: white;
        }
        
        .status-completed {
            background-color: #616161;
            color: white;
        }
        
        /* Booking Details */
        .booking-details {
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-group {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 15px;
            color: #777;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        /* Price Breakdown */
        .price-breakdown {
            background-color: #f8f5f0;
            padding: 25px;
            border-bottom: 1px solid #eae0d5;
        }
        
        .price-breakdown-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.3rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #d9c7b0;
            display: flex;
            align-items: center;
        }
        
        .price-breakdown-title i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #d9c7b0;
        }
        
        .price-item:last-child {
            border-top: 2px solid #d9c7b0;
            margin-top: 15px;
            padding-top: 15px;
            font-weight: 700;
            font-size: 1.2rem;
            border-bottom: none;
        }
        
        .amenity-list {
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px dashed #d9c7b0;
            border-bottom: 1px dashed #d9c7b0;
        }
        
        .amenity-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: #5c4a3a;
        }
        
        .loyalty-discount {
            color: var(--success-color);
            font-weight: 600;
        }
        
        /* Payment Info */
        .payment-info {
            padding: 25px;
            border-bottom: 1px solid #eae0d5;
        }
        
        .payment-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }
        
        .payment-title i {
            margin-right: 10px;
            color: var(--secondary-color);
        }
        
        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        /* Action Buttons */
        .booking-actions {
            padding: 20px 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            background-color: #f8f5f0;
            border-top: 1px solid #eae0d5;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #d9c7b0;
            margin-bottom: 25px;
        }
        
        .empty-state h3 {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }
        
        .empty-state p {
            font-size: 18px;
            margin-bottom: 30px;
            color: #777;
        }
        
        /* Messages */
        .message-container {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-align: center;
            max-width: 800px;
            margin: 0 auto 30px;
            box-shadow: var(--shadow);
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        /* Cancellation Policy */
        .cancellation-policy {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .nav-links {
                position: fixed;
                top: 80px;
                left: -100%;
                width: 80%;
                height: calc(100vh - 80px);
                background-color: var(--dark-color);
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                padding-top: 3rem;
                gap: 2rem;
                transition: var(--transition);
                z-index: 999;
            }

            .nav-links.active {
                left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .booking-header h2 {
                margin-bottom: 0;
            }
            
            .btn {
                width: 100%;
            }
            
            .booking-actions {
                flex-direction: column;
            }
            
            .page-title {
                font-size: 2rem;
            }

            .navbar {
                padding: 1rem 3%;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <?php include 'components/navbar.php'; ?>
    <!-- <nav class="navbar" id="navbar">
        <a href="homepage.php" class="nav-brand">EFZEE COTTAGE</a>
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="homepage.php">Home</a>
            <a href="homepage.php#about">About Us</a>
            <a href="homepage.php#gallery">Gallery</a>
            <a href="homepage.php#booking">Book Now</a>
            <a href="mybooking.php" class="active">My Bookings</a>
            <div class="nav-user-menu">
            <button id="loginBtn" class="nav-button">Login / Sign Up</button>
            <form id="logoutForm" action="logout.php" method="POST" style="display: inline;"></form>
            <a href="logout.php" id="logoutBtn" style="display: none;">Logout</a>

            </form>

        </div>
        </div>
    </nav> -->

    <div class="container" style="margin-top: 10px;">
        <h1 class="page-title">My Bookings</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message-container success-message">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message-container error-message">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <h3>No Bookings Yet</h3>
                <p>You haven't made any bookings yet. Start planning your cozy getaway now!</p>
                <a href="homepage.php#booking" class="btn btn-primary">
                    <i class="fas fa-home"></i> Browse Homestays
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-header">
                        <h2><?php echo htmlspecialchars($booking['homestay_name']); ?></h2>
                        <span class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>

                    <div class="booking-details">
                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-sign-in-alt"></i> Check-in Date</div>
                            <div><?php echo date('F d, Y', strtotime($booking['check_in_date'])); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-sign-out-alt"></i> Check-out Date</div>
                            <div><?php echo date('F d, Y', strtotime($booking['check_out_date'])); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-users"></i> Total Guests</div>
                            <div><?php echo $booking['total_guests']; ?> persons</div>
                        </div>
                        
                        <div class="detail-group">
                            <div class="detail-label"><i class="fas fa-hashtag"></i> Booking ID</div>
                            <div>#<?php echo $booking['booking_id']; ?></div>
                        </div>
                    </div>

                    <!-- Price Breakdown Section -->
                    <div class="price-breakdown">
                        <div class="price-breakdown-title">
                            <i class="fas fa-receipt"></i> Price Breakdown
                        </div>

                        <?php
                        $check_in = new DateTime($booking['check_in_date']);
                        $check_out = new DateTime($booking['check_out_date']);
                        $nights = $check_in->diff($check_out)->days;
                        $base_price = $booking['price_per_night'] * $nights;
                        ?>

                        <div class="price-item">
                            <span>Base Price (<?php echo $nights; ?> nights × RM<?php echo number_format($booking['price_per_night'], 2); ?>)</span>
                            <span>RM <?php echo number_format($base_price, 2); ?></span>
                        </div>

                        <?php if ($booking['amenity_names']): ?>
                            <div class="amenity-list">
                                <div class="detail-label">Selected Amenities:</div>
                                <?php
                                $amenity_names = explode(',', $booking['amenity_names']);
                                $amenity_prices = explode(',', $booking['amenity_prices']);
                                $total_amenities = 0;

                                for ($i = 0; $i < count($amenity_names); $i++):
                                    $amenity_price = $amenity_prices[$i] * $nights;
                                    $total_amenities += $amenity_price;
                                    ?>
                                    <div class="amenity-item">
                                        <span><?php echo htmlspecialchars($amenity_names[$i]); ?> (RM<?php echo $amenity_prices[$i]; ?> × <?php echo $nights; ?> nights)</span>
                                        <span>RM <?php echo number_format($amenity_price, 2); ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($booking['discount_percentage'] > 0): ?>
                            <div class="price-item loyalty-discount">
                                <span>Loyalty Discount (<?php echo $booking['discount_percentage']; ?>%)</span>
                                <span>- RM <?php echo number_format(($base_price + ($total_amenities ?? 0)) * ($booking['discount_percentage'] / 100), 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($has_previous_booking): ?>
                            <div class="price-item loyalty-discount">
                                <span>Returning Customer Discount</span>
                                <span>- RM 20.00</span>
                            </div>
                        <?php endif; ?>

                        <div class="price-item">
                            <strong>Total Amount</strong>
                            <strong>RM <?php echo number_format($booking['total_price'], 2); ?></strong>
                        </div>
                    </div>

                    <div class="payment-info">
                        <div class="payment-title">
                            <i class="fas fa-credit-card"></i> Payment Information
                        </div>
                        <div class="payment-details">
                            <div class="detail-group">
                                <div class="detail-label">Method</div>
                                <div><?php echo $booking['payment_method'] ? ucfirst(str_replace('_', ' ', $booking['payment_method'])) : 'Not specified'; ?></div>
                            </div>
                            
                            <div class="detail-group">
                                <div class="detail-label">Status</div>
                                <div><?php echo $booking['payment_status'] ? ucfirst($booking['payment_status']) : 'Pending'; ?></div>
                            </div>
                            
                            <?php if ($booking['payment_date']): ?>
                            <div class="detail-group">
                                <div class="detail-label">Date</div>
                                <div><?php echo date('F d, Y', strtotime($booking['payment_date'])); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($booking['status'] !== 'cancelled'): 
                        $check_in_date = new DateTime($booking['check_in_date']);
                        $current_date = new DateTime();
                        $hours_difference = ($check_in_date->getTimestamp() - $current_date->getTimestamp()) / 3600;
                        $can_cancel = $hours_difference >= 24;
                    ?>
                        <div class="booking-actions">
                            <?php if ($can_cancel): ?>
                                <form action="mybooking.php" method="POST">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times-circle"></i> Cancel Booking
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="cancellation-policy">
                                    <i class="fas fa-info-circle"></i> Booking can only be cancelled at least 24 hours before check-in
                                </div>
                            <?php endif; ?>
                            
                            <a href="homepage.php#booking" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book Another Stay
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Simple confirmation for cancellation
        document.querySelectorAll('.btn-danger').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to cancel this booking?')) {
                    e.preventDefault();
                }
            });
        });

        // Close mobile menu when clicking on links
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            });
        });
    </script>
</body>

</html>