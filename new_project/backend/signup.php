<?php
// signup.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$name     = trim($_POST['name']     ?? '');
$email    = trim($_POST['email']    ?? '');
$phone    = trim($_POST['phone']    ?? '');
$password =       $_POST['password'] ?? '';

if (!$name || !$email || !$phone || !$password) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

// Split name into first and last
$parts      = explode(' ', $name, 2);
$first_name = $parts[0];
$last_name  = $parts[1] ?? '';

// Check duplicate email
$check = $conn->prepare("SELECT passenger_id FROM passenger WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already registered."]);
    $check->close();
    exit;
}
$check->close();

$hashed = password_hash($password, PASSWORD_BCRYPT);

$conn->begin_transaction();

try {
    // Insert into passenger table
    $stmt = $conn->prepare(
        "INSERT INTO passenger (first_name, last_name, email, password) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed);
    $stmt->execute();
    $passenger_id = $stmt->insert_id;
    $stmt->close();

    // Insert into passenger_phone table
    $phone_stmt = $conn->prepare(
        "INSERT INTO passenger_phone (passenger_id, phone_no) VALUES (?, ?)"
    );
    $phone_stmt->bind_param("is", $passenger_id, $phone);
    $phone_stmt->execute();
    $phone_stmt->close();

    $conn->commit();

    echo json_encode([
        "success"      => true,
        "message"      => "Registration successful!",
        "passenger_id" => $passenger_id,
        "name"         => $name
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Registration failed: " . $e->getMessage()]);
}

$conn->close();
?>
