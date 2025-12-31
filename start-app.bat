@echo off
chcp 65001 >nul 2>&1
title Dashboard Monitoring PLN - Start
color 0A

echo.
echo ========================================
echo  Dashboard Monitoring PLN - Starting...
echo ========================================
echo.

REM Check if setup has been done
if not exist "vendor\autoload.php" (
    echo [X] Aplikasi belum di-setup!
    echo     Jalankan setup.bat terlebih dahulu.
    echo.
    pause
    exit /b 1
)

if not exist "node_modules" (
    echo [X] NPM dependencies belum diinstall!
    echo     Jalankan setup.bat terlebih dahulu.
    echo.
    pause
    exit /b 1
)

if not exist ".env" (
    echo [X] File .env tidak ditemukan!
    echo     Jalankan setup.bat terlebih dahulu.
    echo.
    pause
    exit /b 1
)

echo Menjalankan Backend Server (Laravel)...
start "Backend - Laravel" cmd /k "title Backend - Laravel && php artisan serve"

echo Menunggu Backend siap...
timeout /t 2 >nul

echo Menjalankan Frontend Server (Vite)...
start "Frontend - Vite" cmd /k "title Frontend - Vite && npm run dev"

echo.
echo ========================================
echo  Server sudah berjalan!
echo ========================================
echo.
echo  Backend:  http://localhost:8000
echo  Frontend: http://localhost:5173 (Vite dev server)
echo.
echo  Buka browser dan akses: http://localhost:8000
echo.
echo  Untuk menghentikan server, tutup kedua
echo  terminal yang berjalan.
echo ========================================
echo.

echo Membuka browser dalam 3 detik...
timeout /t 3 >nul
start http://localhost:8000

echo.
echo Tekan tombol apa saja untuk menutup window ini...
pause >nul
