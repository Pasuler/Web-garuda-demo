# 🚀 Cara Menggunakan Setup Garuda Indonesia Website

Panduan lengkap cara menggunakan file setup yang telah disediakan untuk menginstall website Garuda Indonesia di perangkat baru.

## 📁 File Setup yang Tersedia

1. **`SETUP_INSTRUCTIONS.md`** - Panduan manual lengkap
2. **`setup_windows.bat`** - Script otomatis untuk Windows
3. **`setup_linux_mac.sh`** - Script otomatis untuk Linux/Mac
4. **`CARA_MENGGUNAKAN.md`** - File ini (panduan penggunaan)

---

## 🎯 Pilih Metode Setup Anda

### Metode 1: Setup Otomatis (RECOMMENDED) ⚡
**Paling cepat dan mudah - hanya 2-3 langkah!**

### Metode 2: Setup Manual 📖  
**Ikuti panduan step-by-step detail**

---

## 🔥 METODE 1: SETUP OTOMATIS

### Untuk Windows (Laragon/XAMPP)

#### Step 1: Clone Repository
```bash
# Jika menggunakan Laragon
cd C:\laragon\www
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda

# Jika menggunakan XAMPP  
cd C:\xampp\htdocs
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda
```

#### Step 2: Masuk ke Direktori Project
```bash
cd website-garuda
```

#### Step 3: Pastikan MySQL/MariaDB Berjalan
- **Laragon:** Buka Laragon → Klik "Start All"
- **XAMPP:** Buka XAMPP Control Panel → Start Apache & MySQL

#### Step 4: Jalankan Setup Otomatis
```bash
# Double-click file setup_windows.bat
# ATAU jalankan di Command Prompt:
setup_windows.bat
```

#### Step 5: Website Siap Digunakan! 🎉
- **Website:** http://localhost/website-garuda
- **Admin Panel:** http://localhost/website-garuda/admin
- **Login Admin:** admin@garudaindonesia.com / admin123

---

### Untuk Linux/Mac (XAMPP/LAMP/MAMP)

#### Step 1: Clone Repository
```bash
# XAMPP
cd /opt/lampp/htdocs
sudo git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda

# LAMP/Manual Apache
cd /var/www/html
sudo git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda

# MAMP (Mac)
cd /Applications/MAMP/htdocs
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda
```

#### Step 2: Masuk ke Direktori dan Set Permission
```bash
cd website-garuda
sudo chown -R $USER:$USER .
```

#### Step 3: Pastikan Services Berjalan
```bash
# Cek Apache
sudo systemctl status apache2
# atau
sudo systemctl start apache2

# Cek MySQL
sudo systemctl status mysql
# atau  
sudo systemctl start mysql
```

#### Step 4: Jalankan Setup Otomatis
```bash
# Berikan permission execute
chmod +x setup_linux_mac.sh

# Jalankan script
./setup_linux_mac.sh
```

#### Step 5: Website Siap Digunakan! 🎉
- **Website:** http://localhost/website-garuda
- **Admin Panel:** http://localhost/website-garuda/admin

---

## 📖 METODE 2: SETUP MANUAL

Jika ingin melakukan setup manual atau script otomatis gagal:

### Step 1: Baca Panduan Lengkap
```bash
# Buka file panduan
notepad SETUP_INSTRUCTIONS.md      # Windows
nano SETUP_INSTRUCTIONS.md         # Linux
open SETUP_INSTRUCTIONS.md         # Mac
```

### Step 2: Pilih Platform Anda
Di dalam `SETUP_INSTRUCTIONS.md`, pilih salah satu:
- **Setup dengan Laragon** (Windows)
- **Setup dengan XAMPP** (Windows/Linux/Mac)

### Step 3: Ikuti Langkah Demi Langkah
Ikuti semua instruksi di file tersebut, mulai dari:
1. Download dan install web server
2. Clone repository
3. Setup database manual
4. Konfigurasi config.php
5. Set permissions
6. Testing

---

## 🔧 Apa yang Dilakukan Script Otomatis?

### `setup_windows.bat`
```
[1/4] ✅ Mengecek koneksi MySQL
[2/4] ✅ Membuat database 'garuda_indonesia_website'  
[3/4] ✅ Import struktur tabel dan data sample
[4/4] ✅ Setup folder upload + permissions
```

