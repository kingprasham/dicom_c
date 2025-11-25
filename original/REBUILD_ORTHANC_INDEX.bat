@echo off
REM ========================================================================
REM  REBUILD ORTHANC INDEX - Fix 404 Errors
REM  Must run as Administrator!
REM ========================================================================

title Rebuild Orthanc Index
color 0C

echo ========================================================================
echo  REBUILD ORTHANC INDEX
echo ========================================================================
echo.
echo  WARNING: This will rebuild Orthanc's internal database index.
echo.
echo  This fixes:
echo    - 404 errors when accessing DICOM files
echo    - Storage/index mismatch
echo    - Corrupted or orphaned metadata
echo.
echo  IMPORTANT: This will DELETE all current Orthanc data and re-import
echo             from storage. Make sure you have backups!
echo.
echo ========================================================================
echo.

pause

echo.
echo Step 1: Stopping Orthanc Service...
net stop "Orthanc Service" 2>nul

if %errorLevel% EQU 0 (
    echo Orthanc stopped
) else (
    echo Orthanc service not running or already stopped
)

timeout /t 3 /nobreak >nul

echo.
echo Step 2: Deleting old index database...
del /Q "C:\Orthanc\OrthancStorage\index" 2>nul
del /Q "C:\Orthanc\OrthancStorage\index-journal" 2>nul

if exist "C:\Orthanc\OrthancStorage\index" (
    echo ERROR: Could not delete index file
    echo Please delete manually: C:\Orthanc\OrthancStorage\index
    pause
    exit /b 1
) else (
    echo Old index deleted
)

echo.
echo Step 3: Starting Orthanc (will rebuild index)...
net start "Orthanc Service"

if %errorLevel% EQU 0 (
    echo.
    echo ========================================================================
    echo  SUCCESS!
    echo ========================================================================
    echo.
    echo  Orthanc is rebuilding its index from storage files.
    echo  This may take a few minutes...
    echo.
    echo  After 2-3 minutes:
    echo    1. Check: http://localhost:8042
    echo    2. Login: orthanc / orthanc
    echo    3. Verify studies appear
    echo.
    echo  If no studies appear, you need to re-send DICOM from your app.
    echo.
) else (
    echo.
    echo ERROR: Failed to start Orthanc
    echo.
    echo Try starting manually from services.msc
    echo.
)

pause
