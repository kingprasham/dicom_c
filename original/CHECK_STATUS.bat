@echo off
echo ========================================
echo   Checking Services Status
echo ========================================
echo.

echo Processes running:
tasklist | findstr /i "python.exe ngrok.exe"

echo.
echo ========================================
echo   Testing URLs
echo ========================================
echo.

echo Local Gateway:
curl -s http://localhost:5000/health
echo.

echo.
echo Remote Tunnel:
curl -s https://brendon-interannular-nonconnectively.ngrok-free.dev/health
echo.

echo.
echo ========================================
if errorlevel 0 (
    echo [✓] Everything is running!
) else (
    echo [✗] Something is not working
)
echo ========================================
echo.
pause
