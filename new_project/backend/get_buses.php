<?php
// get_buses.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

// Get buses
$buses_result = $conn->query(
    "SELECT bus_id, bus_no_plate, route, total_seats FROM bus ORDER BY bus_id"
);
if (!$buses_result) {
    echo json_encode(["success" => false, "message" => $conn->error]);
    exit;
}

// Get ticket type prices from lookup table
$types_result = $conn->query(
    "SELECT type_id, type_name, price FROM ticket_type ORDER BY type_id"
);
$prices   = [];
$features = [
    "Standard"    => "Non AC",
    "Business"    => "AC",
    "First Class" => "AC + WIFI"
];
while ($row = $types_result->fetch_assoc()) {
    $prices[$row['type_name']] = (float)$row['price'];
}

$buses = [];
while ($row = $buses_result->fetch_assoc()) {
    $row['bus_id']      = (int)$row['bus_id'];
    $row['total_seats'] = (int)$row['total_seats'];
    $row['prices']      = $prices;
    $row['features']    = $features;
    $buses[] = $row;
}

echo json_encode(["success" => true, "buses" => $buses]);

$conn->close();
?>
