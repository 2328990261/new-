@echo off
chcp 65001 >nul
echo =========================================
echo 小程序图片上传到服务器
echo =========================================
echo.

REM 配置
set LOCAL_PATH=tjiajiao91-main\微信小程序\预约家教小程序\static

REM 检查本地文件
if not exist "%LOCAL_PATH%\ai-avatar.png" (
    echo 错误: 找不到 ai-avatar.png
    pause
    exit /b 1
)

if not exist "%LOCAL_PATH%\customer-qrcode.jpg" (
    echo 错误: 找不到 customer-qrcode.jpg
    pause
    exit /b 1
)

echo 本地文件检查通过
echo.
echo 请使用以下方法之一上传文件到服务器：
echo.
echo 方法1: 使用FTP工具（推荐）
echo ----------------------------------------
echo 1. 打开FileZilla或其他FTP工具
echo 2. 连接到 tjiajiao91.com
echo 3. 在服务器上创建目录: /miniprogram/images/
echo 4. 上传以下文件:
echo    - %LOCAL_PATH%\ai-avatar.png
echo    - %LOCAL_PATH%\customer-qrcode.jpg
echo.
echo 方法2: 使用宝塔面板
echo ----------------------------------------
echo 1. 登录宝塔面板
echo 2. 进入文件管理
echo 3. 创建目录: /miniprogram/images/
echo 4. 上传以下文件:
echo    - %LOCAL_PATH%\ai-avatar.png
echo    - %LOCAL_PATH%\customer-qrcode.jpg
echo.
echo 方法3: 使用SCP命令（需要安装OpenSSH）
echo ----------------------------------------
echo 如果你已安装OpenSSH，可以运行以下命令：
echo.
echo scp "%LOCAL_PATH%\ai-avatar.png" your_username@tjiajiao91.com:/var/www/html/miniprogram/images/
echo scp "%LOCAL_PATH%\customer-qrcode.jpg" your_username@tjiajiao91.com:/var/www/html/miniprogram/images/
echo.
echo =========================================
echo 上传完成后，请验证以下URL：
echo =========================================
echo https://tjiajiao91.com/miniprogram/images/ai-avatar.png
echo https://tjiajiao91.com/miniprogram/images/customer-qrcode.jpg
echo.
echo 验证成功后，可以删除本地的这两个文件。
echo.
pause
