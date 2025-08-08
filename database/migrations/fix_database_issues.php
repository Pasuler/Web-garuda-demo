<?php
/**
 * Fix Common Database Issues Migration
 * This migration fixes common issues found in the database structure and adds missing data
 */

require_once __DIR__ . '/../../includes/config.php';

echo "=========================================\n";
echo "   FIXING DATABASE ISSUES\n";
echo "=========================================\n\n";

try {
    // 1. Ensure all tables exist with proper structure
    echo "🔧 Checking and fixing table structures...\n";
    
    // Check if users table has proper structure
    $check_users = $pdo->query("SHOW COLUMNS FROM users");
    $users_columns = $check_users->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('full_name', $users_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL AFTER email");
        echo "✅ Added full_name column to users table\n";
    }
    
    if (!in_array('phone', $users_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER full_name");
        echo "✅ Added phone column to users table\n";
    }
    
    // 2. Check and fix bookings table
    echo "🔧 Checking bookings table...\n";
    $check_bookings = $pdo->query("SHOW COLUMNS FROM bookings");
    $bookings_columns = $check_bookings->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('seat_numbers', $bookings_columns)) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN seat_numbers VARCHAR(255) DEFAULT NULL");
        echo "✅ Added seat_numbers column to bookings table\n";
    }
    
    // 3. Ensure admin user exists
    echo "🔧 Checking admin user...\n";
    $admin_check = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    
    if ($admin_check == 0) {
        // Create default admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute(['admin@garudaindonesia.com', $admin_password, 'Admin Garuda']);
        echo "✅ Created default admin user\n";
        echo "   Email: admin@garudaindonesia.com\n";
        echo "   Password: admin123\n";
    } else {
        echo "✅ Admin user already exists\n";
    }
    
    // 4. Check sample hotels
    echo "🔧 Checking sample hotels...\n";
    $hotel_count = $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
    
    if ($hotel_count < 3) {
        // Insert sample hotels if missing
        $hotels = [
            ['Hotel Bali Beach Resort', 'Denpasar, Bali', 850000, 4.5, 'hotel1.jpg', 'Hotel mewah di tepi pantai dengan pemandangan laut yang menakjubkan', 'WiFi Gratis, Kolam Renang, Spa, Restoran, Gym', 25],
            ['Ubud Paradise Hotel', 'Ubud, Bali', 650000, 4.3, 'hotel2.jpg', 'Hotel dengan nuansa alam di tengah sawah dan hutan', 'WiFi Gratis, Restaurant, Spa, Tour Guide', 15],
            ['Sanur Sunset Hotel', 'Sanur, Bali', 750000, 4.4, 'hotel3.jpg', 'Hotel modern dengan fasilitas lengkap di Sanur', 'WiFi Gratis, Kolam Renang, Restaurant, Beach Access', 20]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO hotels (name, location, price_per_night, rating, image_url, description, facilities, available_rooms) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($hotels as $hotel) {
            $stmt->execute($hotel);
        }
        
        echo "✅ Added sample hotels\n";
    } else {
        echo "✅ Hotels data already exists\n";
    }
    
    // 5. Check sample tickets
    echo "🔧 Checking sample tickets...\n";
    $ticket_count = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    
    if ($ticket_count < 4) {
        // Insert sample tickets if missing
        $tickets = [
            ['GA101', 'Jakarta', 'Denpasar', '2025-08-15', '08:00:00', '11:00:00', 'Economy', 1200000, 150, 180, 'Boeing 737-800'],
            ['GA102', 'Jakarta', 'Denpasar', '2025-08-15', '14:00:00', '17:00:00', 'Business', 2500000, 20, 30, 'Boeing 737-800'],
            ['GA103', 'Surabaya', 'Denpasar', '2025-08-16', '09:30:00', '11:30:00', 'Economy', 950000, 100, 150, 'Boeing 737-800'],
            ['GA104', 'Jakarta', 'Denpasar', '2025-08-17', '19:00:00', '22:00:00', 'Economy', 1150000, 120, 180, 'Boeing 737-800']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO tickets (flight_code, departure_city, arrival_city, flight_date, departure_time, arrival_time, seat_type, price, available_seats, total_seats, aircraft_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($tickets as $ticket) {
            $stmt->execute($ticket);
        }
        
        echo "✅ Added sample tickets\n";
    } else {
        echo "✅ Tickets data already exists\n";
    }
    
    // 6. Fix any NULL values that might cause issues
    echo "🔧 Fixing NULL values...\n";
    
    // Update any users with NULL full_name
    $pdo->exec("UPDATE users SET full_name = 'User' WHERE full_name IS NULL OR full_name = ''");
    
    // Update hotels with NULL values
    $pdo->exec("UPDATE hotels SET description = 'No description available' WHERE description IS NULL");
    $pdo->exec("UPDATE hotels SET facilities = 'Basic facilities' WHERE facilities IS NULL");
    $pdo->exec("UPDATE hotels SET available_rooms = 0 WHERE available_rooms IS NULL");
    $pdo->exec("UPDATE hotels SET rating = 0.0 WHERE rating IS NULL");
    
    // Update tickets with NULL values
    $pdo->exec("UPDATE tickets SET available_seats = 0 WHERE available_seats IS NULL");
    $pdo->exec("UPDATE tickets SET total_seats = 150 WHERE total_seats IS NULL");
    $pdo->exec("UPDATE tickets SET aircraft_type = 'Boeing 737-800' WHERE aircraft_type IS NULL");
    
    echo "✅ Fixed NULL values in database\n";
    
    // 7. Update database charset if needed
    echo "🔧 Ensuring proper charset...\n";
    $pdo->exec("ALTER DATABASE garuda_indonesia_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database charset updated\n";
    
    echo "\n=========================================\n";
    echo "   DATABASE FIX COMPLETED SUCCESSFULLY\n";
    echo "=========================================\n\n";
    
    echo "🎉 All database issues have been fixed!\n";
    echo "✅ Tables structure verified\n";
    echo "✅ Admin user available\n";
    echo "✅ Sample data loaded\n";
    echo "✅ NULL values fixed\n";
    echo "✅ Charset optimized\n\n";
    
    echo "Default Admin Login:\n";
    echo "Email: admin@garudaindonesia.com\n";
    echo "Password: admin123\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}

echo "\n";
?>
