@echo off
title Check Frontend Files
cls
echo ==========================================
echo Checking Frontend Files
echo ==========================================
echo.

cd /d "%~dp0"

echo Checking Admin Frontend...
echo.

REM Check store modules
echo [1] Checking store modules...
if exist "frontend\admin\src\store\modules\app.js" (
    echo [OK] app.js exists
) else (
    echo [MISSING] app.js
)

if exist "frontend\admin\src\store\modules\user.js" (
    echo [OK] user.js exists
) else (
    echo [MISSING] user.js
)

if exist "frontend\admin\src\store\index.js" (
    echo [OK] store/index.js exists
) else (
    echo [MISSING] store/index.js
)

echo.
echo [2] Checking layout files...
if exist "frontend\admin\src\layout\index.vue" (
    echo [OK] layout/index.vue exists
) else (
    echo [MISSING] layout/index.vue
)

echo.
echo [3] Checking router files...
if exist "frontend\admin\src\router\index.js" (
    echo [OK] router/index.js exists
) else (
    echo [MISSING] router/index.js
)

echo.
echo Checking User Frontend...
echo.

echo [4] Checking user files...
if exist "frontend\user\src\App.vue" (
    echo [OK] App.vue exists
) else (
    echo [MISSING] App.vue
)

if exist "frontend\user\src\main.js" (
    echo [OK] main.js exists
) else (
    echo [MISSING] main.js
)

if exist "frontend\user\src\router\index.js" (
    echo [OK] router/index.js exists
) else (
    echo [MISSING] router/index.js
)

if exist "frontend\user\src\store\index.js" (
    echo [OK] store/index.js exists
) else (
    echo [MISSING] store/index.js
)

echo.
echo ==========================================
echo Check complete!
echo ==========================================
echo.
pause
