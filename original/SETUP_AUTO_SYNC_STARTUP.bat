@echo off
REM ========================================================================
REM  SETUP AUTO-SYNC TO START ON WINDOWS BOOT
REM ========================================================================

title Setup Auto-Sync Startup
color 0E

echo ========================================================================
echo  SETUP AUTO-SYNC ON WINDOWS STARTUP
echo ========================================================================
echo.
echo  This will configure auto-sync to start automatically when Windows boots.
echo.
echo  The sync script will:
echo    - Start hidden in the background
echo    - Sync Orthanc to database every 1 minute
echo    - Run continuously until you stop it
echo.
echo ========================================================================
echo.

REM Get startup folder path
set STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup

echo Startup folder: %STARTUP_FOLDER%
echo.

REM Copy VBS script to startup folder
echo Copying auto-sync script to startup folder...
copy /Y "AUTO_SYNC_LOCAL_HIDDEN.vbs" "%STARTUP_FOLDER%\DicomAutoSync.vbs" > nul

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================================
    echo  SUCCESS! Auto-sync will start on Windows boot
    echo ========================================================================
    echo.
    echo  File created: %STARTUP_FOLDER%\DicomAutoSync.vbs
    echo.
    echo  What happens next:
    echo    - Every time Windows starts, auto-sync starts automatically
    echo    - Runs hidden in background (no window)
    echo    - Syncs every 1 minute
    echo.
    echo  To start now (without reboot):
    echo    Double-click: AUTO_SYNC_LOCAL_HIDDEN.vbs
    echo.
    echo  To stop auto-sync:
    echo    Task Manager → Details → Find wscript.exe → End Task
    echo.
    echo  To remove from startup:
    echo    Delete: %STARTUP_FOLDER%\DicomAutoSync.vbs
    echo.
) else (
    echo.
    echo ========================================================================
    echo  ERROR: Failed to copy file
    echo ========================================================================
    echo.
    echo  Possible causes:
    echo    - Insufficient permissions
    echo    - Startup folder doesn't exist
    echo.
    echo  Try:
    echo    - Run this batch file as Administrator
    echo    - Or manually copy AUTO_SYNC_LOCAL_HIDDEN.vbs to Startup folder
    echo.
)

echo ========================================================================
echo.

pause
