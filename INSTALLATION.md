# Panduan Instalasi Dashboard Monitoring PLN

## 🚀 Quick Start (Rekomendasi)

**Jika Anda sudah install PHP, Composer, dan Node.js:**

1. Double-click `setup.bat` - Setup otomatis
2. Double-click `start-app.bat` - Jalankan aplikasi
3. Buka browser: http://localhost:8000

---

## 📋 Requirements

| Software | Versi Minimum | Download |
|----------|--------------|----------|
| PHP | 8.2+ | https://windows.php.net/download/ |
| Composer | 2.x | https://getcomposer.org/ |
| Node.js | 18.x (LTS) | https://nodejs.org/ |

## 📥 Langkah Instalasi Prerequisites

### 1. Install PHP

1. Download **VS17 x64 Thread Safe** dari https://windows.php.net/download/
2. Extract ke `C:\php`
3. **Tambahkan ke PATH:**
   - Tekan `Win + R`, ketik `sysdm.cpl`, Enter
   - Tab "Advanced" → "Environment Variables"
   - Di "System variables", cari `Path` → Edit → New → `C:\php`
   - OK semua dialog
4. **Konfigurasi php.ini:**
   - Di folder `C:\php`, copy `php.ini-development` menjadi `php.ini`
   - Buka `php.ini` dengan Notepad
   - Cari dan hapus `;` (uncomment) di baris:
     ```ini
     extension=openssl
     extension=pdo_sqlite
     extension=mbstring
     extension=fileinfo
     extension=zip
     extension_dir = "ext"
     ```
5. **Verifikasi:** Buka CMD baru, ketik `php -v`

### 2. Install Composer

1. Download https://getcomposer.org/Composer-Setup.exe
2. Jalankan installer
3. **Verifikasi:** Buka CMD baru, ketik `composer -V`

### 3. Install Node.js

1. Download LTS dari https://nodejs.org/
2. Jalankan installer (default semua)
3. **Verifikasi:** Buka CMD baru, ketik `npm -v`

---

## 🛠️ Setup Project

### Cara Otomatis (Rekomendasi)

1. Copy folder project ke lokasi yang diinginkan
2. **Double-click `setup.bat`**
3. Tunggu sampai selesai
4. Jika ditanya "Langsung jalankan aplikasi?", ketik `Y`

### Cara Manual

```powershell
# 1. Copy environment file
Copy-Item .env.example .env

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Generate application key
php artisan key:generate

# 5. Buat database
New-Item -Path database\database.sqlite -ItemType File -Force

# 6. Jalankan migrasi
php artisan migrate
```

---

## ▶️ Menjalankan Aplikasi

### Cara Otomatis (Rekomendasi)

**Double-click `start-app.bat`** - Otomatis menjalankan kedua server dan membuka browser

### Cara Manual

**Terminal 1 - Backend:**
```powershell
php artisan serve
```

**Terminal 2 - Frontend:**
```powershell
npm run dev
```

Akses aplikasi di: http://localhost:8000

---

## 📊 Import Data

1. Buka http://localhost:8000
2. Navigasi ke halaman monitoring yang diinginkan:
   - **BFKO Monitoring** - Import file Excel BFKO
   - **CC Card Monitoring** - Import file CSV CC Card
   - **Service Fee Monitoring** - Import file Excel Service Fee
   - **SPPD Monitoring** - Import file Excel SPPD
3. Klik tombol Import dan pilih file

### Format File yang Didukung:
- **BFKO:** Excel (.xlsx) dengan format standar
- **CC Card:** CSV dengan 9 atau 14 kolom
- **Service Fee:** Excel (.xlsx) dengan format standar
- **SPPD:** Excel (.xlsx) dengan format standar

---

## 🔧 Troubleshooting

### Error "PHP tidak ditemukan"
- Pastikan PHP sudah di-install dan ditambahkan ke PATH
- **Restart terminal/CMD** setelah menambahkan PATH
- Cek dengan: `where php`

### Error "pdo_sqlite tidak aktif"
- Buka `C:\php\php.ini`
- Cari `extension=pdo_sqlite`, hapus `;` di depannya
- Pastikan `extension_dir = "ext"` juga di-uncomment
- Restart terminal

### Error "npm tidak ditemukan"
- Pastikan Node.js sudah terinstall
- Restart terminal setelah install
- Cek dengan: `where npm`

### Error "SQLSTATE[HY000] unable to open database file"
- Pastikan folder `database` ada
- Jalankan: `php artisan migrate --force`

### Halaman kosong / Error 500
- Cek log di `storage/logs/laravel.log`
- Pastikan `php artisan key:generate` sudah dijalankan
- Jalankan: `php artisan config:clear`

---

## 📁 Struktur File Penting

```
├── setup.bat           # Setup otomatis (jalankan pertama kali)
├── start-app.bat       # Jalankan aplikasi (kedua server)
├── start-backend.bat   # Jalankan backend saja
├── start-frontend.bat  # Jalankan frontend saja
├── .env                # Konfigurasi (dibuat dari .env.example)
└── database/
    └── database.sqlite # Database SQLite
```

---

## 👥 Support

Jika ada masalah, hubungi tim IT atau lihat log error di `storage/logs/laravel.log`

### Port 8000 sudah dipakai
```powershell
php artisan serve --port=8080
```

### Database locked
- Tutup semua koneksi ke database
- Restart Laravel server

## Production Deployment

### Build Assets
```powershell
npm run build
```

### Web Server Configuration
Untuk production, gunakan web server seperti:
- Apache dengan mod_php
- Nginx dengan PHP-FPM
- IIS dengan PHP FastCGI

Point document root ke folder `public/`

### Environment Variables
Edit `.env` untuk production:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com
```

### Optimize Laravel
```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## File Structure

```
├── app/                  # Laravel application code
├── bootstrap/            # Framework bootstrap
├── config/              # Configuration files
├── database/            # Migrations & seeders
│   └── database.sqlite  # SQLite database file
├── data/                # CSV import files
├── public/              # Web root (assets)
├── resources/           # Views, JS, CSS
│   ├── js/             # React components
│   └── css/            # Tailwind CSS
├── routes/              # Route definitions
├── storage/             # Logs, cache
└── vendor/              # Composer dependencies
```

## Support

Untuk bantuan lebih lanjut, hubungi tim development.
