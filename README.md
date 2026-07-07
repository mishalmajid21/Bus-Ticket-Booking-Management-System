# Bus Ticket Booking Management System

This is a mini project I made for my Database Systems course. It's a simple bus ticket booking website where users can sign up, log in, pick a bus and seats, book a ticket, and view/cancel their bookings. There's also an admin side where the admin can see all bookings, manage buses, and view registered passengers.

## What it does

**For passengers:**
- Sign up / login
- Reset password
- Pick a bus, journey date, ticket type (Standard / Business / First Class), and payment method
- See a seat map (booked seats are shown in red, you pick your own seat)
- Book a ticket
- View booking history
- Print a bill for any booking
- Cancel a confirmed booking

**For admin:**
- Separate admin login
- Dashboard showing total bookings, revenue, passengers, buses etc.
- View all bookings (with filter for confirmed/cancelled) and cancel any of them
- Add new buses / delete buses
- View all registered passengers

## Tech used

- HTML, CSS, JavaScript (plain, no framework)
- PHP (backend, using mysqli)
- MySQL (database)
- Built and tested using WAMP Server

## How to run it

1. Install [WAMP Server](https://www.wampserver.com/) (or XAMPP, just change `db.php` accordingly).
2. Put this whole project folder inside `www` (for WAMP) or `htdocs` (for XAMPP).
3. Start WAMP, make sure it shows green (all services running).
4. Open phpMyAdmin (`localhost/phpmyadmin`) and import `database_v2.sql` — this creates the database and all tables, plus some sample buses and one admin account.
5. Open `http://localhost/your-folder-name/index.html` in your browser.

## Default Login

**Admin:**
- Username: `admin`
- Password: `admin123`

**Passenger:** you make your own account through Sign Up.

## Database structure (short version)

- `passenger` — user accounts
- `passenger_phone` — phone numbers (a passenger can have more than one)
- `bus` — bus info (plate number, route, total seats)
- `seat` — individual seats per bus
- `ticket_type` — the 3 ticket types and their prices (Standard, Business, First Class)
- `ticket` — one row per booking
- `ticket_seats` — links a ticket to the seats booked in it (since one booking can have multiple seats)
- `payment`, `cash_payment`, `card_payment`, `online_payment` — payment details based on method used
- `standard_ticket`, `business_ticket`, `first_class_ticket` — extra info specific to each ticket type
- `admin` — admin login accounts

## Notes / limitations

- This was made for a university project, so the "login system" is pretty basic — it just uses `localStorage` in the browser to remember who's logged in, it's not using real sessions or tokens. Not meant for real-world/production use.
- Passwords are hashed using PHP's `password_hash()` so at least they're not stored in plain text.
- Seat layout is fixed at a simple 2-2 pattern with an aisle, not fully dynamic.
- No email verification or forgot-password-via-email flow — the "reset password" page just asks for your old password.

## Folder structure

```
/
├── index.html
├── login.html
├── signup.html
├── reset.html
├── DASHBOARD.html
├── history.html
├── admin_login.html
├── ADMIN_DASHBOARD.html
├── style.css
├── database_v2.sql
└── backend/
    ├── db.php
    ├── login.php
    ├── signup.php
    ├── reset_password.php
    ├── get_buses.php
    ├── get_booked_seats.php
    ├── book_ticket.php
    ├── get_history.php
    ├── cancel_booking.php
    ├── admin_login.php
    ├── admin_get_stats.php
    ├── admin_get_bookings.php
    ├── admin_cancel_booking.php
    ├── admin_get_buses.php
    ├── admin_add_bus.php
    ├── admin_delete_bus.php
    └── admin_get_passengers.php
```
