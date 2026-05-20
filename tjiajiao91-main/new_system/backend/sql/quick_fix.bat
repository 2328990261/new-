@echo off
chcp 65001 >nul
echo ========================================
echo 快速修复：创建教师验证码表
echo ========================================
echo.
echo 根据您的数据库配置：
echo   主机: 127.0.0.1
echo   端口: 3309
echo   数据库: myjiajiao
echo   用户: jiajiao
echo.
echo 正在创建 fa_teacher_verification_codes 表...
echo.

mysql -h127.0.0.1 -P3309 -ujiajiao -p123456 myjiajiao < quick_fix_verification_table.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo ✓ 表创建成功！
    echo ========================================
    echo.
    echo 现在可以刷新浏览器重试登录了！
) else (
    echo.
    echo ========================================
    echo ✗ 创建失败
    echo ========================================
    echo.
    echo 可能的原因：
    echo 1. MySQL未安装或未添加到PATH
    echo 2. 数据库连接信息不正确
    echo 3. 数据库用户权限不足
    echo.
    echo 解决方案：
    echo 1. 手动登录MySQL执行 quick_fix_verification_table.sql
    echo 2. 或者使用phpMyAdmin等工具导入SQL文件
)

echo.
pause
