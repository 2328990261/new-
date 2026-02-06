@echo off
chcp 65001 >nul
echo ========================================
echo    91家教管理系统 - 上传到 Gitee
echo ========================================
echo.

cd /d "%~dp0"

echo [1/6] 检查 Git 是否安装...
git --version >nul 2>&1
if errorlevel 1 (
    echo ❌ 错误: 未检测到 Git，请先安装 Git
    echo 下载地址: https://git-scm.com/download/win
    pause
    exit /b 1
)
echo ✓ Git 已安装

echo.
echo [2/6] 检查 .gitignore 文件...
if not exist ".gitignore" (
    echo ❌ 错误: .gitignore 文件不存在
    pause
    exit /b 1
)
echo ✓ .gitignore 文件存在

echo.
echo [3/6] 初始化 Git 仓库...
if not exist ".git" (
    git init
    echo ✓ Git 仓库已初始化
) else (
    echo ✓ Git 仓库已存在
)

echo.
echo [4/6] 配置远程仓库...
git remote get-url origin >nul 2>&1
if errorlevel 1 (
    git remote add origin git@gitee.com:yuchendeloy/tjiajiao91.git
    echo ✓ 远程仓库已添加
) else (
    echo ✓ 远程仓库已存在
    git remote -v
)

echo.
echo [5/6] 检查待提交文件...
echo.
git status
echo.

echo ⚠️  请仔细检查上面的文件列表！
echo.
echo 确保以下文件 NOT 在列表中：
echo   - backend/.env
echo   - backend/cert/wechat/*.pem
echo   - node_modules/
echo   - backend/vendor/
echo   - backend/runtime/
echo.

set /p confirm="确认继续上传？(Y/N): "
if /i not "%confirm%"=="Y" (
    echo 已取消上传
    pause
    exit /b 0
)

echo.
echo [6/6] 添加、提交并推送文件...
git add .

echo.
set /p commit_msg="请输入提交信息 (直接回车使用默认信息): "
if "%commit_msg%"=="" (
    set commit_msg=feat: 91家教管理系统初始版本
)

git commit -m "%commit_msg%"

echo.
echo 正在推送到 Gitee...
git branch -M main
git push -u origin main

if errorlevel 1 (
    echo.
    echo ❌ 推送失败！
    echo.
    echo 可能的原因：
    echo 1. SSH 密钥未配置
    echo 2. 网络连接问题
    echo 3. 权限不足
    echo.
    echo 请查看错误信息并解决后重试
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
echo 后续更新使用以下命令：
echo   git add .
echo   git commit -m "描述更改"
echo   git push
echo.
pause
