<?php
require_once '../../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectTo('../auth/login.php');
}

$user = getUserData();

// Check if user data is valid
if (!$user || !isset($user['id'])) {
    // If it's AJAX request, return error
    if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }
    
    // Otherwise redirect to login
    session_destroy();
    redirectTo('../auth/login.php');
}

$success_sent = false;

// Handle AJAX requests for new messages
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    // Get latest messages
    $chats_stmt = $pdo->prepare("
        SELECT c.*, 
               CASE 
                   WHEN c.is_from_admin = 1 THEN 'Customer Service'
                   ELSE u.full_name 
               END as sender_name,
               CASE 
                   WHEN c.is_from_admin = 1 THEN 'admin'
                   ELSE 'user' 
               END as sender_type
        FROM chats c
        LEFT JOIN users u ON c.id_user = u.id
        WHERE c.id_user = ?
        ORDER BY c.created_at ASC
    ");
    $chats_stmt->execute([$user['id']]);
    $chats = $chats_stmt->fetchAll();
    
    // Mark admin messages as read
    $pdo->prepare("UPDATE chats SET is_read = 1 WHERE id_user = ? AND is_from_admin = 1")->execute([$user['id']]);
    
    echo json_encode($chats);
    exit;
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = sanitizeInput($_POST['message']);
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO chats (id_user, message, is_from_admin, created_at) VALUES (?, ?, 0, NOW())");
            $stmt->execute([$user['id'], $message]);
            $success_sent = true;
        } catch (Exception $e) {
            // Handle error silently
        }
    }
    
    // If it's an AJAX request, return JSON response
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success_sent]);
        exit;
    }
}

