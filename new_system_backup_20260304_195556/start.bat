@echo off
title Start System
cls
echo ==========================================
echo Starting Tutor Management System
echo ==========================================
echo.

set PHP_PATH=C:\phpstudy_pro\Extensions\php\php7.4.3nts\php.exe

if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found
    pause
    exit /b 1
)

echo [OK] PHP found
echo.

where node >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js not found
    pause
    exit /b 1
)

echo [OK] Node.js found
echo.

cd /d "%~dp0"

echo Stopping old processes...
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im node.exe >nul 2>&1
echo [OK] Old processes stopped
echo.

echo Starting servers...
echo.

echo [1/3] Starting Backend API Server...
start "Backend" cmd /k "cd /d %CD%\backend\public && %PHP_PATH% -S localhost:8080 router.php"

timeout /t 3 /nobreak >nul

echo [2/3] Starting Admin Frontend...
start "Admin" cmd /k "cd /d %CD%\frontend\admin && npm run dev -- --port 3000"

timeout /t 3 /nobreak >nul

echo [3/3] Starting User Frontend...
start "User" cmd /k "cd /d %CD%\frontend\user && npm run dev -- --port 3001"

echo.
echo ==========================================
echo All servers started successfully!
echo ==========================================
echo.
echo Access URLs:
echo   Backend API:  http://localhost:8080
echo   phpMyAdmin:   http://localhost:8080/phpMyAdmin4.8.5/
echo   Admin Panel:  http://localhost:3000
echo   User Portal:  http://localhost:3001
echo.
echo Database:
echo   User: jE2se7DGe5HfE6zL
echo   Pass: myjiajiao
echo   DB:   myjiajiao
echo.
echo Admin:
echo   User: admin
echo   Pass: admin123
echo.
echo Wait 10-20 seconds for all servers to start...
echo.
pause