<?php
require_once __DIR__ . '/../../includes/config.php';

try {
    // Add seat_numbers column to bookings table if it doesn't exist
    $check_column = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'seat_numbers'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN seat_numbers VARCHAR(255) DEFAULT NULL");
        echo "✅ Column 'seat_numbers' added to bookings table successfully.\n";
    } else {
        echo "ℹ️  Column 'seat_numbers' already exists in bookings table.\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
