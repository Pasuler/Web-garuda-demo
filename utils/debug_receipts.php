<?php
require_once 'includes/config.php';

echo "=== DEBUGGING PAYMENT RECEIPTS ===\n\n";

// Check current payment data
echo "Current payment data:\n";
$stmt = $pdo->query("
    SELECT 
        p.id, 
        p.id_booking, 
        p.payment_status, 
        p.payment_receipt,
        p.receipt_image,
        p.payment_date,
        b.id_user
    FROM payments p 
    JOIN bookings b ON p.id_booking = b.id 
    ORDER BY p.id
");

while ($row = $stmt->fetch()) {
    echo "Payment ID: {$row['id']}\n";
    echo "  Booking ID: {$row['id_booking']}\n";
    echo "  User ID: {$row['id_user']}\n";
    echo "  Status: {$row['payment_status']}\n";
    echo "  payment_receipt: " . ($row['payment_receipt'] ?: 'NULL') . "\n";
    echo "  receipt_image: " . ($row['receipt_image'] ?: 'NULL') . "\n";
    echo "  Payment Date: " . ($row['payment_date'] ?: 'NULL') . "\n";
    echo "  ---\n";
}

echo "\nChecking files in uploads/receipts/:\n";
$upload_dir = 'uploads/receipts/';
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "File: $file\n";
        }
    }
    if (count($files) <= 2) {
        echo "No receipt files found in uploads/receipts/\n";
    }
} else {
    echo "Directory uploads/receipts/ does not exist!\n";
}
?>
