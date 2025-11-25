@echo off
echo Restarting Python API Gateway...
echo.

REM Kill all Python processes running the gateway
echo Stopping gateway processes...
wmic process where "name='python.exe' and commandline like '%%orthanc_api_gateway%%'" delete 2>nul

timeout /t 2 /nobreak >nul

REM Start the gateway
echo Starting gateway...
start /b C:\Users\prash\AppData\Local\Programs\Python\Python311\python.exe "c:\xampp\htdocs\papa\dicom_again\orthanc_api_gateway.py"

timeout /t 3 /nobreak

REM Check if it's running
echo.
echo Checking if gateway is running...
curl http://localhost:5000/health

echo.
echo.
echo Gateway restarted! Press any key to close...
pause >nul
