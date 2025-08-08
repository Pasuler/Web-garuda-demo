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
        $name = sanitizeInput($_POST['name']);
        $location = sanitizeInput($_POST['location']);
        $price_per_night = floatval($_POST['price_per_night']);
        $rating = floatval($_POST['rating']);
        $description = sanitizeInput($_POST['description']);
        $facilities = sanitizeInput($_POST['facilities']);
        $available_rooms = intval($_POST['available_rooms']);
        
        // Handle image upload
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_url = uploadFile($_FILES['image'], '../uploads/hotels/');
            if (!$image_url) {
                $error = 'Gagal mengupload gambar hotel.';
            }
        } elseif ($action == 'edit' && !empty($_POST['existing_image'])) {
            $image_url = $_POST['existing_image'];
        }
        
        if (!$error) {
            try {
                if ($action == 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO hotels (name, location, price_per_night, rating, image_url, description, facilities, available_rooms) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $location, $price_per_night, $rating, $image_url, $description, $facilities, $available_rooms]);
                    $success = 'Hotel berhasil ditambahkan!';
                } else {
                    $hotel_id = intval($_POST['hotel_id']);
                    $stmt = $pdo->prepare("
                        UPDATE hotels 
                        SET name=?, location=?, price_per_night=?, rating=?, image_url=?, description=?, facilities=?, available_rooms=? 
                        WHERE id=?
                    ");
                    $stmt->execute([$name, $location, $price_per_night, $rating, $image_url, $description, $facilities, $available_rooms, $hotel_id]);
                    $success = 'Hotel berhasil diupdate!';
                }
            } catch (Exception $e) {
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    } elseif ($action == 'delete') {
        $hotel_id = intval($_POST['hotel_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
            $stmt->execute([$hotel_id]);
            $success = 'Hotel berhasil dihapus!';
        } catch (Exception $e) {
            $error = 'Tidak dapat menghapus hotel: ' . $e->getMessage();
        }
    }
}

// Get hotels
$hotels_stmt = $pdo->query("SELECT * FROM hotels ORDER BY created_at DESC");
$hotels = $hotels_stmt->fetchAll();

// Get hotel for editing
$edit_hotel = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_hotel = $edit_stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hotel - Admin Garuda Indonesia</title>
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
        
        .hotel-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
            <li><a href="hotels.php" class="active"><i class="fas fa-hotel"></i> Kelola Hotel</a></li>
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
                    <i class="fas fa-hotel"></i>
                </div>
                <h1>Kelola Hotel</h1>
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

            <!-- Add/Edit Hotel Form -->
            <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="<?php echo $edit_hotel ? 'edit' : 'add'; ?>">
                <?php if ($edit_hotel): ?>
                    <input type="hidden" name="hotel_id" value="<?php echo $edit_hotel['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?php echo $edit_hotel['image_url']; ?>">
                <?php endif; ?>
                
                <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                    <?php echo $edit_hotel ? 'Edit Hotel' : 'Tambah Hotel Baru'; ?>
                </h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nama Hotel</label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php echo $edit_hotel ? htmlspecialchars($edit_hotel['name']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="location" class="form-control" required 
                                   value="<?php echo $edit_hotel ? htmlspecialchars($edit_hotel['location']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Harga per Malam (Rp)</label>
                            <input type="number" name="price_per_night" class="form-control" required 
                                   value="<?php echo $edit_hotel ? $edit_hotel['price_per_night'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Rating (1-5)</label>
                            <input type="number" name="rating" class="form-control" step="0.1" min="1" max="5" required 
                                   value="<?php echo $edit_hotel ? $edit_hotel['rating'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Jumlah Kamar Tersedia</label>
                            <input type="number" name="available_rooms" class="form-control" required 
                                   value="<?php echo $edit_hotel ? $edit_hotel['available_rooms'] : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3" required><?php echo $edit_hotel ? htmlspecialchars($edit_hotel['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Fasilitas</label>
                    <textarea name="facilities" class="form-control" rows="2" placeholder="WiFi Gratis, Kolam Renang, Spa, dll" required><?php echo $edit_hotel ? htmlspecialchars($edit_hotel['facilities']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Gambar Hotel</label>
                    <input type="file" name="image" class="form-control" accept="image/*" <?php echo !$edit_hotel ? 'required' : ''; ?>>
                    <?php if ($edit_hotel && $edit_hotel['image_url']): ?>
                        <div style="margin-top: 0.5rem;">
                            <img src="../uploads/hotels/<?php echo $edit_hotel['image_url']; ?>" alt="Current Image" class="hotel-image">
                            <small style="color: #666; margin-left: 1rem;">Gambar saat ini</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $edit_hotel ? 'Update Hotel' : 'Tambah Hotel'; ?>
                    </button>
                    <?php if ($edit_hotel): ?>
                        <a href="hotels.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Hotels List -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-list"></i>
                </div>
                <h2>Daftar Hotel</h2>
            </div>

            <?php if (count($hotels) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Hotel</th>
                            <th>Lokasi</th>
                            <th>Harga/Malam</th>
                            <th>Rating</th>
                            <th>Kamar Tersedia</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotels as $hotel): ?>
                        <tr>
                            <td>
                                <?php if ($hotel['image_url']): ?>
                                    <img src="../uploads/hotels/<?php echo $hotel['image_url']; ?>" alt="Hotel Image" class="hotel-image">
                                <?php else: ?>
                                    <div style="width: 80px; height: 60px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                                        <i class="fas fa-image" style="color: #9ca3af;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($hotel['name']); ?></strong><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($hotel['facilities']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($hotel['location']); ?></td>
                            <td>Rp <?php echo number_format($hotel['price_per_night'], 0, ',', '.'); ?></td>
                            <td>
                                <div style="color: #f59e0b;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $hotel['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span style="margin-left: 0.5rem; color: #666;"><?php echo $hotel['rating']; ?></span>
                                </div>
                            </td>
                            <td><?php echo $hotel['available_rooms']; ?> kamar</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="hotels.php?edit=<?php echo $hotel['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" action="" style="display: inline;" 
                                          onsubmit="return confirm('Yakin ingin menghapus hotel ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
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
                    <i class="fas fa-hotel" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                    Belum ada hotel yang terdaftar
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html>
