<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: deepseek.php');
    exit();
}

// Database connection
require_once 'config.php';

// Handle review moderation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_review_status':
                $review_id = $_POST['review_id'];
                $status = $_POST['status'];
                $admin_response = $_POST['admin_response'] ?? null;

                $stmt = $conn->prepare("UPDATE reviews SET status = ?, admin_response = ? WHERE review_id = ?");
                $stmt->bind_param("ssi", $status, $admin_response, $review_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Review status updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to update review status.";
                }
                break;

            case 'delete_review':
                $review_id = $_POST['review_id'];
                
                $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
                $stmt->bind_param("i", $review_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Review deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete review.";
                }
                break;
        }
        
        header('Location: admin_reviews.php');
        exit();
    }
}

// Get review statistics
$stats = $conn->query("SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews
FROM reviews");
$review_stats = $stats->fetch_assoc();

// Get all reviews with related user and homestay information
$query = "
    SELECT r.*, u.name AS user_name, h.name AS homestay_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.user_id 
    JOIN homestays h ON r.homestay_id = h.homestay_id 
    ORDER BY r.created_at DESC
";

$reviews = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Management - EFZEE COTTAGE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .nav-link {
            color: #fff;
        }
        .nav-link:hover {
            background-color: #495057;
        }
        .nav-link.active {
            background-color: #0d6efd;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .star-rating {
            color: #ffc107;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar p-3">
            <h3 class="text-white mb-4">Admin Panel</h3>
            <div class="nav flex-column">
                <a href="admin.php" class="nav-link mb-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="admin_bookings.php" class="nav-link mb-2">
                    <i class="fas fa-calendar-alt me-2"></i> Bookings
                </a>
                <a href="admin_homestays.php" class="nav-link mb-2">
                    <i class="fas fa-home me-2"></i> Homestays
                </a>
                <a href="admin_amenities.php" class="nav-link mb-2">
                    <i class="fas fa-concierge-bell me-2"></i> Amenities
                </a>
                <a href="admin_payments.php" class="nav-link mb-2">
                    <i class="fas fa-money-bill me-2"></i> Payments
                </a>
                <a href="admin_users.php" class="nav-link mb-2">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <!-- <a href="admin_reviews.php" class="nav-link active mb-2">
                    <i class="fas fa-star me-2"></i> Reviews
                </a> -->
                <a href="logout.php" class="nav-link mt-4 text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Review Management</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Reviews</h5>
                            <h3><?php echo $review_stats['total_reviews']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Average Rating</h5>
                            <h3><?php echo number_format($review_stats['avg_rating'] ?? 0, 2); ?></h3>

                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Approved Reviews</h5>
                            <h3><?php echo $review_stats['approved_reviews']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Pending Reviews</h5>
                            <h3><?php echo $review_stats['pending_reviews']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews Table -->
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Homestay</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($review = $reviews->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $review['review_id']; ?></td>
                                        <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($review['homestay_name']); ?></td>
                                        <td class="star-rating">
                                            <?php 
                                                for ($i = 0; $i < $review['rating']; $i++) {
                                                    echo '<i class="fas fa-star"></i>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                echo nl2br(htmlspecialchars(substr($review['comment'], 0, 100)));
                                                if (strlen($review['comment']) > 100) echo '...';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $review['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($review['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $review['review_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteReview(<?php echo $review['review_id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Review Modal -->
                                    <div class="modal fade" id="reviewModal<?php echo $review['review_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Review Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="admin_reviews.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_review_status">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Guest</label>
                                                            <p class="form-control-static"><?php echo htmlspecialchars($review['user_name']); ?></p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Homestay</label>
                                                            <p class="form-control-static"><?php echo htmlspecialchars($review['homestay_name']); ?></p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Rating</label>
                                                            <p class="form-control-static star-rating">
                                                                <?php 
                                                                    for ($i = 0; $i < $review['rating']; $i++) {
                                                                        echo '<i class="fas fa-star"></i>';
                                                                    }
                                                                ?>
                                                            </p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Review Comment</label>
                                                            <p class="form-control-static"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="pending" <?php echo $review['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="approved" <?php echo $review['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Admin Response (Optional)</label>
                                                            <textarea name="admin_response" class="form-control" rows="3"><?php echo htmlspecialchars($review['admin_response'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Update Review</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this review? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form action="admin_reviews.php" method="POST">
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="review_id" id="deleteReviewId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteReview(reviewId) {
    document.getElementById('deleteReviewId').value = reviewId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>