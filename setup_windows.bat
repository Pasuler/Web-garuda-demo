@echo off
echo =========================================
echo   GARUDA INDONESIA WEBSITE - QUICK SETUP
echo =========================================
echo.

REM Check if we're in the right directory
if not exist "database\database.sql" (
    echo ERROR: database.sql not found!
    echo Please run this script from the website-garuda directory
    pause
    exit /b 1
)

echo [1/4] Checking MySQL connection...
mysql -u root -p -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
    echo ERROR: Cannot connect to MySQL!
    echo Please make sure MySQL is running and you have the correct credentials.
    pause
    exit /b 1
)

echo [2/4] Creating database...
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS garuda_indonesia_website;"
if errorlevel 1 (
    echo ERROR: Failed to create database!
    pause
    exit /b 1
)

echo [3/4] Importing database structure...
mysql -u root -p garuda_indonesia_website < database\database.sql
if errorlevel 1 (
    echo ERROR: Failed to import database!
    pause
    exit /b 1
)

echo [4/4] Setting up upload directories...
if not exist "uploads" mkdir uploads
if not exist "uploads\hotels" mkdir uploads\hotels
if not exist "uploads\payment_receipts" mkdir uploads\payment_receipts
if not exist "uploads\receipts" mkdir uploads\receipts
if not exist "pages\booking\uploads" mkdir pages\booking\uploads
if not exist "pages\booking\uploads\receipts" mkdir pages\booking\uploads\receipts

REM Set permissions (Windows)
icacls uploads /grant Everyone:(OI)(CI)F >nul 2>&1
icacls pages\booking\uploads /grant Everyone:(OI)(CI)F >nul 2>&1

echo.
echo =========================================
echo   SETUP COMPLETED SUCCESSFULLY!
echo =========================================
echo.
echo Default Admin Login:
echo Email: admin@garudaindonesia.com  
echo Password: admin123
echo.
echo Website URL: http://localhost/website-garuda
echo Admin Panel: http://localhost/website-garuda/admin
echo.
echo IMPORTANT: Change the default admin password after first login!
echo.
pause