### `setup_linux_mac.sh`
```
[1/5] ✅ Cek instalasi MySQL
[2/5] ✅ Test koneksi database (input password)
[3/5] ✅ Buat database dan import
[4/5] ✅ Setup direktori upload
[5/5] ✅ Set permissions & ownership
```

---

## 🎯 Quick Start (TL;DR)

### Laragon (Windows) - 30 detik setup:
```bash
cd C:\laragon\www
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda
cd website-garuda
setup_windows.bat
# Buka: http://localhost/website-garuda
```

### XAMPP (Windows) - 30 detik setup:
```bash
cd C:\xampp\htdocs  
git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda
cd website-garuda
setup_windows.bat
# Buka: http://localhost/website-garuda
```

### Linux/Mac - 1 menit setup:
```bash
cd /opt/lampp/htdocs  # atau /var/www/html
sudo git clone https://github.com/Pasuler/Web-garuda-demo.git website-garuda
cd website-garuda
chmod +x setup_linux_mac.sh
./setup_linux_mac.sh
# Buka: http://localhost/website-garuda
```

---

## 🔐 Login Default Setelah Setup

### Admin Account
- **URL:** http://localhost/website-garuda/admin
- **Email:** `admin@garudaindonesia.com`
- **Password:** `admin123`

### User Account (untuk testing)
Bisa register user baru di: http://localhost/website-garuda/pages/auth/register.php

---

## ❗ Troubleshooting Cepat

### Error "Database connection failed"
```bash
# Pastikan MySQL berjalan
# Laragon: Start MySQL di panel Laragon  
# XAMPP: Start MySQL di XAMPP Control Panel
# Linux: sudo systemctl start mysql
```

### Error "Permission denied" (Linux/Mac)
```bash
sudo chown -R www-data:www-data website-garuda/
sudo chmod -R 755 website-garuda/
sudo chmod -R 777 website-garuda/uploads/
```

### Port 80 sudah digunakan
- **Laragon:** Settings → Apache → Port → Ubah ke 8080
- **XAMPP:** Edit httpd.conf → Listen 8080
- Akses: http://localhost:8080/website-garuda

### Script setup_windows.bat tidak jalan
```bash
# Pastikan menjalankan sebagai Administrator
# Klik kanan → Run as Administrator
```

---

## 📋 Checklist Setelah Instalasi

- [ ] Website bisa diakses di http://localhost/website-garuda
- [ ] Admin panel bisa login di /admin
- [ ] Bisa register user baru
- [ ] Upload receipt berfungsi di booking
- [ ] Database terisi data sample (hotel, tiket)
- [ ] **PENTING:** Ganti password admin default!

---

## 🆘 Butuh Bantuan?

1. **Cek file `SETUP_INSTRUCTIONS.md`** untuk troubleshooting lengkap
2. **Lihat folder `utils/`** untuk script debugging
3. **Buat issue di GitHub** jika masih error
4. **Kontak tim development**

---

## 📝 Catatan Penting

- **Database:** Script otomatis membuat database `garuda_indonesia_website`
- **Sample Data:** Sudah include 3 hotel, 4 tiket, 1 admin user
- **Permissions:** Upload folder sudah di-set otomatis
- **Security:** Jangan lupa ganti password admin setelah login!
- **Backup:** Selalu backup database sebelum update

---

## 🔄 Update Website dari Repository

Jika ada update baru:
```bash
cd website-garuda
git pull origin main
# Jalankan migrasi jika ada perubahan database
```

---

## 📊 Database Migration Guide

### Apa itu Database Migration?
Migration adalah cara untuk mengupdate struktur database (menambah tabel, kolom, index, dll) tanpa merusak data yang sudah ada.

### Kapan Perlu Menjalankan Migration?
- Setelah `git pull` dari repository
- Ada update struktur database baru
- Ada file baru di folder `database/migrations/`
- Ada instruksi khusus dari developer

---

### 🛠️ Cara Menjalankan Migration

#### Metode 1: Manual via PHP (Recommended)

##### Windows (Laragon/XAMPP):
```bash
cd C:\laragon\www\website-garuda
# atau cd C:\xampp\htdocs\website-garuda

# Jalankan semua migration files
php database/migrations/add_seat_column.php

# Jika ada migration file lain, jalankan satu per satu:
# php database/migrations/nama_migration_file.php
```

