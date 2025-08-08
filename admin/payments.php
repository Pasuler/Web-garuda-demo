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

$error = '';
$success = '';

// Handle payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $payment_id = intval($_POST['payment_id']);
    
    if ($action == 'verify' || $action == 'reject') {
        $new_status = ($action == 'verify') ? 'paid' : 'failed';
        
        try {
            $pdo->beginTransaction();
            
            // Update payment status
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET payment_status = ?, verified_by = ?, verified_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $admin['id'], $payment_id]);
            
            // Update booking status
            if ($new_status == 'paid') {
                $pdo->query("
                    UPDATE bookings b 
                    JOIN payments p ON b.id = p.id_booking 
                    SET b.booking_status = 'confirmed' 
                    WHERE p.id = $payment_id
                ");
            } else {
                $pdo->query("
                    UPDATE bookings b 
                    JOIN payments p ON b.id = p.id_booking 
                    SET b.booking_status = 'cancelled' 
                    WHERE p.id = $payment_id
                ");
            }
            
            $pdo->commit();
            
            // Get booking and user info for notification
            $notif_stmt = $pdo->prepare("
                SELECT b.id as booking_id, b.id_user, u.full_name, u.email
                FROM bookings b 
                JOIN payments p ON b.id = p.id_booking
                JOIN users u ON b.id_user = u.id
                WHERE p.id = ?
            ");
            $notif_stmt->execute([$payment_id]);
            $booking_info = $notif_stmt->fetch();
            
            if ($booking_info) {
                // Send notification to user via chat
                if ($new_status == 'paid') {
                    $message = "✅ PEMBAYARAN DIKONFIRMASI\n\nSelamat! Pembayaran untuk Booking #{$booking_info['booking_id']} telah berhasil dikonfirmasi oleh admin.\n\nPemesanan Anda sekarang sudah aktif. Terima kasih telah menggunakan layanan Garuda Indonesia!";
                } else {
                    $message = "❌ PEMBAYARAN DITOLAK\n\nMaaf, pembayaran untuk Booking #{$booking_info['booking_id']} tidak dapat dikonfirmasi.\n\nSilakan hubungi customer service atau unggah ulang bukti pembayaran yang benar. Kami siap membantu Anda 24/7.";
                }
                
                $chat_stmt = $pdo->prepare("
                    INSERT INTO chats (id_user, message, is_from_admin, created_at) 
                    VALUES (?, ?, 1, NOW())
                ");
                $chat_stmt->execute([$booking_info['id_user'], $message]);
            }
            
            if ($action == 'verify') {
                $success = 'Pembayaran berhasil diverifikasi dan notifikasi telah dikirim!';
            } else {
                $success = 'Pembayaran ditolak dan notifikasi telah dikirim!';
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get payments with booking and user details
$where_clause = '';
$params = [];

if ($status_filter != 'all') {
    $where_clause = 'WHERE p.payment_status = ?';
    $params[] = $status_filter;
}

$payments_stmt = $pdo->prepare("
    SELECT 
        p.*,
        b.booking_type,
        b.total_amount as booking_amount,
        b.passengers,
        b.rooms,
        b.check_in_date,
        b.check_out_date,
        u.full_name,
        u.email,
        h.name as hotel_name,
        t.flight_code,
        t.departure_city,
        t.arrival_city,
        admin.full_name as verified_by_name
    FROM payments p
    JOIN bookings b ON p.id_booking = b.id
    JOIN users u ON b.id_user = u.id
    LEFT JOIN hotels h ON b.id_hotel = h.id
    LEFT JOIN tickets t ON b.id_ticket = t.id
    LEFT JOIN users admin ON p.verified_by = admin.id
    $where_clause
    ORDER BY p.created_at DESC
");
$payments_stmt->execute($params);
$payments = $payments_stmt->fetchAll();

// Get payment statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid,
        COUNT(CASE WHEN payment_status = 'failed' THEN 1 END) as failed,
        SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END) as total_revenue
    FROM payments
");
$stats = $stats_stmt->fetch();

// Ensure stats values are not null
$stats['pending'] = $stats['pending'] ?? 0;
$stats['paid'] = $stats['paid'] ?? 0;
$stats['failed'] = $stats['failed'] ?? 0;
$stats['total_revenue'] = $stats['total_revenue'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Admin Garuda Indonesia</title>
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-pending .stat-value { color: #f59e0b; }
        .stat-paid .stat-value { color: #10b981; }
        .stat-failed .stat-value { color: #ef4444; }
        .stat-revenue .stat-value { color: var(--primary-blue); }
        
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
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-unpaid {
            background: #f3f4f6;
            color: #374151;
        }
        
        .receipt-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .receipt-modal.active {
            display: flex;
        }
        
        .receipt-content {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            background: var(--light-gray);
            padding: 0.5rem;
            border-radius: 10px;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark-gray);
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="../Assets/images/Logo(Vertikal).png" alt="Garuda Indonesia" style="height: 60px; margin-bottom: 1rem;">
            <h3 style="color: var(--white); margin: 0;">Admin Panel</h3>
        </div>
        
        <ul class="admin-nav">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="bookings.php"><i class="fas fa-calendar-alt"></i> Pemesanan</a></li>
            <li><a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
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
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h1>Verifikasi Pembayaran</h1>
            </div>

            <?php if ($error): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card stat-pending">
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Menunggu Verifikasi</div>
                </div>
                <div class="stat-card stat-paid">
                    <div class="stat-value"><?php echo $stats['paid']; ?></div>
                    <div class="stat-label">Sudah Terbayar</div>
                </div>
                <div class="stat-card stat-failed">
                    <div class="stat-value"><?php echo $stats['failed']; ?></div>
                    <div class="stat-label">Ditolak</div>
                </div>
                <div class="stat-card stat-revenue">
                    <div class="stat-value">Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Pendapatan</div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="card">
            <div class="filter-tabs">
                <a href="payments.php?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Semua
                </a>
                <a href="payments.php?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending (<?php echo $stats['pending']; ?>)
                </a>
                <a href="payments.php?status=paid" class="filter-tab <?php echo $status_filter == 'paid' ? 'active' : ''; ?>">
                    <i class="fas fa-check"></i> Terbayar
                </a>
                <a href="payments.php?status=failed" class="filter-tab <?php echo $status_filter == 'failed' ? 'active' : ''; ?>">
                    <i class="fas fa-times"></i> Ditolak
                </a>
            </div>

            <!-- Payments Table -->
            <?php if (count($payments) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Detail Pemesanan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Bukti Pembayaran</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>#<?php echo $payment['id']; ?></td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($payment['full_name']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($payment['email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong style="color: var(--primary-blue);">
                                        <?php 
                                        switch($payment['booking_type']) {
                                            case 'ticket_only': echo 'Tiket Saja'; break;
                                            case 'hotel_only': echo 'Hotel Saja'; break;
                                            case 'ticket_hotel': echo 'Tiket + Hotel'; break;
                                        }
                                        ?>
                                    </strong><br>
                                    <?php if ($payment['flight_code']): ?>
                                        <small><i class="fas fa-plane"></i> <?php echo $payment['flight_code']; ?> 
                                        (<?php echo $payment['departure_city']; ?> → <?php echo $payment['arrival_city']; ?>)</small><br>
                                    <?php endif; ?>
                                    <?php if ($payment['hotel_name']): ?>
                                        <small><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($payment['hotel_name']); ?></small><br>
                                    <?php endif; ?>
                                    <small style="color: #666;">
                                        <?php if ($payment['passengers'] > 1): ?>
                                            <?php echo $payment['passengers']; ?> penumpang
                                        <?php endif; ?>
                                        <?php if ($payment['rooms'] > 1): ?>
                                            <?php echo $payment['rooms']; ?> kamar
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <strong>Rp <?php echo number_format($payment['payment_amount'], 0, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                    <?php 
                                    switch($payment['payment_status']) {
                                        case 'unpaid': echo 'Belum Bayar'; break;
                                        case 'pending': echo 'Menunggu Verifikasi'; break;
                                        case 'paid': echo 'Terbayar'; break;
                                        case 'failed': echo 'Ditolak'; break;
                                    }
                                    ?>
                                </span>
                                <?php if ($payment['verified_by_name']): ?>
                                    <br><small style="color: #666;">
                                        Oleh: <?php echo htmlspecialchars($payment['verified_by_name']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($payment['receipt_image']) && !empty($payment['receipt_image'])): ?>
                                    <button class="btn btn-secondary btn-sm" 
                                            onclick="showReceipt('../uploads/receipts/<?php echo $payment['receipt_image']; ?>')">
                                        <i class="fas fa-eye"></i> Lihat Bukti
                                    </button>
                                <?php else: ?>
                                    <span style="color: #999;">Belum upload</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <?php echo date('d/m/Y', strtotime($payment['created_at'])); ?><br>
                                    <small style="color: #666;"><?php echo date('H:i', strtotime($payment['created_at'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <?php if ($payment['payment_status'] == 'pending'): ?>
                                    <div class="action-buttons">
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="verify">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <button type="submit" class="btn btn-sm" 
                                                    style="background: #10b981; color: white;"
                                                    onclick="return confirm('Verifikasi pembayaran ini sebagai TERBAYAR?')">
                                                <i class="fas fa-check"></i> Verifikasi
                                            </button>
                                        </form>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                            <button type="submit" class="btn btn-sm" 
                                                    style="background: #dc2626; color: white;"
                                                    onclick="return confirm('Tolak pembayaran ini? Pemesanan akan dibatalkan.')">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">
                                        <?php echo $payment['payment_status'] == 'paid' ? 'Sudah diverifikasi' : 'Sudah ditolak'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--dark-gray); padding: 2rem;">
                    <i class="fas fa-credit-card" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                    Tidak ada data pembayaran
                    <?php if ($status_filter != 'all'): ?>
                        dengan status <?php echo $status_filter; ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="receipt-modal">
        <div class="receipt-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="color: var(--primary-blue); margin: 0;">
                    <i class="fas fa-receipt"></i> Bukti Pembayaran
                </h3>
                <button onclick="closeReceipt()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="receiptContent"></div>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        function showReceipt(imagePath) {
            const modal = document.getElementById('receiptModal');
            const content = document.getElementById('receiptContent');
            
            content.innerHTML = `
                <div style="text-align: center;">
                    <img src="${imagePath}" alt="Bukti Pembayaran" 
                        style="max-width: 100%; max-height: 60vh; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                </div>
            `;
            
            modal.classList.add('active');
        }
        
        function closeReceipt() {
            document.getElementById('receiptModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('receiptModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReceipt();
            }
        });
        
        // Auto-refresh page every 30 seconds for pending payments
        if (window.location.search.includes('status=pending') || window.location.search === '') {
            setTimeout(function() {
                location.reload();
            }, 30000);
        }
    </script>
</body>
</html>
