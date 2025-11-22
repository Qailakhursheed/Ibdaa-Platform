@echo off
echo ========================================
echo    Charts API - Python Server
echo    خادم الرسوم البيانية التفاعلية
echo ========================================
echo.

cd /d "%~dp0"

echo [1] Checking Python installation...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python not installed!
    echo Please install Python 3.x from python.org
    pause
    exit /b 1
)

echo [2] Installing requirements...
pip install -r requirements.txt --quiet

echo.
echo ========================================
echo [3] Starting Flask Server...
echo Server will run on: http://localhost:5000
echo ========================================
echo.
echo Available endpoints:
echo   - GET /api/charts/students-status
echo   - GET /api/charts/courses-status  
echo   - GET /api/charts/revenue-monthly
echo   - GET /api/charts/attendance-rate
echo   - GET /api/charts/performance-overview
echo   - GET /api/charts/grades-distribution
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

python charts_api.py

pause
