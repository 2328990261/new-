@echo off
chcp 65001 >nul
echo ========================================
echo 创建教师验证码表
echo ========================================
echo.

REM 从 .env 文件读取数据库配置
set ENV_FILE=..\.env
if not exist "%ENV_FILE%" (
    echo 错误: 找不到 .env 文件
    pause
    exit /b 1
)

REM 读取数据库配置
for /f "tokens=1,2 delims==" %%a in ('type "%ENV_FILE%" ^| findstr /r "^DATABASE_"') do (
    if "%%a"=="DATABASE_HOSTNAME" set DB_HOST=%%b
    if "%%a"=="DATABASE_DATABASE" set DB_NAME=%%b
    if "%%a"=="DATABASE_USERNAME" set DB_USER=%%b
    if "%%a"=="DATABASE_PASSWORD" set DB_PASS=%%b
    if "%%a"=="DATABASE_HOSTPORT" set DB_PORT=%%b
)

REM 去除可能的引号和空格
set DB_HOST=%DB_HOST:"=%
set DB_NAME=%DB_NAME:"=%
set DB_USER=%DB_USER:"=%
set DB_PASS=%DB_PASS:"=%
set DB_PORT=%DB_PORT:"=%

echo 数据库配置:
echo   主机: %DB_HOST%
echo   端口: %DB_PORT%
echo   数据库: %DB_NAME%
echo   用户: %DB_USER%
echo.

REM 执行 SQL 文件
echo 正在创建教师验证码表...
mysql -h%DB_HOST% -P%DB_PORT% -u%DB_USER% -p%DB_PASS% %DB_NAME% < create_teacher_verification_codes.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo 教师验证码表创建成功！
    echo ========================================
) else (
    echo.
    echo ========================================
    echo 创建失败，请检查错误信息
    echo ========================================
)

echo.
pause
