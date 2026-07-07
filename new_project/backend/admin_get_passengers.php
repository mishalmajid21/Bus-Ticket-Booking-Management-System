<?php
// admin_get_passengers.php - list of registered passengers for admin view
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

$result = $conn->query("
    SELECT p.passenger_id, p.first_name, p.last_name, p.email,
           GROUP_CONCAT(DISTINCT pp.phone_no SEPARATOR ', ') AS phones,
           COUNT(DISTINCT t.ticket_id) AS total_bookings
    FROM passenger p
    LEFT JOIN passenger_phone pp ON p.passenger_id = pp.passenger_id
    LEFT JOIN ticket t           ON p.passenger_id = t.passenger_id
    GROUP BY p.passenger_id
    ORDER BY p.passenger_id DESC
");

if (!$result) {
    echo json_encode(["success" => false, "message" => $conn->error]);
    exit;
}

$passengers = [];
while ($row = $result->fetch_assoc()) {
    $passengers[] = [
        "passenger_id"   => (int)$row['passenger_id'],
        "name"           => trim($row['first_name'] . ' ' . $row['last_name']),
        "email"          => $row['email'],
        "phones"         => $row['phones'] ?? '',
        "total_bookings" => (int)$row['total_bookings']
    ];
}

echo json_encode(["success" => true, "passengers" => $passengers]);

$conn->close();
?>
