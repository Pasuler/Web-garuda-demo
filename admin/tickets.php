<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php');
}

$admin = getUserData();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add' || $action == 'edit') {
        $flight_code = sanitizeInput($_POST['flight_code']);
        $departure_city = sanitizeInput($_POST['departure_city']);
        $arrival_city = sanitizeInput($_POST['arrival_city']);
        $flight_date = $_POST['flight_date'];
        $departure_time = $_POST['departure_time'];
        $arrival_time = $_POST['arrival_time'];
        $seat_type = $_POST['seat_type'];
        $price = floatval($_POST['price']);
        $available_seats = intval($_POST['available_seats']);
        $total_seats = intval($_POST['total_seats']);
        
        try {
            if ($action == 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO tickets (flight_code, departure_city, arrival_city, flight_date, departure_time, arrival_time, seat_type, price, available_seats, total_seats) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$flight_code, $departure_city, $arrival_city, $flight_date, $departure_time, $arrival_time, $seat_type, $price, $available_seats, $total_seats]);
                $success = 'Tiket penerbangan berhasil ditambahkan!';
            } else {
                $ticket_id = intval($_POST['ticket_id']);
                $stmt = $pdo->prepare("
                    UPDATE tickets 
                    SET flight_code=?, departure_city=?, arrival_city=?, flight_date=?, departure_time=?, arrival_time=?, seat_type=?, price=?, available_seats=?, total_seats=? 
                    WHERE id=?
                ");
                $stmt->execute([$flight_code, $departure_city, $arrival_city, $flight_date, $departure_time, $arrival_time, $seat_type, $price, $available_seats, $total_seats, $ticket_id]);
                $success = 'Tiket penerbangan berhasil diupdate!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    } elseif ($action == 'delete') {
        $ticket_id = intval($_POST['ticket_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $success = 'Tiket penerbangan berhasil dihapus!';
        } catch (Exception $e) {
            $error = 'Tidak dapat menghapus tiket: ' . $e->getMessage();
        }
    }
}

// Get tickets
$tickets_stmt = $pdo->query("SELECT * FROM tickets ORDER BY flight_date ASC, departure_time ASC");
$tickets = $tickets_stmt->fetchAll();

// Get ticket for editing
$edit_ticket = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_ticket = $edit_stmt->fetch();
}

