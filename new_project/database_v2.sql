-- ============================================================
-- Bus Ticket Booking Management System
-- UPDATED SCHEMA v2
-- Changes:
--   1. ticket_type lookup table (type_id, type_name, price)
--   2. ticket table: one row per booking, seat_id removed,
--      ticket_type is now FK to ticket_type table
--   3. ticket_seats table: links ticket_id → seat_id (many seats)
--   4. standard_ticket, business_ticket, first_class_ticket:
--      added total_bill column
-- ============================================================

CREATE DATABASE IF NOT EXISTS bus_ticket_db;
USE bus_ticket_db;

-- ─── PASSENGER ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS passenger (
    passenger_id   INT AUTO_INCREMENT PRIMARY KEY,
    first_name     VARCHAR(100) NOT NULL,
    last_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL
);

-- ─── PASSENGER PHONE (multi-valued attribute) ─────────────────
CREATE TABLE IF NOT EXISTS passenger_phone (
    passenger_id   INT NOT NULL,
    phone_no       VARCHAR(20) NOT NULL,
    PRIMARY KEY (passenger_id, phone_no),
    FOREIGN KEY (passenger_id) REFERENCES passenger(passenger_id) ON DELETE CASCADE
);

-- ─── BUS ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bus (
    bus_id         INT AUTO_INCREMENT PRIMARY KEY,
    bus_no_plate   VARCHAR(20)  NOT NULL UNIQUE,
    route          VARCHAR(200) NOT NULL,
    total_seats    INT          NOT NULL DEFAULT 24
);

