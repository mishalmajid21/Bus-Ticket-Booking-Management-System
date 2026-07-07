<?php
// admin_login.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password =       $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(["success" => false, "message" => "Username and password are required."]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT admin_id, full_name, password FROM admin WHERE username = ?"
);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid username or password."]);
    $stmt->close();
    exit;
}

$stmt->bind_result($admin_id, $full_name, $hashed_password);
$stmt->fetch();

if (!password_verify($password, $hashed_password)) {
    echo json_encode(["success" => false, "message" => "Invalid username or password."]);
    $stmt->close();
    exit;
}

echo json_encode([
    "success"   => true,
    "message"   => "Login successful! Welcome, $full_name",
    "admin_id"  => $admin_id,
    "full_name" => $full_name
]);

$stmt->close();
$conn->close();
?>
