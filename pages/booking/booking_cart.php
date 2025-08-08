<?php
require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user = getUserData();

// Get user bookings
$bookings_stmt = $pdo->prepare("
    SELECT b.*, h.name as hotel_name, h.location, t.flight_code, t.departure_city, t.arrival_city, 
           t.flight_date, t.departure_time, p.payment_status, p.payment_date, b.seat_numbers
    FROM bookings b 
    LEFT JOIN hotels h ON b.id_hotel = h.id
    LEFT JOIN tickets t ON b.id_ticket = t.id
    LEFT JOIN payments p ON b.id = p.id_booking
    WHERE b.id_user = ?
    ORDER BY b.created_at DESC
");
$bookings_stmt->execute([$user['id']]);
$bookings = $bookings_stmt->fetchAll();

// Check for success message
$show_success = isset($_GET['updated']) && $_GET['updated'] == '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Saya - Garuda Indonesia</title>
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
                <li><a href="booking_cart.php" class="nav-link active">Pemesanan Saya</a></li>
                <li><a href="../user/chat.php" class="nav-link">Customer Service</a></li>
                <li><a href="../user/profile.php" class="nav-link">Profil</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </nav>
    </header>

    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-list-alt"></i>
                </div>
                <h2>Pemesanan Saya</h2>
            </div>

            <?php if ($show_success): ?>
                <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <i class="fas fa-check-circle"></i> Kursi berhasil dipilih! Silakan lanjutkan ke pembayaran.
                </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <h3 style="color: #6b7280; margin-bottom: 1rem;">Belum Ada Pemesanan</h3>
                    <p style="color: #9ca3af; margin-bottom: 2rem;">Anda belum memiliki riwayat pemesanan. Mulai perjalanan Anda sekarang!</p>
                    <a href="../../index.php" class="btn btn-primary">
                        <i class="fas fa-plane"></i> Mulai Pesan
                    </a>
                </div>
            <?php else: ?>
                <div class="booking-list">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-item" style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: #f8fafc;">
                            <!-- Booking Header -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h4 style="color: var(--primary-blue); margin-bottom: 0.25rem;">
                                        Booking #<?php echo $booking['id']; ?>
                                    </h4>
                                    <small style="color: #6b7280;">
                                        <?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <?php
                                    $status_colors = [
                                        'unpaid' => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e', 'icon' => 'clock', 'label' => 'Belum Bayar'],
                                        'pending_verification' => ['bg' => '#dbeafe', 'border' => '#3b82f6', 'text' => '#1e40af', 'icon' => 'hourglass-half', 'label' => 'Verifikasi'],
                                        'paid' => ['bg' => '#d1fae5', 'border' => '#10b981', 'text' => '#065f46', 'icon' => 'check-circle', 'label' => 'Terbayar'],
                                        'failed' => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#dc2626', 'icon' => 'times-circle', 'label' => 'Ditolak']
                                    ];
                                    $status = $status_colors[$booking['payment_status']] ?? $status_colors['unpaid'];
                                    ?>
                                    <span style="background: <?php echo $status['bg']; ?>; border: 1px solid <?php echo $status['border']; ?>; color: <?php echo $status['text']; ?>; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem; font-weight: 500;">
                                        <i class="fas fa-<?php echo $status['icon']; ?>"></i> <?php echo $status['label']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Booking Details -->
                            <div class="row">
                                <?php if ($booking['id_ticket']): ?>
                                <div class="col-md-6">
                                    <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <h6 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                                            <i class="fas fa-plane"></i> Penerbangan
                                        </h6>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;"><?php echo $booking['flight_code']; ?></p>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                                            <?php echo $booking['departure_city']; ?> → <?php echo $booking['arrival_city']; ?>
                                        </p>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                                            <?php echo date('d M Y', strtotime($booking['flight_date'])); ?> - 
                                            <?php echo date('H:i', strtotime($booking['departure_time'])); ?>
                                        </p>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                                            <?php echo $booking['passengers']; ?> penumpang
                                        </p>
                                        <?php if ($booking['seat_numbers']): ?>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                                            <strong>Kursi:</strong> 
                                            <?php 
                                            $seats = explode(',', $booking['seat_numbers']);
                                            foreach ($seats as $seat) {
                                                echo '<span style="background: #3b82f6; color: white; padding: 0.125rem 0.5rem; border-radius: 12px; margin-right: 0.25rem; font-size: 0.75rem;">' . trim($seat) . '</span>';
                                            }
                                            ?>
                                        </p>
                                        <?php else: ?>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem; color: #f59e0b;">
                                            <a href="seat_selection.php?ticket_id=<?php echo $booking['id_ticket']; ?>&passengers=<?php echo $booking['passengers']; ?>&booking_id=<?php echo $booking['id']; ?>" style="color: #f59e0b; text-decoration: none;">
                                                <i class="fas fa-chair"></i> Pilih Kursi
                                            </a>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($booking['id_hotel']): ?>
                                <div class="col-md-6">
                                    <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <h6 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                                            <i class="fas fa-hotel"></i> Hotel
                                        </h6>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;"><?php echo $booking['hotel_name']; ?></p>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;"><?php echo $booking['location']; ?></p>
                                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                                            <?php echo date('d M Y', strtotime($booking['check_in_date'])); ?> - 
                                            <?php echo date('d M Y', strtotime($booking['check_out_date'])); ?>
                                        </p>
                                        <p style="margin: 0; font-size: 0.875rem;">
                                            <?php echo $booking['rooms']; ?> kamar
                                        </p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Booking Footer -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                <div>
                                    <span style="font-size: 0.875rem; color: #6b7280;">Total: </span>
                                    <span style="font-size: 1.25rem; font-weight: bold; color: var(--primary-blue);">
                                        Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>
                                    </span>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <?php if ($booking['payment_status'] == 'unpaid' || $booking['payment_status'] == 'failed'): ?>
                                        <?php if ($booking['id_ticket'] && !$booking['seat_numbers']): ?>
                                            <a href="seat_selection.php?ticket_id=<?php echo $booking['id_ticket']; ?>&passengers=<?php echo $booking['passengers']; ?>&booking_id=<?php echo $booking['id']; ?>" class="btn btn-outline">
                                                <i class="fas fa-chair"></i> Pilih Kursi
                                            </a>
                                        <?php endif; ?>
                                        <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-credit-card"></i> Bayar Sekarang
                                        </a>
                                    <?php elseif ($booking['payment_status'] == 'pending_verification'): ?>
                                        <?php if ($booking['id_ticket'] && $booking['seat_numbers']): ?>
                                            <a href="seat_selection.php?ticket_id=<?php echo $booking['id_ticket']; ?>&passengers=<?php echo $booking['passengers']; ?>&booking_id=<?php echo $booking['id']; ?>" class="btn" style="background: #f3f4f6; color: #6b7280; font-size: 0.875rem;">
                                                <i class="fas fa-chair"></i> Ubah Kursi
                                            </a>
                                        <?php endif; ?>
                                        <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-eye"></i> Lihat Status
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #10b981; font-weight: 500;">
                                            <i class="fas fa-check-circle"></i> Pembayaran Dikonfirmasi
                                        </span>
                                    <?php endif; ?>
                                    
                                    <a href="../user/chat.php" class="btn btn-outline">
                                        <i class="fas fa-comments"></i> Chat
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Quick Actions -->
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
                    <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">Ingin Melakukan Pemesanan Lagi?</h4>
                    <a href="../../index.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Pemesanan Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto refresh disabled - status updates only when page is manually refreshed
        // setTimeout(function() {
        //     location.reload();
        // }, 30000);
    </script>
</body>
</html>