##### Linux/Mac:
```bash
cd /opt/lampp/htdocs/website-garuda
# atau cd /var/www/html/website-garuda

# Jalankan migration
php database/migrations/add_seat_column.php
```

#### Metode 2: Via Browser (Alternative)
Buka browser dan akses:
```
http://localhost/website-garuda/database/migrations/add_seat_column.php
```

#### Metode 3: MySQL Command Line (Advanced)
```bash
# Login ke MySQL
mysql -u root -p

# Gunakan database
USE garuda_indonesia_website;

# Jalankan SQL command langsung (contoh):
ALTER TABLE bookings ADD COLUMN seat_numbers VARCHAR(255) DEFAULT NULL;
```

---

### 📁 Migration Files yang Tersedia

#### `database/migrations/add_seat_column.php`
**Fungsi:** Menambah kolom `seat_numbers` ke tabel `bookings`
```bash
# Cara menjalankan:
php database/migrations/add_seat_column.php
```

**Output yang diharapkan:**
```
✅ Column 'seat_numbers' added to bookings table successfully.
```

**Jika sudah ada:**
```
ℹ️  Column 'seat_numbers' already exists in bookings table.
```

---

### 🔍 Cara Cek Apakah Migration Berhasil

#### Via PHP Script:
Buat file `check_migration.php`:
```php
<?php
require_once 'includes/config.php';

try {
    // Cek kolom seat_numbers
    $check = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'seat_numbers'");
    if ($check->rowCount() > 0) {
        echo "✅ Migration berhasil: kolom 'seat_numbers' ada\n";
    } else {
        echo "❌ Migration belum dijalankan: kolom 'seat_numbers' tidak ada\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
```

#### Via phpMyAdmin:
1. Buka http://localhost/phpmyadmin
2. Pilih database `garuda_indonesia_website`
3. Klik tabel `bookings`
4. Cek apakah kolom `seat_numbers` sudah ada

#### Via MySQL Command:
```sql
USE garuda_indonesia_website;
DESCRIBE bookings;
-- Cek apakah ada kolom 'seat_numbers'
```

---

### 🚨 Migration Troubleshooting

#### Error: "Table doesn't exist"
```bash
# Pastikan database sudah ada
mysql -u root -p -e "SHOW DATABASES LIKE 'garuda_indonesia_website';"

# Jika belum ada, import ulang database:
mysql -u root -p garuda_indonesia_website < database/database.sql
```

#### Error: "Column already exists"
```
# Ini normal jika migration sudah pernah dijalankan
# Migration file sudah handle pengecekan otomatis
```

#### Error: "Permission denied"
```bash
# Linux/Mac - Set permission:
sudo chown -R www-data:www-data database/
chmod +x database/migrations/*.php
```

#### Error: "PHP command not found"
```bash
# Windows - Tambahkan PHP ke PATH atau gunakan full path:
C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe database/migrations/add_seat_column.php

# Atau jalankan via browser
```

---

### 📋 Checklist Setelah Migration

- [ ] Migration berhasil dijalankan (tidak ada error)
- [ ] Struktur database terupdate (cek via phpMyAdmin)
- [ ] Website masih berfungsi normal
- [ ] Data lama tidak hilang
- [ ] Fitur baru bisa digunakan (jika ada)

---

### 🔄 Rollback Migration (Jika Diperlukan)

Jika migration menyebabkan masalah, lakukan rollback:

#### Rollback `add_seat_column.php`:
```sql
-- Via MySQL command:
USE garuda_indonesia_website;
ALTER TABLE bookings DROP COLUMN seat_numbers;
```

#### Full Database Restore:
```bash
# Backup terlebih dahulu:
mysqldump -u root -p garuda_indonesia_website > backup_before_rollback.sql

# Restore database original:
mysql -u root -p garuda_indonesia_website < database/database.sql
```

---

### 💡 Tips Migration

1. **Selalu backup database** sebelum migration
2. **Test di local** dulu sebelum production
3. **Jalankan migration satu per satu** jika banyak file
4. **Cek log error** jika migration gagal
5. **Dokumentasikan** perubahan yang dilakukan

**Selamat menggunakan Website Garuda Indonesia! ✈️**
