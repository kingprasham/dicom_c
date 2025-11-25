@echo off
REM ========================================================================
REM  ENABLE CORS IN ORTHANC - Fix Image Loading Issue
REM  Must run as Administrator!
REM ========================================================================

title Enable Orthanc CORS
color 0E

echo ========================================================================
echo  ENABLE CORS IN ORTHANC CONFIGURATION
echo ========================================================================
echo.
echo  This will enable CORS (Cross-Origin Resource Sharing) in Orthanc
echo  to fix the "image load error undefined" issue in the DICOM viewer.
echo.
echo  IMPORTANT: You must run this as Administrator!
echo.
echo ========================================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo ERROR: Not running as Administrator!
    echo.
    echo Please right-click this file and select "Run as Administrator"
    echo.
    pause
    exit /b 1
)

echo Running as Administrator... OK
echo.

set CONFIG_DIR=C:\Program Files\Orthanc Server\Configuration
set CONFIG_FILE=%CONFIG_DIR%\orthanc.json
set BACKUP_FILE=%CONFIG_DIR%\orthanc.json.backup

echo Checking for Orthanc configuration...
echo.

if not exist "%CONFIG_FILE%" (
    echo ERROR: Orthanc configuration not found!
    echo Expected location: %CONFIG_FILE%
    echo.
    echo Please check if Orthanc is installed correctly.
    echo.
    pause
    exit /b 1
)

echo Configuration found: %CONFIG_FILE%
echo.

REM Create backup
echo Creating backup...
copy "%CONFIG_FILE%" "%BACKUP_FILE%" >nul 2>&1

if %errorLevel% EQU 0 (
    echo Backup created: %BACKUP_FILE%
) else (
    echo WARNING: Could not create backup
)
echo.

echo ========================================================================
echo  MANUAL CONFIGURATION REQUIRED
echo ========================================================================
echo.
echo  Due to JSON complexity, please manually edit the configuration:
echo.
echo  1. Open: %CONFIG_FILE%
echo     (Use Notepad++ or Administrator Notepad)
echo.
echo  2. Find or add these settings:
echo.
echo     "CorsEnabled" : true,
echo     "CorsAllowedOrigins" : "*",
echo     "CorsAllowedMethods" : "GET,POST,PUT,DELETE,OPTIONS",
echo     "CorsAllowedHeaders" : "*",
echo     "CorsMaxAge" : 3600,
echo     "RemoteAccessAllowed" : true
echo.
echo  3. Save the file
echo.
echo  4. Come back here and press any key to restart Orthanc
echo.
echo ========================================================================
echo.

pause

echo.
echo Restarting Orthanc service...
echo.

REM Stop Orthanc
net stop "Orthanc Service" 2>nul
if %errorLevel% EQU 0 (
    echo Orthanc service stopped...
    timeout /t 3 /nobreak >nul
) else (
    echo Orthanc service was not running
)

REM Start Orthanc
net start "Orthanc Service" 2>nul
if %errorLevel% EQU 0 (
    echo.
    echo ========================================================================
    echo  SUCCESS! Orthanc service restarted
    echo ========================================================================
    echo.
    echo  CORS should now be enabled.
    echo.
    echo  Verify:
    echo    1. Open: http://localhost:8042
    echo    2. Login: orthanc / orthanc
    echo    3. Test DICOM viewer: http://localhost/papa/dicom_again/
    echo.
) else (
    echo.
    echo ========================================================================
    echo  ERROR: Failed to start Orthanc service
    echo ========================================================================
    echo.
    echo  Possible causes:
    echo    - Configuration error (syntax error in JSON)
    echo    - Service not installed
    echo.
    echo  To restore backup:
    echo    copy "%BACKUP_FILE%" "%CONFIG_FILE%"
    echo.
    echo  Then try starting service manually from services.msc
    echo.
)

echo ========================================================================
echo.

pause
