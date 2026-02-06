@echo off
chcp 65001 >nul
color 0A
echo.
echo ========================================
echo   分步上传到 Gitee
echo   第1步：配置登录凭据
echo ========================================
echo.

cd /d "%~dp0"

echo 仓库地址：https://gitee.com/yuchendeloy/tjiajiao91
echo.

echo [步骤 1] 配置 Git 用户信息
echo.
set /p git_username="请输入你的 Gitee 用户名: "
set /p git_email="请输入你的邮箱: "

git config --global user.name "%git_username%"
git config --global user.email "%git_email%"

echo.
echo ✓ 用户信息已配置
echo   用户名: %git_username%
echo   邮箱: %git_email%
echo.

echo [步骤 2] 配置凭据存储
echo.
echo 选择凭据存储方式：
echo 1. 记住密码（推荐）
echo 2. 每次都输入
echo.
set /p cred_choice="请选择 (1 或 2): "

if "%cred_choice%"=="1" (
    git config --global credential.helper store
    echo ✓ 已启用凭据存储，首次输入密码后会自动记住
) else (
    git config --global credential.helper ""
    echo ✓ 每次推送都需要输入密码
)

echo.
echo [步骤 3] 测试连接并保存凭据
echo.
echo 现在进行一次测试连接，请输入你的 Gitee 密码
echo （如果有个人访问令牌，建议使用令牌代替密码）
echo.
echo 提示：输入密码时不会显示字符，这是正常的
echo.

REM 先配置远程仓库
git remote set-url origin https://gitee.com/yuchendeloy/tjiajiao91.git 2>nul
if errorlevel 1 (
    git remote add origin https://gitee.com/yuchendeloy/tjiajiao91.git
)

REM 尝试获取远程信息（这会触发登录）
git ls-remote origin >nul 2>&1

if errorlevel 1 (
    echo.
    echo ⚠ 连接失败，可能是用户名或密码错误
    echo.
    echo 提示：
    echo 1. 确认用户名和密码正确
    echo 2. 建议使用个人访问令牌代替密码
    echo    生成令牌：https://gitee.com/profile/personal_access_tokens
    echo.
    pause
    exit /b 1
)

echo.
echo ✓ 连接成功！凭据已保存
echo.

echo ========================================
echo ✓ 第1步完成！
echo ========================================
echo.
echo 现在可以运行第2步：上传代码
echo.
echo 请双击运行：分步上传-第2步上传.bat
echo.
pause
