<?php
/**
 * Migration Checker - Check database structure and migration status
 * Run this file to verify if migrations have been applied correctly
 */

require_once 'includes/config.php';

echo "===========================================\n";
echo "   GARUDA WEBSITE - MIGRATION CHECKER\n";
echo "===========================================\n\n";

try {
    // Check database connection
    echo "🔍 Checking database connection...\n";
    $pdo->exec("SELECT 1");
    echo "✅ Database connection: OK\n\n";
    
    // Check main tables exist
    echo "🔍 Checking main tables...\n";
    $tables = ['users', 'hotels', 'tickets', 'bookings', 'payments', 'chats'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            echo "✅ Table '$table': EXISTS\n";
        } else {
            echo "❌ Table '$table': NOT FOUND\n";
        }
    }
    
    echo "\n";
    
    // Check specific migration: seat_numbers column
    echo "🔍 Checking migration: seat_numbers column...\n";
    $check_seat_column = $pdo->prepare("SHOW COLUMNS FROM bookings LIKE ?");
    $check_seat_column->execute(['seat_numbers']);
    $seat_column_result = $check_seat_column->fetchAll();
    
    if (count($seat_column_result) > 0) {
        echo "✅ Migration 'add_seat_column.php': APPLIED\n";
    } else {
        echo "⚠️  Migration 'add_seat_column.php': NOT APPLIED\n";
        echo "   → Run: php database/migrations/add_seat_column.php\n";
    }
    
    echo "\n";
    
    // Check sample data
    echo "🔍 Checking sample data...\n";
    
    $user_stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $user_stmt->execute();
    $user_count = $user_stmt->fetchColumn();
    
    $hotel_stmt = $pdo->prepare("SELECT COUNT(*) FROM hotels");
    $hotel_stmt->execute();
    $hotel_count = $hotel_stmt->fetchColumn();
    
    $ticket_stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets");
    $ticket_stmt->execute();
    $ticket_count = $ticket_stmt->fetchColumn();
    
    echo "📊 Users: $user_count\n";
    echo "📊 Hotels: $hotel_count\n";
    echo "📊 Tickets: $ticket_count\n";
    
    if ($user_count > 0 && $hotel_count > 0 && $ticket_count > 0) {
        echo "✅ Sample data: LOADED\n";
    } else {
        echo "⚠️  Sample data: INCOMPLETE\n";
        echo "   → Run: mysql -u root -p garuda_indonesia_website < database/database.sql\n";
    }
    
    echo "\n";
    
    // Check admin user
    echo "🔍 Checking admin user...\n";
    $admin_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_stmt->execute();
    $admin_check = $admin_stmt->fetchColumn();
    
    if ($admin_check > 0) {
        echo "✅ Admin user: EXISTS\n";
        $admin_email_stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        $admin_email_stmt->execute();
        $admin_email = $admin_email_stmt->fetchColumn();
        echo "📧 Admin email: $admin_email\n";
    } else {
        echo "❌ Admin user: NOT FOUND\n";
        echo "   → Run database import or create admin manually\n";
    }
    
    echo "\n";
    
    // Check upload directories
    echo "🔍 Checking upload directories...\n";
    $upload_dirs = [
        'uploads/',
        'uploads/hotels/',
        'uploads/payment_receipts/',
        'uploads/receipts/',
        'pages/booking/uploads/',
        'pages/booking/uploads/receipts/'
    ];
    
    foreach ($upload_dirs as $dir) {
        if (is_dir($dir)) {
            if (is_writable($dir)) {
                echo "✅ Directory '$dir': EXISTS & WRITABLE\n";
            } else {
                echo "⚠️  Directory '$dir': EXISTS but NOT WRITABLE\n";
            }
        } else {
            echo "❌ Directory '$dir': NOT FOUND\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "   MIGRATION CHECKER COMPLETED\n";
    echo "===========================================\n\n";
    
    // Summary
    if ($user_count > 0 && $hotel_count > 0 && $ticket_count > 0 && $admin_check > 0) {
        echo "🎉 DATABASE STATUS: READY\n";
        echo "✅ Website should be working correctly\n";
        echo "🌐 Access: http://localhost/website-garuda\n";
        echo "🔧 Admin: http://localhost/website-garuda/admin\n";
    } else {
        echo "⚠️  DATABASE STATUS: NEEDS ATTENTION\n";
        echo "❗ Please run the setup script or import database manually\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "\n💡 Solutions:\n";
    echo "1. Check if MySQL is running\n";
    echo "2. Verify database credentials in includes/config.php\n";
    echo "3. Make sure database 'garuda_indonesia_website' exists\n";
}

echo "\n";
?>
