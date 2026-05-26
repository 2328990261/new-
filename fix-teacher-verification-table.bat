@echo off
chcp 65001 >nul
echo ========================================
echo 修复教师登录错误 - 创建验证码表
echo ========================================
echo.
echo 错误信息: Table 'fa_teacher_verification_codes' doesn't exist
echo 解决方案: 创建缺失的数据库表
echo.
echo 注意：表名使用 fa_ 前缀（数据库配置）
echo.

cd tjiajiao91-main\new_system\backend\sql

if not exist "quick_fix_verification_table.sql" (
    echo 错误: 找不到 SQL 文件
    pause
    exit /b 1
)

echo 正在执行修复...
echo.
call quick_fix.bat

cd ..\..\..\..

echo.
echo ========================================
echo 修复完成！请刷新浏览器重试登录
echo ========================================
pause
