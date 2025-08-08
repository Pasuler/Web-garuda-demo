<?php
require_once 'includes/config.php';
echo "Checking existing bookings and payments:\n";
$stmt = $pdo->query('SELECT b.id, b.id_user, b.total_amount, p.payment_status, p.receipt_image FROM bookings b LEFT JOIN payments p ON b.id = p.id_booking ORDER BY b.id');
while ($row = $stmt->fetch()) {
    echo "Booking ID: {$row['id']} | User: {$row['id_user']} | Amount: {$row['total_amount']} | Status: " . ($row['payment_status'] ?: 'NO_PAYMENT_RECORD') . " | Receipt: " . ($row['receipt_image'] ?: 'NONE') . "\n";
}
?>
