@echo off
chcp 65001 >nul
echo ========================================
echo 家教信息管理系统 - 一键部署脚本
echo ========================================
echo.

REM 检查PHP是否安装
where php >nul 2>&1
if %errorlevel% neq 0 (
    REM 尝试查找phpstudy_pro中的PHP
    if exist "C:\phpstudy_pro\Extensions\php\php7.4.3nts\php.exe" (
        set PHP_PATH=C:\phpstudy_pro\Extensions\php\php7.4.3nts\php.exe
        echo 找到PHP：%PHP_PATH%
    ) else if exist "C:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe" (
        set PHP_PATH=C:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe
        echo 找到PHP：%PHP_PATH%
    ) else (
        echo 错误：未找到PHP，请先安装PHP
        echo 推荐使用XAMPP、WAMP或phpstudy_pro
        pause
        exit /b 1
    )
) else (
    set PHP_PATH=php
)

REM 检查MySQL是否安装
where mysql >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误：未找到MySQL，请先安装MySQL
    echo 推荐使用XAMPP或WAMP
    pause
    exit /b 1
)

REM 检查Node.js是否安装
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误：未找到Node.js，请先安装Node.js
    echo 下载地址：https://nodejs.org/
    pause
    exit /b 1
)

echo 环境检查通过！
echo.

REM 设置数据库信息
echo 请输入数据库配置信息：
echo.
set /p db_host=数据库主机（默认：localhost）：
if "%db_host%"=="" set db_host=localhost

set /p db_name=数据库名称（默认：jiajiao_system）：
if "%db_name%"=="" set db_name=jiajiao_system

set /p db_user=数据库用户名（默认：root）：
if "%db_user%"=="" set db_user=root

set /p db_pass=数据库密码：

echo.
echo 开始部署...
echo.

REM 1. 创建数据库
echo [1/5] 创建数据库...
mysql -h%db_host% -u%db_user% -p%db_pass% -e "CREATE DATABASE IF NOT EXISTS %db_name% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo 错误：创建数据库失败
    pause
    exit /b 1
)

REM 2. 导入数据库结构
echo [2/5] 导入数据库结构...
mysql -h%db_host% -u%db_user% -p%db_pass% %db_name% < "..\database\create_tables.sql"
if %errorlevel% neq 0 (
    echo 错误：导入数据库结构失败
    pause
    exit /b 1
)

REM 3. 导入基础数据
echo [3/5] 导入基础数据...
mysql -h%db_host% -u%db_user% -p%db_pass% %db_name% < "..\database\import_pca_complete_full.sql"
if %errorlevel% neq 0 (
    echo 错误：导入基础数据失败
    pause
    exit /b 1
)

REM 4. 安装PHP依赖
echo [4/5] 安装PHP依赖...
cd "..\backend"
composer install --no-dev --optimize-autoloader
if %errorlevel% neq 0 (
    echo 错误：安装PHP依赖失败
    pause
    exit /b 1
)

REM 5. 构建前端项目
echo [5/5] 构建前端项目...
cd "..\frontend\admin"
npm install
npm run build
if %errorlevel% neq 0 (
    echo 错误：构建前端项目失败
    pause
    exit /b 1
)

cd "..\user"
npm install
npm run build
if %errorlevel% neq 0 (
    echo 错误：构建用户端项目失败
    pause
    exit /b 1
)

echo.
echo ========================================
echo 部署完成！
echo ========================================
echo.
echo 数据库信息：
echo 主机：%db_host%
echo 数据库：%db_name%
echo 用户名：%db_user%
echo.
echo 请配置Web服务器指向以下目录：
echo 后端：new_system\backend\public
echo 前端：new_system\frontend\admin\dist
echo 用户端：new_system\frontend\user\dist
echo.
echo 默认管理员账号：
echo 用户名：admin
echo 密码：admin123
echo.
echo 请及时修改默认密码！
echo.
pause
