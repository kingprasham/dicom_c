@echo off
REM ========================================================================
REM  AUTO SYNC LOCAL ORTHANC - Runs every 1 minute
REM ========================================================================
REM
REM  This script automatically syncs LOCAL Orthanc to LOCAL database
REM  Use this when your app sends directly to localhost Orthanc
REM
REM ========================================================================

title Auto Sync Local Orthanc
color 0A

echo ========================================================================
echo  AUTO SYNC LOCAL ORTHANC TO DATABASE
echo ========================================================================
echo.
echo  This will sync data from localhost Orthanc every 1 minute
echo.
echo  Orthanc: http://localhost:8042
echo  Database: localhost/dicom_again
echo.
echo  Press Ctrl+C to stop auto-sync
echo ========================================================================
echo.

:LOOP
echo.
echo [%DATE% %TIME%] Running sync...
echo.

REM Run the sync script using curl
curl -s http://localhost/papa/dicom_again/sync_orthanc.php > nul

if %ERRORLEVEL% EQU 0 (
    echo [%TIME%] Sync completed successfully
) else (
    echo [%TIME%] Sync failed - Check Apache/MySQL running
)

echo.
echo Next sync in 1 minute...
echo.

REM Wait 1 minute (60 seconds)
timeout /t 60 /nobreak

goto LOOP
