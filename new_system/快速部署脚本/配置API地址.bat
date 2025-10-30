@echo off
chcp 65001 >nul
echo ========================================
echo 配置API地址
echo ========================================
echo.

echo 请输入API服务器地址（例如：http://localhost:8080）：
set /p api_url=

if "%api_url%"=="" (
    echo 错误：API地址不能为空
    pause
    exit /b 1
)

echo.
echo 开始配置API地址...
echo.

REM 配置管理端API地址
echo [1/2] 配置管理端API地址...
cd "..\frontend\admin\src\api"
if exist "config.js" (
    echo 更新管理端API配置...
    powershell -Command "(Get-Content 'config.js') -replace 'baseURL:.*', 'baseURL: \"%api_url%\"' | Set-Content 'config.js'"
    echo 管理端API配置更新完成！
) else (
    echo 警告：未找到管理端配置文件
)

echo.

REM 配置用户端API地址
echo [2/2] 配置用户端API地址...
cd "..\..\..\user\src\api"
if exist "config.js" (
    echo 更新用户端API配置...
    powershell -Command "(Get-Content 'config.js') -replace 'baseURL:.*', 'baseURL: \"%api_url%\"' | Set-Content 'config.js'"
    echo 用户端API配置更新完成！
) else (
    echo 警告：未找到用户端配置文件
)

echo.
echo ========================================
echo API地址配置完成！
echo ========================================
echo.
echo 配置的API地址：%api_url%
echo.
echo 请重新构建前端项目以使配置生效：
echo 运行：构建前端项目.bat
echo.
pause
