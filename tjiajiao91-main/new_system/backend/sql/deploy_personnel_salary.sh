#!/bin/bash

# 人员薪酬管理功能部署脚本
# 使用方法: ./deploy_personnel_salary.sh

echo "========================================="
echo "人员薪酬管理功能部署脚本"
echo "========================================="
echo ""

# 数据库配置（请根据实际情况修改）
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="your_database_name"
DB_USER="your_username"
DB_PASS="your_password"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 检查MySQL是否可用
echo -e "${YELLOW}正在检查MySQL连接...${NC}"
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo -e "${RED}错误: 无法连接到MySQL数据库${NC}"
    echo "请检查数据库配置信息"
    exit 1
fi

echo -e "${GREEN}MySQL连接成功${NC}"
echo ""

# 执行SQL脚本
echo -e "${YELLOW}正在创建人员薪酬表...${NC}"
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < create_personnel_salary_table.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}数据库表创建成功${NC}"
else
    echo -e "${RED}数据库表创建失败${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}部署完成！${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "接下来的步骤："
echo "1. 重启后端服务（如果需要）"
echo "2. 重新编译前端代码（如果需要）"
echo "3. 访问管理后台 -> 企业管理 -> 薪酬管理"
echo ""
echo "详细使用说明请查看: README_PERSONNEL_SALARY.md"
