<?php
// get_history.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

$passenger_id = intval($_GET['passenger_id'] ?? 0);

if (!$passenger_id) {
    echo json_encode(["success" => false, "message" => "passenger_id is required."]);
    exit;
}

// Fetch all tickets for this passenger (one row per ticket)
$stmt = $conn->prepare("
    SELECT
        t.ticket_id,
        t.booking_date,
        t.travel_date,
        t.status,
        t.total_bill,
        tt.type_name    AS ticket_type,
        b.bus_no_plate  AS bus_number,
        b.route,
        p.payment_type,
        p.transaction_date
    FROM ticket t
    JOIN ticket_type tt ON t.type_id    = tt.type_id
    JOIN payment     p  ON t.payment_id = p.payment_id
    -- derive bus from the seats linked to this ticket
    JOIN ticket_seats ts ON t.ticket_id = ts.ticket_id
    JOIN seat         s  ON ts.seat_id  = s.seat_id
    JOIN bus          b  ON s.bus_id    = b.bus_id
    WHERE t.passenger_id = ?
    GROUP BY t.ticket_id
    ORDER BY t.booking_date DESC, t.ticket_id ASC
");
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// For each ticket, fetch its seat numbers separately
$bookings = [];
foreach ($tickets as $t) {
    $tid = (int)$t['ticket_id'];

    $seats_stmt = $conn->prepare("
        SELECT s.seat_number
        FROM ticket_seats ts
        JOIN seat s ON ts.seat_id = s.seat_id
        WHERE ts.ticket_id = ?
        ORDER BY s.seat_number
    ");
    $seats_stmt->bind_param("i", $tid);
    $seats_stmt->execute();
    $seats_result = $seats_stmt->get_result();
    $seats = [];
    while ($row = $seats_result->fetch_assoc()) {
        $seats[] = (int)$row['seat_number'];
    }
    $seats_stmt->close();

    $bookings[] = [
        "ticket_id"     => $tid,
        "busNumber"     => $t['bus_number'],
        "route"         => $t['route'],
        "date"          => $t['travel_date'],
        "booking_date"  => $t['booking_date'],
        "seats"         => $seats,
        "ticketType"    => $t['ticket_type'],
        "paymentMethod" => $t['payment_type'],
        "totalBill"     => (float)$t['total_bill'],
        "status"        => $t['status']
    ];
}

echo json_encode(["success" => true, "bookings" => $bookings]);

$conn->close();
?>
