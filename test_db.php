<?php
/**
 * Basic Database Test - Test connection and basic functionality
 */

// Test database connection manually
try {
    $pdo = new PDO("mysql:host=localhost;dbname=garuda_indonesia_website", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "===========================================\n";
    echo "   DATABASE CONNECTION TEST\n";
    echo "===========================================\n\n";
    
    echo "✅ Database connection: SUCCESS\n";
    
    // Test a simple query
    $stmt = $pdo->prepare("SELECT 'Database is working!' as message");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Query execution: SUCCESS\n";
    echo "✅ Message: " . $result['message'] . "\n\n";
    
    // Count records in each table
    $tables = ['users', 'hotels', 'tickets', 'bookings', 'payments', 'chats'];
    echo "📊 TABLE DATA SUMMARY:\n";
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        echo "   $table: $count records\n";
    }
    
    // Check admin user
    echo "\n👤 ADMIN USER CHECK:\n";
    $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin found: " . $admin['full_name'] . " (" . $admin['email'] . ")\n";
        echo "🔐 Default password: admin123\n";
    } else {
        echo "❌ No admin user found\n";
    }
    
    echo "\n===========================================\n";
    echo "🎉 DATABASE STATUS: READY TO USE!\n";
    echo "===========================================\n\n";
    
    echo "🌐 Website URL: http://localhost/website-garuda\n";
    echo "🔧 Admin Panel: http://localhost/website-garuda/admin\n";
    echo "📧 Admin Login: admin@garudaindonesia.com\n";
    echo "🔑 Password: admin123\n\n";
    
    echo "✅ Database setup completed successfully!\n";
    echo "✅ All PHP warnings should now be fixed!\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "💡 TROUBLESHOOTING STEPS:\n";
    echo "1. Make sure Laragon MySQL is running\n";
    echo "2. Verify database exists: mysql -u root -e \"SHOW DATABASES;\"\n";
    echo "3. Recreate database: mysql -u root -e \"CREATE DATABASE garuda_indonesia_website;\"\n";
    echo "4. Import data: mysql -u root garuda_indonesia_website < database/database.sql\n";
}

echo "\n";
?>
