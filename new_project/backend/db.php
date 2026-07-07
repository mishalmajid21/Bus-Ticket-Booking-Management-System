<?php
// db.php - Database connection

$host = "localhost";
$user = "root";
$pass = "";           // Default WAMP password is empty
$dbname = "new_bus_ticket_booking_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Connection failed: " . $conn->connect_error
    ]));
}

$conn->set_charset("utf8");
?>
