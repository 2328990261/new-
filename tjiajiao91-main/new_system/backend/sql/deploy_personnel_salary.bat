@echo off
chcp 65001 >nul
REM 人员薪酬管理功能部署脚本 (Windows版本)
REM 使用方法: deploy_personnel_salary.bat

echo =========================================
echo 人员薪酬管理功能部署脚本
echo =========================================
echo.

REM 数据库配置（请根据实际情况修改）
set DB_HOST=localhost
set DB_PORT=3306
set DB_NAME=your_database_name
set DB_USER=your_username
set DB_PASS=your_password

REM MySQL可执行文件路径（如果mysql不在PATH中，请修改此路径）
set MYSQL_BIN=mysql

echo 正在检查MySQL连接...
%MYSQL_BIN% -h%DB_HOST% -P%DB_PORT% -u%DB_USER% -p%DB_PASS% -e "SELECT 1" >nul 2>&1

if %errorlevel% neq 0 (
    echo [错误] 无法连接到MySQL数据库
    echo 请检查数据库配置信息
    pause
    exit /b 1
)

echo [成功] MySQL连接成功
echo.

echo 正在创建人员薪酬表...
%MYSQL_BIN% -h%DB_HOST% -P%DB_PORT% -u%DB_USER% -p%DB_PASS% %DB_NAME% < create_personnel_salary_table.sql

if %errorlevel% equ 0 (
    echo [成功] 数据库表创建成功
) else (
    echo [错误] 数据库表创建失败
    pause
    exit /b 1
)

echo.
echo =========================================
echo 部署完成！
echo =========================================
echo.
echo 接下来的步骤：
echo 1. 重启后端服务（如果需要）
echo 2. 重新编译前端代码（如果需要）
echo 3. 访问管理后台 -^> 企业管理 -^> 薪酬管理
echo.
echo 详细使用说明请查看: README_PERSONNEL_SALARY.md
echo.
pause
