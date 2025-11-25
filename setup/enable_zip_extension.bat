@echo off
echo ================================================
echo Enabling ZIP Extension for PHP
echo ================================================
echo.

REM Find php.ini location
set PHP_INI=C:\xampp\php\php.ini

echo Checking if php.ini exists at: %PHP_INI%
if not exist "%PHP_INI%" (
    echo ERROR: php.ini not found at %PHP_INI%
    echo Please check your XAMPP installation path
    pause
    exit /b 1
)

echo.
echo Creating backup of php.ini...
copy "%PHP_INI%" "%PHP_INI%.backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >nul
echo Backup created!

echo.
echo Enabling php_zip extension...

REM Check if extension is already enabled
findstr /C:"extension=zip" "%PHP_INI%" >nul
if %errorlevel% equ 0 (
    echo ZIP extension is already enabled!
) else (
    REM Check if it's commented out
    findstr /C:";extension=zip" "%PHP_INI%" >nul
    if %errorlevel% equ 0 (
        echo Found commented extension, uncommenting...
        powershell -Command "(gc '%PHP_INI%') -replace ';extension=zip', 'extension=zip' | Out-File -encoding ASCII '%PHP_INI%'"
        echo Extension enabled!
    ) else (
        echo Adding extension=zip to php.ini...
        echo extension=zip >> "%PHP_INI%"
        echo Extension added!
    )
)

echo.
echo ================================================
echo Configuration Updated!
echo ================================================
echo.
echo NEXT STEP: Restart Apache from XAMPP Control Panel
echo.
echo 1. Open XAMPP Control Panel
echo 2. Click "Stop" next to Apache
echo 3. Wait a few seconds
echo 4. Click "Start" next to Apache
echo 5. Try the backup again
echo.
pause
