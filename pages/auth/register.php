<?php
require_once '../../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('../../index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar! Silakan gunakan email lain.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $phone, $hashed_password]);
                
                $success = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
                
                // Clear form data
                $_POST = array();
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
    <title>Daftar - Garuda Indonesia</title>
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card fade-in-up" style="max-width: 500px;">
            <div class="auth-logo">
                <img src="../../Assets/images/Logo(Vertikal).png" alt="Garuda Indonesia" class="pulse">
                <h2 class="auth-title">Bergabung dengan Kami</h2>
                <p style="color: var(--dark-gray); margin-bottom: 2rem;">Daftar untuk menikmati layanan terbaik Garuda Indonesia</p>
            </div>

            <?php if ($error): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <div style="margin-top: 1rem;">
                        <a href="login.php" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-sign-in-alt"></i> Login Sekarang
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                                Nama Lengkap
                            </label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                class="form-control" 
                                placeholder="Masukkan nama lengkap Anda"
                                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
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
                                placeholder="contoh@email.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                                No. Telepon
                            </label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                class="form-control" 
                                placeholder="08xxxxxxxxxx"
                                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
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
                                    placeholder="Minimal 6 karakter"
                                    required
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('password', 'toggleIcon1')" 
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-blue); cursor: pointer;"
                                >
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                            <div id="passwordStrength" style="margin-top: 0.5rem; font-size: 0.875rem;"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock" style="margin-right: 0.5rem; color: var(--primary-blue);"></i>
                                Konfirmasi Password
                            </label>
                            <div style="position: relative;">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    placeholder="Ulangi password"
                                    required
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('confirm_password', 'toggleIcon2')" 
                                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-blue); cursor: pointer;"
                                >
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" style="margin-top: 0.5rem; font-size: 0.875rem;"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="terms" required style="margin-top: 0.25rem;">
                        <span style="color: var(--dark-gray); font-size: 0.875rem;">
                            Saya menyetujui <a href="#" style="color: var(--primary-blue);">Syarat dan Ketentuan</a> serta <a href="#" style="color: var(--primary-blue);">Kebijakan Privasi</a> Garuda Indonesia
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 1rem;">
                    <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>
                    Daftar Sekarang
                </button>
            </form>

            <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <p style="color: var(--dark-gray); margin-bottom: 1rem;">Sudah memiliki akun?</p>
                <a href="login.php" class="btn btn-outline" style="width: 100%;">
                    <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                    Masuk ke Akun
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
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

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let message = '';
            let color = '';

            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            switch(strength) {
                case 0:
                case 1:
                    message = 'Password sangat lemah';
                    color = '#ef4444';
                    break;
                case 2:
                    message = 'Password lemah';
                    color = '#f97316';
                    break;
                case 3:
                    message = 'Password sedang';
                    color = '#eab308';
                    break;
                case 4:
                    message = 'Password kuat';
                    color = '#22c55e';
                    break;
                case 5:
                    message = 'Password sangat kuat';
                    color = '#16a34a';
                    break;
            }

            strengthDiv.innerHTML = `<span style="color: ${color};">${message}</span>`;
        });

        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPassword === '') {
                matchDiv.innerHTML = '';
            } else if (password === confirmPassword) {
                matchDiv.innerHTML = '<span style="color: #16a34a;"><i class="fas fa-check"></i> Password cocok</span>';
            } else {
                matchDiv.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times"></i> Password tidak cocok</span>';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
                return;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('62')) {
                value = '0' + value.substring(2);
            }
            this.value = value;
        });
    </script>
</body>
</html>
