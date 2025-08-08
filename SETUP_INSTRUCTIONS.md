# Setup Instructions - Garuda Indonesia Website

Panduan lengkap untuk menginstal dan menjalankan website Garuda Indonesia di perangkat baru.

## Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi / MariaDB 10.3 atau lebih tinggi
- Apache Web Server
- Git (opsional, untuk clone repository)

---

## Setup dengan Laragon (Windows)

### 1. Download dan Install Laragon
- Download Laragon dari https://laragon.org/download/
- Install Laragon dengan pengaturan default
- Pastikan PHP, MySQL/MariaDB, dan Apache terinstall

### 2. Clone atau Copy Project
Buka **Terminal** di Laragon:

```bash
# Jika menggunakan Git
cd C:\laragon\www
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda

# Atau jika sudah ada folder project, pastikan berada di:
# C:\laragon\www\website-garuda\
```

### 3. Setup Database
Buka **Terminal** di Laragon dan jalankan perintah berikut:

```bash
# Masuk ke direktori project
cd C:\laragon\www\website-garuda

# Login ke MySQL
mysql -u root -p
```

Di dalam MySQL console, jalankan:

```sql
-- Import database
source database/database.sql;

-- Atau jika ingin manual
CREATE DATABASE garuda_indonesia_website;
exit
```

Kemudian import file SQL:

```bash
mysql -u root -p garuda_indonesia_website < database/database.sql
```

### 4. Konfigurasi Database Connection
Edit file `includes/config.php`:

```php
<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";  // Kosong untuk Laragon default
$dbname = "garuda_indonesia_website";
?>
```

### 5. Set Permissions untuk Upload Directory
Buka **Command Prompt as Administrator**:

```cmd
cd C:\laragon\www\website-garuda
icacls uploads /grant Everyone:(OI)(CI)F
icacls pages/booking/uploads /grant Everyone:(OI)(CI)F
```

### 6. Start Services dan Test
1. Buka Laragon
2. Click **Start All**
3. Akses website di: `http://localhost/website-garuda`

### 7. Login Admin Default
- **Email:** admin@garudaindonesia.com
- **Password:** admin123

---

## Setup dengan XAMPP (Windows/Linux/Mac)

### 1. Download dan Install XAMPP
- Download XAMPP dari https://www.apachefriends.org/download.html
- Install XAMPP dengan komponen PHP, MySQL, dan Apache

### 2. Clone atau Copy Project
Buka **Command Prompt/Terminal**:

```bash
# Windows
cd C:\xampp\htdocs
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda

# Linux/Mac
cd /opt/lampp/htdocs
sudo git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda
```

### 3. Start XAMPP Services
1. Buka XAMPP Control Panel
2. Start **Apache** dan **MySQL**

### 4. Setup Database
Buka **Command Prompt/Terminal**:

#### Windows:
```cmd
cd C:\xampp\htdocs\website-garuda

# Login ke MySQL
C:\xampp\mysql\bin\mysql.exe -u root -p

# Atau gunakan phpMyAdmin di http://localhost/phpmyadmin
```

#### Linux/Mac:
```bash
cd /opt/lampp/htdocs/website-garuda

# Login ke MySQL
/opt/lampp/bin/mysql -u root -p
```

Di dalam MySQL console:

```sql
-- Import database
CREATE DATABASE garuda_indonesia_website;
USE garuda_indonesia_website;
source database/database.sql;

-- Atau manual import
exit
```

Import via command line:
```bash
# Windows
C:\xampp\mysql\bin\mysql.exe -u root -p garuda_indonesia_website < database/database.sql

# Linux/Mac
/opt/lampp/bin/mysql -u root -p garuda_indonesia_website < database/database.sql
```

### 5. Konfigurasi Database Connection
Edit file `includes/config.php`:

```php
<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";  // Atau password MySQL Anda
$dbname = "garuda_indonesia_website";
?>
```

### 6. Set Permissions untuk Upload Directory

#### Windows:
```cmd
cd C:\xampp\htdocs\website-garuda
icacls uploads /grant Everyone:(OI)(CI)F
icacls pages\booking\uploads /grant Everyone:(OI)(CI)F
```

#### Linux/Mac:
```bash
cd /opt/lampp/htdocs/website-garuda
sudo chmod -R 777 uploads/
sudo chmod -R 777 pages/booking/uploads/
sudo chown -R www-data:www-data uploads/
sudo chown -R www-data:www-data pages/booking/uploads/
```

### 7. Test Website
Akses website di: `http://localhost/website-garuda`

---

## Troubleshooting

### Database Connection Error
- Pastikan MySQL service berjalan
- Cek username dan password di `includes/config.php`
- Pastikan database `garuda_indonesia_website` sudah dibuat

### Upload File Error
- Pastikan folder `uploads/` dan `pages/booking/uploads/` memiliki permission write
- Cek setting PHP `upload_max_filesize` dan `post_max_size`

### Permission Denied Error (Linux/Mac)
```bash
sudo chown -R www-data:www-data /opt/lampp/htdocs/website-garuda/
sudo chmod -R 755 /opt/lampp/htdocs/website-garuda/
sudo chmod -R 777 /opt/lampp/htdocs/website-garuda/uploads/
```

### Port Conflict
- Laragon: Ubah port di Laragon settings
- XAMPP: Edit `httpd.conf` untuk mengubah port Apache

---

## Default Login Credentials

### Admin Account
- **Email:** admin@garudaindonesia.com
- **Password:** admin123

**⚠️ PENTING:** Ganti password admin default setelah instalasi untuk keamanan!

---

## File dan Folder Penting

```
website-garuda/
├── database/
│   └── database.sql          # File database utama
├── includes/
│   ├── config.php           # Konfigurasi database
│   └── upload_config.php    # Konfigurasi upload
├── uploads/                 # Folder upload files
├── admin/                   # Panel admin
└── pages/                   # Halaman utama website
```

---

## Kontak Support

Jika mengalami kendala dalam instalasi, silakan hubungi tim development atau buat issue di repository GitHub.
