<?php
// get_booked_seats.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

$bus_id      = intval($_GET['bus_id']      ?? 0);
$travel_date = trim($_GET['travel_date']   ?? '');

if (!$bus_id || !$travel_date) {
    echo json_encode(["success" => false, "message" => "bus_id and travel_date are required."]);
    exit;
}

// Now joins through ticket_seats bridge table
$stmt = $conn->prepare("
    SELECT s.seat_number
    FROM ticket t
    JOIN ticket_seats ts ON t.ticket_id = ts.ticket_id
    JOIN seat         s  ON ts.seat_id  = s.seat_id
    WHERE s.bus_id      = ?
      AND t.travel_date = ?
      AND t.status      = 'Confirmed'
");
$stmt->bind_param("is", $bus_id, $travel_date);
$stmt->execute();
$result = $stmt->get_result();

$booked = [];
while ($row = $result->fetch_assoc()) {
    $booked[] = (int)$row['seat_number'];
}

echo json_encode(["success" => true, "booked_seats" => $booked]);

$stmt->close();
$conn->close();
?>
