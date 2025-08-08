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

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Ensure integers for SQL safety
$limit = (int)$limit;
$offset = (int)$offset;

// Build query for bookings with user and related data
$query = "SELECT b.*, u.full_name, u.email, h.name as hotel_name, t.flight_code, t.departure_city, t.arrival_city, p.payment_status 
        FROM bookings b 
        LEFT JOIN users u ON b.id_user = u.id 
        LEFT JOIN hotels h ON b.id_hotel = h.id 
        LEFT JOIN tickets t ON b.id_ticket = t.id 
        LEFT JOIN payments p ON b.id = p.id_booking 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR t.flight_code LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $query .= " AND b.booking_status = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $query .= " AND b.booking_type = ?";
    $params[] = $type_filter;
}

$query .= " ORDER BY b.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM bookings b 
                LEFT JOIN users u ON b.id_user = u.id 
                LEFT JOIN tickets t ON b.id_ticket = t.id 
                WHERE 1=1";
$count_params = [];

if (!empty($search)) {
    $count_query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR t.flight_code LIKE ?)";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

if (!empty($status_filter)) {
    $count_query .= " AND b.booking_status = ?";
    $count_params[] = $status_filter;
}

if (!empty($type_filter)) {
    $count_query .= " AND b.booking_type = ?";
    $count_params[] = $type_filter;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_bookings = $count_stmt->fetchColumn();
$total_pages = ceil($total_bookings / $limit);

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN booking_status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN booking_status = 'pending' THEN 1 END) as pending_bookings,
    COUNT(CASE WHEN booking_status = 'cancelled' THEN 1 END) as cancelled_bookings
    FROM bookings";
$stats_stmt = $pdo->query($stats_query);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --secondary-blue: #3b82f6;
            --light-blue: #dbeafe;
            --dark-gray: #374151;
            --medium-gray: #6b7280;
            --light-gray: #f3f4f6;
            --white: #ffffff;
            --red: #dc2626;
            --green: #16a34a;
            --orange: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 260px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: var(--white);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-logo {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 2rem;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin: 0.25rem 0;
        }

        .admin-nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .admin-nav i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: rgba(255,255,255,0.1);
            color: var(--white);
        }

        .admin-main {
            margin-left: 260px;
            flex: 1;
            padding: 2rem;
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--medium-gray);
            font-size: 0.875rem;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-pending { background-color: var(--orange); color: var(--white); }
        .badge-confirmed { background-color: var(--green); color: var(--white); }
        .badge-cancelled { background-color: var(--red); color: var(--white); }
        .badge-ticket-only { background-color: var(--secondary-blue); color: var(--white); }
        .badge-hotel-only { background-color: var(--green); color: var(--white); }
        .badge-ticket-hotel { background-color: var(--primary-blue); color: var(--white); }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .form-input, .form-select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            flex: 1;
            min-width: 150px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary { background-color: var(--primary-blue); color: var(--white); }
        .btn-primary:hover { background-color: var(--secondary-blue); }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.8rem; }

        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--dark-gray);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: var(--dark-gray);
        }

        .pagination a:hover { background-color: var(--light-blue); }
        .pagination .current {
            background-color: var(--primary-blue);
            color: var(--white);
            border-color: var(--primary-blue);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-logo">
            <h3 style="color: var(--white); margin: 0;">Admin Panel</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem; margin: 0.5rem 0 0;">
                Selamat datang, <?php echo htmlspecialchars($admin['full_name']); ?>
            </p>
        </div>
        
        <ul class="admin-nav">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="bookings.php" class="active"><i class="fas fa-calendar-alt"></i> Pemesanan</a></li>
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
        <div class="card">
            <h1 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                <i class="fas fa-calendar-alt"></i> Kelola Pemesanan
            </h1>
            <p style="color: var(--dark-gray); margin: 0;">
                Kelola semua pemesanan tiket dan hotel
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: var(--primary-blue);"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Pemesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--green);"><?php echo $stats['confirmed_bookings']; ?></div>
                <div class="stat-label">Dikonfirmasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--orange);"><?php echo $stats['pending_bookings']; ?></div>
                <div class="stat-label">Menunggu</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--red);"><?php echo $stats['cancelled_bookings']; ?></div>
                <div class="stat-label">Dibatalkan</div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card">
            <form method="GET" class="search-filters">
                <input type="text" name="search" class="form-input" placeholder="Cari nama, email, atau kode penerbangan..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                </select>
                <select name="type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="ticket_only" <?php echo $type_filter === 'ticket_only' ? 'selected' : ''; ?>>Tiket Saja</option>
                    <option value="hotel_only" <?php echo $type_filter === 'hotel_only' ? 'selected' : ''; ?>>Hotel Saja</option>
                    <option value="ticket_hotel" <?php echo $type_filter === 'ticket_hotel' ? 'selected' : ''; ?>>Tiket + Hotel</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <a href="bookings.php" class="btn" style="background-color: var(--medium-gray); color: var(--white);">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </form>

            <!-- Bookings Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Tipe Pemesanan</th>
                            <th>Detail</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--medium-gray);">
                                    Tidak ada pemesanan ditemukan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong><br>
                                        <small style="color: var(--medium-gray);"><?php echo htmlspecialchars($booking['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo str_replace('_', '-', $booking['booking_type']); ?>">
                                            <?php 
                                            echo match($booking['booking_type']) {
                                                'ticket_only' => 'Tiket Saja',
                                                'hotel_only' => 'Hotel Saja',
                                                'ticket_hotel' => 'Tiket + Hotel'
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['flight_code']): ?>
                                            <div><strong>Penerbangan:</strong> <?php echo $booking['flight_code']; ?></div>
                                            <div><small><?php echo $booking['departure_city']; ?> → <?php echo $booking['arrival_city']; ?></small></div>
                                        <?php endif; ?>
                                        <?php if ($booking['hotel_name']): ?>
                                            <div><strong>Hotel:</strong> <?php echo htmlspecialchars($booking['hotel_name']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($booking['check_in_date']): ?>
                                            <div><small>Check-in: <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['booking_status']; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($booking['payment_status']): ?>
                                            <span class="badge badge-<?php echo $booking['payment_status']; ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--medium-gray);">Belum ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php 
                    $query_params = http_build_query(array_filter([
                        'search' => $search, 
                        'status' => $status_filter,
                        'type' => $type_filter
                    ]));
                    $query_string = !empty($query_params) ? '&' . $query_params : '';
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p style="color: var(--medium-gray); font-size: 0.875rem; margin-top: 1rem;">
                Menampilkan <?php echo count($bookings); ?> dari <?php echo $total_bookings; ?> pemesanan
            </p>
        </div>
    </div>
</body>
</html>
