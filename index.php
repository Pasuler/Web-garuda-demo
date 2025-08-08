<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('login.php');
}

$user = getUserData();

// Check if user data is valid
if (!$user || !isset($user['full_name'])) {
    // If user data is not found, destroy session and redirect
    session_destroy();
    redirectTo('login.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garuda Indonesia - Tiket & Hotel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="logo-container">
                <img src="Assets/images/Logo(Horizontal).png" alt="Garuda Indonesia" class="logo">
                <span class="brand-text">Garuda Indonesia</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link active">Beranda</a></li>
                <li><a href="pages/booking/booking_cart.php" class="nav-link">Pemesanan Saya</a></li>
                <li><a href="pages/user/chat.php" class="nav-link">Customer Service</a></li>
                <li><a href="pages/user/profile.php" class="nav-link">Profil</a></li>
                <li><a href="logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content fade-in-up">
            <h1>Selamat Datang, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Nikmati perjalanan tak terlupakan dengan Garuda Indonesia. Pesan tiket pesawat dan hotel dalam satu platform yang mudah dan terpercaya.</p>
            <div style="margin-top: 2rem;">
                <a href="#booking-section" class="btn btn-primary pulse">Mulai Pemesanan</a>
                <a href="pages/booking/booking_cart.php" class="btn btn-secondary">Lihat Pemesanan</a>
            </div>
        </div>
    </section>

    <!-- Booking Options Section -->
    <section class="section" id="booking-section">
        <div class="container">
            <h2 class="text-center mb-4" style="color: var(--primary-blue); font-size: 2.5rem;">Pilih Jenis Pemesanan</h2>
            <p class="text-center mb-5" style="font-size: 1.2rem; color: var(--dark-gray);">Kami menyediakan berbagai pilihan pemesanan sesuai kebutuhan perjalanan Anda</p>
            
            <div class="booking-options fade-in-up">
                <!-- Tiket + Hotel -->
                <div class="booking-card" onclick="selectBooking('ticket_hotel')">
                    <div class="booking-icon">
                        <i class="fas fa-plane"></i> + <i class="fas fa-hotel"></i>
                    </div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Tiket Pesawat + Hotel</h3>
                    <p style="color: var(--dark-gray); margin-bottom: 1.5rem;">Paket lengkap perjalanan dengan tiket pesawat dan akomodasi hotel. Hemat waktu dan biaya dengan sekali pemesanan.</p>
                    <div class="btn btn-outline">Pilih Paket</div>
                </div>

                <!-- Hotel Only -->
                <div class="booking-card" onclick="selectBooking('hotel_only')">
                    <div class="booking-icon">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Hotel Saja</h3>
                    <p style="color: var(--dark-gray); margin-bottom: 1.5rem;">Cari dan pesan hotel terbaik di Bali dengan berbagai pilihan lokasi dan fasilitas menarik.</p>
                    <div class="btn btn-outline">Pilih Hotel</div>
                </div>

                <!-- Ticket Only -->
                <div class="booking-card" onclick="selectBooking('ticket_only')">
                    <div class="booking-icon">
                        <i class="fas fa-plane"></i>
                    </div>
                    <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Tiket Pesawat Saja</h3>
                    <p style="color: var(--dark-gray); margin-bottom: 1.5rem;">Pesan tiket pesawat Garuda Indonesia dengan berbagai pilihan kelas dan jadwal penerbangan.</p>
                    <div class="btn btn-outline">Pilih Tiket</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" style="background: var(--light-gray);">
        <div class="container">
            <h2 class="text-center mb-5" style="color: var(--primary-blue); font-size: 2.5rem;">Mengapa Memilih Kami?</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-center fade-in-up">
                        <div class="card-icon" style="margin: 0 auto 1rem;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">Terpercaya & Aman</h4>
                        <p>Sistem pembayaran yang aman dan terjamin dengan verifikasi langsung dari admin kami.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center fade-in-up">
                        <div class="card-icon" style="margin: 0 auto 1rem;">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">Customer Service 24/7</h4>
                        <p>Tim customer service kami siap membantu Anda kapan saja untuk pengalaman perjalanan terbaik.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center fade-in-up">
                        <div class="card-icon" style="margin: 0 auto 1rem;">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">Kualitas Premium</h4>
                        <p>Hotel dan penerbangan berkualitas tinggi dengan standar pelayanan terbaik dari Garuda Indonesia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <img src="Assets/images/Logo(Horizontal).png" alt="Garuda Indonesia" class="footer-logo">
                    <h3>Garuda Indonesia</h3>
                    <p>Maskapai penerbangan nasional Indonesia yang melayani rute domestik dan internasional dengan standar pelayanan terbaik.</p>
                </div>
                <div class="footer-section">
                    <h3>Layanan</h3>
                    <ul style="list-style: none;">
                        <li><a href="#" style="color: var(--white); text-decoration: none;">Tiket Pesawat</a></li>
                        <li><a href="#" style="color: var(--white); text-decoration: none;">Reservasi Hotel</a></li>
                        <li><a href="#" style="color: var(--white); text-decoration: none;">Customer Service</a></li>
                        <li><a href="#" style="color: var(--white); text-decoration: none;">Bantuan</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Kontak</h3>
                    <p><i class="fas fa-phone"></i> +62 21 2351 9999</p>
                    <p><i class="fas fa-envelope"></i> info@garuda-indonesia.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> Jakarta, Indonesia</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Garuda Indonesia. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        function selectBooking(type) {
            // Remove selected class from all cards
            document.querySelectorAll('.booking-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Redirect to booking page with type
            setTimeout(() => {
                window.location.href = `pages/booking/booking.php?type=${type}`;
            }, 500);
        }

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
