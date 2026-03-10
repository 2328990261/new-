@echo off
chcp 65001 >nul
echo ========================================
echo 构建前端项目
echo ========================================
echo.

REM 检查Node.js是否安装
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误：未找到Node.js，请先安装Node.js
    echo 下载地址：https://nodejs.org/
    pause
    exit /b 1
)

REM 检查npm是否安装
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误：未找到npm，请先安装npm
    pause
    exit /b 1
)

echo 开始构建前端项目...
echo.

REM 构建管理端
echo [1/2] 构建管理端...
cd "..\frontend\admin"
echo 安装依赖...
npm install
if %errorlevel% neq 0 (
    echo 错误：安装管理端依赖失败
    pause
    exit /b 1
)

echo 构建管理端...
npm run build
if %errorlevel% neq 0 (
    echo 错误：构建管理端失败
    pause
    exit /b 1
)

echo 管理端构建完成！
echo.

REM 构建用户端
echo [2/2] 构建用户端...
cd "..\user"
echo 安装依赖...
npm install
if %errorlevel% neq 0 (
    echo 错误：安装用户端依赖失败
    pause
    exit /b 1
)

echo 构建用户端...
npm run build
if %errorlevel% neq 0 (
    echo 错误：构建用户端失败
    pause
    exit /b 1
)

echo 用户端构建完成！
echo.

echo ========================================
echo 前端项目构建完成！
echo ========================================
echo.
echo 构建结果：
echo 管理端：new_system\frontend\admin\dist
echo 用户端：new_system\frontend\user\dist
echo.
echo 请将构建结果部署到Web服务器。
echo.
pause