// Indonesian cities
$cities = [
    'Jakarta', 'Denpasar', 'Surabaya', 'Medan', 'Makassar', 'Yogyakarta', 
    'Semarang', 'Bandung', 'Palembang', 'Balikpapan', 'Manado', 'Pontianak'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tiket - Admin Garuda Indonesia</title>
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
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .seat-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .seat-economy {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .seat-business {
            background: #fef3c7;
            color: #92400e;
        }
        
        .seat-first {
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
        </div>
        
        <ul class="admin-nav">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="bookings.php"><i class="fas fa-calendar-alt"></i> Pemesanan</a></li>
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
            <li><a href="hotels.php"><i class="fas fa-hotel"></i> Kelola Hotel</a></li>
            <li><a href="tickets.php" class="active"><i class="fas fa-plane"></i> Kelola Tiket</a></li>
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
                    <i class="fas fa-plane"></i>
                </div>
                <h1>Kelola Tiket Penerbangan</h1>
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

            <!-- Add/Edit Ticket Form -->
            <form method="POST" action="" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="<?php echo $edit_ticket ? 'edit' : 'add'; ?>">
                <?php if ($edit_ticket): ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $edit_ticket['id']; ?>">
                <?php endif; ?>
                
                <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                    <?php echo $edit_ticket ? 'Edit Tiket Penerbangan' : 'Tambah Tiket Penerbangan Baru'; ?>
                </h3>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Kode Penerbangan</label>
                            <input type="text" name="flight_code" class="form-control" required 
                                   placeholder="Contoh: GA101"
                                   value="<?php echo $edit_ticket ? htmlspecialchars($edit_ticket['flight_code']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Kota Keberangkatan</label>
                            <select name="departure_city" class="form-control" required>
                                <option value="">Pilih kota...</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city; ?>" 
                                            <?php echo ($edit_ticket && $edit_ticket['departure_city'] == $city) ? 'selected' : ''; ?>>
                                        <?php echo $city; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Kota Tujuan</label>
                            <select name="arrival_city" class="form-control" required>
                                <option value="">Pilih kota...</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city; ?>" 
                                            <?php echo ($edit_ticket && $edit_ticket['arrival_city'] == $city) ? 'selected' : ''; ?>>
                                        <?php echo $city; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Tanggal Penerbangan</label>
                            <input type="date" name="flight_date" class="form-control" required 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo $edit_ticket ? $edit_ticket['flight_date'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Waktu Keberangkatan</label>
                            <input type="time" name="departure_time" class="form-control" required 
                                   value="<?php echo $edit_ticket ? $edit_ticket['departure_time'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Waktu Tiba</label>
                            <input type="time" name="arrival_time" class="form-control" required 
                                   value="<?php echo $edit_ticket ? $edit_ticket['arrival_time'] : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Kelas Kursi</label>
                            <select name="seat_type" class="form-control" required>
                                <option value="">Pilih kelas...</option>
                                <option value="Economy" <?php echo ($edit_ticket && $edit_ticket['seat_type'] == 'Economy') ? 'selected' : ''; ?>>
                                    Economy
                                </option>
                                <option value="Business" <?php echo ($edit_ticket && $edit_ticket['seat_type'] == 'Business') ? 'selected' : ''; ?>>
                                    Business
                                </option>
                                <option value="First Class" <?php echo ($edit_ticket && $edit_ticket['seat_type'] == 'First Class') ? 'selected' : ''; ?>>
                                    First Class
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Harga Tiket (Rp)</label>
                            <input type="number" name="price" class="form-control" required 
                                   value="<?php echo $edit_ticket ? $edit_ticket['price'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Kursi Tersedia</label>
                            <input type="number" name="available_seats" class="form-control" required 
                                   value="<?php echo $edit_ticket ? $edit_ticket['available_seats'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Total Kursi</label>
                            <input type="number" name="total_seats" class="form-control" required 
                                   value="<?php echo $edit_ticket ? $edit_ticket['total_seats'] : ''; ?>">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_ticket ? 'Update Tiket' : 'Tambah Tiket'; ?>
                    </button>
                    <?php if ($edit_ticket): ?>
                        <a href="tickets.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tickets List -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-list"></i>
                </div>
                <h2>Daftar Tiket Penerbangan</h2>
            </div>

            <?php if (count($tickets) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Rute</th>
                            <th>Tanggal & Waktu</th>
                            <th>Kelas</th>
                            <th>Harga</th>
                            <th>Kursi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary-blue);"><?php echo htmlspecialchars($ticket['flight_code']); ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span><?php echo htmlspecialchars($ticket['departure_city']); ?></span>
                                    <i class="fas fa-arrow-right" style="color: var(--primary-blue);"></i>
                                    <span><?php echo htmlspecialchars($ticket['arrival_city']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo date('d M Y', strtotime($ticket['flight_date'])); ?></strong><br>
                                    <small style="color: #666;">
                                        <?php echo date('H:i', strtotime($ticket['departure_time'])); ?> - 
                                        <?php echo date('H:i', strtotime($ticket['arrival_time'])); ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="seat-type-badge seat-<?php echo strtolower(str_replace(' ', '', $ticket['seat_type'])); ?>">
                                    <?php echo $ticket['seat_type']; ?>
                                </span>
                            </td>
                            <td>Rp <?php echo number_format($ticket['price'], 0, ',', '.'); ?></td>
                            <td>
                                <div>
                                    <strong style="color: var(--success);"><?php echo $ticket['available_seats']; ?></strong> 
                                    / <?php echo $ticket['total_seats']; ?>
                                </div>
                                <div style="margin-top: 0.25rem;">
                                    <div style="width: 100px; height: 4px; background: #e5e7eb; border-radius: 2px;">
                                        <div style="width: <?php echo ($ticket['available_seats'] / $ticket['total_seats']) * 100; ?>%; height: 100%; background: var(--success); border-radius: 2px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="tickets.php?edit=<?php echo $ticket['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" action="" style="display: inline;" 
                                          onsubmit="return confirm('Yakin ingin menghapus tiket ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="background: #dc2626; color: white;">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--dark-gray); padding: 2rem;">
                    <i class="fas fa-plane" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                    Belum ada tiket penerbangan yang terdaftar
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
    <script>
        // Auto-populate departure time + 3 hours for arrival time
        document.querySelector('input[name="departure_time"]').addEventListener('change', function() {
            const arrivalTimeInput = document.querySelector('input[name="arrival_time"]');
            if (!arrivalTimeInput.value) {
                const departureTime = new Date('2000-01-01 ' + this.value);
                departureTime.setHours(departureTime.getHours() + 3);
                const hours = departureTime.getHours().toString().padStart(2, '0');
                const minutes = departureTime.getMinutes().toString().padStart(2, '0');
                arrivalTimeInput.value = hours + ':' + minutes;
            }
        });

        // Validate that available seats <= total seats
        document.querySelector('input[name="available_seats"]').addEventListener('input', function() {
            const totalSeats = parseInt(document.querySelector('input[name="total_seats"]').value) || 0;
            const availableSeats = parseInt(this.value) || 0;
            
            if (availableSeats > totalSeats) {
                this.setCustomValidity('Kursi tersedia tidak boleh lebih dari total kursi');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
