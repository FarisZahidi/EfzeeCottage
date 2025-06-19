<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get homestay ID
$homestay_id = $_POST['homestay_id'] ?? 0;

// Verify homestay exists
$query = "SELECT homestay_id FROM homestays WHERE homestay_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $homestay_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Invalid homestay']);
    exit;
}

// Handle file upload
if (!isset($_FILES['images'])) {
    echo json_encode(['success' => false, 'message' => 'No images uploaded']);
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/homestays/' . $homestay_id;
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
$errors = [];

// Start transaction
$conn->begin_transaction();

try {
    // Process each uploaded file
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['images']['name'][$key];
        $file_size = $_FILES['images']['size'][$key];
        $file_type = $_FILES['images']['type'][$key];
        $file_error = $_FILES['images']['error'][$key];

        // Skip if there was an upload error
        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading {$file_name}: " . upload_error_message($file_error);
            continue;
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "{$file_name} is not an allowed image type";
            continue;
        }

        // Validate file size (max 5MB)
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "{$file_name} exceeds maximum file size of 5MB";
            continue;
        }

        // Generate unique filename
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_filename = uniqid('homestay_') . '.' . $extension;
        $file_path = $upload_dir . '/' . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($tmp_name, $file_path)) {
            // Save file info to database
            $relative_path = 'uploads/homestays/' . $homestay_id . '/' . $new_filename;
            $query = "INSERT INTO homestay_images (homestay_id, image_path, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('is', $homestay_id, $relative_path);
            $stmt->execute();

            $uploaded_files[] = [
                'original_name' => $file_name,
                'saved_name' => $new_filename,
                'path' => $relative_path
            ];
        } else {
            $errors[] = "Failed to save {$file_name}";
        }
    }

    // If there were any successful uploads, commit the transaction
    if (!empty($uploaded_files)) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'uploaded_files' => $uploaded_files,
            'errors' => $errors
        ]);
    } else {
        // If no files were uploaded successfully, rollback and return error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload any images',
            'errors' => $errors
        ]);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log('Error uploading homestay images: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to upload images: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();

// Helper function to get upload error message
function upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}