// Get chat messages for initial load
$chats_stmt = $pdo->prepare("
    SELECT c.*, 
           CASE 
               WHEN c.is_from_admin = 1 THEN 'Customer Service'
               ELSE ? 
           END as sender_name,
           CASE 
               WHEN c.is_from_admin = 1 THEN 'admin'
               ELSE 'user' 
           END as sender_type
    FROM chats c
    WHERE c.id_user = ?
    ORDER BY c.created_at ASC
");
$chats_stmt->execute([$user['full_name'], $user['id']]);
$chats = $chats_stmt->fetchAll();

// Mark admin messages as read
$pdo->prepare("UPDATE chats SET is_read = 1 WHERE id_user = ? AND is_from_admin = 1")->execute([$user['id']]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service - Garuda Indonesia</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-container {
            height: 500px;
            overflow-y: auto;
            background: #f0f2f5;
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            scroll-behavior: smooth;
        }
        
        .chat-message {
            margin-bottom: 0.75rem;
            display: flex;
            animation: fadeInUp 0.3s ease-out;
            clear: both;
        }
        
        .chat-message.user {
            justify-content: flex-end;
        }
        
        .chat-message.admin {
            justify-content: flex-start;
        }
        
        .message-bubble {
            max-width: 75%;
            padding: 0.75rem 1rem;
            word-wrap: break-word;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .message-bubble.user {
            background: var(--primary-blue);
            color: white;
            border-radius: 18px 18px 4px 18px;
            margin-left: auto;
        }
        
        .message-bubble.admin {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 18px 18px 18px 4px;
            margin-right: auto;
        }
        
        .message-time {
            font-size: 0.7rem;
            opacity: 0.8;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        .message-time.admin {
            text-align: left;
        }
        
        .sender-name {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            opacity: 0.9;
        }
        
        .sender-name.admin {
            color: #10b981;
        }
        
        .sender-name.user {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .message-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin: 0 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.875rem;
        }
        
        .message-avatar.user {
            background: var(--primary-blue);
            color: white;
            order: 1;
        }
        
        .message-avatar.admin {
            background: #10b981;
            color: white;
        }
        
        .typing-indicator {
            display: none;
            margin-bottom: 1rem;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #9ca3af;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) {
            animation-delay: 0s;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .quick-replies {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .quick-reply {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .quick-reply:hover {
            background: var(--primary-blue);
            color: white;
            border-color: var(--primary-blue);
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
                <li><a href="chat.php" class="nav-link active">Customer Service</a></li>
                <li><a href="profile.php" class="nav-link">Profil</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Keluar</a></li>
            </ul>
        </nav>
    </header>

    <div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <!-- Chat Header with Manual Refresh -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div class="card-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div>
                                <h2 style="margin: 0;">Customer Service</h2>
                                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                                    <i class="fas fa-circle" style="color: #10b981; font-size: 0.5rem;"></i>
                                    Tim kami siap membantu Anda 24/7
                                </div>
                            </div>
                        </div>
                        <button onclick="refreshMessages()" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                        </button>
                    </div>

                    <!-- Chat Container -->
                    <div class="chat-container" id="chatContainer">
                        <div id="messagesContainer">
                            <!-- Messages will be loaded here -->
                        </div>
                        
                        <div class="typing-indicator" id="typingIndicator">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div class="message-avatar admin">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div style="background: white; padding: 0.75rem 1rem; border-radius: 18px; border: 1px solid #e5e7eb;">
                                    <div style="display: flex; gap: 0.25rem;">
                                        <div class="typing-dot"></div>
                                        <div class="typing-dot"></div>
                                        <div class="typing-dot"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Replies -->
                    <div class="quick-replies">
                        <div class="quick-reply" onclick="setMessage('Saya butuh bantuan dengan pembayaran')">
                            💳 Bantuan Pembayaran
                        </div>
                        <div class="quick-reply" onclick="setMessage('Bagaimana status pemesanan saya?')">
                            📋 Status Pemesanan
                        </div>
                        <div class="quick-reply" onclick="setMessage('Saya ingin mengubah jadwal penerbangan')">
                            ✈️ Reschedule Tiket
                        </div>
                        <div class="quick-reply" onclick="setMessage('Saya ingin membatalkan pemesanan')">
                            ❌ Pembatalan
                        </div>
                    </div>

                    <!-- Message Input -->
                    <form method="POST" id="chatForm" style="display: flex; gap: 0.5rem;">
                        <input type="text" name="message" id="messageInput" class="form-control" 
                               placeholder="Ketik pesan Anda..." required style="flex: 1;">
                        <button type="submit" class="btn btn-primary" id="sendButton" style="padding: 0.75rem 1.5rem;">
                            <i class="fas fa-paper-plane" id="sendIcon"></i>
                            <i class="fas fa-spinner fa-spin" id="loadingIcon" style="display: none;"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Service Info -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3>Informasi Layanan</h3>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <i class="fas fa-clock"></i> Jam Operasional
                        </h4>
                        <p style="margin: 0; color: #6b7280;">24 Jam / 7 Hari</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <i class="fas fa-reply"></i> Waktu Respons
                        </h4>
                        <p style="margin: 0; color: #6b7280;">Rata-rata 5-10 menit</p>
                    </div>

                    <div>
                        <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                            <i class="fas fa-star"></i> Rating Layanan
                        </h4>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="color: #fbbf24;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span style="color: #6b7280;">4.9/5 (2,847 ulasan)</span>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="card mt-4">
                    <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-question-circle"></i> FAQ
                    </h4>
                    
                    <div class="faq-item" style="margin-bottom: 1rem; cursor: pointer;" onclick="toggleFAQ(this)">
                        <div style="font-weight: 600; color: var(--primary-blue); margin-bottom: 0.25rem;">
                            <i class="fas fa-chevron-right" style="transition: transform 0.3s;"></i>
                            Berapa lama verifikasi pembayaran?
                        </div>
                        <div style="display: none; color: #6b7280; font-size: 0.875rem; margin-left: 1rem;">
                            Verifikasi pembayaran biasanya memakan waktu 1-24 jam setelah bukti transfer diunggah.
                        </div>
                    </div>

                    <div class="faq-item" style="margin-bottom: 1rem; cursor: pointer;" onclick="toggleFAQ(this)">
                        <div style="font-weight: 600; color: var(--primary-blue); margin-bottom: 0.25rem;">
                            <i class="fas fa-chevron-right" style="transition: transform 0.3s;"></i>
                            Bagaimana cara mengubah jadwal?
                        </div>
                        <div style="display: none; color: #6b7280; font-size: 0.875rem; margin-left: 1rem;">
                            Hubungi customer service dengan menyebutkan kode booking Anda untuk proses reschedule.
                        </div>
                    </div>

                    <div class="faq-item" style="margin-bottom: 1rem; cursor: pointer;" onclick="toggleFAQ(this)">
                        <div style="font-weight: 600; color: var(--primary-blue); margin-bottom: 0.25rem;">
                            <i class="fas fa-chevron-right" style="transition: transform 0.3s;"></i>
                            Apakah bisa refund?
                        </div>
                        <div style="display: none; color: #6b7280; font-size: 0.875rem; margin-left: 1rem;">
                            Kebijakan refund berlaku sesuai dengan syarat dan ketentuan yang berlaku untuk setiap jenis tiket.
                        </div>
                    </div>
                </div>

                <!-- Contact Alternative -->
                <div class="card mt-4">
                    <h4 style="color: var(--primary-blue); margin-bottom: 1rem;">
                        <i class="fas fa-phone"></i> Kontak Lain
                    </h4>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>Call Center:</strong><br>
                        <a href="tel:+622180417777" style="color: var(--primary-blue);">+62 21 8041 7777</a>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong>WhatsApp:</strong><br>
                        <a href="https://wa.me/6281234567890" style="color: var(--primary-blue);">+62 812 3456 7890</a>
                    </div>
                    <div>
                        <strong>Email:</strong><br>
                        <a href="mailto:cs@garudaindonesia.com" style="color: var(--primary-blue);">cs@garudaindonesia.com</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let lastMessageCount = 0;
        let refreshInterval;
        let isLoading = false;

        // Manual refresh function
        function refreshMessages() {
            const refreshIcon = document.getElementById('refreshIcon');
            refreshIcon.classList.add('fa-spin');
            
            loadMessages()
                .then(() => {
                    refreshIcon.classList.remove('fa-spin');
                })
                .catch(() => {
                    refreshIcon.classList.remove('fa-spin');
                });
        }

        // Load messages (return promise for refresh feedback)
        function loadMessages() {
            if (isLoading) return Promise.resolve();
            
            return fetch('chat.php?ajax=1')
                .then(response => response.json())
                .then(data => {
                    renderMessages(data);
                    if (data.length !== lastMessageCount) {
                        scrollToBottom();
                        lastMessageCount = data.length;
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    throw error;
                });
        }

        // Render messages
        function renderMessages(messages) {
            const container = document.getElementById('messagesContainer');
            
            if (messages.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>Selamat datang di Customer Service Garuda Indonesia!</p>
                        <p>Silakan kirim pesan untuk memulai percakapan.</p>
                    </div>
                `;
                return;
            }

            let html = '';
            messages.forEach(msg => {
                const isAdmin = msg.is_from_admin == 1;
                const messageClass = isAdmin ? 'admin' : 'user';
                const avatarIcon = isAdmin ? 'headset' : 'user';
                const timeClass = isAdmin ? 'admin' : '';
                
                html += `
                    <div class="chat-message ${messageClass}">
                        ${isAdmin ? `<div class="message-avatar admin">
                            <i class="fas fa-${avatarIcon}"></i>
                        </div>` : ''}
                        <div class="message-bubble ${messageClass}">
                            ${isAdmin ? `<div class="sender-name admin">Customer Service</div>` : ''}
                            <div style="white-space: pre-wrap;">${escapeHtml(msg.message)}</div>
                            <div class="message-time ${timeClass}">
                                ${formatDate(msg.created_at)}
                            </div>
                        </div>
                        ${!isAdmin ? `<div class="message-avatar user">
                            <i class="fas fa-${avatarIcon}"></i>
                        </div>` : ''}
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Auto-scroll to bottom
        function scrollToBottom() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Set message from quick reply
        function setMessage(message) {
            document.getElementById('messageInput').value = message;
            document.getElementById('messageInput').focus();
        }

        // Toggle FAQ
        function toggleFAQ(element) {
            const answer = element.querySelector('div:last-child');
            const icon = element.querySelector('i');
            
            if (answer.style.display === 'none' || answer.style.display === '') {
                answer.style.display = 'block';
                icon.style.transform = 'rotate(90deg)';
            } else {
                answer.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Show loading state
        function showLoading() {
            document.getElementById('sendIcon').style.display = 'none';
            document.getElementById('loadingIcon').style.display = 'inline-block';
            document.getElementById('sendButton').disabled = true;
            document.getElementById('messageInput').disabled = true;
        }

        // Hide loading state
        function hideLoading() {
            document.getElementById('sendIcon').style.display = 'inline-block';
            document.getElementById('loadingIcon').style.display = 'none';
            document.getElementById('sendButton').disabled = false;
            document.getElementById('messageInput').disabled = false;
        }

        // Show typing indicator
        function showTyping() {
            document.getElementById('typingIndicator').style.display = 'block';
            scrollToBottom();
        }

        // Hide typing indicator
        function hideTyping() {
            document.getElementById('typingIndicator').style.display = 'none';
        }

        // Form submission with AJAX to prevent page refresh
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default form submission to avoid page refresh
            
            if (isLoading) return;
            
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) return;

            isLoading = true;
            showLoading();
            showTyping();

            const formData = new FormData();
            formData.append('message', message);
            formData.append('ajax', '1');

            fetch('chat.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    return loadMessages(); // Reload messages immediately
                } else {
                    throw new Error('Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Gagal mengirim pesan. Silakan coba lagi.');
            })
            .finally(() => {
                isLoading = false;
                hideLoading();
                setTimeout(hideTyping, 1000); // Hide typing indicator after 1 second
            });
        });

        // Auto-refresh messages every 3 seconds
        function startAutoRefresh() {
            // Auto-refresh disabled - messages only refresh on manual send
            // refreshInterval = setInterval(loadMessages, 3000);
        }

        // Stop auto-refresh
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages(); // Load initial messages
            // startAutoRefresh(); // Auto-refresh disabled
            
            // Focus on message input
            document.getElementById('messageInput').focus();
            
            // Enter key to send message
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('chatForm').dispatchEvent(new Event('submit'));
                }
            });
        });

        // Stop auto-refresh when page is unloaded
        window.addEventListener('beforeunload', function() {
            stopAutoRefresh();
        });

        // Restart auto-refresh when page becomes visible again  
        document.addEventListener('visibilitychange', function() {
            // Auto-refresh disabled
            /*
            if (document.visibilityState === 'visible') {
                loadMessages();
                if (!refreshInterval) {
                    startAutoRefresh();
                }
            } else {
                stopAutoRefresh();
            }
            */
        });
    </script>
</body>
</html>
