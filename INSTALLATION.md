# Panduan Instalasi Dashboard Monitoring PLN

## Requirements
- PHP >= 8.2
- Composer
- Node.js >= 18.x
- SQLite (sudah include di PHP)

## Langkah Instalasi

### 1. Install Prerequisites

#### Windows:
1. **PHP 8.2+**
   - Download: https://windows.php.net/download/
   - Extract ke `C:\php`
   - Tambahkan `C:\php` ke PATH environment variable
   - Copy `php.ini-development` menjadi `php.ini`
   - Enable extensions di php.ini (hapus ; di depan):
     ```
     extension=pdo_sqlite
     extension=sqlite3
     extension=mbstring
     extension=fileinfo
     extension=openssl
     ```

2. **Composer**
   - Download: https://getcomposer.org/Composer-Setup.exe
   - Jalankan installer

3. **Node.js & NPM**
   - Download LTS: https://nodejs.org/
   - Jalankan installer

### 2. Setup Project

```powershell
# Clone atau copy project
git clone https://github.com/FirjiAchmad24/dashmonitoring-pln.git
cd dashmonitoring-pln

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
Copy-Item .env.example .env

# Generate application key
php artisan key:generate

# Create database dan jalankan migrasi
php artisan migrate

# (Opsional) Import data dari CSV
# Copy file CSV ke folder data/
# Kemudian import via interface web
```

### 3. Jalankan Development Server

**Terminal 1 - Laravel Backend:**
```powershell
php artisan serve
```

**Terminal 2 - Vite Frontend:**
```powershell
npm run dev
```

Akses aplikasi di: http://localhost:8000

## Import Data

1. Buka http://localhost:8000
2. Login (jika ada auth)
3. Navigasi ke halaman import untuk masing-masing monitoring:
   - BFKO Monitoring
   - CC Card Monitoring  
   - Service Fee Monitoring
4. Upload file CSV sesuai format

## Troubleshooting

### Error "SQLite extension not loaded"
- Pastikan `extension=pdo_sqlite` dan `extension=sqlite3` sudah di-enable di `php.ini`
- Restart terminal setelah edit php.ini

### Error "npm command not found"
- Pastikan Node.js sudah terinstall
- Restart terminal setelah install Node.js

### Error "composer command not found"
- Pastikan Composer sudah terinstall
- Restart terminal setelah install Composer

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
