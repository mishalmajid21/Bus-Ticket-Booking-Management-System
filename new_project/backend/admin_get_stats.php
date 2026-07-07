<?php
// admin_get_stats.php - quick numbers for the admin dashboard cards
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

$stats = [
    "total_passengers"    => 0,
    "total_buses"         => 0,
    "total_bookings"      => 0,
    "confirmed_bookings"  => 0,
    "cancelled_bookings"  => 0,
    "total_revenue"       => 0.0,
    "bookings_today"      => 0
];

if ($row = $conn->query("SELECT COUNT(*) AS c FROM passenger")->fetch_assoc()) {
    $stats["total_passengers"] = (int)$row['c'];
}

if ($row = $conn->query("SELECT COUNT(*) AS c FROM bus")->fetch_assoc()) {
    $stats["total_buses"] = (int)$row['c'];
}

if ($row = $conn->query("SELECT COUNT(*) AS c FROM ticket")->fetch_assoc()) {
    $stats["total_bookings"] = (int)$row['c'];
}

if ($row = $conn->query("SELECT COUNT(*) AS c FROM ticket WHERE status = 'Confirmed'")->fetch_assoc()) {
    $stats["confirmed_bookings"] = (int)$row['c'];
}

if ($row = $conn->query("SELECT COUNT(*) AS c FROM ticket WHERE status = 'Cancelled'")->fetch_assoc()) {
    $stats["cancelled_bookings"] = (int)$row['c'];
}

if ($row = $conn->query("SELECT COALESCE(SUM(total_bill),0) AS s FROM ticket WHERE status = 'Confirmed'")->fetch_assoc()) {
    $stats["total_revenue"] = (float)$row['s'];
}

if ($row = $conn->query("SELECT COUNT(*) AS c FROM ticket WHERE booking_date = CURDATE()")->fetch_assoc()) {
    $stats["bookings_today"] = (int)$row['c'];
}

echo json_encode(["success" => true, "stats" => $stats]);

$conn->close();
?>
