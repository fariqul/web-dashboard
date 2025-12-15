@echo off
echo ========================================
echo Dashboard Monitoring PLN - Quick Setup
echo ========================================
echo.

REM Check if composer exists
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Composer tidak ditemukan!
    echo Silakan install Composer dari https://getcomposer.org/
    pause
    exit /b 1
)

REM Check if npm exists
where npm >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] NPM tidak ditemukan!
    echo Silakan install Node.js dari https://nodejs.org/
    pause
    exit /b 1
)

REM Check if php exists
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP tidak ditemukan!
    echo Silakan install PHP dari https://windows.php.net/download/
    pause
    exit /b 1
)

echo [1/6] Memeriksa file .env...
if not exist ".env" (
    echo Membuat file .env dari .env.example...
    copy .env.example .env
) else (
    echo File .env sudah ada.
)

echo.
echo [2/6] Install PHP dependencies...
call composer install
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Gagal install composer dependencies!
    pause
    exit /b 1
)

echo.
echo [3/6] Install JavaScript dependencies...
call npm install
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Gagal install npm dependencies!
    pause
    exit /b 1
)

echo.
echo [4/6] Generate application key...
php artisan key:generate

echo.
echo [5/6] Membuat database...
if not exist "database\database.sqlite" (
    type nul > database\database.sqlite
    echo Database SQLite dibuat.
) else (
    echo Database sudah ada.
)

echo.
echo [6/6] Jalankan migrasi database...
php artisan migrate --force
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Gagal menjalankan migrasi!
    pause
    exit /b 1
)

echo.
echo ========================================
echo Setup selesai!
echo ========================================
echo.
echo Untuk menjalankan aplikasi:
echo 1. Buka terminal pertama dan jalankan: php artisan serve
echo 2. Buka terminal kedua dan jalankan: npm run dev
echo 3. Akses http://localhost:8000 di browser
echo.
pause
