@echo off
REM Auto-Scanner Windows Task Scheduler Script
REM This script should be scheduled using Windows Task Scheduler

cd /d "%~dp0"

echo Starting Auto-Scanner Service...
echo Time: %date% %time%

php auto-scanner-service.php

echo Auto-Scanner Service Completed
echo.
