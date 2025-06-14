<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: deepseek.php');
    exit();
}

// Database connection
require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_user':
                $user_id = $_POST['user_id'];
                $name = $_POST['name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $role = $_POST['role'];

                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "User updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to update user.";
                }
                break;

            case 'delete_user':
                $user_id = $_POST['user_id'];
                
                // First check if user has any bookings
                $check = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $user_id");
                $result = $check->fetch_assoc();
                
                if ($result['count'] > 0) {
                    $_SESSION['error_message'] = "Cannot delete user with existing bookings.";
                } else {
                    // Delete user's reviews first
                    $conn->query("DELETE FROM reviews WHERE user_id = $user_id");
                    
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "User deleted successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to delete user.";
                    }
                }
                break;
        }
        
        header('Location: admin_users.php');
        exit();
    }
}

// Get user statistics
$stats = $conn->query("SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
    COUNT(CASE WHEN role = 'guest' THEN 1 END) as guest_count,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today
FROM users");
$user_stats = $stats->fetch_assoc();

// Get all users with their booking counts
$query = "SELECT u.*, 
          COUNT(DISTINCT b.booking_id) as booking_count,
          COUNT(DISTINCT r.review_id) as review_count
          FROM users u 
          LEFT JOIN bookings b ON u.user_id = b.user_id 
          LEFT JOIN reviews r ON b.booking_id = r.booking_id 
          GROUP BY u.user_id 
          ORDER BY u.created_at DESC";
$users = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - EFZEE COTTAGE</title>
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
                <a href="admin_users.php" class="nav-link active mb-2">
                    <i class="fas fa-users me-2"></i> Users
                </a>
                <!-- <a href="admin_reviews.php" class="nav-link mb-2">
                    <i class="fas fa-star me-2"></i> Reviews
                </a> -->
                <a href="logout.php" class="nav-link mt-4 text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">User Management</h2>

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
                            <h5 class="card-title">Total Users</h5>
                            <h3><?php echo $user_stats['total_users']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Admin Users</h5>
                            <h3><?php echo $user_stats['admin_count']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Guest Users</h5>
                            <h3><?php echo $user_stats['guest_count']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stats-card bg-warning text-dark">
                        <div class="card-body">
                            <h5 class="card-title">New Today</h5>
                            <h3><?php echo $user_stats['new_today']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Bookings</th>
                                    <th>Reviews</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $user['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['booking_count']; ?></td>
                                        <td><?php echo $user['review_count']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['user_id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php if ($user['booking_count'] == 0): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Edit User Modal -->
                                    <div class="modal fade" id="editUserModal<?php echo $user['user_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit User</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="admin_users.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Phone</label>
                                                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Role</label>
                                                            <select name="role" class="form-select" required>
                                                                <option value="guest" <?php echo $user['role'] === 'guest' ? 'selected' : ''; ?>>Guest</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Update User</button>
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
                Are you sure you want to delete this user? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form action="admin_users.php" method="POST">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteUser(userId) {
    document.getElementById('deleteUserId').value = userId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>