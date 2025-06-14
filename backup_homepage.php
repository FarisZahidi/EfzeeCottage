<?php
// Start session
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

// Handle form submissions

// Initialize error messages
$login_error = '';
$signup_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Login success: store user session and set message
                $_SESSION['user'] = [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                $_SESSION['login_success'] = "Welcome back to EFZEE COTTAGE";

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } elseif ($user['role'] === 'guest') {
                    header("Location: homepage.php");
                } else {
                    // Default fallback (optional)
                    header("Location: homepage.php");
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid email or password.";
            }
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $_SESSION['login_error'] = "Please fill in both email and password.";
    }
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($name && $email && $password && $phone) {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['signup_error'] = "Email already registered.";
        } else {
            $stmt->close();

            // Hash the password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'guest')");
            $stmt->bind_param("ssss", $name, $email, $password_hash, $phone);

            if ($stmt->execute()) {
                $_SESSION['signup_success'] = "Account created successfully! Please log in.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['signup_error'] = "Error during signup: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $_SESSION['signup_error'] = "Please fill in all required fields.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EFZEE COTTAGE - Luxury Retreat in Batu Pahat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Playfair Display', serif;
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1.5rem;
        }

        a {
            text-decoration: none;
            color: inherit;
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
        }

        .nav-links {
            display: flex;
            align-items: center;

            /* Payment Section Styles */
            .payment-section {
                background-color: var(--light-color);
                padding: 5rem 0;
            }

            .payment-methods {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
                margin: 3rem 0;
            }

            .payment-method {
                background: white;
                padding: 2rem;
                border-radius: var(--border-radius);
                text-align: center;
                box-shadow: var(--shadow);
                transition: var(--transition);
            }

            .payment-method:hover {
                transform: translateY(-5px);
            }

            .payment-method i {
                font-size: 2.5rem;
                color: var(--secondary-color);
                margin-bottom: 1rem;
            }

            .payment-info {
                background: white;
                padding: 2rem;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
                margin-top: 2rem;
            }

            .payment-info ul {
                list-style: none;
                padding: 0;
            }

            .payment-info li {
                padding: 0.5rem 0;
                position: relative;
                padding-left: 1.5rem;
            }

            .payment-info li:before {
                content: '✓';
                color: var(--secondary-color);
                position: absolute;
                left: 0;
            }

            /* Reviews Section Styles */
            .reviews-section {
                background-color: var(--light-color);
                padding: 5rem 0;
            }

            .reviews-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
                margin: 3rem 0;
            }

            .review-card {
                background: white;
                padding: 2rem;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
            }

            .review-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
            }

            .rating {
                color: #ffd700;
            }

            .homestay-name {
                color: var(--secondary-color);
                font-weight: 600;
                margin-bottom: 0.5rem;
            }

            .review-text {
                margin: 1rem 0;
                font-style: italic;
            }

            .review-date {
                color: #666;
                font-size: 0.9rem;
            }

            .write-review {
                background: white;
                padding: 2rem;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow);
                margin-top: 3rem;
            }

            .review-form {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }

            .star-rating {
                display: flex;
                flex-direction: row-reverse;
                gap: 0.5rem;
            }

            .star-rating input {
                display: none;
            }

            .star-rating label {
                cursor: pointer;
                color: #ddd;
                font-size: 1.5rem;
            }

            .star-rating input:checked~label,
            .star-rating label:hover,
            .star-rating label:hover~label {
                color: #ffd700;
            }

            .submit-review-btn {
                background: var(--secondary-color);
                color: white;
                border: none;
                padding: 0.8rem;
                border-radius: var(--border-radius);
                cursor: pointer;
                transition: var(--transition);
            }

            .submit-review-btn:hover {
                background: var(--primary-color);
            }

            /* Footer Styles */
            .site-footer {
                background-color: var(--dark-color);
                color: var(--text-light);
                padding: 4rem 5% 2rem;
            }

            .footer-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 3rem;
                margin-bottom: 2rem;
            }

            .footer-section h3 {
                color: var(--secondary-color);
                margin-bottom: 1.5rem;
            }

            .footer-section ul {
                list-style: none;
                padding: 0;
            }

            .footer-section ul li {
                margin-bottom: 0.8rem;
            }

            .footer-section ul li a {
                transition: var(--transition);
            }

            .footer-section ul li a:hover {
                color: var(--secondary-color);
                padding-left: 5px;
            }

            .footer-section p {
                margin-bottom: 1rem;
            }

            .footer-section i {
                margin-right: 0.5rem;
                color: var(--secondary-color);
            }

            .social-links {
                display: flex;
                gap: 1rem;
            }

            .social-link {
                width: 35px;
                height: 35px;
                background: rgba(255, 255, 255, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: var(--transition);
            }

            .social-link:hover {
                background: var(--secondary-color);
                transform: translateY(-3px);
            }

            .footer-bottom {
                text-align: center;
                padding-top: 2rem;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            @media (max-width: 768px) {

                .payment-methods,
                .reviews-container,
                .footer-content {
                    grid-template-columns: 1fr;
                }

                .footer-section {
                    text-align: center;
                }

                .social-links {
                    justify-content: center;
                }
            }

            gap: 2rem;
        }

        .nav-links a {
            color: var(--light-color);
            font-weight: 400;
            position: relative;
            padding: 0.5rem 0;
            transition: var(--transition);
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

        /* Parallax Sections */
        .parallax-section {
            min-height: 100vh;
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            text-align: center;
            padding: 6rem 2rem;
        }

        .parallax-content {
            max-width: 1200px;
            margin: 0 auto;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 3rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(5px);
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cta-button {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            margin-top: 1.5rem;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cta-button:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* Section-specific styles */
        #home {
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1522708323590-d24dbb6b0267');
        }

        #about {
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1554469384-e58f16f4d6f7');
        }

        #gallery {
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1560448204-603b3fc33ddc');
        }

        #booking {
            background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://images.unsplash.com/photo-1584622650111-993a426fbf0a');
        }

        /* About Section Features */
        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: var(--shadow);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: var(--light-color);
            margin-bottom: 0.8rem;
        }

        /* Gallery Section */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 3rem;
        }

        .gallery-item {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            height: 250px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .gallery-item:hover {
            transform: scale(1.03);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .gallery-item:hover img {
            transform: scale(1.1);
        }

        .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            color: white;
            padding: 1.5rem 1rem 1rem;
            opacity: 0;
            transition: var(--transition);
        }

        .gallery-item:hover .overlay {
            opacity: 1;
        }

        /* Booking Section */
        .booking-form {
            max-width: 800px;
            margin: 3rem auto 0;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            color: var(--text-color);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: inherit;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 400;
            cursor: pointer;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .price-breakdown {
            background-color: rgba(52, 152, 219, 0.1);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
        }

        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .price-item.total {
            font-weight: 600;
            font-size: 1.1rem;
            border-bottom: none;
            margin-top: 0.5rem;
        }

        .submit-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
        }

        .submit-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        /* Active section indicator */
        .active-section {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .active-section a {
            display: block;
            width: 12px;
            height: 12px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            transition: var(--transition);
            position: relative;
        }

        .active-section a.active {
            background-color: white;
            transform: scale(1.3);
        }

        .active-section a::after {
            content: attr(data-section);
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            background-color: white;
            color: var(--dark-color);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            opacity: 0;
            pointer-events: none;
            transition: var(--transition);
            white-space: nowrap;
        }

        .active-section a:hover::after {
            opacity: 1;
            right: 20px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #777;
            transition: var(--transition);
        }

        .close:hover {
            color: var(--accent-color);
        }

        .tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .tab-btn {
            padding: 0.8rem 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #777;
            position: relative;
            transition: var(--transition);
        }

        .tab-btn.active {
            color: var(--secondary-color);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--secondary-color);
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .auth-form input {
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: inherit;
            transition: var(--transition);
        }

        .auth-form input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .auth-form button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 0.5rem;
        }

        .auth-form button:hover {
            background-color: #2980b9;
        }

        /* Responsive Design */
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

            .parallax-content {
                padding: 2rem;
            }

            .about-grid {
                grid-template-columns: 1fr;
            }

            .active-section {
                right: 10px;
            }
        }

        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .parallax-section {
                background-attachment: scroll;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        .calendar-container {
            max-width: 600px;
            margin: 20px auto;
            text-align: center;
        }

        /* Booking Form Styles */
        .booking-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .amenity-item:hover {
            border-color: var(--secondary-color);
            background-color: #f8f9fa;
        }

        .amenity-item input[type="checkbox"] {
            display: none;
        }

        .amenity-item label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            width: 100%;
        }

        .amenity-item i {
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .amenity-item input[type="checkbox"]:checked+label {
            color: var(--secondary-color);
            font-weight: 500;
        }

        .price-breakdown {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin: 2rem 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }

        .price-row.total {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--secondary-color);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #dee2e6;
        }

        .payment-method-select {
            margin: 2rem 0;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .payment-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .payment-option:hover {
            border-color: var(--secondary-color);
            background-color: #f8f9fa;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-option i {
            font-size: 2rem;
            color: var(--secondary-color);
        }

        .payment-option input[type="radio"]:checked+i {
            color: var(--primary-color);
        }

        .payment-option span {
            font-weight: 500;
        }


        .calendar-grid {
            width: 100%;
            max-width: 600px;
            margin: auto;
        }

        .calendar-header,
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }

        .calendar-day {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .calendar-day.past {
            background-color: #eee;
            color: #aaa;
            pointer-events: none;
        }

        .calendar-day.booked {
            background-color: #ffd6d6;
            color: #900;
            font-weight: bold;
        }

        .calendar-day.available {
            background-color: #ddffdd;
        }

        .calendar-day.empty {
            background-color: transparent;
            border: none;
        }

        .booked-label {
            display: block;
            font-size: 0.75em;
            color: #c00;
        }

        .amenity-row i {
            margin-right: 8px;
            color: var(--secondary-color);
            width: 16px;
            text-align: center;
        }

        .amenity-row {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }

        .amenity-row:last-child {
            border-bottom: none;
        }

        .amenity-row span:first-child {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }

        .amenity-row span:last-child {
            font-weight: 500;
        }

        /* Loyalty Program Styles */
        .loyalty-card {
            background: rgba(0, 0, 0, 0.7);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .loyalty-card h3 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .loyalty-progress {
            margin: 1.5rem 0;
        }

        .tier-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .current-tier {
            font-weight: bold;
            color: var(--primary-color);
        }

        .next-tier {
            font-size: 0.9em;
            color: #666;
        }

        .progress-bar {
            height: 10px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
            transition: width 0.5s ease;
        }

        .points-info {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            font-size: 0.9em;
        }

        .loyalty-benefits ul {
            list-style: none;
            padding: 0;
            margin-top: 1rem;
        }

        .loyalty-benefits li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .loyalty-benefits i {
            color: var(--secondary-color);
        }

        /* Loyalty Discount Styles */
        .discount-row {
            color: #2ecc71;
            font-weight: 600;
            background-color: rgba(46, 204, 113, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }

        .discount-row i {
            margin-right: 8px;
            color: #2ecc71;
        }

        .loyalty-message {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .loyalty-message i {
            color: #f1c40f;
            font-size: 1.2em;
        }

        .price-row.total {
            font-size: 1.2em;
            font-weight: bold;
            border-top: 2px solid #eee;
            padding-top: 12px;
            margin-top: 12px;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">EFZEE COTTAGE</div>
        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links">
            <a href="#home" class="active">Home</a>
            <a href="#about">About Us</a>
            <a href="#gallery">Gallery</a>
            <a href="#booking">Book Now</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="mybooking.php">My Bookings</a>
            <?php endif; ?>
            <!-- <a href="#reviews">Reviews</a> -->
            <div class="nav-user-menu">
                <button id="loginBtn" class="nav-button">Login / Sign Up</button>
                <form id="logoutForm" action="logout.php" method="POST" style="display: inline;"></form>
                <a href="logout.php" id="logoutBtn" style="display: none;">Logout</a>

                </form>

            </div>
        </div>
    </nav>

    <!-- Active Section Indicator -->
    <div class="active-section">
        <a href="#home" class="active" data-section="Home"></a>
        <a href="#about" data-section="About"></a>
        <a href="#gallery" data-section="Gallery"></a>
        <a href="#booking" data-section="Booking"></a>
    </div>

    <!-- Home Section -->
    <section id="home" class="parallax-section">
        <div class="parallax-content">
            <h1>Welcome to EFZEE COTTAGE</h1>
            <p class="subtitle">Luxury Retreat in the Heart of Batu Pahat</p>
            <p>Experience unparalleled comfort and serenity in our beautifully designed cottages, where every detail is
                crafted for your relaxation.</p>
            <a href="#booking" class="cta-button">Book Your Stay</a>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="parallax-section">
        <div class="parallax-content">
            <h1>About EFZEE COTTAGE</h1>
            <p class="subtitle">Your Home Away From Home</p>
            <p>Nestled in the picturesque landscape of Batu Pahat, EFZEE COTTAGE offers a perfect blend of modern luxury
                and traditional charm. Our mission is to provide an unforgettable experience where comfort meets
                elegance.</p>

            <div class="about-grid">
                <div class="feature-card">
                    <i class="fas fa-home"></i>
                    <h3>Premium Accommodations</h3>
                    <p>Spacious, air-conditioned rooms with premium bedding, ensuite bathrooms, and private balconies
                        overlooking lush gardens.</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Prime Location</h3>
                    <p>Centrally located with easy access to Batu Pahat's top attractions, dining, and shopping
                        destinations.</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-concierge-bell"></i>
                    <h3>Exceptional Service</h3>
                    <p>24/7 concierge service with personalized attention to make your stay truly special.</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-umbrella-beach"></i>
                    <h3>Resort Amenities</h3>
                    <p>Swimming pool, spa services, fitness center, and complimentary bicycles for exploring the area.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="parallax-section">
        <div class="parallax-content">
            <h1>Our Gallery</h1>
            <p class="subtitle">A Visual Journey Through Our Retreat</p>

            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267" alt="Living Room">
                    <div class="overlay">Elegant Living Space</div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1560448204-603b3fc33ddc" alt="Bedroom">
                    <div class="overlay">Luxury Bedroom Suite</div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a" alt="Kitchen">
                    <div class="overlay">Fully Equipped Kitchen</div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1552321554-5fefe8c9ef14" alt="Bathroom">
                    <div class="overlay">Spa-like Bathroom</div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1587714656374-fb3c9c3a4c82" alt="Dining Area">
                    <div class="overlay">Gourmet Dining Area</div>
                </div>
                <div class="gallery-item">
                    <img src="https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7" alt="Garden">
                    <div class="overlay">Tranquil Garden Oasis</div>
                </div>
            </div>
        </div>
    </section>



    <!-- Booking Section -->
    <section id="booking" class="parallax-section">




        <div class="parallax-content">
            <div class="loyalty-card">
                <h3><i class="fas fa-crown"></i> Your Loyalty Status</h3>
                <?php if (isset($_SESSION['user'])):
                    $loyalty = getCustomerLoyaltyStatus($_SESSION['user']['user_id'], $conn);
                    $tiers = getLoyaltyTiers($conn);
                    $nextTier = null;

                    $loyalty = getCustomerLoyaltyStatus($_SESSION['user']['user_id'], $conn) ?? [
                        'loyalty_points' => 0,
                        'current_tier' => '-'
                    ];

                    // Find next tier
                    foreach ($tiers as $tier) {
                        if ($tier['min_points'] > $loyalty['loyalty_points']) {
                            $nextTier = $tier;
                            break;
                        }
                    }
                    ?>
                    <div class="loyalty-progress">
                        <div class="tier-info">
                            <span class="current-tier">Tier <?= $loyalty['current_tier'] ?? '' ?></span>

                            <?php if ($nextTier): ?>
                                <span class="next-tier">Next: Tier <?= $nextTier['tier_id'] ?> (<?= $nextTier['min_points'] ?>
                                    points)</span>
                            <?php endif; ?>
                        </div>
                        <?php
                        $progress = 0;
                        if ($nextTier && $nextTier['min_points'] > 0) {
                            $progress = ($loyalty['loyalty_points'] / $nextTier['min_points']) * 100;
                            $progress = min(100, $progress); // cap at 100%
                        }
                        ?>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?= round($progress, 2) ?>%"></div>
                        </div>

                        <div class="points-info">
                            <span><?= $loyalty['loyalty_points'] ?? '' ?> points</span>

                            <?php if ($nextTier): ?>
                                <span><?= $nextTier['min_points'] - $loyalty['loyalty_points'] ?> to next tier</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="loyalty-benefits">
                        <h4>Your Benefits:</h4>
                        <ul>
                            <li><i class="fas fa-check"></i>
                                <?= calculateLoyaltyDiscount($_SESSION['user']['user_id'], $conn) ?>% discount on all
                                bookings
                            </li>
                            <li><i class="fas fa-check"></i> Priority customer support</li>
                            <li><i class="fas fa-check"></i> Early access to promotions</li>
                        </ul>
                    </div>
                <?php else: ?>
                    <p>Sign in to view your loyalty status and earn rewards!</p>
                <?php endif; ?>
            </div>
            <h1>Book Your Stay</h1>
            <p class="subtitle">Reserve Your Perfect Getaway</p>

            <div id="loginMessage" class="login-message" style="display: none;">
                <p>Please login to complete your booking</p>
            </div>

            <?php
            include 'config.php'; // Database connection
            $selected_homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : 1;

            // Get booked dates for selected homestay
            $booked_dates = [];
            $stmt = $conn->prepare("SELECT check_in_date, check_out_date FROM bookings WHERE status != 'cancelled' AND homestay_id = ?");
            $stmt->bind_param("i", $selected_homestay_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $start = new DateTime($row['check_in_date']);
                $end = new DateTime($row['check_out_date']);
                for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
                    $booked_dates[] = $date->format('Y-m-d');
                }
            }
            $stmt->close();

            $today = new DateTime();
            $month = $today->format('F Y');
            $days_in_month = $today->format('t');
            $first_day = new DateTime($today->format('Y-m-01'));
            $starting_day = $first_day->format('w'); // 0-6 (Sun-Sat)
            ?>

            <!-- Calendar -->
            <div class="calendar-container">
                <h3>Check Availability</h3>

                <!-- Dropdown to select Homestay -->
                <?php
                $selected_homestay_id = isset($_GET['homestay_id']) ? (int) $_GET['homestay_id'] : null;
                ?>

                <form method="get" style="margin-bottom: 20px;">
                    <label for="homestay_id"><strong>Select Homestay:</strong></label>
                    <select name="homestay_id" id="homestay_id" onchange="this.form.submit()">
                        <?php
                        // Fetch only available homestays from the database
                        $query = "SELECT homestay_id, name FROM homestays WHERE status = 'available'";
                        $result = mysqli_query($conn, $query);

                        if ($result && mysqli_num_rows($result) > 0):
                            while ($row = mysqli_fetch_assoc($result)):
                                $selected = ($selected_homestay_id == $row['homestay_id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $row['homestay_id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                                <?php
                            endwhile;
                        else:
                            ?>
                            <option disabled>No available homestays</option>
                        <?php endif; ?>
                    </select>
                </form>



                <div id="calendar">
                    <div class="calendar-grid">
                        <h4><?= $month; ?></h4>
                        <div class="calendar-header">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>

                        <div class="calendar-days">
                            <?php
                            for ($i = 0; $i < $starting_day; $i++) {
                                echo "<div class='calendar-day empty'></div>";
                            }

                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $date_str = $today->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                                $current_date = new DateTime($date_str);
                                $is_booked = in_array($date_str, $booked_dates);
                                $is_past = $current_date < new DateTime('today');

                                $classes = ['calendar-day'];
                                if ($is_past) {
                                    $classes[] = 'past';
                                } elseif ($is_booked) {
                                    $classes[] = 'booked';
                                } else {
                                    $classes[] = 'available';
                                }

                                echo "<div class='" . implode(' ', $classes) . "'>";
                                echo $day;
                                if ($is_booked && !$is_past) {
                                    echo "<span class='booked-label'>Booked</span>";
                                }
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <form id="bookingForm" class="booking-form" method="POST" action="process_booking.php">
                <div class="form-row">
                    <div class="form-group">
                        <label>Check-in Date</label>
                        <input type="date" name="check_in_date" id="checkInDate" required>
                    </div>
                    <div class="form-group">
                        <label>Check-out Date</label>
                        <input type="date" name="check_out_date" id="checkOutDate" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Number of Guests</label>
                        <input type="number" name="total_guests" id="totalGuests" min="1" max="10" required>
                    </div>
                    <div class="form-group">
                        <label>Homestay</label>
                        <select name="homestay_id" id="homestaySelect" required>
                            <option value="" disabled selected>Select Homestay</option>
                            <?php
                            $homestaysQuery = "SELECT * FROM homestays WHERE status = 'available' ORDER BY name";
                            $homestaysResult = $conn->query($homestaysQuery);
                            while ($homestay = $homestaysResult->fetch_assoc()):
                                ?>
                                <option value="<?= $homestay['homestay_id'] ?>"
                                    data-price="<?= $homestay['price_per_night'] ?>"
                                    data-max-guests="<?= $homestay['max_guests'] ?>">
                                    <?= htmlspecialchars($homestay['name']) ?> -
                                    RM<?= number_format($homestay['price_per_night'], 2) ?>/night
                                </option>
                            <?php endwhile; ?>
                        </select>

                    </div>

                    <div id="amenitiesContainer" class="form-group">
                    </div>

                </div>

                <?php
                // Fetch amenities with prices for the selected homestay
                $selected_homestay_id = isset($_GET['homestay_id']) ? intval($_GET['homestay_id']) : 1;

                // Query to get amenities available for this homestay
                $amenities_query = "SELECT a.amenity_id, a.name, a.icon, a.price 
                   FROM amenities a
                   JOIN homestay_amenities ha ON a.amenity_id = ha.amenity_id
                   WHERE ha.homestay_id = ?";
                $stmt = $conn->prepare($amenities_query);
                $stmt->bind_param("i", $selected_homestay_id);
                $stmt->execute();
                $amenities_result = $stmt->get_result();
                $available_amenities = $amenities_result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Convert to JSON for JavaScript use
                $amenities_json = json_encode($available_amenities);
                ?>


                <div class="price-breakdown">
                    <h3>Price Breakdown</h3>
                    <div class="price-row">
                        <span>Base Rate (per night):</span>
                        <span id="baseRate">RM 0.00</span>
                    </div>
                    <div class="price-row">
                        <span>Number of Nights:</span>
                        <span id="numberOfNights">0</span>
                    </div>

                    <div id="selectedAmenitiesContainer">
                        <!-- Selected amenities price rows will be inserted here -->
                    </div>

                    <div class="price-row subtotal-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">RM 0.00</span>
                    </div>

                    <div class="price-row discount-row" style="display: none;">
                        <span><i class="fas fa-tag"></i> <span id="discountText">Loyalty Discount</span>:</span>
                        <span id="discountAmount">-RM 0.00</span>
                    </div>

                    <div class="price-row total">
                        <span>Total:</span>
                        <span id="totalPrice">RM 0.00</span>
                    </div>
                </div>


                <script>
                    // Use PHP JSON data
                    const availableAmenities = <?= $amenities_json ?>;

                    // Example base rate and nights (replace with your dynamic values)
                    let baseRate = parseFloat(document.querySelector('#homestaySelect option:checked').dataset.price) || 0;
                    let numberOfNights = 1;  // You can change this dynamically as needed

                    document.getElementById('baseRate').textContent = `RM ${baseRate.toFixed(2)}`;
                    document.getElementById('numberOfNights').textContent = numberOfNights;

                    const selectedAmenitiesContainer = document.getElementById('selectedAmenitiesContainer');

                    // Function to update price breakdown when amenities change
                    function updatePriceBreakdown(selectedAmenityIds) {
                        // Clear current amenities list
                        selectedAmenitiesContainer.innerHTML = '';

                        let amenitiesTotal = 0;
                        selectedAmenityIds.forEach(id => {
                            const amenity = availableAmenities.find(a => a.amenity_id == id);
                            if (amenity) {
                                amenitiesTotal += parseFloat(amenity.price);
                                // Create a price row for this amenity
                                const row = document.createElement('div');
                                row.classList.add('price-row');
                                row.innerHTML = `<span>${amenity.name}:</span> <span>RM ${parseFloat(amenity.price).toFixed(2)}</span>`;
                                selectedAmenitiesContainer.appendChild(row);
                            }
                        });

                        // Calculate subtotal: (baseRate * nights) + amenitiesTotal
                        const subtotal = (baseRate * numberOfNights) + amenitiesTotal;
                        document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;

                        // For simplicity, no discount here
                        document.getElementById('totalPrice').textContent = `RM ${subtotal.toFixed(2)}`;
                    }

                    // Hook this to your amenities checkboxes change event
                    // Example: Assuming you have checkboxes named amenities[] with amenity_id as value
                    function setupAmenityCheckboxes() {
                        const checkboxes = document.querySelectorAll('input[name="amenities[]"]');
                        checkboxes.forEach(cb => {
                            cb.addEventListener('change', () => {
                                const selected = Array.from(checkboxes)
                                    .filter(chk => chk.checked)
                                    .map(chk => chk.value);
                                updatePriceBreakdown(selected);
                            });
                        });
                    }

                    // Initialize price breakdown with no amenities selected
                    updatePriceBreakdown([]);

                    // Wait for DOM fully loaded to bind events
                    document.addEventListener('DOMContentLoaded', () => {
                        setupAmenityCheckboxes();

                        // Also update baseRate if homestay changes
                        const homestaySelect = document.getElementById('homestaySelect');
                        homestaySelect.addEventListener('change', () => {
                            baseRate = parseFloat(homestaySelect.options[homestaySelect.selectedIndex].dataset.price) || 0;
                            document.getElementById('baseRate').textContent = `RM ${baseRate.toFixed(2)}`;
                            updatePriceBreakdown(
                                Array.from(document.querySelectorAll('input[name="amenities[]"]:checked')).map(cb => cb.value)
                            );
                        });
                    });
                </script>


                <!-- Add this somewhere visible -->
                <div id="loyaltyMessage" class="loyalty-message" style="display: none;">
                    <i class="fas fa-crown"></i>
                    <span id="loyaltyMessageText"></span>
                </div>

                <?php $user_id = $_SESSION['user']['user_id'] ?? 0; ?>
                <input type="hidden" name="user_id" value="<?= $_SESSION['user']['user_id'] ?? 0; ?>">
                <input type="hidden" name="calculated_price" id="calculatedPrice" value="0.00">

                <div class="payment-method-select">
                    <h3>Select Payment Method</h3>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="credit_card" required>
                            <i class="fas fa-credit-card"></i>
                            <span>Credit Card</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="debit_card" required>
                            <i class="fas fa-money-check"></i>
                            <span>Debit Card</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" required>
                            <i class="fas fa-university"></i>
                            <span>Bank Transfer</span>
                        </label>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="e_wallet" required>
                            <i class="fas fa-wallet"></i>
                            <span>E-Wallet</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Book Now</button>
            </form>
        </div>
    </section>

    <!-- Reviews Section -->
    <!-- <section id="reviews" class="parallax-section">
        <div class="parallax-content">
            <h1>Guest Reviews</h1>
            <p class="subtitle">What Our Guests Say About Us</p>

            <div class="reviews-container">
                <?php
                // Fetch approved reviews with user and homestay details
                $stmt = $conn->prepare("SELECT r.*, u.name as user_name, h.name as homestay_name 
                                      FROM reviews r 
                                      JOIN users u ON r.user_id = u.user_id 
                                      JOIN homestays h ON r.homestay_id = h.homestay_id 
                                      WHERE r.status = 'approved' 
                                      ORDER BY r.created_at DESC 
                                      LIMIT 6");
                $stmt->execute();
                $reviews = $stmt->get_result();

                while ($review = $reviews->fetch_assoc()):
                    $rating_stars = str_repeat('<i class="fas fa-star"></i>', $review['rating']) .
                        str_repeat('<i class="far fa-star"></i>', 5 - $review['rating']);
                    ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                                <div class="homestay-name"><?php echo htmlspecialchars($review['homestay_name']); ?></div>
                            </div>
                            <div class="rating"><?php echo $rating_stars; ?></div>
                        </div>
                        <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                        <div class="review-date"><?php echo date('F j, Y', strtotime($review['review_date'])); ?></div>
                    </div>
                <?php endwhile;
                $stmt->close(); ?>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="write-review">
                    <h3>Write a Review</h3>
                    <form id="reviewForm" class="review-form">
                        <div class="form-group">
                            <label for="reviewHomestay">Select Homestay:</label>
                            <select name="homestay_id" id="reviewHomestay" required>
                                <?php
                                // Fetch homestays where the user has completed bookings
                                $stmt = $conn->prepare("SELECT DISTINCT h.homestay_id, h.name 
                                                  FROM homestays h 
                                                  JOIN bookings b ON h.homestay_id = b.homestay_id 
                                                  WHERE b.user_id = ? AND b.status = 'completed'");
                                $stmt->bind_param('i', $_SESSION['user_id']);
                                $stmt->execute();
                                $homestays = $stmt->get_result();

                                while ($homestay = $homestays->fetch_assoc()):
                                    echo "<option value='{$homestay['homestay_id']}'>{$homestay['name']}</option>";
                                endwhile;
                                $stmt->close();
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Rating:</label>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>"
                                        required>
                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reviewComment">Your Review:</label>
                            <textarea name="comment" id="reviewComment" rows="4" required></textarea>
                        </div>

                        <button type="submit" class="submit-review-btn">Submit Review</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section> -->

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About EFZEE COTTAGE</h3>
                <p>Experience luxury and comfort in the heart of Batu Pahat. Our homestays offer the perfect blend of
                    modern amenities and traditional charm.</p>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#gallery">Gallery</a></li>
                    <li><a href="#booking">Book Now</a></li>
                    <li><a href="#reviews">Reviews</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Jalan Batu Pahat, Johor</li>
                    <li><i class="fas fa-phone"></i> +60 12-345 6789</li>
                    <li><i class="fas fa-envelope"></i> info@efzeecottage.com</li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> EFZEE COTTAGE. All rights reserved.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="tabs">
                <button class="tab-btn active" data-tab="login">Login</button>
                <button class="tab-btn" data-tab="signup">Sign Up</button>
            </div>
            <!-- Login Form -->
            <form id="loginForm" class="auth-form" method="POST" action="">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="hidden" name="action" value="login">
                <button type="submit">Login</button>
            </form>

            <!-- Sign Up Form -->
            <form id="signupForm" class="auth-form" style="display: none;" method="POST" action="">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Create Password" required>
                <input type="tel" name="phone" placeholder="Phone Number" required>
                <input type="hidden" name="action" value="signup">
                <button type="submit">Create Account</button>
                <p class="text-center">By signing up, you agree to our <a href="#"
                        style="color: var(--secondary-color);">Terms of Service</a></p>
            </form>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });

        // Navbar Scroll Effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Active Section Indicator
        const sections = document.querySelectorAll('.parallax-section');
        const navDots = document.querySelectorAll('.active-section a');

        window.addEventListener('scroll', () => {
            let current = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;

                if (pageYOffset >= (sectionTop - sectionHeight / 3)) {
                    current = section.getAttribute('id');
                }
            });

            navDots.forEach(dot => {
                dot.classList.remove('active');
                if (dot.getAttribute('href') === `#${current}`) {
                    dot.classList.add('active');
                }
            });

            // Update nav links
            document.querySelectorAll('.nav-links a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // Modal Functionality
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.getElementById('loginBtn');
        const closeBtn = document.querySelector('.close');
        const tabBtns = document.querySelectorAll('.tab-btn');

        loginBtn.addEventListener('click', () => {
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        closeBtn.addEventListener('click', () => {
            loginModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        window.addEventListener('click', (e) => {
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Switch tabs
                document.querySelector('.tab-btn.active').classList.remove('active');
                btn.classList.add('active');

                // Show corresponding form
                const tab = btn.getAttribute('data-tab');
                document.querySelectorAll('.auth-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.getElementById(`${tab}Form`).style.display = 'flex';
            });
        });

        // Check login status on page load
        function checkLoginStatus() {
            const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;

            if (isLoggedIn) {
                document.getElementById('loginBtn').style.display = 'none';
                document.getElementById('logoutBtn').style.display = 'block';
                document.getElementById('bookingForm').style.display = 'block';
                document.getElementById('loginMessage').style.display = 'none';
            } else {
                document.getElementById('loginBtn').style.display = 'block';
                document.getElementById('logoutBtn').style.display = 'none';
                document.getElementById('bookingForm').style.display = 'none';
                document.getElementById('loginMessage').style.display = 'block';
            }
        }

        // Initialize on page load
        checkLoginStatus();

        // Booking Form Calculation
        const bookingForm = document.getElementById('bookingForm');
        const roomType = document.getElementById('roomType');
        const guests = document.getElementById('guests');
        const amenities = document.querySelectorAll('input[name="amenities"]');


        // Booking Calculation Logic
        document.addEventListener('DOMContentLoaded', function () {
            // Parse PHP amenities data
            const availableAmenities = <?php echo $amenities_json; ?>;

            // Create amenity prices mapping
            const amenityPrices = {};
            availableAmenities.forEach(amenity => {
                amenityPrices[amenity.amenity_id] = {
                    name: amenity.name,
                    price: parseFloat(amenity.price),
                    icon: amenity.icon
                };
            });

            // Get all necessary elements
            const checkInDate = document.getElementById('checkInDate');
            const checkOutDate = document.getElementById('checkOutDate');
            const homestaySelect = document.getElementById('homestaySelect');
            const totalGuests = document.getElementById('totalGuests');
            const amenityCheckboxes = document.querySelectorAll('input[name="amenities[]"]');
            const priceBreakdown = document.querySelector('.price-breakdown');

            // Price display elements
            const baseRateElement = document.getElementById('baseRate');
            const numberOfNightsElement = document.getElementById('numberOfNights');
            const subtotalElement = document.getElementById('subtotal');
            const totalPriceElement = document.getElementById('totalPrice');
            const calculatedPrice = document.getElementById('calculatedPrice');

            // Add event listeners
            [checkInDate, checkOutDate, homestaySelect, totalGuests].forEach(element => {
                element.addEventListener('change', calculateTotal);
            });

            amenityCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', calculateTotal);
            });

            // Initial calculation
            calculateTotal();

            async function calculateTotal() {
                // Get selected homestay price
                const selectedOption = homestaySelect.options[homestaySelect.selectedIndex];
                const pricePerNight = parseFloat(selectedOption.getAttribute('data-price'));
                const maxGuests = parseInt(selectedOption.getAttribute('data-max-guests'));

                // Validate guests number
                const guests = parseInt(totalGuests.value) || 1;
                if (guests > maxGuests) {
                    totalGuests.value = maxGuests;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maximum Guests Exceeded',
                        text: `This homestay can only accommodate ${maxGuests} guests.`
                    });
                }

                // Calculate number of nights
                const checkIn = new Date(checkInDate.value);
                const checkOut = new Date(checkOutDate.value);
                let nights = 0;

                if (checkIn && checkOut && checkOut > checkIn) {
                    nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                }

                // Calculate base rate for the entire stay
                const baseRate = pricePerNight * nights;

                // Calculate amenities total and collect selected amenities
                let amenitiesTotal = 0;
                const selectedAmenities = [];

                amenityCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const amenityId = parseInt(checkbox.value);
                        const amenity = amenityPrices[amenityId];
                        if (amenity) {
                            amenitiesTotal += amenity.price;
                            selectedAmenities.push({
                                name: amenity.name,
                                price: amenity.price,
                                icon: amenity.icon
                            });
                        }
                    }
                });

                // Calculate subtotal (base rate already includes nights)
                const subtotal = baseRate + (amenitiesTotal * nights);

                // Get loyalty discount if logged in
                let loyaltyDiscount = 0;
                let discountAmount = 0;
                let discountTier = '';

                if (<?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>) {
                    try {
                        const response = await fetch('get_loyalty_discount.php?user_id=<?= $_SESSION['user']['user_id'] ?? 0 ?>');
                        const data = await response.json();
                        loyaltyDiscount = data.discount || 0;
                        discountAmount = subtotal * (loyaltyDiscount / 100);
                    } catch (error) {
                        console.error('Error fetching loyalty discount:', error);
                    }
                }

                // Calculate total with discount
                const total = subtotal - discountAmount;

                // Update price breakdown HTML
                updatePriceBreakdown(baseRate, nights, selectedAmenities, subtotal, loyaltyDiscount, discountAmount);

                // Update hidden calculated price
                calculatedPrice.value = total.toFixed(2);
            }

            function updatePriceBreakdown(baseRate, nights, selectedAmenities, subtotal, discountPercent = 0, discountAmount = 0) {
                // Clear existing amenity rows and discount row
                const existingRows = priceBreakdown.querySelectorAll('.amenity-row, .discount-row');
                existingRows.forEach(row => row.remove());

                // Update base values (show per night rate for clarity)
                baseRateElement.textContent = `RM ${pricePerNight.toFixed(2)}`;
                numberOfNightsElement.textContent = nights;
                subtotalElement.textContent = `RM ${subtotal.toFixed(2)}`;

                // Add selected amenities to breakdown
                selectedAmenities.forEach(amenity => {
                    const amenityRow = document.createElement('div');
                    amenityRow.className = 'price-row amenity-row';
                    amenityRow.innerHTML = `
            <span>
                <i class="${amenity.icon}"></i>
                ${amenity.name}:
            </span>
            <span>RM ${amenity.price.toFixed(2)}</span>
        `;
                    // Insert before subtotal row
                    subtotalElement.parentElement.before(amenityRow);
                });

                // Add discount row if applicable
                if (discountPercent > 0) {
                    const discountRow = document.createElement('div');
                    discountRow.className = 'price-row discount-row';
                    discountRow.innerHTML = `
            <span>
                <i class="fas fa-percentage"></i>
                Loyalty Discount (${discountPercent}%):
            </span>
            <span>-RM ${discountAmount.toFixed(2)}</span>
        `;
                    subtotalElement.parentElement.before(discountRow);
                }

                // Update total with discount
                totalPriceElement.textContent = `RM ${(subtotal + selectedAmenities).toFixed(2)}`;
            }
            // Set minimum checkout date to be after checkin date
            checkInDate.addEventListener('change', function () {
                if (this.value) {
                    const nextDay = new Date(this.value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    checkOutDate.min = nextDay.toISOString().split('T')[0];

                    // If current checkout is before new checkin, reset it
                    if (checkOutDate.value && new Date(checkOutDate.value) <= new Date(this.value)) {
                        checkOutDate.value = '';
                    }
                }
                calculateTotal();
            });

            checkOutDate.addEventListener('change', calculateTotal);
        });

        // Event listeners are already added within DOMContentLoaded
        // Booking form submission
        bookingForm.addEventListener('submit', (e) => {
            e.preventDefault();
            Swal.fire({
                title: 'Booking Confirmed!',
                html: `
                    <p>Your reservation at EFZEE COTTAGE has been confirmed.</p>
                    <p><strong>Total:</strong> ${document.getElementById('totalPrice').textContent}</p>
                `,
                icon: 'success',
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
            });
        });

        // Initialize calendar with current date
        const today = new Date();
        document.getElementById('checkIn').valueAsDate = today;
        document.getElementById('checkOut').valueAsDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);

        // Calendar day click handler
        document.querySelectorAll('.calendar-day.available').forEach(day => {
            day.addEventListener('click', function () {
                if (!<?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>) {
                    document.getElementById('loginMessage').style.display = 'block';
                    loginModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                } else {
                    // Set dates when clicked
                    const date = new Date();
                    date.setDate(parseInt(this.textContent));
                    document.getElementById('checkIn').valueAsDate = date;
                    document.getElementById('checkOut').valueAsDate = new Date(date.getTime() + 24 * 60 * 60 * 1000);
                    calculateTotal();
                }
            });
        });

        // Initial calculation
        calculateTotal();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkInInput = document.querySelector('input[name="check_in_date"]');
            const checkOutInput = document.querySelector('input[name="check_out_date"]');
            const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

            // Set min date to today
            checkInInput.min = today;
            checkOutInput.min = today;

            // Disable booked dates
            function disableBookedDates(input) {
                input.addEventListener('input', function () {
                    const selectedDate = this.value;
                });
            }

            disableBookedDates(checkInInput);
            disableBookedDates(checkOutInput);
        });
    </script>

    <script>
        // Review Form Submission
        document.getElementById('reviewForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Review Submitted!',
                            text: 'Thank you for your feedback. Your review will be visible after approval.',
                            icon: 'success',
                            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
                        }).then(() => {
                            this.reset();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to submit review. Please try again.',
                            icon: 'error',
                            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An unexpected error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
                    });
                });
        });
    </script>

    <script>
        document.getElementById("bookingForm").addEventListener("submit", function (e) {
            e.preventDefault(); // prevent default form submission

            const form = e.target;
            const formData = new FormData(form);

            fetch("process_booking.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.json()) // expect JSON from PHP
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Booking Successful!',
                            text: 'Your booking has been placed successfully.',
                            confirmButtonText: 'View Booking'
                        }).then(() => {
                            window.location.href = 'mybooking.php'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Booking Failed',
                            text: data.message || 'An error occurred. Please try again.'
                        });
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'System Error',
                        text: 'Something went wrong!'
                    });
                });
        });
    </script>

    <script>
        document.getElementById('homestaySelect').addEventListener('change', function () {
            const homestayId = this.value;

            fetch(`get_amenities.php?homestay_id=${homestayId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('amenitiesContainer');
                    container.innerHTML = '';

                    if (data.length > 0) {
                        const label = document.createElement('label');
                        label.innerHTML = '<strong>Select Additional Amenities:</strong>';
                        container.appendChild(label);

                        const grid = document.createElement('div');
                        grid.className = 'amenities-grid';

                        data.forEach(amenity => {
                            const item = document.createElement('div');
                            item.className = 'amenity-item';

                            item.innerHTML = `
                        <input type="checkbox" name="amenities[]" id="amenity${amenity.amenity_id}" value="${amenity.amenity_id}">
                        <label for="amenity${amenity.amenity_id}">
                            <i class="${amenity.icon}"></i>
                            <span>${amenity.name} (RM${parseFloat(amenity.price).toFixed(2)})</span>
                        </label>
                    `;
                            grid.appendChild(item);
                        });

                        container.appendChild(grid);
                    } else {
                        container.innerHTML = '<p>No amenities available for this homestay.</p>';
                    }
                });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const homestaySelect = document.getElementById('homestaySelect');
            const baseRateSpan = document.getElementById('baseRate');
            const nightsSpan = document.getElementById('numberOfNights');
            const subtotalSpan = document.getElementById('subtotal');
            const totalSpan = document.getElementById('totalPrice');
            const amenitiesContainer = document.getElementById('amenityChargesContainer');
            const discountRow = document.querySelector('.discount-row');
            const discountAmount = document.getElementById('discountAmount');

            let baseRate = 0;
            let numberOfNights = 1; // You can adjust this or get from a date picker
            let selectedAmenities = [];

            function updatePriceBreakdown() {
                let amenityTotal = selectedAmenities.reduce((sum, a) => sum + parseFloat(a.price), 0);
                let baseSubtotal = baseRate * numberOfNights;
                let subtotal = baseSubtotal + amenityTotal;

                // Update UI
                baseRateSpan.textContent = `RM ${baseRate.toFixed(2)}`;
                nightsSpan.textContent = numberOfNights;
                subtotalSpan.textContent = `RM ${subtotal.toFixed(2)}`;
                totalSpan.textContent = `RM ${total.toFixed(2)}`;

                // Show/hide discount row
                if (discount > 0) {
                    discountRow.style.display = 'flex';
                    discountAmount.textContent = `-RM ${discount.toFixed(2)}`;
                } else {
                    discountRow.style.display = 'none';
                }

                // Show selected amenities in breakdown
                amenitiesContainer.innerHTML = '';
                selectedAmenities.forEach(a => {
                    const row = document.createElement('div');
                    row.className = 'price-row';
                    row.innerHTML = `
                <span>${a.name}:</span>
                <span>RM ${parseFloat(a.price).toFixed(2)}</span>
            `;
                    amenitiesContainer.appendChild(row);
                });
            }

            function fetchAmenities(homestayId) {
                fetch(`get_amenities.php?homestay_id=${homestayId}`)
                    .then(res => res.json())
                    .then(data => {
                        const amenitiesGrid = document.querySelector('.amenities-grid');
                        amenitiesGrid.innerHTML = '';

                        data.forEach(amenity => {
                            const id = `amenity${amenity.amenity_id}`;
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'amenities[]';
                            checkbox.id = id;
                            checkbox.value = amenity.amenity_id;
                            checkbox.dataset.price = amenity.price;
                            checkbox.dataset.name = amenity.name;

                            const label = document.createElement('label');
                            label.htmlFor = id;
                            label.innerHTML = `<i class="${amenity.icon}"></i> <span>${amenity.name} (RM${parseFloat(amenity.price).toFixed(2)})</span>`;

                            const item = document.createElement('div');
                            item.className = 'amenity-item';
                            item.appendChild(checkbox);
                            item.appendChild(label);

                            amenitiesGrid.appendChild(item);

                            checkbox.addEventListener('change', () => {
                                if (checkbox.checked) {
                                    selectedAmenities.push({
                                        amenity_id: amenity.amenity_id,
                                        name: amenity.name,
                                        price: parseFloat(amenity.price)
                                    });
                                } else {
                                    selectedAmenities = selectedAmenities.filter(a => a.amenity_id !== amenity.amenity_id);
                                }
                                updatePriceBreakdown();
                            });
                        });
                    });
            }

            homestaySelect.addEventListener('change', function () {
                const selected = homestaySelect.selectedOptions[0];
                baseRate = parseFloat(selected.dataset.price);
                selectedAmenities = []; // Reset
                fetchAmenities(selected.value);
                updatePriceBreakdown();
            });

            // Trigger initial if already selected
            if (homestaySelect.value) {
                const selected = homestaySelect.selectedOptions[0];
                baseRate = parseFloat(selected.dataset.price);
                fetchAmenities(selected.value);
                updatePriceBreakdown();
            }
        });
    </script>




</body>

</html>

<?php
// SweetAlert triggers after PHP processing

if (isset($_SESSION['login_success'])) {
    echo "<script>
    Swal.fire({
        title: 'Login Successful!',
        text: " . json_encode($_SESSION['login_success']) . ",
        icon: 'success',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['login_success']);
}

if (isset($_SESSION['login_error'])) {
    echo "<script>
    Swal.fire({
        title: 'Login Failed',
        text: " . json_encode($_SESSION['login_error']) . ",
        icon: 'error',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['login_error']);
}
?>

<?php
if (isset($_SESSION['signup_success'])) {
    echo "<script>
    Swal.fire({
        title: 'Account Created!',
        text: " . json_encode($_SESSION['signup_success']) . ",
        icon: 'success',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['signup_success']);
}

if (isset($_SESSION['signup_error'])) {
    echo "<script>
    Swal.fire({
        title: 'Signup Failed',
        text: " . json_encode($_SESSION['signup_error']) . ",
        icon: 'error',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim()
    });
    </script>";
    unset($_SESSION['signup_error']);
}
?>

<?php
// Function to get customer loyalty status
function getCustomerLoyaltyStatus($user_id, $conn)
{
    $stmt = $conn->prepare("SELECT * FROM customer_loyalty WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get loyalty tiers
function getLoyaltyTiers($conn)
{
    $result = $conn->query("SELECT * FROM loyalty_tiers ORDER BY min_points ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to calculate loyalty discount
function calculateLoyaltyDiscount($user_id, $conn)
{
    $loyalty = getCustomerLoyaltyStatus($user_id, $conn);
    $tiers = getLoyaltyTiers($conn);

    if (!$loyalty)
        return 0;

    $discount = 0;
    foreach ($tiers as $tier) {
        if ($loyalty['loyalty_points'] >= $tier['min_points']) {
            $discount = $tier['discount_percentage'];
        }
    }

    return $discount;
}
?>