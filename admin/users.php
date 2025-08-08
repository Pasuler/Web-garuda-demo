<?php
require_once '../includes/config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    redirectTo('../login.php');
}

$admin = getUserData();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'add':
            $email = sanitizeInput($_POST['email']);
            $full_name = sanitizeInput($_POST['full_name']);
            $phone = sanitizeInput($_POST['phone']);
            $password = $_POST['password'];
            $role = $_POST['role'] ?? 'user';
            
            if (empty($email) || empty($full_name) || empty($password)) {
                $error = 'Email, nama lengkap, dan password harus diisi!';
            } else {
                try {
                    // Check if email already exists
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check_stmt->execute([$email]);
                    
                    if ($check_stmt->fetch()) {
                        $error = 'Email sudah terdaftar!';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$email, $full_name, $phone, $hashed_password, $role]);
                        $message = 'Pengguna berhasil ditambahkan!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'edit':
            $id = (int)$_POST['id'];
            $email = sanitizeInput($_POST['email']);
            $full_name = sanitizeInput($_POST['full_name']);
            $phone = sanitizeInput($_POST['phone']);
            $role = $_POST['role'];
            $password = $_POST['password'];
            
            if (empty($email) || empty($full_name)) {
                $error = 'Email dan nama lengkap harus diisi!';
            } else {
                try {
                    // Check if email already exists for other users
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $check_stmt->execute([$email, $id]);
                    
                    if ($check_stmt->fetch()) {
                        $error = 'Email sudah digunakan pengguna lain!';
                    } else {
                        if (!empty($password)) {
                            // Update with new password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ?, phone = ?, password = ?, role = ? WHERE id = ?");
                            $stmt->execute([$email, $full_name, $phone, $hashed_password, $role, $id]);
                        } else {
                            // Update without changing password
                            $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ?, phone = ?, role = ? WHERE id = ?");
                            $stmt->execute([$email, $full_name, $phone, $role, $id]);
                        }
                        $message = 'Pengguna berhasil diperbarui!';
                    }
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            
            // Prevent deleting current admin user
            if ($id == $_SESSION['user_id']) {
                $error = 'Tidak dapat menghapus akun admin yang sedang login!';
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Pengguna berhasil dihapus!';
                } catch(PDOException $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Ensure integers for SQL safety
$limit = (int)$limit;
$offset = (int)$offset;

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM users WHERE 1=1";
$count_params = [];

if (!empty($search)) {
    $count_query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

if (!empty($role_filter)) {
    $count_query .= " AND role = ?";
    $count_params[] = $role_filter;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Panel</title>
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

        .btn-primary {
            background-color: var(--primary-blue);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--secondary-blue);
        }

        .btn-success {
            background-color: var(--green);
            color: var(--white);
        }

        .btn-danger {
            background-color: var(--red);
            color: var(--white);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-gray);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary-blue);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--white);
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--dark-gray);
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-admin {
            background-color: var(--red);
            color: var(--white);
        }

        .badge-user {
            background-color: var(--secondary-blue);
            color: var(--white);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--medium-gray);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: var(--green);
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: var(--red);
            border: 1px solid #fecaca;
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .search-filters .form-input,
        .search-filters .form-select {
            flex: 1;
            min-width: 200px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: var(--dark-gray);
        }

        .pagination a:hover {
            background-color: var(--light-blue);
        }

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
            <li><a href="bookings.php"><i class="fas fa-calendar-alt"></i> Pemesanan</a></li>
            <li><a href="payments.php"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
            <li><a href="hotels.php"><i class="fas fa-hotel"></i> Kelola Hotel</a></li>
            <li><a href="tickets.php"><i class="fas fa-plane"></i> Kelola Tiket</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Pengguna</a></li>
            <li><a href="chats.php"><i class="fas fa-comments"></i> Customer Service</a></li>
            <li><a href="../index.php"><i class="fas fa-globe"></i> Lihat Website</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="card">
            <h1 style="color: var(--primary-blue); margin-bottom: 0.5rem;">
                <i class="fas fa-users"></i> Kelola Pengguna
            </h1>
            <p style="color: var(--dark-gray); margin: 0;">
                Kelola semua pengguna sistem
            </p>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Controls -->
        <div class="card">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
                <button class="btn btn-primary" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Tambah Pengguna
                </button>
            </div>

            <!-- Search and Filters -->
            <form method="GET" class="search-filters">
                <input type="text" name="search" class="form-input" placeholder="Cari nama, email, atau telepon..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="role" class="form-select">
                    <option value="">Semua Role</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <a href="users.php" class="btn" style="background-color: var(--medium-gray); color: var(--white);">
                    <i class="fas fa-refresh"></i> Reset
                </a>
            </form>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Role</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--medium-gray);">
                                    Tidak ada pengguna ditemukan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        <?php endif; ?>
                                    </td>
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
                    $query_params = http_build_query(array_filter(['search' => $search, 'role' => $role_filter]));
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
                Menampilkan <?php echo count($users); ?> dari <?php echo $total_users; ?> pengguna
            </p>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn" style="background-color: var(--medium-gray); color: var(--white);" 
                            onclick="closeModal('addModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit Pengguna</h2>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editUserId">
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="editEmail" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="full_name" id="editFullName" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" id="editPhone" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password Baru (kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" id="editPassword" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" id="editRole" class="form-select">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn" style="background-color: var(--medium-gray); color: var(--white);" 
                            onclick="closeModal('editModal')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-trash"></i> Konfirmasi Hapus</h2>
            </div>
            <p>Apakah Anda yakin ingin menghapus pengguna <strong id="deleteUserName"></strong>?</p>
            <p style="color: var(--red); font-size: 0.875rem;">Tindakan ini tidak dapat dibatalkan.</p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteUserId">
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn" style="background-color: var(--medium-gray); color: var(--white);" 
                            onclick="closeModal('deleteModal')">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            
            // Reset forms
            if (modalId === 'addModal') {
                document.querySelector('#addModal form').reset();
            } else if (modalId === 'editModal') {
                document.getElementById('editForm').reset();
            }
        }

        function editUser(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editFullName').value = user.full_name;
            document.getElementById('editPhone').value = user.phone || '';
            document.getElementById('editRole').value = user.role;
            document.getElementById('editPassword').value = '';
            
            openModal('editModal');
        }

        function deleteUser(id, name) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;
            
            openModal('deleteModal');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
