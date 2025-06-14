<?php
include 'include/config.php'; // or wherever you handle DB connection

if (isset($_GET['homestay_id'])) {
    $homestay_id = intval($_GET['homestay_id']);

    $query = "
        SELECT a.amenity_id, a.name, a.icon, a.price
        FROM amenities a
        INNER JOIN homestay_amenities ha ON a.amenity_id = ha.amenity_id
        WHERE ha.homestay_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $homestay_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $amenities = [];
    while ($row = $result->fetch_assoc()) {
        $amenities[] = $row;
    }

    echo json_encode($amenities);
}
?>