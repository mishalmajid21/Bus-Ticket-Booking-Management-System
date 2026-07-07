<?php
// admin_cancel_booking.php - admin can cancel ANY passenger's booking
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$ticket_id = intval($_POST['ticket_id'] ?? 0);

if (!$ticket_id) {
    echo json_encode(["success" => false, "message" => "ticket_id is required."]);
    exit;
}

$check = $conn->prepare("SELECT ticket_id FROM ticket WHERE ticket_id = ? AND status = 'Confirmed'");
$check->bind_param("i", $ticket_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Booking not found or already cancelled."]);
    $check->close();
    exit;
}
$check->close();

$upd     = $conn->prepare("UPDATE ticket               SET status = 'Cancelled' WHERE ticket_id = ?");
$upd_std = $conn->prepare("UPDATE standard_ticket       SET status = 'Cancelled' WHERE ticket_id = ?");
$upd_bus = $conn->prepare("UPDATE business_ticket       SET status = 'Cancelled' WHERE ticket_id = ?");
$upd_fc  = $conn->prepare("UPDATE first_class_ticket    SET status = 'Cancelled' WHERE ticket_id = ?");

$upd->bind_param("i", $ticket_id);

if ($upd->execute()) {
    $upd_std->bind_param("i", $ticket_id); $upd_std->execute(); $upd_std->close();
    $upd_bus->bind_param("i", $ticket_id); $upd_bus->execute(); $upd_bus->close();
    $upd_fc->bind_param("i",  $ticket_id); $upd_fc->execute();  $upd_fc->close();
    echo json_encode(["success" => true, "message" => "Booking Cancelled by Admin"]);
} else {
    echo json_encode(["success" => false, "message" => "Cancellation failed: " . $conn->error]);
}

$upd->close();
$conn->close();
?>
