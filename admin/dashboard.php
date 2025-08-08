<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php');
}

$admin = getUserData();

// Check if admin data is valid
if (!$admin || !isset($admin['full_name'])) {
    // If admin data is not found, destroy session and redirect
    session_destroy();
    redirectTo('../login.php');
}

// Get statistics
$stats = [];

// Total users
$user_stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['users'] = $user_stmt->fetch()['total'];

// Total bookings
$booking_stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
$stats['bookings'] = $booking_stmt->fetch()['total'];

// Pending payments
$pending_stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE payment_status = 'pending'");
$stats['pending_payments'] = $pending_stmt->fetch()['total'];

// Total revenue
$revenue_stmt = $pdo->query("SELECT SUM(payment_amount) as total FROM payments WHERE payment_status = 'paid'");
$stats['revenue'] = $revenue_stmt->fetch()['total'] ?: 0;

// Recent bookings
$recent_bookings_stmt = $pdo->query("
    SELECT b.*, u.full_name, u.email, h.name as hotel_name, t.flight_code
    FROM bookings b 
    LEFT JOIN users u ON b.id_user = u.id
    LEFT JOIN hotels h ON b.id_hotel = h.id
    LEFT JOIN tickets t ON b.id_ticket = t.id
    ORDER BY b.created_at DESC 
    LIMIT 10
");
$recent_bookings = $recent_bookings_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Garuda Indonesia</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-gray) 100%);
            padding: 2rem 1rem;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .admin-main {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
        }
        
        .admin-nav li {
            margin-bottom: 0.5rem;
        }
        
        .admin-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--white);
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover,
        .admin-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-blue);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-blue);
        }
        
        .stat-label {
            color: var(--dark-gray);
            margin-top: 0.5rem;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-table th {
            background: var(--primary-blue);
            color: var(--white);
            padding: 1rem;
            text-align: left;
        }
        
        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="../Assets/images/Logo(Vertikal).png" alt="Garuda Indonesia" style="height: 60px; margin-bottom: 1rem;">
            <h3 style="color: var(--white); margin: 0;">Admin Panel</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem; margin: 0.5rem 0 0;">
                Selamat datang, <?php echo htmlspecialchars($admin['full_name']); ?>
            </p>
        </div>
        
        <ul class="admin-nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="bookings.php"><i class="fas fa-calendar-alt"></i> Pemesanan</a></li>
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
            <li><a href="hotels.php"><i class="fas fa-hotel"></i> Kelola Hotel</a></li>
            <li><a href="tickets.php"><i class="fas fa-plane"></i> Kelola Tiket</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Pengguna</a></li>
            <li><a href="chats.php"><i class="fas fa-comments"></i> Customer Service</a></li>
            <li><a href="../index.php"><i class="fas fa-globe"></i> Lihat Website</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="card" style="margin-bottom: 2rem;">
            <h1 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                <i class="fas fa-tachometer-alt"></i> Dashboard Admin
            </h1>
            <p style="color: var(--dark-gray); margin: 0;">
                Kelola sistem pemesanan Garuda Indonesia dari satu tempat
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">
                    <i class="fas fa-users" style="margin-right: 0.5rem;"></i>
                    <?php echo number_format($stats['users']); ?>
                </div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">
                    <i class="fas fa-calendar-alt" style="margin-right: 0.5rem;"></i>
                    <?php echo number_format($stats['bookings']); ?>
                </div>
                <div class="stat-label">Total Pemesanan</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">
                    <i class="fas fa-clock" style="margin-right: 0.5rem;"></i>
                    <?php echo number_format($stats['pending_payments']); ?>
                </div>
                <div class="stat-label">Pembayaran Pending</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">
                    <i class="fas fa-money-bill-wave" style="margin-right: 0.5rem;"></i>
                    Rp <?php echo number_format($stats['revenue'], 0, ',', '.'); ?>
                </div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-list"></i>
                </div>
                <h2>Pemesanan Terbaru</h2>
            </div>

            <?php if (count($recent_bookings) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Jenis</th>
                            <th>Detail</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($booking['email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <?php 
                                switch($booking['booking_type']) {
                                    case 'ticket_only': echo 'Tiket Saja'; break;
                                    case 'hotel_only': echo 'Hotel Saja'; break;
                                    case 'ticket_hotel': echo 'Tiket + Hotel'; break;
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($booking['flight_code']): ?>
                                    <i class="fas fa-plane"></i> <?php echo $booking['flight_code']; ?><br>
                                <?php endif; ?>
                                <?php if ($booking['hotel_name']): ?>
                                    <i class="fas fa-hotel"></i> <?php echo htmlspecialchars($booking['hotel_name']); ?>
                                <?php endif; ?>
                            </td>
                            <td>Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                    <?php 
                                    switch($booking['booking_status']) {
                                        case 'pending': echo 'Pending'; break;
                                        case 'confirmed': echo 'Dikonfirmasi'; break;
                                        case 'cancelled': echo 'Dibatalkan'; break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--dark-gray); padding: 2rem;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                    Belum ada pemesanan
                </p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-plus-circle"></i> Aksi Cepat
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="hotels.php?action=add" class="btn btn-outline">
                            <i class="fas fa-hotel"></i> Tambah Hotel Baru
                        </a>
                        <a href="tickets.php?action=add" class="btn btn-outline">
                            <i class="fas fa-plane"></i> Tambah Penerbangan Baru
                        </a>
                        <a href="payments.php?status=pending" class="btn btn-outline">
                            <i class="fas fa-clock"></i> Verifikasi Pembayaran
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-chart-line"></i> Laporan Singkat
                    </h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-arrow-up" style="color: var(--success);"></i>
                            Pemesanan hari ini: 
                            <?php
                            $today_stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE DATE(created_at) = CURDATE()");
                            echo $today_stmt->fetch()['total'];
                            ?>
                        </li>
                        <li style="margin-bottom: 0.5rem;">
                            <i class="fas fa-money-bill-wave" style="color: var(--gold);"></i>
                            Pendapatan hari ini: Rp 
                            <?php
                            $today_revenue_stmt = $pdo->query("
                                SELECT SUM(p.payment_amount) as total 
                                FROM payments p 
                                JOIN bookings b ON p.id_booking = b.id 
                                WHERE p.payment_status = 'paid' AND DATE(b.created_at) = CURDATE()
                            ");
                            echo number_format($today_revenue_stmt->fetch()['total'] ?: 0, 0, ',', '.');
                            ?>
                        </li>
                        <li>
                            <i class="fas fa-clock" style="color: var(--warning);"></i>
                            Pembayaran perlu verifikasi: <?php echo $stats['pending_payments']; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
