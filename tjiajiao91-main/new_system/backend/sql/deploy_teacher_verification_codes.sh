#!/bin/bash

echo "========================================"
echo "创建教师验证码表"
echo "========================================"
echo ""

# 从 .env 文件读取数据库配置
ENV_FILE="../.env"
if [ ! -f "$ENV_FILE" ]; then
    echo "错误: 找不到 .env 文件"
    exit 1
fi

# 读取数据库配置
DB_HOST=$(grep "^DATABASE_HOSTNAME=" "$ENV_FILE" | cut -d '=' -f2 | tr -d '"' | tr -d ' ')
DB_PORT=$(grep "^DATABASE_HOSTPORT=" "$ENV_FILE" | cut -d '=' -f2 | tr -d '"' | tr -d ' ')
DB_NAME=$(grep "^DATABASE_DATABASE=" "$ENV_FILE" | cut -d '=' -f2 | tr -d '"' | tr -d ' ')
DB_USER=$(grep "^DATABASE_USERNAME=" "$ENV_FILE" | cut -d '=' -f2 | tr -d '"' | tr -d ' ')
DB_PASS=$(grep "^DATABASE_PASSWORD=" "$ENV_FILE" | cut -d '=' -f2 | tr -d '"' | tr -d ' ')

echo "数据库配置:"
echo "  主机: $DB_HOST"
echo "  端口: $DB_PORT"
echo "  数据库: $DB_NAME"
echo "  用户: $DB_USER"
echo ""

# 执行 SQL 文件
echo "正在创建教师验证码表..."
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < create_teacher_verification_codes.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "========================================"
    echo "教师验证码表创建成功！"
    echo "========================================"
else
    echo ""
    echo "========================================"
    echo "创建失败，请检查错误信息"
    echo "========================================"
    exit 1
fi

echo ""
