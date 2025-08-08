<?php
require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('../auth/login.php');
}

$user = getUserData();

// Get booking info from session or parameters
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$passengers = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$ticket_id) {
    redirectTo('booking.php');
}

// Get ticket information
$ticket_stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$ticket_stmt->execute([$ticket_id]);
$ticket = $ticket_stmt->fetch();

if (!$ticket) {
    redirectTo('booking.php');
}

// Get already booked seats for this flight
$booked_seats_stmt = $pdo->prepare("
    SELECT seat_numbers FROM bookings 
    WHERE id_ticket = ? AND seat_numbers IS NOT NULL AND seat_numbers != ''
");
$booked_seats_stmt->execute([$ticket_id]);
$booked_results = $booked_seats_stmt->fetchAll();

$booked_seats = [];
foreach ($booked_results as $row) {
    if ($row['seat_numbers']) {
        $seats = explode(',', $row['seat_numbers']);
        $booked_seats = array_merge($booked_seats, $seats);
    }
}

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['seats'])) {
    $selected_seats = $_POST['seats'];
    
    if (count($selected_seats) != $passengers) {
        $error = "Silakan pilih " . $passengers . " kursi sesuai jumlah penumpang.";
    } else {
        // Save seat selection to booking
        $seat_numbers = implode(',', $selected_seats);
        
        if ($booking_id) {
            // Update existing booking
            $update_stmt = $pdo->prepare("UPDATE bookings SET seat_numbers = ? WHERE id = ?");
            $update_stmt->execute([$seat_numbers, $booking_id]);
        } else {
            // Store in session for new booking
            $_SESSION['selected_seats'] = $seat_numbers;
        }
        
        // Redirect to payment or booking completion
        $redirect_url = $booking_id ? "booking_cart.php?updated=1" : "booking.php?step=confirm&ticket_id=" . $ticket_id;
        redirectTo($redirect_url);
    }
}

// Generate seat map (A320 configuration: 6 seats per row, rows 1-30)
$seat_rows = 30;
$seats_per_row = 6;
$seat_letters = ['A', 'B', 'C', 'D', 'E', 'F'];

