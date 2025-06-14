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
            case 'add_amenity':
                $name = $_POST['name'];
                $icon = $_POST['icon'];

                $stmt = $conn->prepare("INSERT INTO amenities (name, icon) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $icon);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Amenity added successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to add amenity.";
                }
                break;

            case 'update_amenity':
                $amenity_id = $_POST['amenity_id'];
                $name = $_POST['name'];
                $icon = $_POST['icon'];

                $stmt = $conn->prepare("UPDATE amenities SET name = ?, icon = ? WHERE amenity_id = ?");
                $stmt->bind_param("ssi", $name, $icon, $amenity_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Amenity updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to update amenity.";
                }
                break;

            case 'delete_amenity':
                $amenity_id = $_POST['amenity_id'];
                
                // First delete from homestay_amenities
                $conn->query("DELETE FROM homestay_amenities WHERE amenity_id = $amenity_id");
                
                $stmt = $conn->prepare("DELETE FROM amenities WHERE amenity_id = ?");
                $stmt->bind_param("i", $amenity_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Amenity deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete amenity.";
                }
                break;
        }
        
        header('Location: admin_amenities.php');
        exit();
    }
}

// Get all amenities with usage count
$query = "SELECT a.*, COUNT(ha.homestay_id) as usage_count 
          FROM amenities a 
          LEFT JOIN homestay_amenities ha ON a.amenity_id = ha.amenity_id 
          GROUP BY a.amenity_id 
          ORDER BY a.name";
$amenities = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Amenities - EFZEE COTTAGE</title>
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
        .icon-preview {
            font-size: 1.5rem;
            margin-right: 10px;
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
                <a href="admin_amenities.php" class="nav-link active mb-2">
                    <i class="fas fa-concierge-bell me-2"></i> Amenities
                </a>
                <a href="admin_payments.php" class="nav-link mb-2">
                    <i class="fas fa-money-bill me-2"></i> Payments
                </a>
                <a href="admin_users.php" class="nav-link mb-2">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Amenities</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAmenityModal">
                    <i class="fas fa-plus me-2"></i>Add New Amenity
                </button>
            </div>

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

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Usage Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($amenity = $amenities->fetch_assoc()): ?>
                                    <tr>
                                        <td><i class="<?php echo $amenity['icon']; ?> fa-lg"></i></td>
                                        <td><?php echo htmlspecialchars($amenity['name']); ?></td>
                                        <td><?php echo $amenity['usage_count']; ?> homestays</td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editAmenityModal<?php echo $amenity['amenity_id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteAmenity(<?php echo $amenity['amenity_id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Amenity Modal -->
                                    <div class="modal fade" id="editAmenityModal<?php echo $amenity['amenity_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Amenity</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="admin_amenities.php" method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="update_amenity">
                                                        <input type="hidden" name="amenity_id" value="<?php echo $amenity['amenity_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($amenity['name']); ?>" required>
                                                        </div>

                                                        <div class="mb-3">
    <label class="form-label">Amenity Icon</label>
    <div class="input-group">
        <select name="icon" class="form-select" onchange="document.getElementById('icon-preview').className = this.value;">
            <option value="fas fa-wifi" <?php if($amenity['icon'] == 'fas fa-wifi') echo 'selected'; ?>>Wi-Fi</option>
            <option value="fas fa-car" <?php if($amenity['icon'] == 'fas fa-car') echo 'selected'; ?>>Parking</option>
            <option value="fas fa-swimmer" <?php if($amenity['icon'] == 'fas fa-swimmer') echo 'selected'; ?>>Swimming Pool</option>
            <option value="fas fa-tv" <?php if($amenity['icon'] == 'fas fa-tv') echo 'selected'; ?>>Television</option>
            <option value="fas fa-utensils" <?php if($amenity['icon'] == 'fas fa-utensils') echo 'selected'; ?>>Kitchen</option>
            <option value="fas fa-snowflake" <?php if($amenity['icon'] == 'fas fa-snowflake') echo 'selected'; ?>>Air Conditioning</option>
            <option value="fas fa-bath" <?php if($amenity['icon'] == 'fas fa-bath') echo 'selected'; ?>>Bathroom</option>
            <option value="fas fa-bed" <?php if($amenity['icon'] == 'fas fa-bed') echo 'selected'; ?>>Bed</option>
            <!-- Add more options as needed -->
        </select>
        <span class="input-group-text"><i id="icon-preview" class="<?php echo $amenity['icon']; ?>"></i></span>
    </div>
    <div class="form-text">Choose a Font Awesome icon that represents the amenity.</div>
</div>

<script>
    // Ensure icon preview shows correctly on page load
    window.addEventListener('DOMContentLoaded', function () {
        const select = document.querySelector('select[name="icon"]');
        const preview = document.getElementById('icon-preview');
        if (select && preview) {
            preview.className = select.value;
        }
    });
</script>

                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Update Amenity</button>
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

<!-- Add Amenity Modal -->
<div class="modal fade" id="addAmenityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Amenity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_amenities.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_amenity">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amenity Icon</label>
                        <div class="input-group">
                            <select name="icon" class="form-select" id="add-icon-select" onchange="document.getElementById('add-icon-preview').className = this.value;">
                                <option value="fas fa-wifi">Wi-Fi</option>
                                <option value="fas fa-car">Parking</option>
                                <option value="fas fa-swimmer">Swimming Pool</option>
                                <option value="fas fa-tv">Television</option>
                                <option value="fas fa-utensils">Kitchen</option>
                                <option value="fas fa-snowflake">Air Conditioning</option>
                                <option value="fas fa-bath">Bathroom</option>
                                <option value="fas fa-bed">Bed</option>
                                <!-- Add more as needed -->
                            </select>
                            <span class="input-group-text"><i id="add-icon-preview" class="fas fa-icons"></i></span>
                        </div>
                        <div class="form-text">Choose a Font Awesome icon that represents the amenity.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Amenity</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Set initial preview when modal opens
    const addIconSelect = document.getElementById('add-icon-select');
    const addIconPreview = document.getElementById('add-icon-preview');

    if (addIconSelect && addIconPreview) {
        addIconSelect.addEventListener('change', function () {
            addIconPreview.className = this.value;
        });

        // Set preview on page load
        window.addEventListener('DOMContentLoaded', function () {
            addIconPreview.className = addIconSelect.value;
        });
    }
</script>


<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this amenity? This will remove it from all homestays that have it.
            </div>
            <div class="modal-footer">
                <form action="admin_amenities.php" method="POST">
                    <input type="hidden" name="action" value="delete_amenity">
                    <input type="hidden" name="amenity_id" id="deleteAmenityId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteAmenity(amenityId) {
    document.getElementById('deleteAmenityId').value = amenityId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>