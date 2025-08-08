-- Database: garuda_indonesia_website
CREATE DATABASE IF NOT EXISTS garuda_indonesia_website;
USE garuda_indonesia_website;

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: hotels
CREATE TABLE hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    rating DECIMAL(2,1) DEFAULT 0,
    image_url VARCHAR(255),
    description TEXT,
    facilities TEXT,
    available_rooms INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flight_code VARCHAR(10) NOT NULL,
    departure_city VARCHAR(50) NOT NULL,
    arrival_city VARCHAR(50) NOT NULL,
    flight_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    seat_type ENUM('Economy', 'Business', 'First Class') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT DEFAULT 0,
    total_seats INT DEFAULT 0,
    aircraft_type VARCHAR(50) DEFAULT 'Boeing 737-800',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_hotel INT NULL,
    id_ticket INT NULL,
    booking_type ENUM('ticket_only', 'hotel_only', 'ticket_hotel') NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_in_date DATE NULL,
    check_out_date DATE NULL,
    passengers INT DEFAULT 1,
    rooms INT DEFAULT 1,
    seat_numbers VARCHAR(255) DEFAULT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_hotel) REFERENCES hotels(id) ON DELETE SET NULL,
    FOREIGN KEY (id_ticket) REFERENCES tickets(id) ON DELETE SET NULL
);

-- Table: payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_booking INT NOT NULL,
    payment_status ENUM('unpaid', 'pending', 'pending_verification', 'paid', 'failed') DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    receipt_image VARCHAR(255),
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP NULL,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_booking) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: chats
CREATE TABLE chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    message TEXT NOT NULL,
    response TEXT NULL,
    is_from_user BOOLEAN DEFAULT TRUE,
    is_from_admin BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    responded_by INT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (email, password, full_name, role) VALUES 
('admin@garudaindonesia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Garuda', 'admin');

-- Insert sample hotels
INSERT INTO hotels (name, location, price_per_night, rating, image_url, description, facilities, available_rooms) VALUES
('Hotel Bali Beach Resort', 'Denpasar, Bali', 850000, 4.5, 'hotel1.jpg', 'Hotel mewah di tepi pantai dengan pemandangan laut yang menakjubkan', 'WiFi Gratis, Kolam Renang, Spa, Restoran, Gym', 25),
('Ubud Paradise Hotel', 'Ubud, Bali', 650000, 4.3, 'hotel2.jpg', 'Hotel dengan nuansa alam di tengah sawah dan hutan', 'WiFi Gratis, Restaurant, Spa, Tour Guide', 15),
('Sanur Sunset Hotel', 'Sanur, Bali', 750000, 4.4, 'hotel3.jpg', 'Hotel modern dengan fasilitas lengkap di Sanur', 'WiFi Gratis, Kolam Renang, Restaurant, Beach Access', 20);

-- Insert sample tickets
INSERT INTO tickets (flight_code, departure_city, arrival_city, flight_date, departure_time, arrival_time, seat_type, price, available_seats, total_seats, aircraft_type) VALUES
('GA101', 'Jakarta', 'Denpasar', '2025-08-15', '08:00:00', '11:00:00', 'Economy', 1200000, 150, 180, 'Boeing 737-800'),
('GA102', 'Jakarta', 'Denpasar', '2025-08-15', '14:00:00', '17:00:00', 'Business', 2500000, 20, 30, 'Boeing 737-800'),
('GA103', 'Surabaya', 'Denpasar', '2025-08-16', '09:30:00', '11:30:00', 'Economy', 950000, 100, 150, 'Boeing 737-800'),
('GA104', 'Jakarta', 'Denpasar', '2025-08-17', '19:00:00', '22:00:00', 'Economy', 1150000, 120, 180, 'Boeing 737-800');

