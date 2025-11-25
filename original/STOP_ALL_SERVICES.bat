@echo off
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: STOP ALL SERVICES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

color 0C
echo.
echo ========================================================================
echo           STOPPING HOSPITAL DICOM SYSTEM
echo ========================================================================
echo.

set LOG_FILE=C:\HospitalDICOM\logs\service_stop.log
echo Service stop initiated: %date% %time% > "%LOG_FILE%"

:: Stop Auto-Sync Service
echo [1/4] Stopping Auto-Sync Service...
taskkill /FI "WINDOWTITLE eq AUTO_SYNC_SERVICE*" /F >nul 2>&1
echo Auto-Sync stopped >> "%LOG_FILE%"

:: Stop Orthanc
echo [2/4] Stopping Orthanc PACS Server...
net stop "Orthanc Service" >> "%LOG_FILE%" 2>&1

:: Stop Apache
echo [3/4] Stopping Apache Web Server...
taskkill /F /IM httpd.exe >nul 2>&1
echo Apache stopped >> "%LOG_FILE%"

:: Stop MySQL
echo [4/4] Stopping MySQL Database...
taskkill /F /IM mysqld.exe >nul 2>&1
echo MySQL stopped >> "%LOG_FILE%"

echo.
echo ========================================================================
echo           ALL SERVICES STOPPED
echo ========================================================================
echo.
echo All Hospital DICOM services have been stopped.
echo To restart: run START_ALL_SERVICES.bat
echo.
echo Log file: %LOG_FILE%
echo.
pause
