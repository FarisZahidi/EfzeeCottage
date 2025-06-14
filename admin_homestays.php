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
            case 'add_homestay':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $address = $_POST['address'];
                $price = $_POST['price'];
                $max_guests = $_POST['max_guests'];
                $bedrooms = $_POST['bedrooms'];
                $bathrooms = $_POST['bathrooms'];
                $status = $_POST['status'];

                $stmt = $conn->prepare("INSERT INTO homestays (name, description, address, price_per_night, max_guests, bedrooms, bathrooms, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdiiis", $name, $description, $address, $price, $max_guests, $bedrooms, $bathrooms, $status);
                
                if ($stmt->execute()) {
                    $homestay_id = $stmt->insert_id;
                    
                    // Handle amenities
                    if (isset($_POST['amenities'])) {
                        $amenity_stmt = $conn->prepare("INSERT INTO homestay_amenities (homestay_id, amenity_id) VALUES (?, ?)");
                        foreach ($_POST['amenities'] as $amenity_id) {
                            $amenity_stmt->bind_param("ii", $homestay_id, $amenity_id);
                            $amenity_stmt->execute();
                        }
                    }
                    $_SESSION['success_message'] = "Homestay added successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to add homestay.";
                }
                break;

            case 'update_homestay':
                $homestay_id = $_POST['homestay_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $address = $_POST['address'];
                $price = $_POST['price'];
                $max_guests = $_POST['max_guests'];
                $bedrooms = $_POST['bedrooms'];
                $bathrooms = $_POST['bathrooms'];
                $status = $_POST['status'];

                $stmt = $conn->prepare("UPDATE homestays SET name = ?, description = ?, address = ?, price_per_night = ?, max_guests = ?, bedrooms = ?, bathrooms = ?, status = ? WHERE homestay_id = ?");
                $stmt->bind_param("sssdiiisi", $name, $description, $address, $price, $max_guests, $bedrooms, $bathrooms, $status, $homestay_id);
                
                if ($stmt->execute()) {
                    // Update amenities
                    $conn->query("DELETE FROM homestay_amenities WHERE homestay_id = $homestay_id");
                    
                    if (isset($_POST['amenities'])) {
                        $amenity_stmt = $conn->prepare("INSERT INTO homestay_amenities (homestay_id, amenity_id) VALUES (?, ?)");
                        foreach ($_POST['amenities'] as $amenity_id) {
                            $amenity_stmt->bind_param("ii", $homestay_id, $amenity_id);
                            $amenity_stmt->execute();
                        }
                    }
                    $_SESSION['success_message'] = "Homestay updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to update homestay.";
                }
                break;

            case 'delete_homestay':
                $homestay_id = $_POST['homestay_id'];
                
                // First delete related records
                $conn->query("DELETE FROM homestay_amenities WHERE homestay_id = $homestay_id");
                
                $stmt = $conn->prepare("DELETE FROM homestays WHERE homestay_id = ?");
                $stmt->bind_param("i", $homestay_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Homestay deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete homestay.";
                }
                break;
        }
        
        header('Location: admin_homestays.php');
        exit();
    }
}

// Get all homestays
$homestays = $conn->query("SELECT * FROM homestays ORDER BY homestay_id DESC");

