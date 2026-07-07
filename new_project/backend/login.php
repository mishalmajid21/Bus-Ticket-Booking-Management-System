<?php
// login.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password =       $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT passenger_id, first_name, last_name, password FROM passenger WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    $stmt->close();
    exit;
}

$stmt->bind_result($passenger_id, $first_name, $last_name, $hashed_password);
$stmt->fetch();

if (!password_verify($password, $hashed_password)) {
    echo json_encode(["success" => false, "message" => "Invalid email or password."]);
    $stmt->close();
    exit;
}

$full_name = trim("$first_name $last_name");

echo json_encode([
    "success"      => true,
    "message"      => "Login successful! Welcome, $full_name",
    "passenger_id" => $passenger_id,
    "name"         => $full_name
]);

$stmt->close();
$conn->close();
?>
