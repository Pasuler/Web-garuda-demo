<?php
require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('../auth/login.php');
}

$user = getUserData();

$error = '';
$success = '';

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if (!empty($full_name) && !empty($email)) {
        try {
            if (!empty($new_password)) {
                // Update dengan password baru
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $hashed_password, $user['id']]);
                $success = "Profil dan password berhasil diperbarui!";
            } else {
                // Update tanpa password
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $user['id']]);
                $success = "Profil berhasil diperbarui!";
            }
            
            // Refresh user data
            $user = getUserData();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Email sudah digunakan oleh user lain!";
            } else {
                $error = "Gagal memperbarui profil: " . $e->getMessage();
            }
        }
    } else {
        $error = "Nama lengkap dan email wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Garuda Indonesia</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding-top: 2rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .profile-avatar {
            position: relative;
            z-index: 1;
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.2);
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            font-weight: bold;
            backdrop-filter: blur(10px);
        }
        
        .profile-info {
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .form-section {
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .form-group-modern {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-group-modern label {
            position: absolute;
            top: -10px;
            left: 15px;
            background: white;
            padding: 0 8px;
            font-size: 0.875rem;
            color: var(--primary-blue);
            font-weight: 600;
            z-index: 1;
        }
        
        .form-group-modern input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        
        .form-group-modern input:focus {
            border-color: var(--primary-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            border-radius: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            min-height: 100px;
        }
        
        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .action-btn i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .action-btn.primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: white;
        }
        
        .action-btn.outline {
            background: white;
            color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        
        .action-btn.outline:hover {
            background: var(--primary-blue);
            color: white;
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }
        
        .btn-save {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-back {
            background: white;
            border: 2px solid #e2e8f0;
            color: var(--dark-gray);
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-back:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }
        
        .alert-modern {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #dc2626;
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
                <li><a href="../booking/booking_cart.php" class="nav-link">Pemesanan Saya</a></li>
                <li><a href="chat.php" class="nav-link">Customer Service</a></li>
                <li><a href="profile.php" class="nav-link active">Profil</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </nav>
    </header>

    <div class="profile-container">
        <div class="container">
            <!-- Profile Card -->
            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2 style="margin-bottom: 0.5rem; font-size: 2rem;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p style="margin-bottom: 0; opacity: 0.9; font-size: 1.1rem;"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.8;">
                            <i class="fas fa-calendar"></i> Bergabung <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                    
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                $booking_count = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE id_user = ?");
                                $booking_count->execute([$user['id']]);
                                echo $booking_count->fetchColumn();
                                ?>
                            </div>
                            <div class="stat-label">Total Pemesanan</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                $paid_count = $pdo->prepare("
                                    SELECT COUNT(*) FROM bookings b 
                                    LEFT JOIN payments p ON b.id = p.id_booking 
                                    WHERE b.id_user = ? AND p.payment_status = 'paid'
                                ");
                                $paid_count->execute([$user['id']]);
                                echo $paid_count->fetchColumn();
                                ?>
                            </div>
                            <div class="stat-label">Pembayaran Selesai</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo ucfirst($user['role']); ?></div>
                            <div class="stat-label">Status Akun</div>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="form-section">
                    <?php if ($error): ?>
                        <div class="alert-modern alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert-modern alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo $success; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-grid">
                            <div>
                                <h3 style="color: var(--primary-blue); margin-bottom: 1.5rem;">
                                    <i class="fas fa-user-edit"></i> Informasi Personal
                                </h3>
                                
                                <div class="form-group-modern">
                                    <label for="full_name">
                                        <i class="fas fa-user"></i> Nama Lengkap
                                    </label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>

                                <div class="form-group-modern">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i> Alamat Email
                                    </label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <div class="form-group-modern">
                                    <label for="phone">
                                        <i class="fas fa-phone"></i> Nomor Telepon
                                    </label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Contoh: +62812345678">
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="color: var(--primary-blue); margin-bottom: 1.5rem;">
                                    <i class="fas fa-shield-alt"></i> Keamanan Akun
                                </h3>
                                
                                <div class="form-group-modern">
                                    <label for="new_password">
                                        <i class="fas fa-lock"></i> Password Baru
                                    </label>
                                    <input type="password" id="new_password" name="new_password" placeholder="Kosongkan jika tidak ingin mengubah">
                                </div>
                                
                                <div style="background: #f8fafc; border-radius: 12px; padding: 1.5rem; margin-top: 1rem;">
                                    <h4 style="color: var(--dark-gray); margin-bottom: 1rem; font-size: 1rem;">
                                        <i class="fas fa-info-circle" style="color: var(--primary-blue);"></i> Info Akun
                                    </h4>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                        <div><strong>ID User:</strong> #<?php echo $user['id']; ?></div>
                                        <div><strong>Terakhir Update:</strong> <?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?></div>
                                        <div><strong>Role:</strong> <span style="color: var(--primary-blue); font-weight: 600;"><?php echo ucfirst($user['role']); ?></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i>
                                Simpan Perubahan
                            </button>
                            <a href="../booking/booking_cart.php" class="btn-back">
                                <i class="fas fa-arrow-left"></i>
                                Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="profile-card">
                <div style="padding: 2rem;">
                    <h3 style="color: var(--primary-blue); margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-bolt"></i> Aksi Cepat
                    </h3>
                    
                    <div class="action-grid">
                        <a href="../booking/booking_cart.php" class="action-btn outline">
                            <i class="fas fa-list-alt"></i>
                            <span>Pemesanan Saya</span>
                        </a>
                        
                        <a href="chat.php" class="action-btn outline">
                            <i class="fas fa-comments"></i>
                            <span>Customer Service</span>
                        </a>
                        
                        <?php if ($user['role'] == 'admin'): ?>
                        <a href="../../admin/dashboard.php" class="action-btn primary">
                            <i class="fas fa-cogs"></i>
                            <span>Admin Panel</span>
                        </a>
                        
                        <a href="../../admin/users.php" class="action-btn outline">
                            <i class="fas fa-users"></i>
                            <span>Kelola Users</span>
                        </a>
                        <?php else: ?>
                        <a href="../../index.php" class="action-btn outline">
                            <i class="fas fa-home"></i>
                            <span>Beranda</span>
                        </a>
                        
                        <a href="../booking/booking.php" class="action-btn outline">
                            <i class="fas fa-plane"></i>
                            <span>Pesan Tiket</span>
                        </a>
                        <?php endif; ?>
                        
                        <a href="../auth/logout.php" class="action-btn secondary">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Keluar</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
