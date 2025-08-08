<?php
require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user = getUserData();

// Check if user data is valid
if (!$user || !isset($user['id'])) {
    // If user data is not found, destroy session and redirect
    session_destroy();
    redirectTo('../../login.php');
}

$booking_type = isset($_GET['type']) ? $_GET['type'] : 'ticket_hotel';

// Validate booking type
$valid_types = ['ticket_only', 'hotel_only', 'ticket_hotel'];
if (!in_array($booking_type, $valid_types)) {
    $booking_type = 'ticket_hotel';
}

// Get available hotels
$hotels_stmt = $pdo->query("SELECT * FROM hotels WHERE available_rooms > 0 ORDER BY rating DESC");
$hotels = $hotels_stmt->fetchAll();

// Get available tickets
$tickets_stmt = $pdo->query("SELECT * FROM tickets WHERE available_seats > 0 AND flight_date >= CURDATE() ORDER BY flight_date ASC, departure_time ASC");
$tickets = $tickets_stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_hotel = ($booking_type != 'ticket_only') ? (int)$_POST['id_hotel'] : null;
    $id_ticket = ($booking_type != 'hotel_only') ? (int)$_POST['id_ticket'] : null;
    $check_in_date = ($booking_type != 'ticket_only') ? $_POST['check_in_date'] : null;
    $check_out_date = ($booking_type != 'ticket_only') ? $_POST['check_out_date'] : null;
    $passengers = (int)$_POST['passengers'] ?: 1;
    $rooms = ($booking_type != 'ticket_only') ? (int)$_POST['rooms'] : 1;
    
    // Calculate total amount
    $total_amount = 0;
    
    if ($id_hotel && $booking_type != 'ticket_only') {
        $hotel_stmt = $pdo->prepare("SELECT price_per_night FROM hotels WHERE id = ?");
        $hotel_stmt->execute([$id_hotel]);
        $hotel = $hotel_stmt->fetch();
        
        if ($hotel && $check_in_date && $check_out_date) {
            $days = (strtotime($check_out_date) - strtotime($check_in_date)) / (60 * 60 * 24);
            $total_amount += $hotel['price_per_night'] * $days * $rooms;
        }
    }
    
    if ($id_ticket && $booking_type != 'hotel_only') {
        $ticket_stmt = $pdo->prepare("SELECT price FROM tickets WHERE id = ?");
        $ticket_stmt->execute([$id_ticket]);
        $ticket = $ticket_stmt->fetch();
        
        if ($ticket) {
            $total_amount += $ticket['price'] * $passengers;
        }
    }
    
    if ($total_amount > 0) {
        try {
            $pdo->beginTransaction();
            
            // Insert booking
            $booking_stmt = $pdo->prepare("
                INSERT INTO bookings (id_user, id_hotel, id_ticket, booking_type, check_in_date, check_out_date, passengers, rooms, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $booking_stmt->execute([
                $user['id'], $id_hotel, $id_ticket, $booking_type, 
                $check_in_date, $check_out_date, $passengers, $rooms, $total_amount
            ]);
            
            $booking_id = $pdo->lastInsertId();
            
            // Insert payment record
            $payment_stmt = $pdo->prepare("
                INSERT INTO payments (id_booking, payment_amount, payment_status) 
                VALUES (?, ?, 'unpaid')
            ");
            $payment_stmt->execute([$booking_id, $total_amount]);
            
            $pdo->commit();
            
            // If booking includes flight ticket, redirect to seat selection
            if ($id_ticket && $booking_type != 'hotel_only') {
                redirectTo("seat_selection.php?ticket_id=$id_ticket&passengers=$passengers&booking_id=$booking_id");
            } else {
                // Redirect directly to payment page for hotel-only bookings
                redirectTo("payment.php?booking_id=$booking_id");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan saat memproses pemesanan. Silakan coba lagi.';
        }
    } else {
        $error = 'Silakan pilih setidaknya satu layanan untuk dipesan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - Garuda Indonesia</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="logo-container">
                <img src="../../Assets/images/Logo(Horizontal).png" alt="Garuda Indonesia" class="logo">
                <span class="brand-text">Garuda Indonesia</span>
            </div>
            <ul class="nav-menu">
                <li><a href="../../index.php" class="nav-link">Beranda</a></li>
                <li><a href="booking_cart.php" class="nav-link">Pemesanan Saya</a></li>
                <li><a href="../user/chat.php" class="nav-link">Customer Service</a></li>
                <li><a href="../user/profile.php" class="nav-link">Profil</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </nav>
    </header>

    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h2>Form Pemesanan</h2>
                    </div>

                    <?php if ($error): ?>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Type Selector -->
                    <div class="form-group">
                        <label class="form-label">Jenis Pemesanan</label>
                        <div class="booking-options" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="booking-card <?php echo $booking_type == 'ticket_hotel' ? 'selected' : ''; ?>" 
                                 onclick="changeBookingType('ticket_hotel')">
                                <div class="booking-icon" style="font-size: 1.5rem;">
                                    <i class="fas fa-plane"></i> + <i class="fas fa-hotel"></i>
                                </div>
                                <h4>Tiket + Hotel</h4>
                            </div>
                            <div class="booking-card <?php echo $booking_type == 'hotel_only' ? 'selected' : ''; ?>" 
                                 onclick="changeBookingType('hotel_only')">
                                <div class="booking-icon" style="font-size: 1.5rem;">
                                    <i class="fas fa-hotel"></i>
                                </div>
                                <h4>Hotel Saja</h4>
                            </div>
                            <div class="booking-card <?php echo $booking_type == 'ticket_only' ? 'selected' : ''; ?>" 
                                 onclick="changeBookingType('ticket_only')">
                                <div class="booking-icon" style="font-size: 1.5rem;">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <h4>Tiket Saja</h4>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="" id="bookingForm">
                        <!-- Ticket Selection -->
                        <?php if ($booking_type != 'hotel_only'): ?>
                        <div class="form-group" id="ticketSection">
                            <label class="form-label">
                                <i class="fas fa-plane" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                                Pilih Penerbangan
                            </label>
                            <select name="id_ticket" class="form-control" required>
                                <option value="">Pilih penerbangan...</option>
                                <?php foreach ($tickets as $ticket): ?>
                                    <option value="<?php echo $ticket['id']; ?>" 
                                            data-price="<?php echo $ticket['price']; ?>">
                                        <?php echo $ticket['flight_code']; ?> - 
                                        <?php echo $ticket['departure_city']; ?> → <?php echo $ticket['arrival_city']; ?> - 
                                        <?php echo date('d M Y', strtotime($ticket['flight_date'])); ?> 
                                        <?php echo date('H:i', strtotime($ticket['departure_time'])); ?> - 
                                        <?php echo $ticket['seat_type']; ?> - 
                                        Rp <?php echo number_format($ticket['price'], 0, ',', '.'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Jumlah Penumpang</label>
                                    <input type="number" name="passengers" class="form-control" min="1" max="10" value="1" required>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Hotel Selection -->
                        <?php if ($booking_type != 'ticket_only'): ?>
                        <div class="form-group" id="hotelSection">
                            <label class="form-label">
                                <i class="fas fa-hotel" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                                Pilih Hotel
                            </label>
                            <select name="id_hotel" class="form-control" required>
                                <option value="">Pilih hotel...</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel['id']; ?>" 
                                            data-price="<?php echo $hotel['price_per_night']; ?>">
                                        <?php echo $hotel['name']; ?> - <?php echo $hotel['location']; ?> - 
                                        ★ <?php echo $hotel['rating']; ?> - 
                                        Rp <?php echo number_format($hotel['price_per_night'], 0, ',', '.'); ?>/malam
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label class="form-label">Check-in</label>
                                    <input type="date" name="check_in_date" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Check-out</label>
                                    <input type="date" name="check_out_date" class="form-control" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Kamar</label>
                                    <input type="number" name="rooms" class="form-control" min="1" max="5" value="1" required>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-arrow-right" style="margin-right: 0.5rem;"></i>
                            Lanjut ke Pembayaran
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3>Ringkasan Pemesanan</h3>
                    </div>

                    <div id="bookingSummary">
                        <p style="text-align: center; color: var(--dark-gray); font-style: italic;">
                            Silakan pilih layanan untuk melihat ringkasan
                        </p>
                    </div>

                    <div id="totalAmount" style="display: none; padding-top: 1rem; border-top: 2px solid var(--primary-blue); margin-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: var(--primary-blue);">
                            <span>Total:</span>
                            <span id="totalPrice">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- Features Card -->
                <div class="card mt-4">
                    <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-shield-alt"></i> Mengapa Memilih Kami?
                    </h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                            Pembayaran Aman & Terpercaya
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                            Customer Service 24/7
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                            Harga Terbaik Terjamin
                        </li>
                        <li>
                            <i class="fas fa-check-circle" style="color: var(--success); margin-right: 0.5rem;"></i>
                            Refund & Reschedule Mudah
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        let currentBookingType = '<?php echo $booking_type; ?>';

        function changeBookingType(type) {
            if (type !== currentBookingType) {
                window.location.href = `booking.php?type=${type}`;
            }
        }

        // Calculate total when selection changes
        function updateBookingSummary() {
            const ticketSelect = document.querySelector('select[name="id_ticket"]');
            const hotelSelect = document.querySelector('select[name="id_hotel"]');
            const passengersInput = document.querySelector('input[name="passengers"]');
            const roomsInput = document.querySelector('input[name="rooms"]');
            const checkInInput = document.querySelector('input[name="check_in_date"]');
            const checkOutInput = document.querySelector('input[name="check_out_date"]');
            
            let summary = '';
            let total = 0;

            // Ticket calculation
            if (ticketSelect && ticketSelect.value) {
                const selectedTicket = ticketSelect.options[ticketSelect.selectedIndex];
                const ticketPrice = parseInt(selectedTicket.dataset.price);
                const passengers = parseInt(passengersInput?.value || 1);
                const ticketTotal = ticketPrice * passengers;
                
                summary += `
                    <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h5 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <i class="fas fa-plane"></i> Penerbangan
                        </h5>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">${selectedTicket.text.split(' - ')[0]}</p>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">${passengers} penumpang × Rp ${ticketPrice.toLocaleString('id-ID')}</p>
                        <p style="margin: 0; font-weight: bold;">Subtotal: Rp ${ticketTotal.toLocaleString('id-ID')}</p>
                    </div>
                `;
                total += ticketTotal;
            }

            // Hotel calculation
            if (hotelSelect && hotelSelect.value && checkInInput?.value && checkOutInput?.value) {
                const selectedHotel = hotelSelect.options[hotelSelect.selectedIndex];
                const hotelPrice = parseInt(selectedHotel.dataset.price);
                const rooms = parseInt(roomsInput?.value || 1);
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const hotelTotal = hotelPrice * nights * rooms;
                
                if (nights > 0) {
                    summary += `
                        <div style="margin-bottom: 1rem;">
                            <h5 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                                <i class="fas fa-hotel"></i> Hotel
                            </h5>
                            <p style="margin: 0.25rem 0; font-size: 0.875rem;">${selectedHotel.text.split(' - ')[0]}</p>
                            <p style="margin: 0.25rem 0; font-size: 0.875rem;">${nights} malam × ${rooms} kamar × Rp ${hotelPrice.toLocaleString('id-ID')}</p>
                            <p style="margin: 0; font-weight: bold;">Subtotal: Rp ${hotelTotal.toLocaleString('id-ID')}</p>
                        </div>
                    `;
                    total += hotelTotal;
                }
            }

            // Update summary
            const summaryDiv = document.getElementById('bookingSummary');
            const totalDiv = document.getElementById('totalAmount');
            const totalPrice = document.getElementById('totalPrice');

            if (summary) {
                summaryDiv.innerHTML = summary;
                totalDiv.style.display = 'block';
                totalPrice.textContent = `Rp ${total.toLocaleString('id-ID')}`;
            } else {
                summaryDiv.innerHTML = '<p style="text-align: center; color: var(--dark-gray); font-style: italic;">Silakan pilih layanan untuk melihat ringkasan</p>';
                totalDiv.style.display = 'none';
            }
        }

        // Add event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bookingForm');
            if (form) {
                form.addEventListener('change', updateBookingSummary);
                form.addEventListener('input', updateBookingSummary);
            }

            // Set minimum dates
            const today = new Date().toISOString().split('T')[0];
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];

            const checkInInput = document.querySelector('input[name="check_in_date"]');
            const checkOutInput = document.querySelector('input[name="check_out_date"]');

            if (checkInInput) {
                checkInInput.addEventListener('change', function() {
                    if (checkOutInput) {
                        const checkInDate = new Date(this.value);
                        checkInDate.setDate(checkInDate.getDate() + 1);
                        checkOutInput.min = checkInDate.toISOString().split('T')[0];
                        
                        if (checkOutInput.value && checkOutInput.value <= this.value) {
                            checkOutInput.value = checkInDate.toISOString().split('T')[0];
                        }
                    }
                });
            }

            updateBookingSummary();
        });
    </script>
</body>
</html>
