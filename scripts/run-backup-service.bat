@echo off
REM Hospital DICOM Viewer Pro v2.0
REM Automated Backup Service - Windows Batch Script
REM
REM This script runs the backup service and can be scheduled via Windows Task Scheduler
REM
REM Setup Windows Task Scheduler:
REM 1. Open Task Scheduler
REM 2. Create Basic Task
REM 3. Name: "DICOM Viewer Daily Backup"
REM 4. Trigger: Daily at 2:00 AM
REM 5. Action: Start a Program
REM 6. Program: C:\xampp\htdocs\papa\dicom_again\claude\scripts\run-backup-service.bat
REM 7. Run whether user is logged on or not
REM 8. Run with highest privileges

REM Set working directory
cd /d "%~dp0"

REM Set PHP path (adjust if needed)
set PHP_PATH=C:\xampp\php\php.exe

REM Run backup service
"%PHP_PATH%" backup-service.php

REM Exit with the same error code as the PHP script
exit /b %ERRORLEVEL%
