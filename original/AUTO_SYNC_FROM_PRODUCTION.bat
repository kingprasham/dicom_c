@echo off
REM ========================================================================
REM  AUTO SYNC FROM PRODUCTION - Runs sync every 2 minutes
REM ========================================================================
REM
REM  This script automatically syncs DICOM data from the PRODUCTION Orthanc
REM  server to your LOCAL database every 2 minutes.
REM
REM  Use this when:
REM  - MRI machines send data to PRODUCTION server
REM  - You want to see that data on LOCALHOST automatically
REM
REM ========================================================================

title Auto Sync from Production
color 0A

echo ========================================================================
echo  AUTO SYNC FROM PRODUCTION TO LOCAL DATABASE
echo ========================================================================
echo.
echo  This will sync data from production every 2 minutes
echo.
echo  Production: https://brendon-interannular-nonconnectively.ngrok-free.dev/
echo  Local DB: localhost/dicom_again
echo.
echo  Press Ctrl+C to stop auto-sync
echo ========================================================================
echo.

:LOOP
echo.
echo [%DATE% %TIME%] Running sync...
echo.

REM Run the sync script using curl
curl -s http://localhost/papa/dicom_again/sync_from_production.php > nul

if %ERRORLEVEL% EQU 0 (
    echo [%TIME%] Sync completed successfully
) else (
    echo [%TIME%] Sync failed - Check connection
)

echo.
echo Next sync in 2 minutes...
echo.

REM Wait 2 minutes (120 seconds)
timeout /t 120 /nobreak

goto LOOP
