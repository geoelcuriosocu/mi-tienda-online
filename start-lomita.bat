@echo off
title LaLomita - Starting services
cd /d "%~dp0"

echo.
echo === LaLomita - Tienda Online ===
echo.

netstat -ano | findstr ":3306" >nul
if errorlevel 1 (
    echo Starting MariaDB...
    start "MariaDB" /MIN "C:\Program Files\MariaDB 13.0\bin\mysqld.exe" --defaults-file="%~dp0mysql-my.ini" --console
    timeout /t 6 /nobreak >nul
) else (
    echo MariaDB already running.
)

netstat -ano | findstr ":8000" >nul
if errorlevel 1 (
    echo Starting LaLomita web server...
    start "LaLomita" /MIN "C:\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
    timeout /t 4 /nobreak >nul
) else (
    echo Web server already running.
)

echo.
echo Opening http://127.0.0.1:8000
start "" "http://127.0.0.1:8000"
echo.
echo Done. You can close this window.
echo.
