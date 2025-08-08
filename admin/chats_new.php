<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php');
}

$admin = getUserData();

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] == 'users') {
        // Get users with chat activity
        $users_stmt = $pdo->prepare("
            SELECT DISTINCT u.id, u.full_name, u.email,
                   (SELECT COUNT(*) FROM chats c2 WHERE c2.id_user = u.id AND c2.is_from_admin = 0 AND c2.is_read = 0) as unread_count,
                   (SELECT c3.created_at FROM chats c3 WHERE c3.id_user = u.id ORDER BY c3.created_at DESC LIMIT 1) as last_activity,
                   (SELECT c4.message FROM chats c4 WHERE c4.id_user = u.id ORDER BY c4.created_at DESC LIMIT 1) as last_message
            FROM users u
            WHERE u.role = 'user' AND EXISTS (SELECT 1 FROM chats c WHERE c.id_user = u.id)
            ORDER BY last_activity DESC
        ");
        $users_stmt->execute();
        echo json_encode($users_stmt->fetchAll());
        exit;
    }
    
    if ($_GET['ajax'] == 'messages' && isset($_GET['user_id'])) {
        $user_id = (int)$_GET['user_id'];
        
        // Get messages for specific user
        $messages_stmt = $pdo->prepare("
            SELECT c.*, u.full_name as user_name,
                   CASE WHEN c.is_from_admin = 1 THEN 'admin' ELSE 'user' END as sender_type
            FROM chats c
            LEFT JOIN users u ON c.id_user = u.id
            WHERE c.id_user = ?
            ORDER BY c.created_at ASC
        ");
        $messages_stmt->execute([$user_id]);
        
        // Mark user messages as read
        $pdo->prepare("UPDATE chats SET is_read = 1 WHERE id_user = ? AND is_from_admin = 0")->execute([$user_id]);
        
        echo json_encode($messages_stmt->fetchAll());
        exit;
    }
}

// Handle new message from admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'], $_POST['user_id'])) {
    $message = sanitizeInput($_POST['message']);
    $user_id = (int)$_POST['user_id'];
    
    if (!empty($message) && $user_id > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO chats (id_user, message, is_from_admin, created_at) VALUES (?, ?, 1, NOW())");
            $stmt->execute([$user_id, $message]);
            
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (Exception $e) {
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Customer Service - Admin Garuda Indonesia</title>
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

        .chat-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            height: 70vh;
            gap: 1rem;
        }
        
        .users-list {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow-y: auto;
        }
        
        .user-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .user-item:hover {
            background: #f8fafc;
        }
        
        .user-item.active {
            background: var(--primary-blue);
            color: white;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-bottom: 0.25rem;
        }
        
        .last-message {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            float: right;
        }
        
        .chat-area {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            border-radius: 12px 12px 0 0;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f0f2f5;
            max-height: 400px;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid #e5e7eb;
            background: white;
            border-radius: 0 0 12px 12px;
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            clear: both;
        }
        
        .message.admin {
            justify-content: flex-end;
        }
        
        .message.user {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 75%;
            padding: 0.75rem 1rem;
            border-radius: 18px;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .message-bubble.admin {
            background: var(--primary-blue);
            color: white;
            border-radius: 18px 18px 4px 18px;
        }
        
        .message-bubble.user {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 18px 18px 18px 4px;
        }
        
        .message-time {
            font-size: 0.7rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }
        
        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .quick-reply-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
        }
        
        .quick-reply-btn:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
        }
        
        .empty-chat {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
            text-align: center;
        }

        @media (max-width: 768px) {
            .chat-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 200px 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-logo">
            <img src="../Assets/images/Logo(Vertikal).png" alt="Garuda Indonesia" style="height: 60px; margin-bottom: 1rem;">
            <h3 style="color: var(--white); margin: 0;">Admin Panel</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem; margin: 0.5rem 0 0;">
                Selamat datang, <?php echo htmlspecialchars($admin['full_name']); ?>
            </p>
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
        <div class="card" style="padding: 0;">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h2>Customer Service Chat</h2>
                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
                    Kelola percakapan dengan pelanggan
                </div>
            </div>

            <div class="chat-layout">
                <!-- Users List -->
                <div class="users-list">
                    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb; background: #f8fafc; font-weight: 600;">
                        <i class="fas fa-users"></i> Pelanggan Online
                    </div>
                    <div id="usersList">
                        <div style="padding: 2rem; text-align: center; color: #6b7280;">
                            <i class="fas fa-spinner fa-spin"></i><br>
                            Memuat daftar pelanggan...
                        </div>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="chat-area">
                    <div class="chat-header" id="chatHeader">
                        <div style="color: #6b7280; text-align: center;">
                            <i class="fas fa-arrow-left"></i> Pilih pelanggan untuk memulai chat
                        </div>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <div class="empty-chat">
                            <div>
                                <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <p>Pilih pelanggan dari daftar sebelah kiri untuk memulai percakapan</p>
                            </div>
                        </div>
                    </div>

                    <div class="chat-input" id="chatInput" style="display: none;">
                        <!-- Quick Replies -->
                        <div class="quick-replies">
                            <div class="quick-reply-btn" onclick="insertQuickReply('Terima kasih telah menghubungi Customer Service Garuda Indonesia. Ada yang bisa saya bantu?')">
                                👋 Salam pembuka
                            </div>
                            <div class="quick-reply-btn" onclick="insertQuickReply('Pembayaran Anda sedang diverifikasi oleh tim kami. Mohon tunggu 1x24 jam.')">
                                💳 Status pembayaran
                            </div>
                            <div class="quick-reply-btn" onclick="insertQuickReply('Silakan upload bukti pembayaran yang jelas dan sesuai dengan jumlah yang tertera.')">
                                📄 Upload ulang bukti
                            </div>
                            <div class="quick-reply-btn" onclick="insertQuickReply('Untuk reschedule tiket, silakan berikan kode booking dan tanggal penerbangan yang diinginkan.')">
                                ✈️ Reschedule
                            </div>
                        </div>

                        <!-- Message Input -->
                        <form id="messageForm" style="display: flex; gap: 0.5rem;">
                            <input type="hidden" id="selectedUserId" name="user_id">
                            <input type="text" id="messageInput" name="message" class="form-control" placeholder="Ketik balasan Anda..." required style="flex: 1;">
                            <input type="hidden" name="ajax" value="1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentUserId = null;
        let refreshInterval = null;

        // Load users list
        function loadUsers() {
            fetch('chats.php?ajax=users')
                .then(response => response.json())
                .then(users => {
                    renderUsersList(users);
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                });
        }

        // Render users list
        function renderUsersList(users) {
            const container = document.getElementById('usersList');
            
            if (users.length === 0) {
                container.innerHTML = `
                    <div style="padding: 2rem; text-align: center; color: #6b7280;">
                        <i class="fas fa-inbox"></i><br>
                        Belum ada percakapan
                    </div>
                `;
                return;
            }

            let html = '';
            users.forEach(user => {
                const unreadBadge = user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : '';
                const activeClass = currentUserId == user.id ? 'active' : '';
                const lastMessage = user.last_message ? (user.last_message.length > 40 ? user.last_message.substring(0, 40) + '...' : user.last_message) : 'Belum ada pesan';
                
                html += `
                    <div class="user-item ${activeClass}" onclick="selectUser(${user.id}, '${user.full_name}', '${user.email}')">
                        ${unreadBadge}
                        <div class="user-name">${user.full_name}</div>
                        <div class="user-email">${user.email}</div>
                        <div class="last-message">${lastMessage}</div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Select user
        function selectUser(userId, userName, userEmail) {
            currentUserId = userId;
            document.getElementById('selectedUserId').value = userId;
            
            // Update active state
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update chat header
            document.getElementById('chatHeader').innerHTML = `
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="message-avatar" style="background: #10b981; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">${userName}</div>
                        <div style="font-size: 0.75rem; opacity: 0.7;">${userEmail}</div>
                    </div>
                </div>
            `;
            
            // Show chat input
            document.getElementById('chatInput').style.display = 'block';
            
            // Load messages for this user
            loadMessages(userId);
            
            // Start auto-refresh for this conversation
            startMessageRefresh(userId);
        }

        // Load messages for specific user
        function loadMessages(userId) {
            fetch(`chats.php?ajax=messages&user_id=${userId}`)
                .then(response => response.json())
                .then(messages => {
                    renderMessages(messages);
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                });
        }

        // Render messages
        function renderMessages(messages) {
            const container = document.getElementById('chatMessages');
            
            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-chat">
                        <div>
                            <i class="fas fa-comment-alt" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>Belum ada percakapan dengan pelanggan ini</p>
                        </div>
                    </div>
                `;
                return;
            }

            let html = '';
            messages.forEach(msg => {
                const isAdmin = msg.sender_type === 'admin';
                const messageClass = isAdmin ? 'admin' : 'user';
                const time = new Date(msg.created_at).toLocaleString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <div class="message ${messageClass}">
                        <div class="message-bubble ${messageClass}">
                            <div style="white-space: pre-wrap;">${escapeHtml(msg.message)}</div>
                            <div class="message-time">${time}</div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Insert quick reply
        function insertQuickReply(message) {
            document.getElementById('messageInput').value = message;
            document.getElementById('messageInput').focus();
        }

        // Start message refresh for current conversation
        function startMessageRefresh(userId) {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            
            refreshInterval = setInterval(() => {
                if (currentUserId === userId) {
                    loadMessages(userId);
                }
            }, 3000);
        }

        // Send message
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('chats.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('messageInput').value = '';
                    loadMessages(currentUserId);
                    loadUsers(); // Refresh users list to update last message
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
            
            // Auto-refresh users list every 10 seconds
            setInterval(loadUsers, 10000);
        });

        // Clean up intervals when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>
