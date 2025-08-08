# Website Garuda Indonesia - Sistem Pemesanan Tiket & Hotel

Website sistem pemesanan tiket pesawat dan hotel yang terintegrasi untuk Garuda Indonesia. Dibangun dengan PHP, MySQL, dan teknologi web modern.

## 🚀 Fitur Utama

- **Sistem Autentikasi**: Login dan registrasi pengguna
- **Pemesanan Terintegrasi**: Tiket pesawat, hotel, atau kombinasi keduanya
- **Panel Admin**: Kelola data hotel, tiket, dan pembayaran
- **Upload Bukti Pembayaran**: Sistem verifikasi pembayaran
- **Customer Service**: Fitur chat dengan CS
- **Responsive Design**: Tampilan optimal di semua perangkat

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Icons**: Font Awesome 6
- **Server**: Apache/Nginx (Laragon, XAMPP, dll)

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache/Nginx web server
- Extensions PHP yang diperlukan:
  - PDO
  - PDO_MySQL
  - GD (untuk upload gambar)
  - Session

## 🔧 Instalasi

### 1. Clone atau Download Project
```bash
git clone <repository-url>
# atau download dan extract ke folder web server
```

### 2. Setup Database
1. Buat database baru dengan nama `garuda_indonesia_website`
2. Import file `database.sql` ke database tersebut:
   ```sql
   mysql -u root -p garuda_indonesia_website < database.sql
   ```

### 3. Konfigurasi Database
Edit file `includes/config.php` sesuai dengan konfigurasi database Anda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'garuda_indonesia_website');
```

### 4. Set Permissions
Berikan permission write pada folder uploads:
```bash
chmod 755 uploads/
chmod 755 uploads/payment_receipts/
```

### 5. Akses Website
Buka browser dan akses:
```
http://localhost/website-garuda/
```

## 🔐 Akun Default

### Admin
- **Email**: admin@garuda.co.id
- **Password**: admin123

### User
Daftar akun baru melalui halaman register.

## 📁 Struktur Folder

```
website-garuda/
├── Assets/
│   ├── fonts/
│   └── images/
│       ├── Logo(Horizontal).png
│       └── Logo(Vertikal).png
├── css/
│   └── style.css
├── js/
│   └── main.js
├── includes/
│   └── config.php
├── admin/
│   └── (panel admin files)
├── uploads/
│   └── payment_receipts/
├── database.sql
├── index.php
├── login.php
├── register.php
├── booking.php
├── logout.php
└── README.md
```

## 🗃️ Struktur Database

### Tables
1. **users** - Data pengguna dan admin
2. **hotels** - Data hotel tersedia
3. **tickets** - Data tiket penerbangan
4. **bookings** - Data pemesanan
5. **payments** - Data pembayaran
6. **chats** - Data percakapan CS

## 🎨 Customization

### Logo
Ganti logo di folder `Assets/images/`:
- `Logo(Horizontal).png` - Logo horizontal untuk header
- `Logo(Vertikal).png` - Logo vertikal untuk halaman login/register

### Warna & Styling
Edit file `css/style.css`, terutama bagian CSS variables:
```css
:root {
    --primary-blue: #1e3a8a;
    --secondary-blue: #3b82f6;
    --garuda-teal: #0891b2;
    /* ... */
}
```

## 🔧 Pengembangan Lanjutan

### Menambah Hotel Baru
1. Login sebagai admin
2. Akses panel admin
3. Tambahkan data hotel baru

### Menambah Rute Penerbangan
1. Login sebagai admin
2. Akses panel admin
3. Tambahkan tiket penerbangan baru

## 🐛 Troubleshooting

### Error Database Connection
- Pastikan service MySQL berjalan
- Periksa kredensial database di `includes/config.php`
- Pastikan database sudah diimport

### Permission Denied saat Upload
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### Session Issues
Pastikan PHP session dapat menulis ke folder temp:
```bash
chmod 777 /tmp
```

## 📞 Support

Untuk pertanyaan teknis atau bantuan pengembangan:
- Email: support@garuda-indonesia.com
- Documentation: [Link dokumentasi]

## 📄 License

© 2025 Garuda Indonesia. All rights reserved.

## 🚀 Deployment

### Production Checklist
- [ ] Ganti password default admin
- [ ] Update konfigurasi database
- [ ] Set environment ke production
- [ ] Enable SSL/HTTPS
- [ ] Set proper file permissions
- [ ] Backup database secara berkala
- [ ] Monitor logs error

### Performance Optimization
- Enable PHP OPcache
- Compress CSS/JS files
- Optimize database queries
- Implement caching mechanism
- Use CDN untuk assets

## 🔄 Updates

### Version History
- **v1.0.0** - Initial release dengan fitur dasar
- **v1.1.0** - Tambahan fitur chat CS (coming soon)
- **v1.2.0** - Panel admin lengkap (coming soon)

---

**Catatan**: Website ini dibangun untuk keperluan demo dan pembelajaran. Untuk penggunaan produksi, diperlukan additional security measures dan optimizations.
