<?php
// admin_delete_bus.php - remove a bus (blocked if it has active bookings)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$bus_id = intval($_POST['bus_id'] ?? 0);

if (!$bus_id) {
    echo json_encode(["success" => false, "message" => "bus_id is required."]);
    exit;
}

// Refuse to delete a bus that still has confirmed bookings on it
$check = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM ticket_seats ts
    JOIN ticket t ON ts.ticket_id = t.ticket_id
    JOIN seat   s ON ts.seat_id   = s.seat_id
    WHERE s.bus_id = ? AND t.status = 'Confirmed'
");
$check->bind_param("i", $bus_id);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if ((int)$row['c'] > 0) {
    echo json_encode(["success" => false, "message" => "Cannot delete: this bus has active confirmed bookings."]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM bus WHERE bus_id = ?");
$stmt->bind_param("i", $bus_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Bus deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Bus not found."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Failed to delete bus: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
