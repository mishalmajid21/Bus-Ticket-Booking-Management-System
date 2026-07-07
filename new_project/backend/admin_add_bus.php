<?php
// admin_add_bus.php - add a new bus + auto-generate its seats
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$bus_no_plate = trim($_POST['bus_no_plate'] ?? '');
$route        = trim($_POST['route']        ?? '');
$total_seats  = intval($_POST['total_seats'] ?? 24);

if (!$bus_no_plate || !$route) {
    echo json_encode(["success" => false, "message" => "Bus number plate and route are required."]);
    exit;
}

if ($total_seats < 1 || $total_seats > 100) {
    echo json_encode(["success" => false, "message" => "Total seats must be between 1 and 100."]);
    exit;
}

$check = $conn->prepare("SELECT bus_id FROM bus WHERE bus_no_plate = ?");
$check->bind_param("s", $bus_no_plate);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "A bus with this number plate already exists."]);
    $check->close();
    exit;
}
$check->close();

$conn->begin_transaction();

try {
    $stmt = $conn->prepare(
        "INSERT INTO bus (bus_no_plate, route, total_seats) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("ssi", $bus_no_plate, $route, $total_seats);
    $stmt->execute();
    $bus_id = $stmt->insert_id;
    $stmt->close();

    $seat_stmt = $conn->prepare("INSERT INTO seat (bus_id, seat_number) VALUES (?, ?)");
    for ($i = 1; $i <= $total_seats; $i++) {
        $seat_stmt->bind_param("ii", $bus_id, $i);
        $seat_stmt->execute();
    }
    $seat_stmt->close();

    $conn->commit();

    echo json_encode([
        "success" => true,
        "message" => "Bus added successfully!",
        "bus_id"  => $bus_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Failed to add bus: " . $e->getMessage()]);
}

$conn->close();
?>
