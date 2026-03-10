@echo off
chcp 65001
echo ========================================
echo 一键打包系统（前端+后端）
echo ========================================
echo.

:: 创建打包输出目录
set OUTPUT_DIR=打包输出_%date:~0,4%%date:~5,2%%date:~8,2%
set OUTPUT_DIR=%OUTPUT_DIR: =0%
if not exist "%OUTPUT_DIR%" mkdir "%OUTPUT_DIR%"

echo 输出目录: %OUTPUT_DIR%
echo.

:: ==========================================
:: 1. 打包管理后台前端
:: ==========================================
echo [1/4] 正在打包管理后台前端...
cd tjiajiao91-main\new_system\frontend\admin
call npm run build
if %errorlevel% neq 0 (
    echo 管理后台打包失败！
    cd ..\..\..\..\
    pause
    exit /b 1
)
echo 管理后台打包完成！

:: 压缩管理后台
echo 正在压缩管理后台...
cd ..\..\..\..\
powershell -command "Compress-Archive -Path 'tjiajiao91-main\new_system\frontend\admin\dist\*' -DestinationPath '%OUTPUT_DIR%\admin-frontend.zip' -Force"
echo 管理后台压缩完成！
echo.

:: ==========================================
:: 2. 打包用户端前端
:: ==========================================
echo [2/4] 正在打包用户端前端...
cd tjiajiao91-main\new_system\frontend\user
call npm run build
if %errorlevel% neq 0 (
    echo 用户端打包失败！
    cd ..\..\..\..\
    pause
    exit /b 1
)
echo 用户端打包完成！

:: 压缩用户端
echo 正在压缩用户端...
cd ..\..\..\..\
powershell -command "Compress-Archive -Path 'tjiajiao91-main\new_system\frontend\user\dist\*' -DestinationPath '%OUTPUT_DIR%\user-frontend.zip' -Force"
echo 用户端压缩完成！
echo.

:: ==========================================
:: 3. 打包后端
:: ==========================================
echo [3/4] 正在打包后端...
powershell -command "$files = Get-ChildItem -Path 'tjiajiao91-main\new_system\backend' -Recurse | Where-Object { $_.FullName -notmatch '(runtime\\log|runtime\\cache|\.git|node_modules)' }; Compress-Archive -Path $files.FullName -DestinationPath '%OUTPUT_DIR%\backend.zip' -Force"
echo 后端压缩完成！
echo.

:: ==========================================
:: 4. 复制SQL文件
:: ==========================================
echo [4/4] 正在复制SQL文件...
if not exist "%OUTPUT_DIR%\sql" mkdir "%OUTPUT_DIR%\sql"
copy add_invitation_code_field.sql "%OUTPUT_DIR%\sql\" >nul 2>nul
copy fix_invitation_tables.sql "%OUTPUT_DIR%\sql\" >nul 2>nul
copy database_update.sql "%OUTPUT_DIR%\sql\" >nul 2>nul
echo SQL文件复制完成！
echo.

:: ==========================================
:: 5. 创建部署说明
:: ==========================================
echo 正在创建部署说明...
(
echo # 部署说明
echo.
echo ## 文件说明
echo - admin-frontend.zip: 管理后台前端文件
echo - user-frontend.zip: 用户端前端文件
echo - backend.zip: 后端PHP文件
echo - sql/: 数据库更新脚本
echo.
echo ## 部署步骤
echo.
echo ### 1. 部署管理后台
echo 1. 解压 admin-frontend.zip
echo 2. 上传到服务器 /var/www/admin 目录
echo 3. 配置Nginx指向该目录
echo.
echo ### 2. 部署用户端
echo 1. 解压 user-frontend.zip
echo 2. 上传到服务器 /var/www/user 目录
echo 3. 配置Nginx指向该目录
echo.
echo ### 3. 部署后端
echo 1. 解压 backend.zip
echo 2. 上传到服务器 /var/www/backend 目录
echo 3. 编辑 .env 文件配置数据库
echo 4. 设置权限: chmod -R 777 runtime
echo 5. 配置Nginx指向 public 目录
echo.
echo ### 4. 更新数据库
echo ```bash
echo mysql -u用户名 -p数据库名 ^< sql/add_invitation_code_field.sql
echo mysql -u用户名 -p数据库名 ^< sql/fix_invitation_tables.sql
echo ```
echo.
echo ## 注意事项
echo - 部署前请备份数据库
echo - 确保PHP版本 ^>= 7.4
echo - 确保安装了必要的PHP扩展
echo - 配置好SSL证书（HTTPS）
) > "%OUTPUT_DIR%\部署说明.txt"

echo.
echo ========================================
echo 打包完成！
echo ========================================
echo.
echo 所有文件已打包到: %OUTPUT_DIR%
echo.
echo 包含以下文件:
dir /b "%OUTPUT_DIR%"
echo.
echo 请将 %OUTPUT_DIR% 目录中的文件上传到服务器
echo.

:: 打开输出目录
explorer "%OUTPUT_DIR%"

pause
