@echo off
chcp 65001 >nul
color 0A
echo.
echo ========================================
echo   上传到 Gitee 新仓库
echo   91-home-tutoring-backend-php
echo ========================================
echo.

cd /d "%~dp0"

echo 仓库地址：
echo https://gitee.com/yuchendeloy/91-home-tutoring-backend-php
echo.

echo [1/4] 确认远程仓库...
git remote -v
echo.

echo [2/4] 添加文件...
git add .
if errorlevel 1 (
    echo ⚠ 部分文件添加失败，继续...
)
echo ✓ 文件已添加

echo.
echo [3/4] 提交更改...
git commit -m "feat: 91家教管理系统初始版本"
if errorlevel 1 (
    echo ⚠ 提交失败或已提交，尝试直接推送...
)

echo.
echo [4/4] 推送到 Gitee...
echo.
echo 提示：如果要求输入用户名和密码，请输入你的 Gitee 账号
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
    echo 2. 仓库不存在或无权限
    echo 3. 网络连接问题
    echo.
    echo 解决方案：
    echo 1. 确认仓库已创建：
    echo    https://gitee.com/yuchendeloy/91-home-tutoring-backend-php
    echo 2. 检查用户名和密码
    echo 3. 使用个人访问令牌：
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
echo https://gitee.com/yuchendeloy/91-home-tutoring-backend-php
echo.
echo 按任意键打开仓库页面...
pause >nul
start https://gitee.com/yuchendeloy/91-home-tutoring-backend-php

exit /b 0
