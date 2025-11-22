@echo off
echo ========================================
echo Starting WebSocket Server for Ibdaa Platform
echo ========================================
echo.

cd /d %~dp0

echo [INFO] Checking PHP installation...
php --version
if %errorlevel% neq 0 (
    echo [ERROR] PHP not found. Please install PHP or add it to PATH.
    pause
    exit /b 1
)

echo.
echo [INFO] Starting WebSocket server on port 8080...
echo [INFO] Press Ctrl+C to stop the server
echo.
echo ========================================

php websocket_server.php

pause
