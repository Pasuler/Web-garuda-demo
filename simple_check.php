<?php
/**
 * Simple Migration Checker - Check database structure without buffering issues
 */

require_once 'includes/config.php';

echo "===========================================\n";
echo "   GARUDA WEBSITE - SIMPLE CHECKER\n";
echo "===========================================\n\n";

try {
    echo "🔍 Checking database connection...\n";
    $pdo->exec("SELECT 1");
    echo "✅ Database connection: OK\n\n";
    
    // Simple table check
    echo "🔍 Checking tables...\n";
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $expected_tables = ['users', 'hotels', 'tickets', 'bookings', 'payments', 'chats'];
    foreach ($expected_tables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ Table '$table': EXISTS\n";
        } else {
            echo "❌ Table '$table': NOT FOUND\n";
        }
    }
    
    echo "\n🔍 Checking data...\n";
    
    // Simple count queries
    $counts = [];
    foreach ($expected_tables as $table) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $counts[$table] = $count;
            echo "📊 $table: $count records\n";
        }
    }
    
    echo "\n🔍 Checking admin user...\n";
    if (in_array('users', $tables)) {
        $admin_stmt = $pdo->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
        $admin_email = $admin_stmt->fetchColumn();
        if ($admin_email) {
            echo "✅ Admin user found: $admin_email\n";
        } else {
            echo "❌ No admin user found\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "   SUMMARY\n";
    echo "===========================================\n";
    
    $all_tables_exist = true;
    foreach ($expected_tables as $table) {
        if (!in_array($table, $tables)) {
            $all_tables_exist = false;
            break;
        }
    }
    
    if ($all_tables_exist && isset($counts['users']) && $counts['users'] > 0) {
        echo "🎉 DATABASE STATUS: READY\n";
        echo "✅ All tables exist\n";
        echo "✅ Data is available\n";
        echo "🌐 Website should work correctly\n";
    } else {
        echo "⚠️  DATABASE STATUS: NEEDS SETUP\n";
        echo "❗ Run setup scripts or import database\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "\n💡 Solutions:\n";
    echo "1. Make sure MySQL/MariaDB is running\n";
    echo "2. Check database credentials in includes/config.php\n";
    echo "3. Create database: mysql -u root -e \"CREATE DATABASE garuda_indonesia_website;\"\n";
    echo "4. Import database: mysql -u root garuda_indonesia_website < database/database.sql\n";
}

echo "\n";
?>
