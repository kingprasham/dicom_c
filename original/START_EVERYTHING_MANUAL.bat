@echo off
echo ========================================
echo   Manual Startup - Gateway + ngrok
echo ========================================
echo.

cd /d "%~dp0"

REM Kill any existing processes
echo Cleaning up old processes...
taskkill /F /IM python.exe >nul 2>&1
taskkill /F /IM ngrok.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM Clear Python cache
del /F /Q "__pycache__\*.*" >nul 2>&1
rmdir "__pycache__" >nul 2>&1

echo.
echo ========================================
echo   Starting Gateway on port 5000
echo ========================================
echo.

REM Start Python gateway in new window
start "DICOM Gateway - Port 5000" C:\Users\prash\AppData\Local\Programs\Python\Python311\python.exe orthanc_api_gateway.py

echo Waiting 10 seconds for gateway to start...
timeout /t 10 /nobreak >nul

echo.
echo Testing gateway...
curl -s http://localhost:5000/health
echo.

netstat -ano | findstr :5000 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo [ERROR] Gateway not running on port 5000!
    echo Check the Gateway window for errors.
    pause
    exit /b 1
)

echo [SUCCESS] Gateway running!

echo.
echo ========================================
echo   Starting ngrok tunnel
echo ========================================
echo.

REM Start ngrok in new window
start "ngrok Tunnel" C:\ngrok\ngrok.exe http --url=brendon-interannular-nonconnectively.ngrok-free.dev 5000

echo Waiting 8 seconds for ngrok to connect...
timeout /t 8 /nobreak >nul

echo.
echo Testing tunnel...
curl -s https://brendon-interannular-nonconnectively.ngrok-free.dev/health
echo.

echo.
echo ========================================
echo   READY!
echo ========================================
echo.
echo Two windows are now running:
echo   1. DICOM Gateway - Port 5000 (Python)
echo   2. ngrok Tunnel (ngrok)
echo.
echo Keep both windows open while using the system.
echo.
echo Test URLs:
echo   Local:  http://localhost:5000/health
echo   Remote: https://brendon-interannular-nonconnectively.ngrok-free.dev/health
echo.
echo To test the new API endpoint:
echo   Run TEST_NEW_ENDPOINT.bat
echo.
pause
