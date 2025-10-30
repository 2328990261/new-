@echo off
chcp 65001 >nul
echo ========================================
echo 免费SSL证书申请脚本
echo ========================================
echo.

REM 检查是否安装了certbot
where certbot >nul 2>&1
if %errorlevel% neq 0 (
    echo 错误：未找到certbot，请先安装certbot
    echo 安装方法：
    echo 1. 下载并安装Python
    echo 2. 运行：pip install certbot
    echo 3. 或者使用包管理器安装
    pause
    exit /b 1
)

echo 请输入域名（例如：example.com）：
set /p domain=

if "%domain%"=="" (
    echo 错误：域名不能为空
    pause
    exit /b 1
)

echo.
echo 请输入邮箱地址：
set /p email=

if "%email%"=="" (
    echo 错误：邮箱不能为空
    pause
    exit /b 1
)

echo.
echo 请输入网站根目录路径（例如：C:\xampp\htdocs\your-site）：
set /p webroot=

if "%webroot%"=="" (
    echo 错误：网站根目录不能为空
    pause
    exit /b 1
)

echo.
echo 开始申请SSL证书...
echo 域名：%domain%
echo 邮箱：%email%
echo 网站根目录：%webroot%
echo.

REM 申请证书
certbot certonly --webroot -w "%webroot%" -d "%domain%" --email "%email%" --agree-tos --non-interactive

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo SSL证书申请成功！
    echo ========================================
    echo.
    echo 证书文件位置：
    echo 证书文件：C:\Certbot\live\%domain%\fullchain.pem
    echo 私钥文件：C:\Certbot\live\%domain%\privkey.pem
    echo.
    echo 请将证书文件配置到您的Web服务器中。
    echo.
    echo 自动续期设置：
    echo 1. 打开任务计划程序
    echo 2. 创建基本任务
    echo 3. 触发器：每月
    echo 4. 操作：certbot renew --quiet
    echo.
) else (
    echo.
    echo ========================================
    echo SSL证书申请失败！
    echo ========================================
    echo.
    echo 请检查：
    echo 1. 域名是否正确
    echo 2. 网站是否可以正常访问
    echo 3. 防火墙设置
    echo 4. 网络连接
    echo.
)

pause
