@echo off
chcp 65001 >nul
color 0A
echo.
echo ========================================
echo   上传到 Gitee - tjiajiao91
echo ========================================
echo.

cd /d "%~dp0"

echo 仓库地址：https://gitee.com/yuchendeloy/tjiajiao91
echo.

echo [1/5] 配置 Git...
git config core.fileMode false
git config core.autocrlf true
echo ✓ Git 配置完成

echo.
echo [2/5] 确认远程仓库...
git remote -v
echo.

echo [3/5] 添加文件...
git add .
echo ✓ 文件添加完成（忽略部分权限错误）

echo.
echo [4/5] 提交更改...
git commit -m "feat: 91家教管理系统完整代码"
if errorlevel 1 (
    echo ⚠ 可能已提交过，尝试直接推送...
)

echo.
echo [5/5] 推送到 Gitee...
echo.
echo 提示：首次推送需要输入 Gitee 用户名和密码
echo.
git branch -M main
git push -u origin main --force

if errorlevel 1 (
    echo.
    echo ========================================
    echo ❌ 推送失败
    echo ========================================
    echo.
    echo 可能原因：
    echo 1. 用户名或密码错误
    echo 2. 网络连接问题
    echo.
    echo 解决方案：
    echo 1. 检查用户名和密码
    echo 2. 使用个人访问令牌：
    echo    https://gitee.com/profile/personal_access_tokens
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
