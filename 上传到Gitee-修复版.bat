@echo off
chcp 65001 >nul
echo ========================================
echo    91家教管理系统 - 上传到 Gitee
echo    （文件权限修复版）
echo ========================================
echo.

cd /d "%~dp0"

echo [提示] 正在尝试修复文件权限问题...
echo.
echo 请按照以下步骤操作：
echo.
echo 1. 关闭所有可能占用文件的程序：
echo    - 关闭 VS Code / Kiro IDE
echo    - 停止 PHP 服务（如果正在运行）
echo    - 关闭文件资源管理器
echo.
echo 2. 按任意键继续...
pause >nul

echo.
echo [1/5] 检查 Git 是否安装...
git --version >nul 2>&1
if errorlevel 1 (
    echo ❌ 错误: 未检测到 Git
    pause
    exit /b 1
)
echo ✓ Git 已安装

echo.
echo [2/5] 配置 Git...
git config core.fileMode false
git config core.autocrlf true
echo ✓ Git 配置完成

echo.
echo [3/5] 尝试添加文件...
echo.

REM 先尝试添加文档文件
git add .gitignore README.md 2>nul
git add *.md 2>nul
git add *.txt 2>nul
git add *.conf 2>nul

REM 添加数据库文件
git add database/ 2>nul

REM 尝试添加所有文件
git add . 2>nul

echo.
echo [4/5] 检查已添加的文件...
git status --short

echo.
set /p confirm="确认提交这些文件？(Y/N): "
if /i not "%confirm%"=="Y" (
    echo 已取消
    pause
    exit /b 0
)

echo.
echo [5/5] 提交并推送...

set /p commit_msg="请输入提交信息 (直接回车使用默认): "
if "%commit_msg%"=="" (
    set commit_msg=feat: 91家教管理系统初始版本
)

git commit -m "%commit_msg%"

if errorlevel 1 (
    echo.
    echo ❌ 提交失败，可能没有文件被添加
    echo.
    echo 建议：
    echo 1. 关闭所有程序后重试
    echo 2. 或者重启电脑后再运行此脚本
    echo 3. 查看 手动上传步骤.md 获取详细说明
    pause
    exit /b 1
)

echo.
echo 正在推送到 Gitee...
git branch -M main
git push -u origin main

if errorlevel 1 (
    echo.
    echo ❌ 推送失败
    echo.
    echo 可能的原因：
    echo 1. SSH 密钥未配置
    echo 2. 网络连接问题
    echo.
    echo 如果是 SSH 密钥问题，请查看 GIT上传指南.md
    pause
    exit /b 1
)

echo.
echo ========================================
echo ✓ 上传成功！
echo ========================================
echo.
echo 仓库地址: https://gitee.com/yuchendeloy/tjiajiao91
echo.
pause
