@echo off
REM ========================================================================
REM  STOP WINDOWS SCHEDULED TASK - DicomAutoSync
REM  Must run as Administrator to delete the task!
REM ========================================================================

title Stop Scheduled Task - DicomAutoSync
color 0C

echo ========================================================================
echo  STOP SCHEDULED TASK - DicomAutoSync
echo ========================================================================
echo.
echo  This will stop and delete the scheduled task running every 1 minute.
echo.
echo ========================================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo WARNING: Not running as Administrator
    echo You may not be able to delete the scheduled task without admin rights.
    echo.
    echo Recommendation: Right-click this file and select "Run as Administrator"
    echo.
    echo Attempting anyway...
    echo.
)

REM Check if task exists
echo [1/3] Checking if DicomAutoSync task exists...
schtasks /Query /TN "DicomAutoSync" >nul 2>&1
if %errorLevel% EQU 0 (
    echo       Task "DicomAutoSync" FOUND
    echo.

    REM Disable the task first
    echo [2/3] Disabling task...
    schtasks /Change /TN "DicomAutoSync" /DISABLE >nul 2>&1
    if %errorLevel% EQU 0 (
        echo       ✓ Task disabled
    ) else (
        echo       - Could not disable (may need admin rights)
    )

    echo.
    echo [3/3] Deleting task...
    schtasks /Delete /TN "DicomAutoSync" /F >nul 2>&1
    if %errorLevel% EQU 0 (
        echo       ✓ Task "DicomAutoSync" DELETED successfully!
        echo.
        echo ========================================================================
        echo  SUCCESS! The 1-minute sync is STOPPED!
        echo ========================================================================
        echo.
        echo  The scheduled task has been deleted.
        echo  No more curl.exe popups every minute!
        echo.
    ) else (
        echo       ✗ FAILED to delete task
        echo.
        echo ========================================================================
        echo  ERROR: Could not delete task
        echo ========================================================================
        echo.
        echo  You need to run this as Administrator:
        echo    1. Right-click STOP_SCHEDULED_TASK.bat
        echo    2. Select "Run as Administrator"
        echo    3. Click "Yes" on UAC prompt
        echo.
        echo  Or manually delete:
        echo    1. Press Win+R
        echo    2. Type: taskschd.msc
        echo    3. Click "Task Scheduler Library"
        echo    4. Find "DicomAutoSync"
        echo    5. Right-click → Delete
        echo.
    )
) else (
    echo       Task "DicomAutoSync" NOT FOUND in Task Scheduler
    echo.
    echo ========================================================================
    echo  TASK ALREADY REMOVED OR DOESN'T EXIST
    echo ========================================================================
    echo.
    echo  The scheduled task is not running.
    echo.
    echo  If you still see curl.exe popup every minute, check:
    echo    1. Task Scheduler (taskschd.msc) for other tasks
    echo    2. Startup folder for other scripts
    echo.
)

echo ========================================================================
echo.
echo  To re-enable auto-sync later:
echo    Run: SETUP_TASK_SCHEDULER.bat (as Administrator)
echo.
echo ========================================================================
echo.

pause
