@echo off
REM ========================================================================
REM  VERIFY DUAL DESTINATION READINESS
REM  Checks if your PC is ready to receive DICOM from MRI
REM ========================================================================

title Verify Dual Destination Readiness
color 0B

echo ========================================================================
echo  DUAL DESTINATION READINESS CHECK
echo ========================================================================
echo.
echo  This script will verify your PC is ready to receive DICOM studies
echo  directly from the MRI machine.
echo.
echo ========================================================================
echo.

set CHECKS_PASSED=0
set CHECKS_FAILED=0

REM ====================
REM CHECK 1: IP Address
REM ====================
echo [CHECK 1] Getting your IP address...
echo.
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /C:"IPv4"') do (
    set IP=%%a
    echo   Your IP address: %%a
)
echo.
if defined IP (
    echo   [OK] IP address found
    set /a CHECKS_PASSED+=1
) else (
    echo   [FAIL] Could not determine IP address
    set /a CHECKS_FAILED+=1
)
echo.

REM ====================
REM CHECK 2: Orthanc Running
REM ====================
echo [CHECK 2] Checking if Orthanc is running...
echo.
curl -s -u orthanc:orthanc http://localhost:8042/system >nul 2>&1
if %errorLevel% EQU 0 (
    echo   [OK] Orthanc is running on http://localhost:8042
    set /a CHECKS_PASSED+=1
) else (
    echo   [FAIL] Orthanc is NOT running!
    echo   Please start Orthanc service or run Orthanc.exe
    set /a CHECKS_FAILED+=1
)
echo.

REM ====================
REM CHECK 3: DICOM Port Listening
REM ====================
echo [CHECK 3] Checking if DICOM port 4242 is listening...
echo.
netstat -ano | findstr :4242 | findstr LISTENING >nul 2>&1
if %errorLevel% EQU 0 (
    echo   [OK] Port 4242 is LISTENING
    set /a CHECKS_PASSED+=1
) else (
    echo   [FAIL] Port 4242 is NOT listening!
    echo   Orthanc may not be configured correctly
    set /a CHECKS_FAILED+=1
)
echo.

REM ====================
REM CHECK 4: Firewall Rule
REM ====================
echo [CHECK 4] Checking firewall rule for port 4242...
echo.
netsh advfirewall firewall show rule name="DICOM C-STORE (Port 4242)" >nul 2>&1
if %errorLevel% EQU 0 (
    echo   [OK] Firewall rule exists
    set /a CHECKS_PASSED+=1
) else (
    echo   [FAIL] Firewall rule NOT found!
    echo   Run: SETUP_FIREWALL_RULES.bat (as Administrator)
    set /a CHECKS_FAILED+=1
)
echo.

REM ====================
REM CHECK 5: Apache/MySQL Running
REM ====================
echo [CHECK 5] Checking Apache and MySQL...
echo.
tasklist | findstr /I httpd.exe >nul 2>&1
if %errorLevel% EQU 0 (
    echo   [OK] Apache is running
    set /a CHECKS_PASSED+=1
) else (
    echo   [FAIL] Apache is NOT running!
    echo   Start Apache in XAMPP Control Panel
    set /a CHECKS_FAILED+=1
)
echo.

tasklist | findstr /I mysqld.exe >nul 2>&1
if %errorLevel% EQU 0 (
    echo   [OK] MySQL is running
    set /a CHECKS_PASSED+=1
) else (
    echo   [FAIL] MySQL is NOT running!
    echo   Start MySQL in XAMPP Control Panel
    set /a CHECKS_FAILED+=1
)
echo.

REM ====================
REM SUMMARY
REM ====================
set /a TOTAL_CHECKS=%CHECKS_PASSED% + %CHECKS_FAILED%
echo ========================================================================
echo  READINESS SUMMARY
echo ========================================================================
echo.
echo  Checks Passed: %CHECKS_PASSED% / %TOTAL_CHECKS%
echo  Checks Failed: %CHECKS_FAILED% / %TOTAL_CHECKS%
echo.

if %CHECKS_FAILED% EQU 0 (
    echo  STATUS: [READY] Your PC is ready to receive DICOM!
    echo.
    echo  Next steps:
    echo    1. Note your IP address shown above
    echo    2. Configure MRI machine with:
    echo       - IP: %IP%
    echo       - Port: 4242
    echo       - AE Title: HOSPITAL_PACS
    echo    3. Send test study from MRI
    echo    4. Verify at: http://localhost:8042
    echo.
    echo  See: DUAL_DESTINATION_SETUP.txt for detailed instructions
) else (
    echo  STATUS: [NOT READY] Please fix the failed checks above
    echo.
    echo  Common fixes:
    echo    - Start Orthanc service (services.msc^)
    echo    - Run SETUP_FIREWALL_RULES.bat as Administrator
    echo    - Start Apache/MySQL in XAMPP Control Panel
    echo    - Check Orthanc Configuration.json has DicomPort: 4242
    echo.
)

echo.
echo ========================================================================
echo  CONFIGURATION DETAILS
echo ========================================================================
echo.
echo  Your IP Address: %IP%
echo  DICOM Port: 4242
echo  Orthanc AE Title: HOSPITAL_PACS
echo  Orthanc URL: http://localhost:8042
echo  Web Interface: http://localhost/papa/dicom_again/pages/patients.html
echo.
echo ========================================================================
echo.

pause