-- ─── SEAT ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS seat (
    seat_id        INT AUTO_INCREMENT PRIMARY KEY,
    bus_id         INT NOT NULL,
    seat_number    INT NOT NULL,
    FOREIGN KEY (bus_id) REFERENCES bus(bus_id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat (bus_id, seat_number)
);

-- ─── TICKET TYPE (lookup table) ───────────────────────────────
-- Stores the three ticket classes and their prices.
-- Using this as a FK in ticket avoids hardcoding prices in PHP.
CREATE TABLE IF NOT EXISTS ticket_type (
    type_id    INT AUTO_INCREMENT PRIMARY KEY,
    type_name  ENUM('Standard', 'Business', 'First Class') NOT NULL UNIQUE,
    price      DECIMAL(10,2) NOT NULL
);

-- Seed ticket types
INSERT INTO ticket_type (type_name, price) VALUES
('Standard',    3000.00),
('Business',    5000.00),
('First Class', 7000.00);

-- ─── PAYMENT ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payment (
    payment_id       INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    payment_type     ENUM('Cash', 'Credit Card', 'JazzCash', 'EasyPaisa') NOT NULL
);

-- ─── CASH PAYMENT ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cash_payment (
    cash_id        INT AUTO_INCREMENT PRIMARY KEY,
    currency_type  VARCHAR(10) NOT NULL DEFAULT 'PKR',
    payment_id     INT NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payment(payment_id) ON DELETE CASCADE
);

-- ─── CARD PAYMENT ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS card_payment (
    card_id    INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payment(payment_id) ON DELETE CASCADE
);

-- ─── ONLINE PAYMENT ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS online_payment (
    account_no VARCHAR(50) NOT NULL PRIMARY KEY,
    payment_id INT NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payment(payment_id) ON DELETE CASCADE
);

-- ─── TICKET (one row per booking, NOT per seat) ───────────────
-- seat_id removed; seats linked via ticket_seats table below.
-- type_id is a FK to ticket_type lookup table.
CREATE TABLE IF NOT EXISTS ticket (
    ticket_id      INT AUTO_INCREMENT PRIMARY KEY,
    booking_date   DATE NOT NULL,
    travel_date    DATE NOT NULL,
    status         ENUM('Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    passenger_id   INT NOT NULL,
    payment_id     INT,
    type_id        INT NOT NULL,
    total_bill     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (passenger_id) REFERENCES passenger(passenger_id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id)   REFERENCES payment(payment_id)     ON DELETE SET NULL,
    FOREIGN KEY (type_id)      REFERENCES ticket_type(type_id)
);

-- ─── TICKET_SEATS (bridge: one ticket → many seats) ───────────
-- This is the junction table that replaces seat_id in ticket.
-- Each row = one seat belonging to one booking.
CREATE TABLE IF NOT EXISTS ticket_seats (
    ticket_id  INT NOT NULL,
    seat_id    INT NOT NULL,
    PRIMARY KEY (ticket_id, seat_id),
    FOREIGN KEY (ticket_id) REFERENCES ticket(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id)   REFERENCES seat(seat_id)     ON DELETE CASCADE
);

-- ─── STANDARD TICKET (subclass) ───────────────────────────────
CREATE TABLE IF NOT EXISTS standard_ticket (
    ticket_id    INT PRIMARY KEY,
    passenger_id INT NOT NULL,
    booking_date DATE NOT NULL,
    travel_date  DATE NOT NULL,
    status       ENUM('Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    non_ac       TINYINT(1) NOT NULL DEFAULT 1,
    total_bill   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (ticket_id)    REFERENCES ticket(ticket_id)       ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES passenger(passenger_id)
);

-- ─── BUSINESS TICKET (subclass) ───────────────────────────────
CREATE TABLE IF NOT EXISTS business_ticket (
    ticket_id    INT PRIMARY KEY,
    passenger_id INT NOT NULL,
    booking_date DATE NOT NULL,
    travel_date  DATE NOT NULL,
    status       ENUM('Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    ac           TINYINT(1) NOT NULL DEFAULT 1,
    total_bill   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (ticket_id)    REFERENCES ticket(ticket_id)       ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES passenger(passenger_id)
);

-- ─── FIRST CLASS TICKET (subclass) ────────────────────────────
CREATE TABLE IF NOT EXISTS first_class_ticket (
    ticket_id    INT PRIMARY KEY,
    passenger_id INT NOT NULL,
    booking_date DATE NOT NULL,
    travel_date  DATE NOT NULL,
    status       ENUM('Confirmed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    wifi         TINYINT(1) NOT NULL DEFAULT 1,
    total_bill   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (ticket_id)    REFERENCES ticket(ticket_id)       ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES passenger(passenger_id)
);

-- ─── ADMIN ────────────────────────────────────────────────────
-- Separate table for admin/staff accounts, kept apart from
-- passenger so admin login can never collide with a customer.
CREATE TABLE IF NOT EXISTS admin (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(150) NOT NULL DEFAULT 'Administrator',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed a default admin account
-- username: admin | password: admin123
-- (password is bcrypt-hashed, verified with PHP's password_verify())
INSERT INTO admin (username, password, full_name) VALUES
('admin', '$2b$10$pAdjnWwBpO3Lc5PKzCRcD.CZx8RubOVW.cB1A4ub8EXHON0EKFNxO', 'System Admin');

-- ============================================================
-- SEED DATA — Buses & Seats
-- ============================================================

INSERT INTO bus (bus_no_plate, route, total_seats) VALUES
('PK101', 'Karachi → Lahore',       24),
('PK202', 'Islamabad → Multan',     24),
('PK303', 'Peshawar → Faisalabad',  24);

-- Seats for Bus 1
INSERT INTO seat (bus_id, seat_number)
SELECT 1, n FROM (
    SELECT 1 n UNION SELECT 2  UNION SELECT 3  UNION SELECT 4  UNION SELECT 5  UNION
    SELECT 6   UNION SELECT 7  UNION SELECT 8  UNION SELECT 9  UNION SELECT 10 UNION
    SELECT 11  UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
    SELECT 16  UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
    SELECT 21  UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
) nums;

-- Seats for Bus 2
INSERT INTO seat (bus_id, seat_number)
SELECT 2, n FROM (
    SELECT 1 n UNION SELECT 2  UNION SELECT 3  UNION SELECT 4  UNION SELECT 5  UNION
    SELECT 6   UNION SELECT 7  UNION SELECT 8  UNION SELECT 9  UNION SELECT 10 UNION
    SELECT 11  UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
    SELECT 16  UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
    SELECT 21  UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
) nums;

-- Seats for Bus 3
INSERT INTO seat (bus_id, seat_number)
SELECT 3, n FROM (
    SELECT 1 n UNION SELECT 2  UNION SELECT 3  UNION SELECT 4  UNION SELECT 5  UNION
    SELECT 6   UNION SELECT 7  UNION SELECT 8  UNION SELECT 9  UNION SELECT 10 UNION
    SELECT 11  UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION
    SELECT 16  UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20 UNION
    SELECT 21  UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
) nums;
