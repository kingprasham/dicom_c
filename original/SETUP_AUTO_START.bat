@echo off
echo ========================================
echo   Setup Auto-Start on Boot
echo ========================================
echo.

set STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup

echo Installing to Windows Startup folder...
echo.

REM Copy VBS files to Startup
copy /Y "%~dp0start_gateway_hidden.vbs" "%STARTUP%\DICOM_Gateway.vbs" >nul
copy /Y "%~dp0start_ngrok_hidden.vbs" "%STARTUP%\DICOM_Ngrok.vbs" >nul

echo [SUCCESS] Auto-start configured!
echo.
echo Location: %STARTUP%
echo Files:
echo   - DICOM_Gateway.vbs
echo   - DICOM_Ngrok.vbs
echo.
echo These will run automatically when Windows starts.
echo They run completely hidden in the background.
echo.
echo ========================================
echo   Starting Now (Hidden)
echo ========================================
echo.

REM Kill any existing
taskkill /F /IM python.exe >nul 2>&1
taskkill /F /IM ngrok.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM Start hidden
wscript.exe //B //NoLogo "%~dp0start_gateway_hidden.vbs"
timeout /t 8 /nobreak >nul

wscript.exe //B //NoLogo "%~dp0start_ngrok_hidden.vbs"
timeout /t 5 /nobreak >nul

echo.
echo Testing services...
echo.

echo Gateway:
curl -s http://localhost:5000/health
echo.

echo.
echo Tunnel:
curl -s https://brendon-interannular-nonconnectively.ngrok-free.dev/health
echo.

echo.
echo ========================================
echo   COMPLETE!
echo ========================================
echo.
echo Services are now running hidden in background.
echo They will auto-start on every boot.
echo.
echo To check if running:
echo   tasklist ^| findstr "python.exe ngrok.exe"
echo.
echo To stop manually:
echo   taskkill /F /IM python.exe
echo   taskkill /F /IM ngrok.exe
echo.
pause
