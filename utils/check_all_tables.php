<?php
require_once 'includes/config.php';

try {
    $tables = ['users', 'hotels', 'tickets', 'bookings', 'payments', 'chats'];
    
    foreach ($tables as $table) {
        echo "\n=== TABLE: $table ===\n";
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            echo "- {$row['Field']} ({$row['Type']}) " . 
                 ($row['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($row['Default'] ? " DEFAULT '{$row['Default']}'" : '') . 
                 ($row['Extra'] ? " {$row['Extra']}" : '') . "\n";
        }
    }
    
    echo "\n=== CHECKING FOR MISSING COLUMNS ===\n";
    
    // Check if tickets table has all needed columns
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'seat_type'");
    if ($stmt->rowCount() == 0) {
        echo "❌ tickets table missing 'seat_type' column\n";
    } else {
        echo "✅ tickets table has 'seat_type' column\n";
    }
    
    // Check if bookings table has seat_numbers
    $stmt = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'seat_numbers'");
    if ($stmt->rowCount() == 0) {
        echo "❌ bookings table missing 'seat_numbers' column\n";
    } else {
        echo "✅ bookings table has 'seat_numbers' column\n";
    }
    
    // Check if payments has both receipt columns
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_receipt'");
    $has_payment_receipt = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'receipt_image'");
    $has_receipt_image = $stmt->rowCount() > 0;
    
    echo ($has_payment_receipt ? "✅" : "❌") . " payments table has 'payment_receipt' column\n";
    echo ($has_receipt_image ? "✅" : "❌") . " payments table has 'receipt_image' column\n";
    
    if ($has_payment_receipt && $has_receipt_image) {
        echo "⚠️ WARNING: payments table has BOTH receipt columns - should consolidate\n";
    }
    
    // Check chat table columns
    $stmt = $pdo->query("SHOW COLUMNS FROM chats LIKE 'is_from_admin'");
    $has_is_from_admin = $stmt->rowCount() > 0;
    echo ($has_is_from_admin ? "✅" : "❌") . " chats table has 'is_from_admin' column\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
