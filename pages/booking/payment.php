<?php
require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('../auth/login.php');
}

$user = getUserData();
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Validate booking
$booking_stmt = $pdo->prepare("
    SELECT b.*, h.name as hotel_name, h.location, t.flight_code, t.departure_city, t.arrival_city, 
           t.flight_date, t.departure_time, p.payment_status, p.receipt_image, p.payment_date
    FROM bookings b 
    LEFT JOIN hotels h ON b.id_hotel = h.id
    LEFT JOIN tickets t ON b.id_ticket = t.id
    LEFT JOIN payments p ON b.id = p.id_booking
    WHERE b.id = ? AND b.id_user = ?
");
$booking_stmt->execute([$booking_id, $user['id']]);
$booking = $booking_stmt->fetch();

if (!$booking) {
    redirectTo('index.php');
}

// Ensure payment record exists
if (!$booking['payment_status']) {
    $create_payment = $pdo->prepare("
        INSERT INTO payments (id_booking, payment_status, payment_amount, created_at) 
        VALUES (?, 'unpaid', ?, NOW())
    ");
    $create_payment->execute([$booking_id, $booking['total_amount']]);
    
    // Refresh booking data
    $booking_stmt->execute([$booking_id, $user['id']]);
    $booking = $booking_stmt->fetch();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_dir = '../../uploads/receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed)) {
            $filename = 'receipt_' . $booking_id . '_' . time() . '.' . $file_ext;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $filepath)) {
                try {
                    // Update payment record
                    $payment_stmt = $pdo->prepare("
                        UPDATE payments 
                        SET receipt_image = ?, payment_status = 'pending', payment_date = NOW() 
                        WHERE id_booking = ?
                    ");
                    $payment_stmt->execute([$filename, $booking_id]);
                    
                    // Add notification for admin
                    $notif_stmt = $pdo->prepare("
                        INSERT INTO chats (id_user, message, is_from_user, created_at) 
                        VALUES (?, ?, 0, NOW())
                    ");
                    $notif_message = "Bukti pembayaran untuk booking #{$booking_id} telah diunggah dan menunggu verifikasi admin.";
                    $notif_stmt->execute([$user['id'], $notif_message]);
                    
                    $success = 'Bukti pembayaran berhasil diunggah. Admin akan memverifikasi dalam 1x24 jam.';
                    
                    // Refresh booking data
                    $booking_stmt->execute([$booking_id, $user['id']]);
                    $booking = $booking_stmt->fetch();
                    
                } catch (Exception $e) {
                    $error = 'Gagal menyimpan bukti pembayaran: ' . $e->getMessage();
                }
            } else {
                $error = 'Gagal mengunggah file. Periksa permission folder uploads.';
            }
        } else {
            $error = 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.';
        }
    } else {
        if (isset($_FILES['receipt'])) {
            $upload_errors = [
                1 => 'File terlalu besar (melebihi upload_max_filesize)',
                2 => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
                3 => 'File hanya terupload sebagian',
                4 => 'Tidak ada file yang dipilih',
                6 => 'Folder temporary tidak ditemukan',
                7 => 'Gagal menulis file ke disk',
                8 => 'Upload dihentikan oleh extension'
            ];
            $error_code = $_FILES['receipt']['error'];
            $error = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Error upload tidak dikenal';
        } else {
            $error = 'Silakan pilih file bukti pembayaran.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Garuda Indonesia</title>
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
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h2>Pembayaran Booking #<?php echo $booking['id']; ?></h2>
                    </div>

                    <!-- Payment Status -->
                    <div class="payment-status-card" style="margin-bottom: 2rem;">
                        <?php if ($booking['payment_status'] == 'unpaid'): ?>
                            <div style="background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 1rem; border-radius: 10px;">
                                <i class="fas fa-clock"></i> <strong>Menunggu Pembayaran</strong><br>
                                Silakan lakukan pembayaran dan unggah bukti transfer.
                            </div>
                        <?php elseif ($booking['payment_status'] == 'pending_verification'): ?>
                            <div style="background: #dbeafe; border: 1px solid #3b82f6; color: #1e40af; padding: 1rem; border-radius: 10px;">
                                <i class="fas fa-hourglass-half"></i> <strong>Menunggu Verifikasi</strong><br>
                                Bukti pembayaran sedang diverifikasi oleh admin (1x24 jam).
                            </div>
                        <?php elseif ($booking['payment_status'] == 'paid'): ?>
                            <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 1rem; border-radius: 10px;">
                                <i class="fas fa-check-circle"></i> <strong>Pembayaran Berhasil</strong><br>
                                Terima kasih! Pembayaran Anda telah dikonfirmasi.
                            </div>
                        <?php elseif ($booking['payment_status'] == 'failed'): ?>
                            <div style="background: #fef2f2; border: 1px solid #ef4444; color: #dc2626; padding: 1rem; border-radius: 10px;">
                                <i class="fas fa-times-circle"></i> <strong>Pembayaran Ditolak</strong><br>
                                Silakan hubungi customer service atau unggah ulang bukti pembayaran yang benar.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Payment Instructions -->
                    <div class="form-group">
                        <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                            <i class="fas fa-university"></i> Informasi Pembayaran
                        </h3>
                        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                            <h4 style="margin-bottom: 1rem;">Transfer ke Rekening:</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                                    <div>
                                        <strong>Bank BCA</strong><br>
                                        <span style="font-size: 1.2rem; color: var(--primary-blue); font-weight: bold;">1234567890</span><br>
                                        a.n. PT Garuda Indonesia
                                    </div>
                                    <button onclick="copyToClipboard('1234567890')" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                        <i class="fas fa-copy"></i> Salin
                                    </button>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;">
                                    <div>
                                        <strong>Bank Mandiri</strong><br>
                                        <span style="font-size: 1.2rem; color: var(--primary-blue); font-weight: bold;">9876543210</span><br>
                                        a.n. PT Garuda Indonesia
                                    </div>
                                    <button onclick="copyToClipboard('9876543210')" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                        <i class="fas fa-copy"></i> Salin
                                    </button>
                                </div>
                            </div>
                            <div style="margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 8px;">
                                <h4 style="color: #92400e; margin-bottom: 0.5rem;">
                                    <i class="fas fa-exclamation-triangle"></i> Jumlah Transfer
                                </h4>
                                <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-blue);">
                                    Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>
                                </div>
                                <small style="color: #92400e;">Pastikan nominal transfer sesuai dengan jumlah di atas</small>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Receipt Form -->
                    <?php if ($booking['payment_status'] == 'unpaid' || $booking['payment_status'] == 'failed'): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-upload" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                                Unggah Bukti Pembayaran
                            </label>
                            <div class="upload-area" style="border: 2px dashed #d1d5db; padding: 2rem; text-align: center; border-radius: 10px; cursor: pointer;" 
                                 onclick="document.getElementById('receipt').click()">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                                <p style="margin-bottom: 0.5rem; color: #6b7280;">Klik untuk memilih file atau drag & drop</p>
                                <small style="color: #9ca3af;">Format yang didukung: JPG, JPEG, PNG, GIF (Max: 5MB)</small>
                                <input type="file" id="receipt" name="receipt" accept="image/*" required style="display: none;">
                            </div>
                            <div id="preview" style="margin-top: 1rem; display: none;">
                                <img id="preview-img" style="max-width: 200px; border-radius: 8px;" />
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-upload" style="margin-right: 0.5rem;"></i>
                            Unggah Bukti Pembayaran
                        </button>
                    </form>
                    <?php elseif ($booking['receipt_image']): ?>
                    <div class="form-group">
                        <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                            <i class="fas fa-receipt"></i> Bukti Pembayaran
                        </h4>
                        <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 10px;">
                            <img src="../../uploads/receipts/<?php echo $booking['receipt_image']; ?>" 
                                 style="max-width: 300px; width: 100%; border-radius: 8px; cursor: pointer;"
                                 onclick="openImageModal(this.src)" />
                            <p style="margin-top: 0.5rem; color: #6b7280;">
                                Diunggah pada: <?php echo date('d M Y H:i', strtotime($booking['payment_date'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Booking Summary -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h3>Ringkasan Pesanan</h3>
                    </div>

                    <?php if ($booking['id_ticket']): ?>
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h5 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <i class="fas fa-plane"></i> Penerbangan
                        </h5>
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
                    </div>
                    <?php endif; ?>

                    <?php if ($booking['id_hotel']): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h5 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <i class="fas fa-hotel"></i> Hotel
                        </h5>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem;"><?php echo $booking['hotel_name']; ?></p>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem;"><?php echo $booking['location']; ?></p>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                            <?php echo date('d M Y', strtotime($booking['check_in_date'])); ?> - 
                            <?php echo date('d M Y', strtotime($booking['check_out_date'])); ?>
                        </p>
                        <p style="margin: 0.25rem 0; font-size: 0.875rem;">
                            <?php echo $booking['rooms']; ?> kamar
                        </p>
                    </div>
                    <?php endif; ?>

                    <div style="padding-top: 1rem; border-top: 2px solid var(--primary-blue);">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: var(--primary-blue);">
                            <span>Total:</span>
                            <span>Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Customer Service -->
                <div class="card mt-4">
                    <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-headset"></i> Butuh Bantuan?
                    </h4>
                    <p style="margin-bottom: 1rem;">Hubungi customer service kami jika mengalami kesulitan dalam proses pembayaran.</p>
                    <a href="../user/chat.php" class="btn btn-outline" style="width: 100%;">
                        <i class="fas fa-comments"></i> Chat Customer Service
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);">
        <div class="modal-content" style="margin: auto; display: block; width: 80%; max-width: 700px; margin-top: 50px;">
            <img id="modalImage" style="width: 100%; height: auto;">
            <span class="close" style="position: absolute; top: 15px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;" onclick="closeImageModal()">&times;</span>
        </div>
    </div>

    <script>
        // File upload preview
        document.getElementById('receipt').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').style.display = 'block';
                    document.getElementById('preview-img').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Nomor rekening berhasil disalin!');
            });
        }

        // Image modal
        function openImageModal(src) {
            document.getElementById('imageModal').style.display = 'block';
            document.getElementById('modalImage').src = src;
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modal on click outside
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
