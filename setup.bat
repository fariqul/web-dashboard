@echo off
chcp 65001 >nul 2>&1
title Dashboard Monitoring PLN - Setup
color 0A

echo.
echo ========================================
echo  Dashboard Monitoring PLN - Quick Setup
echo ========================================
echo.

REM ============================================
REM Check Prerequisites
REM ============================================

echo [CHECKING PREREQUISITES]
echo ----------------------------------------

REM Check if PHP exists
echo Checking PHP...
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [X] PHP tidak ditemukan!
    echo.
    echo    Silakan install PHP terlebih dahulu:
    echo    1. Download dari https://windows.php.net/download/
    echo    2. Extract ke C:\php
    echo    3. Tambahkan C:\php ke System PATH
    echo    4. Copy php.ini-development ke php.ini
    echo    5. Edit php.ini, uncomment:
    echo       extension=openssl
    echo       extension=pdo_sqlite
    echo       extension=mbstring
    echo       extension=fileinfo
    echo       extension=zip
    echo.
    pause
    exit /b 1
)
for /f "tokens=2 delims= " %%v in ('php -v 2^>nul ^| findstr /i "^PHP"') do echo [OK] PHP version: %%v

REM Check PHP extensions
echo Checking PHP extensions...
php -m 2>nul | findstr /i "pdo_sqlite" >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [X] PHP Extension pdo_sqlite tidak aktif!
    echo    Edit php.ini dan uncomment: extension=pdo_sqlite
    pause
    exit /b 1
)
echo [OK] pdo_sqlite extension aktif

REM Check if Composer exists
echo Checking Composer...
where composer >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [X] Composer tidak ditemukan!
    echo.
    echo    Silakan install Composer:
    echo    1. Download dari https://getcomposer.org/download/
    echo    2. Jalankan Composer-Setup.exe
    echo.
    pause
    exit /b 1
)
echo [OK] Composer ditemukan

REM Check if Node.js/NPM exists
echo Checking Node.js/NPM...
where npm >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [X] Node.js/NPM tidak ditemukan!
    echo.
    echo    Silakan install Node.js:
    echo    1. Download dari https://nodejs.org/ (LTS version)
    echo    2. Jalankan installer
    echo.
    pause
    exit /b 1
)
echo [OK] NPM ditemukan

echo.
echo [OK] Semua prerequisites terpenuhi!
echo.

REM ============================================
REM Setup Application
REM ============================================

echo [SETUP APPLICATION]
echo ----------------------------------------

echo.
echo [1/7] Memeriksa file .env...
if not exist ".env" (
    if exist ".env.example" (
        copy .env.example .env >nul
        echo      File .env dibuat dari .env.example
    ) else (
        echo [X] File .env.example tidak ditemukan!
        pause
        exit /b 1
    )
) else (
    echo      File .env sudah ada.
)

echo.
echo [2/7] Menyiapkan folder storage...
if not exist "storage\app\public" mkdir storage\app\public 2>nul
if not exist "storage\framework\cache\data" mkdir storage\framework\cache\data 2>nul
if not exist "storage\framework\sessions" mkdir storage\framework\sessions 2>nul
if not exist "storage\framework\views" mkdir storage\framework\views 2>nul
if not exist "storage\logs" mkdir storage\logs 2>nul
if not exist "bootstrap\cache" mkdir bootstrap\cache 2>nul
echo      Folder storage siap

echo.
echo [3/7] Membuat database...
if not exist "database" mkdir database 2>nul
if not exist "database\database.sqlite" (
    type nul > database\database.sqlite
    echo      Database SQLite dibuat.
) else (
    echo      Database sudah ada.
)

echo.
echo [4/7] Install PHP dependencies (composer install)...
echo      Ini mungkin memakan waktu beberapa menit...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %ERRORLEVEL% NEQ 0 (
    echo [X] Gagal install composer dependencies!
    pause
    exit /b 1
)
echo      PHP dependencies berhasil diinstall

echo.
echo [5/7] Install JavaScript dependencies (npm install)...
echo      Ini mungkin memakan waktu beberapa menit...
call npm install
if %ERRORLEVEL% NEQ 0 (
    echo [X] Gagal install npm dependencies!
    pause
    exit /b 1
)
echo      JavaScript dependencies berhasil diinstall

echo.
echo [6/7] Generate application key...
php artisan key:generate --force
if %ERRORLEVEL% NEQ 0 (
    echo [WARNING] Gagal generate app key
)

echo.
echo [7/7] Jalankan migrasi database...
php artisan migrate --force
if %ERRORLEVEL% NEQ 0 (
    echo [X] Gagal menjalankan migrasi database!
    pause
    exit /b 1
)
echo      Database berhasil dimigrasi

echo.
echo ========================================
echo            SETUP BERHASIL!
echo ========================================
echo.
echo Aplikasi siap dijalankan!
echo.
echo CARA MENJALANKAN:
echo ----------------------------------------
echo   OPSI 1: Jalankan start-app.bat
echo           (otomatis buka kedua server)
echo.
echo   OPSI 2: Manual di 2 terminal terpisah:
echo           Terminal 1: start-backend.bat
echo           Terminal 2: start-frontend.bat
echo.
echo   Kemudian buka browser: http://localhost:8000
echo ----------------------------------------
echo.

set /p RUNAPP="Langsung jalankan aplikasi? (Y/N): "
if /i "%RUNAPP%"=="Y" (
    echo.
    echo Menjalankan aplikasi...
    start "Backend - Laravel" cmd /k "php artisan serve"
    timeout /t 2 >nul
    start "Frontend - Vite" cmd /k "npm run dev"
    echo.
    echo Server sudah berjalan!
    timeout /t 3 >nul
    start http://localhost:8000
)

pause
