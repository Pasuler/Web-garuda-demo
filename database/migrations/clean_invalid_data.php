<?php
/**
 * Clean Invalid Data Migration
 * This migration removes or fixes invalid data that might cause errors
 */

require_once __DIR__ . '/../../includes/config.php';

echo "=========================================\n";
echo "   CLEANING INVALID DATA\n";
echo "=========================================\n\n";

try {
    // 1. Clean up users table
    echo "🧹 Cleaning users table...\n";
    
    // Remove users with invalid email formats
    $invalid_emails = $pdo->query("SELECT COUNT(*) FROM users WHERE email NOT LIKE '%@%'")->fetchColumn();
    if ($invalid_emails > 0) {
        $pdo->exec("DELETE FROM users WHERE email NOT LIKE '%@%'");
        echo "✅ Removed $invalid_emails users with invalid email formats\n";
    }
    
    // Fix empty full_name fields
    $empty_names = $pdo->exec("UPDATE users SET full_name = CONCAT('User_', id) WHERE full_name = '' OR full_name IS NULL");
    if ($empty_names > 0) {
        echo "✅ Fixed $empty_names users with empty names\n";
    }
    
    // 2. Clean up bookings table
    echo "🧹 Cleaning bookings table...\n";
    
    // Remove bookings with invalid user references
    $invalid_bookings = $pdo->exec("DELETE FROM bookings WHERE id_user NOT IN (SELECT id FROM users)");
    if ($invalid_bookings > 0) {
        echo "✅ Removed $invalid_bookings bookings with invalid user references\n";
    }
    
    // Fix negative amounts
    $negative_amounts = $pdo->exec("UPDATE bookings SET total_amount = ABS(total_amount) WHERE total_amount < 0");
    if ($negative_amounts > 0) {
        echo "✅ Fixed $negative_amounts bookings with negative amounts\n";
    }
    
    // 3. Clean up payments table
    echo "🧹 Cleaning payments table...\n";
    
    // Remove payments with invalid booking references
    $invalid_payments = $pdo->exec("DELETE FROM payments WHERE id_booking NOT IN (SELECT id FROM bookings)");
    if ($invalid_payments > 0) {
        echo "✅ Removed $invalid_payments payments with invalid booking references\n";
    }
    
    // Fix negative payment amounts
    $negative_payments = $pdo->exec("UPDATE payments SET payment_amount = ABS(payment_amount) WHERE payment_amount < 0");
    if ($negative_payments > 0) {
        echo "✅ Fixed $negative_payments payments with negative amounts\n";
    }
    
    // 4. Clean up chats table
    echo "🧹 Cleaning chats table...\n";
    
    // Remove chats with invalid user references
    $invalid_chats = $pdo->exec("DELETE FROM chats WHERE id_user NOT IN (SELECT id FROM users)");
    if ($invalid_chats > 0) {
        echo "✅ Removed $invalid_chats chats with invalid user references\n";
    }
    
    // Remove empty messages
    $empty_messages = $pdo->exec("DELETE FROM chats WHERE message = '' OR message IS NULL");
    if ($empty_messages > 0) {
        echo "✅ Removed $empty_messages chats with empty messages\n";
    }
    
    // 5. Update foreign key constraints to prevent future issues
    echo "🧹 Updating constraints...\n";
    
    // This will help prevent future data integrity issues
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop and recreate foreign keys for better integrity
    try {
        $pdo->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_1");
        $pdo->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_2");
        $pdo->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_3");
    } catch (Exception $e) {
        // Ignore if constraints don't exist
    }
    
    try {
        $pdo->exec("ALTER TABLE payments DROP FOREIGN KEY payments_ibfk_1");
        $pdo->exec("ALTER TABLE payments DROP FOREIGN KEY payments_ibfk_2");
    } catch (Exception $e) {
        // Ignore if constraints don't exist
    }
    
    try {
        $pdo->exec("ALTER TABLE chats DROP FOREIGN KEY chats_ibfk_1");
        $pdo->exec("ALTER TABLE chats DROP FOREIGN KEY chats_ibfk_2");
    } catch (Exception $e) {
        // Ignore if constraints don't exist
    }
    
    // Recreate foreign keys
    $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_hotel FOREIGN KEY (id_hotel) REFERENCES hotels(id) ON DELETE SET NULL");
    $pdo->exec("ALTER TABLE bookings ADD CONSTRAINT fk_bookings_ticket FOREIGN KEY (id_ticket) REFERENCES tickets(id) ON DELETE SET NULL");
    
    $pdo->exec("ALTER TABLE payments ADD CONSTRAINT fk_payments_booking FOREIGN KEY (id_booking) REFERENCES bookings(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE payments ADD CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL");
    
    $pdo->exec("ALTER TABLE chats ADD CONSTRAINT fk_chats_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE chats ADD CONSTRAINT fk_chats_responded_by FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL");
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✅ Updated foreign key constraints\n";
    
    // 6. Optimize tables
    echo "🧹 Optimizing database tables...\n";
    $tables = ['users', 'hotels', 'tickets', 'bookings', 'payments', 'chats'];
    
    foreach ($tables as $table) {
        $pdo->exec("OPTIMIZE TABLE $table");
    }
    
    echo "✅ Database tables optimized\n";
    
    echo "\n=========================================\n";
    echo "   DATA CLEANING COMPLETED SUCCESSFULLY\n";
    echo "=========================================\n\n";
    
    echo "🎉 All invalid data has been cleaned!\n";
    echo "✅ Invalid users removed\n";
    echo "✅ Orphaned records cleaned\n";
    echo "✅ Negative amounts fixed\n";
    echo "✅ Foreign keys updated\n";
    echo "✅ Database optimized\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>
