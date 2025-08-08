<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php');
}

$admin = getUserData();
$error = '';
$success = '';

// Handle sending reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $user_id = intval($_POST['user_id']);
    $response = sanitizeInput($_POST['response']);
    
    if (!empty($response)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO chats (id_user, message, is_from_user, responded_by) 
                VALUES (?, ?, 0, ?)
            ");
            $stmt->execute([$user_id, $response, $admin['id']]);
            $success = 'Balasan berhasil dikirim!';
        } catch (Exception $e) {
            $error = 'Gagal mengirim balasan: ' . $e->getMessage();
        }
    } else {
        $error = 'Pesan balasan tidak boleh kosong!';
    }
}

// Handle sending payment failure notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'payment_notification') {
    $user_id = intval($_POST['user_id']);
    $booking_id = intval($_POST['booking_id']);
    $reason = sanitizeInput($_POST['reason']);
    
    $message = "⚠️ PEMBERITAHUAN PEMBAYARAN\n\n";
    $message .= "Pemesanan ID: #$booking_id\n";
    $message .= "Status: Pembayaran Ditolak\n\n";
    $message .= "Alasan: $reason\n\n";
    $message .= "Silakan upload bukti pembayaran yang valid atau hubungi customer service untuk bantuan lebih lanjut.\n\n";
    $message .= "Terima kasih atas perhatiannya.";
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO chats (id_user, message, is_from_user, responded_by) 
            VALUES (?, ?, 0, ?)
        ");
        $stmt->execute([$user_id, $message, $admin['id']]);
        $success = 'Notifikasi pembayaran berhasil dikirim!';
    } catch (Exception $e) {
        $error = 'Gagal mengirim notifikasi: ' . $e->getMessage();
    }
}

