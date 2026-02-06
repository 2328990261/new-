@echo off
chcp 65001 >nul
color 0A
echo.
echo ========================================
echo   91家教管理系统 - 一键配置并上传
echo ========================================
echo.

cd /d "%~dp0"

echo [步骤 1/4] 检查 SSH 密钥...
if exist "%USERPROFILE%\.ssh\id_rsa.pub" (
    echo ✓ SSH 密钥已存在
) else (
    echo ✗ SSH 密钥不存在，正在生成...
    ssh-keygen -t rsa -b 4096 -C "gitee_key" -f "%USERPROFILE%\.ssh\id_rsa" -N ""
    echo ✓ SSH 密钥已生成
)

echo.
echo [步骤 2/4] 显示 SSH 公钥
echo.
echo ========================================
echo 请复制以下公钥内容：
echo ========================================
echo.
type "%USERPROFILE%\.ssh\id_rsa.pub"
echo.
echo ========================================
echo.

echo 请按照以下步骤操作：
echo.
echo 1. 复制上面显示的公钥（从 ssh-rsa 开始到最后）
echo 2. 打开浏览器访问：https://gitee.com/profile/sshkeys
echo 3. 点击"添加公钥"
echo 4. 标题随便填（如：My Computer）
echo 5. 粘贴公钥内容
echo 6. 点击"确定"
echo.
echo 完成后按任意键继续...
pause >nul

echo.
echo [步骤 3/4] 测试 SSH 连接...
echo.
ssh -T git@gitee.com 2>&1 | findstr /C:"successfully authenticated" >nul
if errorlevel 1 (
    echo.
    echo ❌ SSH 连接失败！
    echo.
    echo 可能的原因：
    echo 1. 公钥还没有添加到 Gitee
    echo 2. 公钥添加不完整
    echo 3. 需要等待 1-2 分钟让 Gitee 同步
    echo.
    echo 请确认已正确添加公钥后，按任意键重试...
    pause >nul
    
    ssh -T git@gitee.com 2>&1 | findstr /C:"successfully authenticated" >nul
    if errorlevel 1 (
        echo.
        echo ❌ 仍然失败，请检查公钥配置
        echo.
        echo 手动测试命令：
        echo   ssh -T git@gitee.com
        echo.
        pause
        exit /b 1
    )
)

echo ✓ SSH 连接成功！

echo.
echo [步骤 4/4] 上传代码到 Gitee...
echo.

REM 配置 Git
git config core.fileMode false
git config core.autocrlf true

REM 添加文件（忽略错误）
echo 正在添加文件...
git add . 2>nul

REM 检查是否有文件被添加
git diff --cached --quiet
if errorlevel 1 (
    echo ✓ 文件已添加
) else (
    echo ⚠ 没有文件被添加，尝试强制添加...
    git add --all --force 2>nul
)

REM 提交
echo 正在提交...
git commit -m "feat: 91家教管理系统初始版本" 2>nul
if errorlevel 1 (
    echo.
    echo ⚠ 提交失败，可能是因为：
    echo 1. 没有文件被添加
    echo 2. 文件被其他程序占用
    echo.
    echo 建议：关闭 Kiro IDE 后重新运行此脚本
    echo.
    pause
    exit /b 1
)

echo ✓ 提交成功

REM 推送
echo 正在推送到 Gitee...
git branch -M main
git push -u origin main

if errorlevel 1 (
    echo.
    echo ❌ 推送失败
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
