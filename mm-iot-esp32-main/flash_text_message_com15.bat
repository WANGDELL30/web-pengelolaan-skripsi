@echo off
setlocal

cd /d "%~dp0"

set "IDF_PATH=C:\Espressif\frameworks\esp-idf-v5.1.1"
set "MMIOT_ROOT=%CD%\mm-iot-esp32-main"
set "IDF_TARGET=esp32s3"

echo Building and flashing Text Message firmware from:
echo %CD%
echo.
echo Expected status marker after flash:
echo firmware_version = text-msg-v8-20260507
echo.

call "%IDF_PATH%\export.bat"
if errorlevel 1 exit /b %errorlevel%

idf.py -p COM15 build flash
if errorlevel 1 (
    echo.
    echo Flash failed. Close Serial Monitor or any app using COM15, then run this file again.
    exit /b %errorlevel%
)

echo.
echo Flash complete. Reset ESP32, then open:
echo http://192.168.1.112/api/status
echo.
echo Confirm the JSON contains:
echo "firmware_version": "text-msg-v8-20260507"

endlocal
