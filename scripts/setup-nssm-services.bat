@echo off
REM ============================================================================
REM Hospital DICOM Viewer Pro v2.0 - NSSM Services Setup
REM This script installs 3 Windows services using NSSM
REM ============================================================================

echo.
echo ========================================================================
echo   Hospital DICOM Viewer Pro v2.0 - NSSM Services Installation
echo ========================================================================
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo Right-click this file and select "Run as Administrator"
    echo.
    pause
    exit /b 1
)

REM Set paths
set PHP_EXE=C:\xampp\php\php.exe
set PROJECT_DIR=C:\xampp\htdocs\papa\dicom_again\claude
set SCRIPTS_DIR=%PROJECT_DIR%\scripts
set LOGS_DIR=%PROJECT_DIR%\logs
set NSSM=%SCRIPTS_DIR%\nssm.exe

echo Checking requirements...
echo.

REM Check if PHP exists
if not exist "%PHP_EXE%" (
    echo ERROR: PHP not found at %PHP_EXE%
    echo Please update PHP_EXE path in this script
    pause
    exit /b 1
)
echo [OK] PHP found at %PHP_EXE%

REM Check if project directory exists
if not exist "%PROJECT_DIR%" (
    echo ERROR: Project directory not found at %PROJECT_DIR%
    echo Please update PROJECT_DIR path in this script
    pause
    exit /b 1
)
echo [OK] Project directory found

REM Check if NSSM exists
if not exist "%NSSM%" (
    echo.
    echo ERROR: NSSM not found at %NSSM%
    echo.
    echo Please download NSSM from: https://nssm.cc/download
    echo Extract nssm.exe to: %SCRIPTS_DIR%\
    echo.
    pause
    exit /b 1
)
echo [OK] NSSM found

REM Create logs directory if not exists
if not exist "%LOGS_DIR%" mkdir "%LOGS_DIR%"
echo [OK] Logs directory ready

echo.
echo ========================================================================
echo   Installing Services...
echo ========================================================================
echo.

REM ============================================================================
REM Service 1: Data Monitor Service
REM Monitors hospital data directory for new DICOM files
REM ============================================================================
echo.
echo [1/3] Installing Data Monitor Service...

REM Remove service if exists
"%NSSM%" stop DicomViewer_Data_Monitor >nul 2>&1
"%NSSM%" remove DicomViewer_Data_Monitor confirm >nul 2>&1

REM Install service
"%NSSM%" install DicomViewer_Data_Monitor "%PHP_EXE%" "%SCRIPTS_DIR%\data-monitor-service.php"
"%NSSM%" set DicomViewer_Data_Monitor AppDirectory "%SCRIPTS_DIR%"
"%NSSM%" set DicomViewer_Data_Monitor DisplayName "DICOM Data Monitor"
"%NSSM%" set DicomViewer_Data_Monitor Description "Monitors hospital data directory and imports new DICOM files to Orthanc"
"%NSSM%" set DicomViewer_Data_Monitor Start SERVICE_AUTO_START
"%NSSM%" set DicomViewer_Data_Monitor AppStdout "%LOGS_DIR%\monitor-service.log"
"%NSSM%" set DicomViewer_Data_Monitor AppStderr "%LOGS_DIR%\monitor-service-error.log"
"%NSSM%" set DicomViewer_Data_Monitor AppRotateFiles 1
"%NSSM%" set DicomViewer_Data_Monitor AppRotateBytes 10485760

echo [OK] Data Monitor Service installed

REM ============================================================================
REM Service 2: FTP Sync Service
REM Syncs files to GoDaddy every 2 minutes
REM ============================================================================
echo.
echo [2/3] Installing FTP Sync Service...

REM Remove service if exists
"%NSSM%" stop DicomViewer_FTP_Sync >nul 2>&1
"%NSSM%" remove DicomViewer_FTP_Sync confirm >nul 2>&1

