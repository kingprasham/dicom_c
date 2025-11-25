@echo off
REM ========================================================================
REM  STOP AUTO-SYNC SCRIPT
REM  Kills the background sync process and removes from startup
REM ========================================================================

title Stop Auto-Sync
color 0C

echo ========================================================================
echo  STOPPING AUTO-SYNC
echo ========================================================================
echo.

REM Kill any running auto-sync processes
echo [1/4] Killing wscript processes (auto-sync runner)...
taskkill /F /IM wscript.exe >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo       ✓ wscript.exe processes terminated
) else (
    echo       - No wscript.exe processes found
)

echo.
echo [2/4] Killing PHP processes (sync script)...
taskkill /F /IM php.exe >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo       ✓ php.exe processes terminated
) else (
    echo       - No php.exe processes found
)

echo.
echo [3/4] Removing auto-sync from Windows Startup...
set STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
set STARTUP_FILE=%STARTUP_FOLDER%\DicomAutoSync.vbs

if exist "%STARTUP_FILE%" (
    del /F "%STARTUP_FILE%" >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo       ✓ Removed DicomAutoSync.vbs from Startup folder
        echo         It will NOT start on next boot
    ) else (
        echo       ✗ Failed to remove from Startup folder
        echo         Manually delete: %STARTUP_FILE%
    )
) else (
    echo       - DicomAutoSync.vbs not in Startup folder
    echo         It was not set to auto-start on boot
)

echo.
echo [4/4] Verifying no sync processes running...
timeout /t 2 /nobreak >nul

tasklist /FI "IMAGENAME eq wscript.exe" 2>nul | find /I "wscript.exe" >nul
if %ERRORLEVEL% EQU 0 (
    echo       ⚠ WARNING: Some wscript.exe still running
    echo         Try running this script again or use Task Manager
) else (
    echo       ✓ No wscript.exe processes running
)

tasklist /FI "IMAGENAME eq php.exe" 2>nul | find /I "php.exe" >nul
if %ERRORLEVEL% EQU 0 (
    echo       ⚠ WARNING: Some php.exe still running
    echo         Try running this script again or use Task Manager
) else (
    echo       ✓ No php.exe processes running
)

echo.
echo ========================================================================
echo  AUTO-SYNC STOPPED!
echo ========================================================================
echo.
echo  Actions taken:
echo    ✓ Killed background sync processes (wscript.exe, php.exe)
echo    ✓ Removed from Windows Startup (won't auto-start on boot)
echo.
echo  The 1-minute sync is now STOPPED.
echo.
echo  To restart auto-sync later:
echo    Run: SETUP_AUTO_SYNC_STARTUP.bat
echo    Or:  AUTO_SYNC_LOCAL_HIDDEN.vbs
echo.
echo ========================================================================
echo.

pause
