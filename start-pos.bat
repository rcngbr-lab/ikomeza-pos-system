@echo off
setlocal enabledelayedexpansion

title IKOMEZA POS SYSTEM - LAN MODE
color 0A

set "APP_DIR=C:\Users\IT BASE\bar-pos-system"
set "PORT=8000"

echo =========================================
echo        STARTING IKOMEZA POS - LAN
echo =========================================

cd /d "%APP_DIR%"

for /f "usebackq tokens=*" %%I in (`powershell -NoProfile -Command "$ip = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254*' -and $_.PrefixOrigin -ne 'WellKnown' } | Sort-Object InterfaceMetric | Select-Object -First 1 -ExpandProperty IPAddress; if ($ip) { $ip } else { '127.0.0.1' }"`) do set "LAN_IP=%%I"

if "%LAN_IP%"=="" set "LAN_IP=127.0.0.1"

echo.
echo Computer URL: http://127.0.0.1:%PORT%
echo Network URL : http://%LAN_IP%:%PORT%
echo.
echo Use the Network URL on phones, tablets, and other computers
echo connected to the same Wi-Fi/LAN.
echo.

if exist "public\hot" (
    echo Removing Vite hot file for LAN-safe static assets...
    del /q "public\hot"
)

echo Clearing Laravel cached config...
php artisan optimize:clear

echo.
echo Building frontend assets for LAN browsers...
call npm run build
if errorlevel 1 (
    echo.
    echo Frontend build failed. Fix npm/Vite errors before starting LAN mode.
    pause
    exit /b 1
)

echo.
echo Starting Laravel LAN server on 0.0.0.0:%PORT%...
start "IKOMEZA POS LAN SERVER" /D "%APP_DIR%" cmd /k "set APP_URL=http://%LAN_IP%:%PORT%&& php artisan serve --host=0.0.0.0 --port=%PORT%"

timeout /t 4 >nul

echo.
echo Starting Electron app on this computer...
start "IKOMEZA POS DESKTOP" /D "%APP_DIR%" cmd /k "npx electron ."

echo.
echo =========================================
echo IKOMEZA POS IS READY ON YOUR NETWORK
echo =========================================
echo.
echo Open on this computer:
echo   http://127.0.0.1:%PORT%
echo.
echo Open on another device on the same network:
echo   http://%LAN_IP%:%PORT%
echo.
echo If another device cannot open it:
echo   1. Make sure both devices are on the same Wi-Fi/LAN.
echo   2. Allow PHP through Windows Firewall.
echo   3. Confirm this computer IP is still %LAN_IP%.
echo.

pause
