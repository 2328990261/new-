@echo off
chcp 65001
echo ========================================
echo 开始打包系统...
echo ========================================

:: 1. 打包管理后台前端
echo.
echo [1/3] 正在打包管理后台前端...
cd tjiajiao91-main\new_system\frontend\admin
call npm run build
if %errorlevel% neq 0 (
    echo 管理后台打包失败！
    pause
    exit /b 1
)
echo 管理后台打包完成！

:: 2. 打包用户端前端
echo.
echo [2/3] 正在打包用户端前端...
cd ..\user
call npm run build
if %errorlevel% neq 0 (
    echo 用户端打包失败！
    pause
    exit /b 1
)
echo 用户端打包完成！

:: 3. 返回根目录
cd ..\..\..\..\

echo.
echo ========================================
echo 打包完成！
echo ========================================
echo.
echo 打包文件位置：
echo - 管理后台: tjiajiao91-main\new_system\frontend\admin\dist
echo - 用户端:   tjiajiao91-main\new_system\frontend\user\dist
echo - 后端:     tjiajiao91-main\new_system\backend
echo.
echo 请将以上目录上传到服务器
echo.
pause
