<?php
// admin_get_buses.php - bus list for the admin "Manage Buses" panel
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

$result = $conn->query("
    SELECT bus_id, bus_no_plate, route, total_seats
    FROM bus
    ORDER BY bus_id
");

if (!$result) {
    echo json_encode(["success" => false, "message" => $conn->error]);
    exit;
}

$buses = [];
while ($row = $result->fetch_assoc()) {
    $bus_id = (int)$row['bus_id'];

    // How many seats on this bus are currently booked (any confirmed ticket, any date)
    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ts.seat_id) AS c
        FROM ticket_seats ts
        JOIN ticket t ON ts.ticket_id = t.ticket_id
        JOIN seat   s ON ts.seat_id   = s.seat_id
        WHERE s.bus_id = ? AND t.status = 'Confirmed'
    ");
    $count_stmt->bind_param("i", $bus_id);
    $count_stmt->execute();
    $count_row = $count_stmt->get_result()->fetch_assoc();
    $count_stmt->close();

    $buses[] = [
        "bus_id"       => $bus_id,
        "bus_no_plate" => $row['bus_no_plate'],
        "route"        => $row['route'],
        "total_seats"  => (int)$row['total_seats'],
        "active_bookings" => (int)$count_row['c']
    ];
}

echo json_encode(["success" => true, "buses" => $buses]);

$conn->close();
?>
