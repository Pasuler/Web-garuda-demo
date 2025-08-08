<?php
require_once 'includes/config.php';

// Check tickets
echo "=== TICKETS ===\n";
$tickets = $pdo->query('SELECT * FROM tickets LIMIT 3')->fetchAll();
foreach($tickets as $t) {
    echo "ID: {$t['id']} - {$t['flight_code']} ({$t['departure_city']} to {$t['arrival_city']})\n";
}

// Check bookings
echo "\n=== BOOKINGS ===\n";
$bookings = $pdo->query('SELECT id, id_ticket, passengers, seat_numbers FROM bookings LIMIT 3')->fetchAll();
foreach($bookings as $b) {
    echo "Booking ID: {$b['id']}, Ticket: {$b['id_ticket']}, Passengers: {$b['passengers']}, Seats: {$b['seat_numbers']}\n";
}
?>
