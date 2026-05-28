@echo off

title IKOMEZA POS SYSTEM

color 0A

echo =========================================
echo        STARTING IKOMEZA POS
echo =========================================

cd /d "C:\Users\IT BASE\bar-pos-system"

echo.
echo Starting Laravel Server...
start cmd /k "php artisan serve --host=0.0.0.0 --port=8000"

timeout /t 5 >nul

echo.
echo Starting Vite / NPM...
start cmd /k "npm run dev"

timeout /t 8 >nul

echo.
echo Starting Electron App...
start cmd /k "npx electron ."

timeout /t 5 >nul

echo.
echo =========================================
echo SYSTEM STARTED SUCCESSFULLY
echo =========================================

pause