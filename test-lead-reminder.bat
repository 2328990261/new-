@echo off
chcp 65001 >nul
echo ========================================
echo 测试线索提醒邮件发送
echo ========================================
echo.

cd tjiajiao91-main\new_system\backend

echo 执行命令: php think lead:send-reminders
echo.

php think lead:send-reminders

echo.
echo ========================================
echo 测试完成
echo ========================================
pause
