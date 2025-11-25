@echo off
REM ========================================================================
REM  SETUP WINDOWS TASK SCHEDULER FOR AUTO-SYNC
REM  Must run as Administrator!
REM ========================================================================

title Setup Task Scheduler - Auto Sync
color 0E

echo ========================================================================
echo  SETUP WINDOWS TASK SCHEDULER FOR AUTO-SYNC
echo ========================================================================
echo.
echo  This will create a scheduled task to sync Orthanc every 1 minute.
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

REM Delete existing task if it exists
echo Removing old task (if exists)...
schtasks /Delete /TN "DicomAutoSync" /F >nul 2>&1
echo.

REM Create scheduled task
echo Creating scheduled task...
echo.

schtasks /Create /TN "DicomAutoSync" /TR "curl -s http://localhost/papa/dicom_again/sync_orthanc.php" /SC MINUTE /MO 1 /ST 00:00 /RL HIGHEST /F

if %errorLevel% EQU 0 (
    echo.
    echo ========================================================================
    echo  SUCCESS! Scheduled task created
    echo ========================================================================
    echo.
    echo  Task Details:
    echo    Name: DicomAutoSync
    echo    Frequency: Every 1 minute
    echo    Action: Sync Orthanc to database
    echo    Status: Running
    echo.
    echo  The sync will now run automatically every minute!
    echo.
    echo  To view task:
    echo    - Open Task Scheduler (taskschd.msc)
    echo    - Look for "DicomAutoSync" in Task Scheduler Library
    echo.
    echo  To disable/delete task:
    echo    - Task Scheduler → Right-click "DicomAutoSync" → Delete
    echo    - Or run: schtasks /Delete /TN "DicomAutoSync" /F
    echo.
    echo  To test immediately:
    echo    - Task Scheduler → Right-click "DicomAutoSync" → Run
    echo.
) else (
    echo.
    echo ========================================================================
    echo  ERROR: Failed to create task
    echo ========================================================================
    echo.
    echo  Possible causes:
    echo    - Not running as Administrator
    echo    - Task Scheduler service not running
    echo.
    echo  Try:
    echo    1. Right-click this file
    echo    2. Select "Run as Administrator"
    echo    3. Click "Yes" on UAC prompt
    echo.
)

echo ========================================================================
echo.

pause
