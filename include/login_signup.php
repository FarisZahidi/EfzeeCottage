<?php
$login_error = '';
$signup_error = '';

// Login process
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
// Signup process
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