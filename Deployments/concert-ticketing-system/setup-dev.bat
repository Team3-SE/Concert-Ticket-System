@echo off
chcp 65001 >nul
echo ===================================================
echo   Concert Ticketing System - Local Setup Script
echo ===================================================

if not exist .env (
    echo [1/5] Copying .env.example to .env...
    copy .env.example .env
) else (
    echo [1/5] .env already exists, skipping...
)

echo.
echo [2/5] Starting Docker containers...
docker-compose up -d

echo.
echo Waiting for containers to initialize (15 seconds)...
timeout /t 15 /nobreak >nul

echo.
echo [3/5] Installing Composer dependencies (This may take a few minutes)...
docker exec concert_app composer install

echo.
echo [4/5] Generating Application Key...
docker exec concert_app php artisan key:generate

echo.
echo [5/5] Running Database Migrations and Seeders...
docker exec concert_app php artisan migrate --seed

echo.
echo ===================================================
echo   Setup Complete! 
echo   Your application is running at: http://localhost:8080/
echo ===================================================
pause
