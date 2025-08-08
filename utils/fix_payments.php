<?php
require_once 'includes/config.php';

echo "Creating missing payment records...\n";

// Get all bookings without payment records
$stmt = $pdo->query("
    SELECT b.id, b.total_amount 
    FROM bookings b 
    LEFT JOIN payments p ON b.id = p.id_booking 
    WHERE p.id_booking IS NULL
");

$missing_payments = $stmt->fetchAll();

foreach ($missing_payments as $booking) {
    try {
        $insert_payment = $pdo->prepare("
            INSERT INTO payments (id_booking, payment_status, payment_amount, created_at) 
            VALUES (?, 'unpaid', ?, NOW())
        ");
        $result = $insert_payment->execute([$booking['id'], $booking['total_amount']]);
        echo "Created payment record for booking ID {$booking['id']}: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    } catch (Exception $e) {
        echo "Failed to create payment record for booking ID {$booking['id']}: " . $e->getMessage() . "\n";
    }
}

echo "\nDone! Now checking all bookings again:\n";
$stmt = $pdo->query('SELECT b.id, b.id_user, b.total_amount, p.payment_status, p.receipt_image FROM bookings b LEFT JOIN payments p ON b.id = p.id_booking ORDER BY b.id');
while ($row = $stmt->fetch()) {
    echo "Booking ID: {$row['id']} | User: {$row['id_user']} | Amount: {$row['total_amount']} | Status: " . ($row['payment_status'] ?: 'NO_PAYMENT_RECORD') . " | Receipt: " . ($row['receipt_image'] ?: 'NONE') . "\n";
}
?>