// Premium seats (rows 1-5)
$premium_rows = [1, 2, 3, 4, 5];
// Standard seats
$standard_rows = range(6, 30);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemilihan Kursi - Garuda Indonesia</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .seat-selection-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .aircraft-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .aircraft-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .aircraft-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .flight-info {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .seat-map {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
        }
        
        .seat-map::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 20px;
            background: #e5e7eb;
            border-radius: 0 0 15px 15px;
        }
        
        .seat-row {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 0.5rem;
            gap: 0.25rem;
        }
        
        .row-number {
            width: 30px;
            text-align: center;
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .seat-group {
            display: flex;
            gap: 0.25rem;
        }
        
        .seat-group.left {
            margin-right: 1rem;
        }
        
        .seat-group.right {
            margin-left: 1rem;
        }
        
        .seat {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.75rem;
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .seat.available {
            background: #10b981;
            color: white;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        
        .seat.available:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.4);
        }
        
        .seat.selected {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
            transform: scale(1.1);
        }
        
        .seat.occupied {
            background: #ef4444;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .seat.premium.available {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
        }
        
        .seat.premium.available:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .seat.premium.selected {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
        }
        
        .seat::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: rgba(0,0,0,0.1);
            border-radius: 1px;
        }
        
        .aisle {
            width: 30px;
        }
        
        .selection-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .selected-seats {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .seat-tag {
            background: #3b82f6;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .continue-btn {
            width: 100%;
            margin-top: 1rem;
        }
        
        .class-section {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px dashed #e5e7eb;
        }
        
        .class-label {
            text-align: center;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
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

    <div class="seat-selection-container">
        <div class="aircraft-container">
            <div class="aircraft-header">
                <h2><i class="fas fa-plane"></i> Pemilihan Kursi</h2>
                <p style="color: #6b7280; margin-top: 0.5rem;">Pilih kursi yang Anda inginkan</p>
            </div>

            <div class="flight-info">
                <div class="row">
                    <div class="col-md-6">
                        <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                            <i class="fas fa-plane-departure"></i> <?php echo $ticket['flight_code']; ?>
                        </h4>
                        <p><strong><?php echo $ticket['departure_city']; ?></strong> → <strong><?php echo $ticket['arrival_city']; ?></strong></p>
                        <p><?php echo date('d M Y', strtotime($ticket['flight_date'])); ?> - <?php echo date('H:i', strtotime($ticket['departure_time'])); ?></p>
                    </div>
                    <div class="col-md-6" style="text-align: right;">
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px;">
                            <p style="margin: 0; color: #6b7280;">Jumlah Penumpang:</p>
                            <p style="margin: 0; font-size: 1.5rem; font-weight: bold; color: var(--primary-blue);"><?php echo $passengers; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="seat-legend">
                <div class="legend-item">
                    <div class="seat available" style="width: 20px; height: 20px; font-size: 0.6rem; margin: 0;"></div>
                    <span>Tersedia</span>
                </div>
                <div class="legend-item">
                    <div class="seat selected" style="width: 20px; height: 20px; font-size: 0.6rem; margin: 0;"></div>
                    <span>Dipilih</span>
                </div>
                <div class="legend-item">
                    <div class="seat occupied" style="width: 20px; height: 20px; font-size: 0.6rem; margin: 0;"></div>
                    <span>Tidak Tersedia</span>
                </div>
                <div class="legend-item">
                    <div class="seat premium available" style="width: 20px; height: 20px; font-size: 0.6rem; margin: 0;"></div>
                    <span>Premium</span>
                </div>
            </div>

            <form method="POST">
                <div class="seat-map">
                    <!-- Premium Class -->
                    <div class="class-section">
                        <div class="class-label">Premium Economy</div>
                        <?php for ($row = 1; $row <= 5; $row++): ?>
                            <div class="seat-row">
                                <div class="row-number"><?php echo $row; ?></div>
                                
                                <!-- Left side seats A, B, C -->
                                <div class="seat-group left">
                                    <?php for ($seat_idx = 0; $seat_idx < 3; $seat_idx++): ?>
                                        <?php 
                                        $seat_letter = $seat_letters[$seat_idx];
                                        $seat_number = $row . $seat_letter;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        ?>
                                        <button type="button" 
                                                class="seat premium <?php echo $is_booked ? 'occupied' : 'available'; ?>" 
                                                data-seat="<?php echo $seat_number; ?>"
                                                <?php echo $is_booked ? 'disabled' : ''; ?>>
                                            <?php echo $seat_letter; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="aisle"></div>
                                
                                <!-- Right side seats D, E, F -->
                                <div class="seat-group right">
                                    <?php for ($seat_idx = 3; $seat_idx < 6; $seat_idx++): ?>
                                        <?php 
                                        $seat_letter = $seat_letters[$seat_idx];
                                        $seat_number = $row . $seat_letter;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        ?>
                                        <button type="button" 
                                                class="seat premium <?php echo $is_booked ? 'occupied' : 'available'; ?>" 
                                                data-seat="<?php echo $seat_number; ?>"
                                                <?php echo $is_booked ? 'disabled' : ''; ?>>
                                            <?php echo $seat_letter; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Standard Class -->
                    <div class="class-section">
                        <div class="class-label">Economy</div>
                        <?php for ($row = 6; $row <= 30; $row++): ?>
                            <div class="seat-row">
                                <div class="row-number"><?php echo $row; ?></div>
                                
                                <!-- Left side seats A, B, C -->
                                <div class="seat-group left">
                                    <?php for ($seat_idx = 0; $seat_idx < 3; $seat_idx++): ?>
                                        <?php 
                                        $seat_letter = $seat_letters[$seat_idx];
                                        $seat_number = $row . $seat_letter;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        ?>
                                        <button type="button" 
                                                class="seat <?php echo $is_booked ? 'occupied' : 'available'; ?>" 
                                                data-seat="<?php echo $seat_number; ?>"
                                                <?php echo $is_booked ? 'disabled' : ''; ?>>
                                            <?php echo $seat_letter; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="aisle"></div>
                                
                                <!-- Right side seats D, E, F -->
                                <div class="seat-group right">
                                    <?php for ($seat_idx = 3; $seat_idx < 6; $seat_idx++): ?>
                                        <?php 
                                        $seat_letter = $seat_letters[$seat_idx];
                                        $seat_number = $row . $seat_letter;
                                        $is_booked = in_array($seat_number, $booked_seats);
                                        ?>
                                        <button type="button" 
                                                class="seat <?php echo $is_booked ? 'occupied' : 'available'; ?>" 
                                                data-seat="<?php echo $seat_number; ?>"
                                                <?php echo $is_booked ? 'disabled' : ''; ?>>
                                            <?php echo $seat_letter; ?>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="selection-summary">
                    <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i> Kursi Terpilih
                    </h4>
                    <div class="selected-seats" id="selectedSeats">
                        <span style="color: #6b7280; font-style: italic;">Belum ada kursi dipilih</span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary continue-btn" id="continueBtn" disabled>
                        <i class="fas fa-arrow-right"></i> Lanjutkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedSeats = [];
        const maxPassengers = <?php echo $passengers; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const seats = document.querySelectorAll('.seat:not(.occupied)');
            const selectedSeatsContainer = document.getElementById('selectedSeats');
            const continueBtn = document.getElementById('continueBtn');

            seats.forEach(seat => {
                seat.addEventListener('click', function() {
                    const seatNumber = this.dataset.seat;
                    
                    if (this.classList.contains('selected')) {
                        // Deselect seat
                        this.classList.remove('selected');
                        this.classList.add('available');
                        selectedSeats = selectedSeats.filter(s => s !== seatNumber);
                    } else if (selectedSeats.length < maxPassengers) {
                        // Select seat
                        this.classList.remove('available');
                        this.classList.add('selected');
                        selectedSeats.push(seatNumber);
                    } else {
                        alert('Anda sudah memilih maksimal ' + maxPassengers + ' kursi.');
                        return;
                    }
                    
                    updateSelectedSeatsDisplay();
                    updateContinueButton();
                });
            });

            function updateSelectedSeatsDisplay() {
                if (selectedSeats.length === 0) {
                    selectedSeatsContainer.innerHTML = '<span style="color: #6b7280; font-style: italic;">Belum ada kursi dipilih</span>';
                } else {
                    selectedSeatsContainer.innerHTML = selectedSeats.map(seat => 
                        `<span class="seat-tag">${seat}</span>`
                    ).join('');
                }
            }

            function updateContinueButton() {
                if (selectedSeats.length === maxPassengers) {
                    continueBtn.disabled = false;
                    continueBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Lanjutkan (' + selectedSeats.length + '/' + maxPassengers + ')';
                    
                    // Add hidden inputs for selected seats
                    const existingInputs = document.querySelectorAll('input[name="seats[]"]');
                    existingInputs.forEach(input => input.remove());
                    
                    selectedSeats.forEach(seat => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'seats[]';
                        input.value = seat;
                        continueBtn.parentNode.appendChild(input);
                    });
                } else {
                    continueBtn.disabled = true;
                    continueBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Pilih ' + (maxPassengers - selectedSeats.length) + ' kursi lagi';
                }
            }
        });
    </script>
</body>
</html>
