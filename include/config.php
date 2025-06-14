<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cozyhomestay');

// Establish connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log("DB Connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}
$conn->set_charset("utf8");

// Input sanitization
function sanitize_input($data)
{
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8'));
}

// CSRF Token functions
function generate_token()
{
    return bin2hex(random_bytes(32));
}
function verify_token($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_token();
}

// JSON response helper
function send_json_response($success, $message, $data = null)
{
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null)
        $response['data'] = $data;
    echo json_encode($response);
    exit();
}

// Error handler
function handle_error($msg)
{
    error_log($msg);
    send_json_response(false, 'An error occurred. Please try again later.');
}

// Middleware
function require_auth()
{
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        send_json_response(false, 'Authentication required');
    }
}
function require_admin()
{
    require_auth();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        send_json_response(false, 'Admin access required');
    }
}