// Get all users who have sent messages
$users_stmt = $pdo->query("
    SELECT DISTINCT 
        u.id, 
        u.full_name, 
        u.email,
        (SELECT COUNT(*) FROM chats WHERE id_user = u.id AND is_from_user = 1) as message_count,
        (SELECT message FROM chats WHERE id_user = u.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM chats WHERE id_user = u.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM chats WHERE id_user = u.id AND is_from_user = 1 AND response IS NULL) as unread_count
    FROM users u
    WHERE u.role = 'user' AND u.id IN (SELECT DISTINCT id_user FROM chats)
    ORDER BY last_message_time DESC
");
$users = $users_stmt->fetchAll();

// Get selected user's chat
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$selected_user = null;
$chats = [];

if ($selected_user_id) {
    // Get user details
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$selected_user_id]);
    $selected_user = $user_stmt->fetch();
    
    if ($selected_user) {
        // Get chat messages
        $chats_stmt = $pdo->prepare("
            SELECT 
                c.*,
                admin.full_name as admin_name
            FROM chats c
            LEFT JOIN users admin ON c.responded_by = admin.id
            WHERE c.id_user = ?
            ORDER BY c.created_at ASC
        ");
        $chats_stmt->execute([$selected_user_id]);
        $chats = $chats_stmt->fetchAll();
        
        // Get user's bookings for payment notifications
        $bookings_stmt = $pdo->prepare("
            SELECT b.*, p.payment_status 
            FROM bookings b
            LEFT JOIN payments p ON b.id = p.id_booking
            WHERE b.id_user = ?
            ORDER BY b.created_at DESC
        ");
        $bookings_stmt->execute([$selected_user_id]);
        $user_bookings = $bookings_stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service Chat - Admin Garuda Indonesia</title>
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
        
        .chat-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            height: 70vh;
        }
        
        .users-list {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .users-list-header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 1rem;
            font-weight: 600;
        }
        
        .users-list-content {
            max-height: 100%;
            overflow-y: auto;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: background 0.3s ease;
        }
        
        .user-item:hover,
        .user-item.active {
            background: var(--light-blue);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .last-message {
            color: #666;
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .unread-badge {
            background: #ef4444;
            color: var(--white);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .chat-area {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .chat-header {
            background: var(--primary-blue);
            color: var(--white);
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            max-height: 400px;
        }
        
        .message {
            margin-bottom: 1rem;
        }
        
        .message-user {
            text-align: right;
        }
        
        .message-admin {
            text-align: left;
        }
        
        .message-bubble {
            display: inline-block;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            max-width: 70%;
            word-wrap: break-word;
        }
        
        .message-user .message-bubble {
            background: var(--light-blue);
            color: var(--dark-gray);
        }
        
        .message-admin .message-bubble {
            background: var(--primary-blue);
            color: var(--white);
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.25rem;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            border-radius: 0 0 10px 10px;
        }
        
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .btn-quick {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 20px;
        }
        
        .no-chat-selected {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #666;
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
            <li><a href="tickets.php"><i class="fas fa-plane"></i> Kelola Tiket</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Pengguna</a></li>
            <li><a href="chats.php" class="active"><i class="fas fa-comments"></i> Customer Service</a></li>
            <li><a href="../index.php"><i class="fas fa-globe"></i> Lihat Website</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h1>Customer Service Chat</h1>
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
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <!-- Users List -->
            <div class="users-list">
                <div class="users-list-header">
                    <i class="fas fa-users"></i> Daftar Pengguna
                </div>
                <div class="users-list-content">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <a href="chats.php?user_id=<?php echo $user['id']; ?>" 
                           class="user-item <?php echo ($selected_user_id == $user['id']) ? 'active' : ''; ?>">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="last-message">
                                    <?php echo htmlspecialchars(substr($user['last_message'], 0, 50)); ?>
                                    <?php if (strlen($user['last_message']) > 50) echo '...'; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #999; margin-top: 0.25rem;">
                                    <?php echo date('d/m/Y H:i', strtotime($user['last_message_time'])); ?>
                                </div>
                            </div>
                            <?php if ($user['unread_count'] > 0): ?>
                                <div class="unread-badge">
                                    <?php echo $user['unread_count']; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: #666;">
                            <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                            Belum ada pesan masuk
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area">
                <?php if ($selected_user): ?>
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($selected_user['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($selected_user['full_name']); ?></div>
                            <div style="font-size: 0.875rem; opacity: 0.8;"><?php echo htmlspecialchars($selected_user['email']); ?></div>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <?php if (count($chats) > 0): ?>
                            <?php foreach ($chats as $chat): ?>
                            <div class="message <?php echo $chat['is_from_user'] ? 'message-user' : 'message-admin'; ?>">
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($chat['message'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('d/m/Y H:i', strtotime($chat['created_at'])); ?>
                                    <?php if (!$chat['is_from_user'] && $chat['admin_name']): ?>
                                        - oleh <?php echo htmlspecialchars($chat['admin_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: #666; margin: 2rem 0;">
                                <i class="fas fa-comment" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                                Belum ada percakapan
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Input -->
                    <div class="chat-input">
                        <!-- Quick Actions -->
                        <div class="quick-actions">
                            <button class="btn btn-quick btn-secondary" onclick="insertQuickReply('greeting')">
                                <i class="fas fa-hand-wave"></i> Salam
                            </button>
                            <button class="btn btn-quick btn-secondary" onclick="insertQuickReply('help')">
                                <i class="fas fa-question-circle"></i> Bantuan
                            </button>
                            <button class="btn btn-quick btn-secondary" onclick="insertQuickReply('payment')">
                                <i class="fas fa-credit-card"></i> Pembayaran
                            </button>
                            <button class="btn btn-quick btn-warning" onclick="showPaymentNotificationModal()">
                                <i class="fas fa-exclamation-triangle"></i> Notif Pembayaran
                            </button>
                        </div>

                        <!-- Reply Form -->
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">
                            <div style="display: flex; gap: 1rem;">
                                <textarea name="response" id="responseText" class="form-control" rows="3" 
                                          placeholder="Ketik balasan Anda..." required style="flex: 1;"></textarea>
                                <button type="submit" class="btn btn-primary" style="align-self: flex-end;">
                                    <i class="fas fa-paper-plane"></i> Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- No Chat Selected -->
                    <div class="no-chat-selected">
                        <i class="fas fa-comments" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <h3 style="color: #666; margin-bottom: 1rem;">Pilih pengguna untuk memulai chat</h3>
                        <p style="color: #999;">Klik pada nama pengguna di sebelah kiri untuk melihat percakapan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Notification Modal -->
    <?php if ($selected_user && isset($user_bookings)): ?>
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 2rem; border-radius: 10px; max-width: 500px; width: 90%;">
            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                <i class="fas fa-exclamation-triangle"></i> Kirim Notifikasi Pembayaran
            </h3>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="payment_notification">
                <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Pilih Pemesanan</label>
                    <select name="booking_id" class="form-control" required>
                        <option value="">Pilih pemesanan...</option>
                        <?php foreach ($user_bookings as $booking): ?>
                        <option value="<?php echo $booking['id']; ?>">
                            #<?php echo $booking['id']; ?> - 
                            <?php echo ucfirst(str_replace('_', ' ', $booking['booking_type'])); ?> - 
                            Rp <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?>
                            <?php if ($booking['payment_status']): ?>
                                (<?php echo $booking['payment_status']; ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alasan Penolakan</label>
                    <textarea name="reason" class="form-control" rows="3" required 
                              placeholder="Contoh: Bukti pembayaran tidak jelas, nominal tidak sesuai, dll"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i> Kirim Notifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="../js/main.js"></script>
    <script>
        // Quick reply templates
        const quickReplies = {
            greeting: "Selamat datang di customer service Garuda Indonesia! 👋\n\nAda yang bisa kami bantu hari ini?",
            help: "Kami siap membantu Anda dengan:\n• Informasi pemesanan tiket dan hotel\n• Bantuan proses pembayaran\n• Perubahan atau pembatalan pemesanan\n• Keluhan dan saran\n\nSilakan sampaikan pertanyaan Anda!",
            payment: "Untuk pembayaran, silakan:\n1. Transfer sesuai nominal yang tertera\n2. Upload bukti pembayaran di website\n3. Tunggu verifikasi maksimal 1x24 jam\n\nJika ada kendala, jangan ragu untuk menghubungi kami!"
        };
        
        function insertQuickReply(type) {
            document.getElementById('responseText').value = quickReplies[type];
        }
        
        function showPaymentNotificationModal() {
            document.getElementById('paymentModal').style.display = 'flex';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        // Auto-scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // Scroll to bottom on page load
        scrollToBottom();
        
        // Auto-refresh chat every 10 seconds
        if (window.location.search.includes('user_id=')) {
            setInterval(function() {
                // Only refresh if user is not typing
                const textarea = document.getElementById('responseText');
                if (document.activeElement !== textarea) {
                    location.reload();
                }
            }, 10000);
        }
        
        // Close modal when clicking outside
        document.getElementById('paymentModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>
