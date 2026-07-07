<?php
// admin_get_bookings.php - every booking in the system, with passenger info
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

// Optional filters
$status = trim($_GET['status'] ?? ''); // 'Confirmed' | 'Cancelled' | '' (all)

$sql = "
    SELECT
        t.ticket_id,
        t.booking_date,
        t.travel_date,
        t.status,
        t.total_bill,
        tt.type_name   AS ticket_type,
        p.passenger_id,
        CONCAT(p.first_name, ' ', p.last_name) AS passenger_name,
        p.email        AS passenger_email,
        pay.payment_type
    FROM ticket t
    JOIN ticket_type tt ON t.type_id    = tt.type_id
    JOIN passenger    p ON t.passenger_id = p.passenger_id
    LEFT JOIN payment pay ON t.payment_id = pay.payment_id
";

if ($status === 'Confirmed' || $status === 'Cancelled') {
    $sql .= " WHERE t.status = '" . $conn->real_escape_string($status) . "' ";
}

$sql .= " ORDER BY t.booking_date DESC, t.ticket_id DESC";

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(["success" => false, "message" => $conn->error]);
    exit;
}

$tickets = $result->fetch_all(MYSQLI_ASSOC);

// Attach bus + seat info per ticket
$bookings = [];
foreach ($tickets as $t) {
    $tid = (int)$t['ticket_id'];

    $seat_stmt = $conn->prepare("
        SELECT s.seat_number, b.bus_no_plate, b.route
        FROM ticket_seats ts
        JOIN seat s ON ts.seat_id = s.seat_id
        JOIN bus  b ON s.bus_id   = b.bus_id
        WHERE ts.ticket_id = ?
        ORDER BY s.seat_number
    ");
    $seat_stmt->bind_param("i", $tid);
    $seat_stmt->execute();
    $seat_res = $seat_stmt->get_result();

    $seats   = [];
    $bus_no  = null;
    $route   = null;
    while ($row = $seat_res->fetch_assoc()) {
        $seats[] = (int)$row['seat_number'];
        $bus_no  = $row['bus_no_plate'];
        $route   = $row['route'];
    }
    $seat_stmt->close();

    $bookings[] = [
        "ticket_id"       => $tid,
        "passenger_id"    => (int)$t['passenger_id'],
        "passenger_name"  => $t['passenger_name'],
        "passenger_email" => $t['passenger_email'],
        "busNumber"       => $bus_no,
        "route"           => $route,
        "seats"           => $seats,
        "date"            => $t['travel_date'],
        "booking_date"    => $t['booking_date'],
        "ticketType"      => $t['ticket_type'],
        "paymentMethod"   => $t['payment_type'],
        "totalBill"       => (float)$t['total_bill'],
        "status"          => $t['status']
    ];
}

echo json_encode(["success" => true, "bookings" => $bookings]);

$conn->close();
?>
