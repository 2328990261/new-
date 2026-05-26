#!/bin/bash
# 线索提醒邮件定时任务配置脚本
# 
# 使用方法：
# 1. 修改下面的 BACKEND_PATH 为你的实际后端路径
# 2. 运行此脚本：bash lead-reminder-cron-setup.sh
# 3. 或者手动添加到 crontab

# ========== 配置区域 ==========
# 后端项目路径（请修改为实际路径）
BACKEND_PATH="/www/wwwroot/tjiajiao91-main/new_system/backend"

# 日志文件路径
LOG_PATH="/var/log/lead-reminders.log"

# PHP 可执行文件路径（通常是 php 或 /usr/bin/php）
PHP_BIN="php"
# ========== 配置区域结束 ==========

echo "========================================="
echo "线索提醒邮件定时任务配置"
echo "========================================="
echo ""
echo "后端路径: $BACKEND_PATH"
echo "日志路径: $LOG_PATH"
echo "PHP路径: $PHP_BIN"
echo ""

# 检查后端路径是否存在
if [ ! -d "$BACKEND_PATH" ]; then
    echo "错误: 后端路径不存在: $BACKEND_PATH"
    echo "请修改脚本中的 BACKEND_PATH 变量"
    exit 1
fi

# 检查 think 文件是否存在
if [ ! -f "$BACKEND_PATH/think" ]; then
    echo "错误: think 文件不存在: $BACKEND_PATH/think"
    echo "请确认后端路径是否正确"
    exit 1
fi

# 测试命令是否可以执行
echo "测试命令..."
cd "$BACKEND_PATH" && $PHP_BIN think lead:send-reminders
if [ $? -ne 0 ]; then
    echo "错误: 命令执行失败"
    echo "请检查 PHP 路径和后端配置"
    exit 1
fi

echo ""
echo "命令测试成功！"
echo ""

# 生成 crontab 配置
CRON_COMMAND="*/5 * * * * cd $BACKEND_PATH && $PHP_BIN think lead:send-reminders >> $LOG_PATH 2>&1"

echo "========================================="
echo "请将以下内容添加到 crontab："
echo "========================================="
echo ""
echo "$CRON_COMMAND"
echo ""
echo "========================================="
echo "添加方法："
echo "1. 运行命令: crontab -e"
echo "2. 在文件末尾添加上面的内容"
echo "3. 保存并退出"
echo ""
echo "或者运行以下命令自动添加："
echo "(crontab -l 2>/dev/null; echo \"$CRON_COMMAND\") | crontab -"
echo "========================================="
echo ""

# 询问是否自动添加
read -p "是否自动添加到 crontab? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # 检查是否已存在
    if crontab -l 2>/dev/null | grep -q "lead:send-reminders"; then
        echo "警告: crontab 中已存在 lead:send-reminders 任务"
        read -p "是否覆盖? (y/n): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "已取消"
            exit 0
        fi
        # 删除旧的任务
        crontab -l 2>/dev/null | grep -v "lead:send-reminders" | crontab -
    fi
    
    # 添加新任务
    (crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -
    
    if [ $? -eq 0 ]; then
        echo "成功添加到 crontab！"
        echo ""
        echo "当前 crontab 列表："
        crontab -l
    else
        echo "添加失败，请手动添加"
    fi
else
    echo "已取消自动添加，请手动配置"
fi

echo ""
echo "========================================="
echo "配置完成！"
echo "========================================="
echo ""
echo "查看日志: tail -f $LOG_PATH"
echo "查看 crontab: crontab -l"
echo "编辑 crontab: crontab -e"
echo "删除 crontab: crontab -r"
echo ""