REM Install service
"%NSSM%" install DicomViewer_FTP_Sync "%PHP_EXE%" "%SCRIPTS_DIR%\sync-service.php"
"%NSSM%" set DicomViewer_FTP_Sync AppDirectory "%SCRIPTS_DIR%"
"%NSSM%" set DicomViewer_FTP_Sync DisplayName "DICOM FTP Sync"
"%NSSM%" set DicomViewer_FTP_Sync Description "Syncs DICOM files to GoDaddy server via FTP"
"%NSSM%" set DicomViewer_FTP_Sync Start SERVICE_AUTO_START
"%NSSM%" set DicomViewer_FTP_Sync AppStdout "%LOGS_DIR%\sync-service.log"
"%NSSM%" set DicomViewer_FTP_Sync AppStderr "%LOGS_DIR%\sync-service-error.log"
"%NSSM%" set DicomViewer_FTP_Sync AppRotateFiles 1
"%NSSM%" set DicomViewer_FTP_Sync AppRotateBytes 10485760

echo [OK] FTP Sync Service installed

REM ============================================================================
REM Service 3: Google Drive Backup Service
REM Daily backup to Google Drive at 2:00 AM
REM ============================================================================
echo.
echo [3/3] Installing Google Drive Backup Service...

REM Remove service if exists
"%NSSM%" stop DicomViewer_GDrive_Backup >nul 2>&1
"%NSSM%" remove DicomViewer_GDrive_Backup confirm >nul 2>&1

REM Install service
"%NSSM%" install DicomViewer_GDrive_Backup "%PHP_EXE%" "%SCRIPTS_DIR%\backup-service.php"
"%NSSM%" set DicomViewer_GDrive_Backup AppDirectory "%SCRIPTS_DIR%"
"%NSSM%" set DicomViewer_GDrive_Backup DisplayName "DICOM GDrive Backup"
"%NSSM%" set DicomViewer_GDrive_Backup Description "Daily automated backup to Google Drive at 2:00 AM"
"%NSSM%" set DicomViewer_GDrive_Backup Start SERVICE_AUTO_START
"%NSSM%" set DicomViewer_GDrive_Backup AppStdout "%LOGS_DIR%\backup-service.log"
"%NSSM%" set DicomViewer_GDrive_Backup AppStderr "%LOGS_DIR%\backup-service-error.log"
"%NSSM%" set DicomViewer_GDrive_Backup AppRotateFiles 1
"%NSSM%" set DicomViewer_GDrive_Backup AppRotateBytes 10485760

echo [OK] Google Drive Backup Service installed

echo.
echo ========================================================================
echo   Starting Services...
echo ========================================================================
echo.

REM Start services
echo Starting Data Monitor Service...
"%NSSM%" start DicomViewer_Data_Monitor
if %errorlevel% equ 0 (
    echo [OK] Data Monitor Service started
) else (
    echo [WARNING] Failed to start Data Monitor Service
)

echo.
echo Starting FTP Sync Service...
"%NSSM%" start DicomViewer_FTP_Sync
if %errorlevel% equ 0 (
    echo [OK] FTP Sync Service started
) else (
    echo [WARNING] Failed to start FTP Sync Service
)

echo.
echo Starting Google Drive Backup Service...
"%NSSM%" start DicomViewer_GDrive_Backup
if %errorlevel% equ 0 (
    echo [OK] Google Drive Backup Service started
) else (
    echo [WARNING] Failed to start Google Drive Backup Service
)

echo.
echo ========================================================================
echo   Installation Complete!
echo ========================================================================
echo.
echo All 3 services have been installed:
echo.
echo   1. DicomViewer_Data_Monitor   - Hospital data monitoring
echo   2. DicomViewer_FTP_Sync        - FTP synchronization
echo   3. DicomViewer_GDrive_Backup   - Google Drive backups
echo.
echo Services Status:
echo.

REM Show service status
"%NSSM%" status DicomViewer_Data_Monitor
"%NSSM%" status DicomViewer_FTP_Sync
"%NSSM%" status DicomViewer_GDrive_Backup

echo.
echo Log files are located in: %LOGS_DIR%
echo.
echo To manage services:
echo   - Open Services (services.msc)
echo   - Or use: sc query DicomViewer_Data_Monitor
echo.
echo To configure services:
echo   - Data Monitor: Admin UI ^> Hospital Data Import
echo   - FTP Sync: Admin UI ^> Sync Configuration
echo   - GDrive Backup: Admin UI ^> Backup Configuration
echo.
echo ========================================================================
pause
