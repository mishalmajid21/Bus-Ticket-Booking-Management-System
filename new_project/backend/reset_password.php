<?php
// reset_password.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$passenger_id = intval($_POST['passenger_id'] ?? 0);
$old_password =        $_POST['oldPassword']  ?? '';
$new_password =        $_POST['newPassword']  ?? '';
$confirm      =        $_POST['confirmPassword'] ?? '';

if (!$passenger_id || !$old_password || !$new_password || !$confirm) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

if ($new_password !== $confirm) {
    echo json_encode(["success" => false, "message" => "New password and confirm password do not match."]);
    exit;
}

// Get current hashed password
$stmt = $conn->prepare("SELECT password FROM passenger WHERE passenger_id = ?");
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$stmt->bind_result($hashed);
$stmt->fetch();
$stmt->close();

if (!$hashed) {
    echo json_encode(["success" => false, "message" => "User not found."]);
    exit;
}

if (!password_verify($old_password, $hashed)) {
    echo json_encode(["success" => false, "message" => "Old password is incorrect."]);
    exit;
}

$new_hashed = password_hash($new_password, PASSWORD_BCRYPT);

$update = $conn->prepare("UPDATE passenger SET password = ? WHERE passenger_id = ?");
$update->bind_param("si", $new_hashed, $passenger_id);

if ($update->execute()) {
    echo json_encode(["success" => true, "message" => "Password updated successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update password."]);
}

$update->close();
$conn->close();
?>
