@echo off
REM Script untuk membuka SQLite database
echo =====================================
echo SQLite Database Viewer
echo =====================================
echo.
echo Database Location: database\database.sqlite
echo.

REM Check if sqlite3 exists
where sqlite3 >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo SQLite3 tidak ditemukan di sistem.
    echo.
    echo Silakan install salah satu:
    echo 1. DB Browser for SQLite: https://sqlitebrowser.org/
    echo 2. SQLite CLI: https://www.sqlite.org/download.html
    echo.
    pause
    exit /b
)

REM Open SQLite
echo Membuka database...
sqlite3 database\database.sqlite