// Get all amenities
$amenities = $conn->query("SELECT * FROM amenities ORDER BY name");
$amenities_list = $amenities->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Homestays - EFZEE COTTAGE</title>
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
                <a href="admin_homestays.php" class="nav-link active mb-2">
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
                <h2>Manage Homestays</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHomestayModal">
                    <i class="fas fa-plus me-2"></i>Add New Homestay
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

            <!-- Homestays Grid -->
            <div class="row">
                <?php while ($homestay = $homestays->fetch_assoc()): 
                    // Get homestay amenities
                    $homestay_id = $homestay['homestay_id'];
                    $amenities_query = "SELECT a.* FROM amenities a 
                                       JOIN homestay_amenities ha ON a.amenity_id = ha.amenity_id 
                                       WHERE ha.homestay_id = $homestay_id";
                    $homestay_amenities = $conn->query($amenities_query)->fetch_all(MYSQLI_ASSOC);
                ?>
                    <div class="col-md-6 col-xl-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($homestay['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($homestay['description']); ?></p>
                                
                                <div class="mb-3">
                                    <strong>Address:</strong> <?php echo htmlspecialchars($homestay['address']); ?>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col">
                                        <strong>Price:</strong> RM <?php echo number_format($homestay['price_per_night'], 2); ?>/night
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col">
                                        <i class="fas fa-users me-2"></i> <?php echo $homestay['max_guests']; ?> guests
                                    </div>
                                    <div class="col">
                                        <i class="fas fa-bed me-2"></i> <?php echo $homestay['bedrooms']; ?> bedrooms
                                    </div>
                                    <div class="col">
                                        <i class="fas fa-bath me-2"></i> <?php echo $homestay['bathrooms']; ?> bathrooms
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <strong>Amenities:</strong><br>
                                    <?php foreach ($homestay_amenities as $amenity): ?>
                                        <span class="badge bg-secondary me-1 mb-1">
                                            <i class="<?php echo $amenity['icon']; ?> me-1"></i>
                                            <?php echo htmlspecialchars($amenity['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mb-3">
                                    <span class="badge bg-<?php 
                                        echo match($homestay['status']) {
                                            'available' => 'success',
                                            'booked' => 'warning',
                                            'maintenance' => 'danger'
                                        }; 
                                    ?>"><?php echo ucfirst($homestay['status']); ?></span>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-sm btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editHomestayModal<?php echo $homestay['homestay_id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteHomestay(<?php echo $homestay['homestay_id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Homestay Modal -->
                    <div class="modal fade" id="editHomestayModal<?php echo $homestay['homestay_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Homestay</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="admin_homestays.php" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update_homestay">
                                        <input type="hidden" name="homestay_id" value="<?php echo $homestay['homestay_id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($homestay['name']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($homestay['description']); ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($homestay['address']); ?></textarea>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Price per Night (RM)</label>
                                                <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $homestay['price_per_night']; ?>" required>
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Max Guests</label>
                                                <input type="number" name="max_guests" class="form-control" value="<?php echo $homestay['max_guests']; ?>" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Bedrooms</label>
                                                <input type="number" name="bedrooms" class="form-control" value="<?php echo $homestay['bedrooms']; ?>" required>
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Bathrooms</label>
                                                <input type="number" name="bathrooms" class="form-control" value="<?php echo $homestay['bathrooms']; ?>" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select" required>
                                                <option value="available" <?php echo $homestay['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                <option value="booked" <?php echo $homestay['status'] === 'booked' ? 'selected' : ''; ?>>Booked</option>
                                                <option value="maintenance" <?php echo $homestay['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Amenities</label>
                                            <div class="row">
                                                <?php 
                                                $homestay_amenity_ids = array_map(function($amenity) {
                                                    return $amenity['amenity_id'];
                                                }, $homestay_amenities);
                                                
                                                foreach ($amenities_list as $amenity): 
                                                ?>
                                                    <div class="col-md-4 mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="amenities[]" 
                                                                   value="<?php echo $amenity['amenity_id']; ?>"
                                                                   <?php echo in_array($amenity['amenity_id'], $homestay_amenity_ids) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label">
                                                                <i class="<?php echo $amenity['icon']; ?> me-1"></i>
                                                                <?php echo htmlspecialchars($amenity['name']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Update Homestay</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Homestay Modal -->
<div class="modal fade" id="addHomestayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Homestay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_homestays.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_homestay">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Price per Night (RM)</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Max Guests</label>
                            <input type="number" name="max_guests" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="booked">Booked</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Amenities</label>
                        <div class="row">
                            <?php foreach ($amenities_list as $amenity): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="<?php echo $amenity['amenity_id']; ?>">
                                        <label class="form-check-label">
                                            <i class="<?php echo $amenity['icon']; ?> me-1"></i>
                                            <?php echo htmlspecialchars($amenity['name']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Homestay</button>
                </div>
            </form>
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
                Are you sure you want to delete this homestay? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form action="admin_homestays.php" method="POST">
                    <input type="hidden" name="action" value="delete_homestay">
                    <input type="hidden" name="homestay_id" id="deleteHomestayId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteHomestay(homestayId) {
    document.getElementById('deleteHomestayId').value = homestayId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
</body>
</html>