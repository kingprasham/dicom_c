@echo off
REM ========================================================================
REM  SETUP WINDOWS FIREWALL FOR DICOM (PORT 4242)
REM  Must run as Administrator!
REM ========================================================================

title Setup DICOM Firewall Rules
color 0E

echo ========================================================================
echo  WINDOWS FIREWALL SETUP FOR DICOM RECEIVING
echo ========================================================================
echo.
echo  This script will configure Windows Firewall to allow:
echo    - DICOM C-STORE (Port 4242 TCP)
echo    - Required for MRI/CT machines to send studies
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

REM Remove any existing rules with same name (cleanup)
echo Removing old firewall rules (if any)...
netsh advfirewall firewall delete rule name="DICOM C-STORE (Port 4242)" >nul 2>&1
echo.

REM Create inbound rule for DICOM port 4242
echo Creating firewall rule for DICOM C-STORE (Port 4242)...
netsh advfirewall firewall add rule name="DICOM C-STORE (Port 4242)" dir=in action=allow protocol=TCP localport=4242 profile=any description="Allows MRI/CT modalities to send DICOM studies via C-STORE"

if %errorLevel% EQU 0 (
    echo.
    echo ========================================================================
    echo  SUCCESS! Firewall rule created.
    echo ========================================================================
    echo.
    echo  Rule details:
    echo    Name: DICOM C-STORE (Port 4242^)
    echo    Protocol: TCP
    echo    Port: 4242
    echo    Direction: Inbound
    echo    Action: Allow
    echo    Profiles: All (Domain, Private, Public^)
    echo.
    echo  You can now receive DICOM studies from MRI/CT machines!
    echo.
) else (
    echo.
    echo ========================================================================
    echo  ERROR: Failed to create firewall rule!
    echo ========================================================================
    echo.
    echo  Possible causes:
    echo    - Not running as Administrator
    echo    - Windows Firewall is disabled
    echo    - Insufficient permissions
    echo.
    echo  Try:
    echo    1. Right-click this file
    echo    2. Select "Run as Administrator"
    echo    3. Click "Yes" on UAC prompt
    echo.
)

echo.
echo Verifying firewall rule...
netsh advfirewall firewall show rule name="DICOM C-STORE (Port 4242)"
echo.

echo ========================================================================
echo  NEXT STEPS
echo ========================================================================
echo.
echo  1. Verify Orthanc is running: http://localhost:8042
echo  2. Check port is listening: netstat -ano ^| findstr :4242
echo  3. Get your IP address: ipconfig
echo  4. Configure MRI machine with your IP and port 4242
echo.
echo  See: DUAL_DESTINATION_SETUP.txt for complete instructions
echo.
echo ========================================================================
echo.

pause
