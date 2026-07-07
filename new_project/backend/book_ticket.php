<?php
// book_ticket.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// Suppress HTML errors - return JSON always
ini_set('display_errors', 0);
error_reporting(0);

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit;
}

$passenger_id = intval($_POST['passenger_id'] ?? 0);
$bus_id       = intval($_POST['bus_id']       ?? 0);
$travel_date  = trim($_POST['travel_date']    ?? '');
$seats        = json_decode($_POST['seats']   ?? '[]', true);
$ticket_type  = trim($_POST['ticket_type']    ?? '');
$payment_type = trim($_POST['payment_type']   ?? '');

if (!$passenger_id || !$bus_id || !$travel_date || empty($seats) || !$ticket_type || !$payment_type) {
    echo json_encode(["success" => false, "message" => "All booking fields are required."]);
    exit;
}

$valid_types   = ['Standard', 'Business', 'First Class'];
$valid_payment = ['Cash', 'Credit Card', 'JazzCash', 'EasyPaisa'];

if (!in_array($ticket_type, $valid_types)) {
    echo json_encode(["success" => false, "message" => "Invalid ticket type."]);
    exit;
}
if (!in_array($payment_type, $valid_payment)) {
    echo json_encode(["success" => false, "message" => "Invalid payment type."]);
    exit;
}

$booking_date = date('Y-m-d');

$conn->begin_transaction();

try {
    // 1. Get type_id and price from ticket_type lookup table
    $type_stmt = $conn->prepare(
        "SELECT type_id, price FROM ticket_type WHERE type_name = ?"
    );
    $type_stmt->bind_param("s", $ticket_type);
    $type_stmt->execute();
    $type_result = $type_stmt->get_result()->fetch_assoc();
    $type_stmt->close();

    if (!$type_result) {
        throw new Exception("Ticket type not found in lookup table.");
    }

    $type_id        = (int)$type_result['type_id'];
    $price_per_seat = (float)$type_result['price'];
    $seat_count     = count($seats);
    $total_bill     = $seat_count * $price_per_seat;

    // 2. Insert payment record
    $pay_stmt = $conn->prepare(
        "INSERT INTO payment (transaction_date, payment_type) VALUES (?, ?)"
    );
    $pay_stmt->bind_param("ss", $booking_date, $payment_type);
    $pay_stmt->execute();
    $payment_id = $pay_stmt->insert_id;
    $pay_stmt->close();

    // 3. Insert payment subtype
    if ($payment_type === 'Cash') {
        $currency = 'PKR';
        $cs = $conn->prepare("INSERT INTO cash_payment (currency_type, payment_id) VALUES (?, ?)");
        $cs->bind_param("si", $currency, $payment_id);
        $cs->execute();
        $cs->close();
    } elseif ($payment_type === 'Credit Card') {
        $cs = $conn->prepare("INSERT INTO card_payment (payment_id) VALUES (?)");
        $cs->bind_param("i", $payment_id);
        $cs->execute();
        $cs->close();
    } else {
        $account = $payment_type . '_' . $passenger_id . '_' . time();
        $cs = $conn->prepare("INSERT INTO online_payment (account_no, payment_id) VALUES (?, ?)");
        $cs->bind_param("si", $account, $payment_id);
        $cs->execute();
        $cs->close();
    }

    // 4. Insert ONE ticket row for the whole booking
    $tkt = $conn->prepare("
        INSERT INTO ticket
            (booking_date, travel_date, status, passenger_id, payment_id, type_id, total_bill)
        VALUES (?, ?, 'Confirmed', ?, ?, ?, ?)
    ");
    $tkt->bind_param("ssiiid", $booking_date, $travel_date, $passenger_id, $payment_id, $type_id, $total_bill);
    $tkt->execute();
    $ticket_id = $tkt->insert_id;
    $tkt->close();

    // 5. Insert each seat into ticket_seats bridge table
    foreach ($seats as $seat_number) {
        $seat_number = intval($seat_number);

        // Get seat_id
        $seat_stmt = $conn->prepare(
            "SELECT seat_id FROM seat WHERE bus_id = ? AND seat_number = ?"
        );
        $seat_stmt->bind_param("ii", $bus_id, $seat_number);
        $seat_stmt->execute();
        $seat_row = $seat_stmt->get_result()->fetch_assoc();
        $seat_stmt->close();

        if (!$seat_row) {
            throw new Exception("Seat $seat_number not found for bus $bus_id.");
        }
        $seat_id = (int)$seat_row['seat_id'];

        // Check seat not already booked for this date
        $conflict = $conn->prepare("
            SELECT t.ticket_id
            FROM ticket t
            JOIN ticket_seats ts ON t.ticket_id = ts.ticket_id
            WHERE ts.seat_id    = ?
              AND t.travel_date = ?
              AND t.status      = 'Confirmed'
        ");
        $conflict->bind_param("is", $seat_id, $travel_date);
        $conflict->execute();
        $conflict->store_result();
        if ($conflict->num_rows > 0) {
            $conflict->close();
            throw new Exception("Seat $seat_number is already booked for this date.");
        }
        $conflict->close();

        // Link seat to ticket
        $ts = $conn->prepare("INSERT INTO ticket_seats (ticket_id, seat_id) VALUES (?, ?)");
        $ts->bind_param("ii", $ticket_id, $seat_id);
        $ts->execute();
        $ts->close();
    }

    // 6. Insert into subclass table with total_bill
    if ($ticket_type === 'Standard') {
        $sub = $conn->prepare("
            INSERT INTO standard_ticket
                (ticket_id, passenger_id, booking_date, travel_date, status, non_ac, total_bill)
            VALUES (?, ?, ?, ?, 'Confirmed', 1, ?)
        ");
        $sub->bind_param("iissd", $ticket_id, $passenger_id, $booking_date, $travel_date, $total_bill);
        $sub->execute();
        $sub->close();
    } elseif ($ticket_type === 'Business') {
        $sub = $conn->prepare("
            INSERT INTO business_ticket
                (ticket_id, passenger_id, booking_date, travel_date, status, ac, total_bill)
            VALUES (?, ?, ?, ?, 'Confirmed', 1, ?)
        ");
        $sub->bind_param("iissd", $ticket_id, $passenger_id, $booking_date, $travel_date, $total_bill);
        $sub->execute();
        $sub->close();
    } elseif ($ticket_type === 'First Class') {
        $sub = $conn->prepare("
            INSERT INTO first_class_ticket
                (ticket_id, passenger_id, booking_date, travel_date, status, wifi, total_bill)
            VALUES (?, ?, ?, ?, 'Confirmed', 1, ?)
        ");
        $sub->bind_param("iissd", $ticket_id, $passenger_id, $booking_date, $travel_date, $total_bill);
        $sub->execute();
        $sub->close();
    }

    $conn->commit();

    echo json_encode([
        "success"    => true,
        "message"    => "Booking Successful!",
        "ticket_id"  => $ticket_id,
        "payment_id" => $payment_id,
        "total_bill" => $total_bill
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
