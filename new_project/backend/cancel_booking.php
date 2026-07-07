<?php
// cancel_booking.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$ticket_id    = intval($_POST['ticket_id']    ?? 0);
$passenger_id = intval($_POST['passenger_id'] ?? 0);

if (!$ticket_id || !$passenger_id) {
    echo json_encode(["success" => false, "message" => "ticket_id and passenger_id are required."]);
    exit;
}

// Verify this ticket belongs to this passenger and is Confirmed
$check = $conn->prepare("
    SELECT ticket_id FROM ticket
    WHERE ticket_id    = ?
      AND passenger_id = ?
      AND status       = 'Confirmed'
");
$check->bind_param("ii", $ticket_id, $passenger_id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Booking not found or already cancelled."]);
    $check->close();
    exit;
}
$check->close();

// Cancel in main ticket table (subclass tables mirror via FK)
$upd = $conn->prepare("UPDATE ticket SET status = 'Cancelled' WHERE ticket_id = ?");
$upd->bind_param("i", $ticket_id);

// Also update the subclass table status
$upd2_std = $conn->prepare("UPDATE standard_ticket    SET status = 'Cancelled' WHERE ticket_id = ?");
$upd2_bus = $conn->prepare("UPDATE business_ticket    SET status = 'Cancelled' WHERE ticket_id = ?");
$upd2_fc  = $conn->prepare("UPDATE first_class_ticket SET status = 'Cancelled' WHERE ticket_id = ?");

if ($upd->execute()) {
    $upd2_std->bind_param("i", $ticket_id); $upd2_std->execute(); $upd2_std->close();
    $upd2_bus->bind_param("i", $ticket_id); $upd2_bus->execute(); $upd2_bus->close();
    $upd2_fc->bind_param("i",  $ticket_id); $upd2_fc->execute();  $upd2_fc->close();
    echo json_encode(["success" => true, "message" => "Booking Cancelled"]);
} else {
    echo json_encode(["success" => false, "message" => "Cancellation failed: " . $conn->error]);
}

$upd->close();
$conn->close();
?>
