<?php
require_once '../../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('../../index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email, password, full_name, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    redirectTo('../../admin/dashboard.php');
                } else {
                    redirectTo('../../index.php');
                }
            } else {
                $error = 'Email atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Garuda Indonesia</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card fade-in-up">
            <div class="auth-logo">
                <img src="../../Assets/images/Logo(Vertikal).png" alt="Garuda Indonesia" class="pulse">
                <h2 class="auth-title">Selamat Datang Kembali</h2>
                <p style="color: var(--dark-gray); margin-bottom: 2rem;">Masuk ke akun Garuda Indonesia Anda</p>
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

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Masukkan email Anda"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                        Password
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Masukkan password Anda"
                            required
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()" 
                            style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-blue); cursor: pointer;"
                        >
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="remember" style="margin: 0;">
                        <span style="color: var(--dark-gray);">Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                    <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                    Masuk
                </button>
            </form>

            <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <p style="color: var(--dark-gray); margin-bottom: 1rem;">Belum memiliki akun?</p>
                <a href="register.php" class="btn btn-outline" style="width: 100%;">
                    <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>
                    Daftar Sekarang
                </a>
            </div>

            <!-- Demo Account Info -->
            <div style="background: var(--light-blue); padding: 1rem; border-radius: 10px; margin-top: 1rem;">
                <p style="color: var(--primary-blue); font-weight: 600; margin-bottom: 0.5rem;">
                    <i class="fas fa-info-circle"></i> Demo Account:
                </p>
                <p style="color: var(--dark-gray); font-size: 0.875rem; margin: 0;">
                    <strong>Admin:</strong> admin@garuda.co.id / admin123<br>
                    <strong>User:</strong> Daftar akun baru untuk pengguna
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Add floating labels effect
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.classList.remove('focused');
                }
            });
        });

        // Prevent multiple form submissions
        document.querySelector('form').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        });
    </script>
</body>
</html>
