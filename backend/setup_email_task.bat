@echo off
chcp 65001 >nul
echo ========================================
echo 设置邮件队列处理计划任务
echo ========================================
echo.

REM 获取当前目录
set CURRENT_DIR=%~dp0
set PHP_PATH=php
set SCRIPT_PATH=%CURRENT_DIR%process_email_queue.php

echo 当前目录: %CURRENT_DIR%
echo PHP路径: %PHP_PATH%
echo 脚本路径: %SCRIPT_PATH%
echo.

echo 正在创建计划任务...
echo 任务名称: EmailQueueProcessor
echo 执行频率: 每分钟
echo.

REM 删除已存在的任务（如果有）
schtasks /Delete /TN "EmailQueueProcessor" /F >nul 2>&1

REM 创建新任务
schtasks /Create /TN "EmailQueueProcessor" /TR "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"" /SC MINUTE /MO 1 /F

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ 计划任务创建成功！
    echo.
    echo 任务将每分钟自动执行一次，处理邮件队列
    echo.
    echo 管理计划任务：
    echo - 查看任务: schtasks /Query /TN "EmailQueueProcessor"
    echo - 运行任务: schtasks /Run /TN "EmailQueueProcessor"
    echo - 停止任务: schtasks /End /TN "EmailQueueProcessor"
    echo - 删除任务: schtasks /Delete /TN "EmailQueueProcessor" /F
    echo.
) else (
    echo.
    echo ✗ 创建失败！
    echo 请以管理员身份运行此脚本
    echo.
)

echo ========================================
pause
