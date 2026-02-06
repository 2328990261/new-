@echo off
chcp 65001 >nul
color 0A
echo.
echo ========================================
echo   91家教管理系统 - 立即上传
echo   （HTTPS 方式 - 更稳定）
echo ========================================
echo.

cd /d "%~dp0"

echo [1/4] 配置远程仓库为 HTTPS...
git remote set-url origin https://gitee.com/yuchendeloy/tjiajiao91.git
echo ✓ 已配置为 HTTPS 方式

echo.
echo [2/4] 添加文件...
git add .
if errorlevel 1 (
    echo ⚠ 部分文件添加失败，但继续...
)

echo.
echo [3/4] 提交更改...
git commit -m "feat: 91家教管理系统初始版本"
if errorlevel 1 (
    echo.
    echo ❌ 提交失败
    echo.
    echo 可能原因：
    echo 1. 没有文件被添加
    echo 2. 已经提交过了
    echo.
    echo 尝试直接推送...
    echo.
)

echo.
echo [4/4] 推送到 Gitee...
echo.
echo 提示：如果要求输入用户名和密码，请输入你的 Gitee 账号信息
echo.
git branch -M main
git push -u origin main

if errorlevel 1 (
    echo.
    echo ========================================
    echo ❌ 推送失败
    echo ========================================
    echo.
    echo 可能原因：
    echo 1. 用户名或密码错误
    echo 2. 网络连接问题
    echo 3. 仓库权限问题
    echo.
    echo 解决方案：
    echo 1. 检查用户名和密码是否正确
    echo 2. 使用个人访问令牌代替密码
    echo    访问：https://gitee.com/profile/personal_access_tokens
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo ✓✓✓ 上传成功！✓✓✓
echo ========================================
echo.
echo 仓库地址：
echo https://gitee.com/yuchendeloy/tjiajiao91
echo.
echo 按任意键打开仓库页面...
pause >nul
start https://gitee.com/yuchendeloy/tjiajiao91

exit /b 0
