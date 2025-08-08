<?php
require_once 'includes/config.php';

echo "=== FIXING DATABASE STRUCTURE ===\n\n";

try {
    // 1. Consolidate payment receipt columns
    echo "1. Consolidating payment receipt columns...\n";
    
    // First, copy data from payment_receipt to receipt_image if receipt_image is empty
    $stmt = $pdo->query("
        UPDATE payments 
        SET receipt_image = payment_receipt 
        WHERE receipt_image IS NULL AND payment_receipt IS NOT NULL
    ");
    echo "   ✅ Copied data from payment_receipt to receipt_image\n";
    
    // Drop the old payment_receipt column
    $pdo->exec("ALTER TABLE payments DROP COLUMN payment_receipt");
    echo "   ✅ Dropped payment_receipt column\n";
    
    // 2. Add missing columns to payments table if needed
    echo "2. Checking payments table for admin_notes...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'admin_notes'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE payments ADD COLUMN admin_notes TEXT NULL");
        echo "   ✅ Added admin_notes column\n";
    } else {
        echo "   ✅ admin_notes column already exists\n";
    }
    
    // 3. Ensure tickets table has proper seat_type enum values
    echo "3. Updating tickets table seat_type enum...\n";
    $pdo->exec("ALTER TABLE tickets MODIFY seat_type ENUM('Economy', 'Business', 'First Class') NOT NULL");
    echo "   ✅ Updated seat_type enum values\n";
    
    // 4. Add aircraft_type column to tickets if missing
    echo "4. Checking tickets table for aircraft_type...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'aircraft_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN aircraft_type VARCHAR(50) DEFAULT 'Boeing 737-800'");
        echo "   ✅ Added aircraft_type column\n";
    } else {
        echo "   ✅ aircraft_type column already exists\n";
    }
    
    // 5. Fix hotels table - add missing available_rooms default
    echo "5. Updating hotels table...\n";
    $pdo->exec("ALTER TABLE hotels MODIFY available_rooms INT DEFAULT 0");
    echo "   ✅ Updated available_rooms default value\n";
    
    // 6. Fix tickets table - add default values for seats
    echo "6. Updating tickets table defaults...\n";
    $pdo->exec("ALTER TABLE tickets MODIFY available_seats INT DEFAULT 0");
    $pdo->exec("ALTER TABLE tickets MODIFY total_seats INT DEFAULT 0");
    echo "   ✅ Updated seat defaults\n";
    
    // 7. Update payment status enum to include 'pending_verification'
    echo "7. Updating payment status enum...\n";
    $pdo->exec("ALTER TABLE payments MODIFY payment_status ENUM('unpaid', 'pending', 'pending_verification', 'paid', 'failed') DEFAULT 'unpaid'");
    echo "   ✅ Updated payment_status enum with pending_verification\n";
    
    // 8. Clean up bookings table - ensure seat_numbers is properly set
    echo "8. Updating bookings table seat_numbers...\n";
    $pdo->exec("ALTER TABLE bookings MODIFY seat_numbers VARCHAR(255) DEFAULT NULL");
    echo "   ✅ Updated seat_numbers column\n";
    
    echo "\n=== VERIFICATION ===\n";
    
    // Verify final structure
    $tables = ['users', 'hotels', 'tickets', 'bookings', 'payments', 'chats'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "✅ Table '$table': $count records\n";
    }
    
    echo "\n=== DATABASE STRUCTURE FIXED SUCCESSFULLY! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
