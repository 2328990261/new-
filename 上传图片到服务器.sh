#!/bin/bash

# 小程序图片上传到服务器脚本
# 使用方法: bash 上传图片到服务器.sh

echo "========================================="
echo "小程序图片上传到服务器"
echo "========================================="
echo ""

# 配置
SERVER_USER="your_username"  # 修改为你的服务器用户名
SERVER_HOST="tjiajiao91.com"
SERVER_PATH="/var/www/html/miniprogram/images"  # 修改为你的实际路径
LOCAL_PATH="tjiajiao91-main/微信小程序/预约家教小程序/static"

# 检查本地文件是否存在
if [ ! -f "$LOCAL_PATH/ai-avatar.png" ]; then
    echo "错误: 找不到 ai-avatar.png"
    exit 1
fi

if [ ! -f "$LOCAL_PATH/customer-qrcode.jpg" ]; then
    echo "错误: 找不到 customer-qrcode.jpg"
    exit 1
fi

echo "本地文件检查通过"
echo ""

# 提示用户输入服务器信息
read -p "请输入服务器用户名 (默认: $SERVER_USER): " input_user
if [ ! -z "$input_user" ]; then
    SERVER_USER=$input_user
fi

read -p "请输入服务器路径 (默认: $SERVER_PATH): " input_path
if [ ! -z "$input_path" ]; then
    SERVER_PATH=$input_path
fi

echo ""
echo "准备上传到: $SERVER_USER@$SERVER_HOST:$SERVER_PATH"
echo ""

# 创建服务器目录
echo "1. 在服务器上创建目录..."
ssh $SERVER_USER@$SERVER_HOST "mkdir -p $SERVER_PATH"

if [ $? -ne 0 ]; then
    echo "错误: 无法连接到服务器或创建目录"
    exit 1
fi

echo "目录创建成功"
echo ""

# 上传文件
echo "2. 上传 ai-avatar.png (746KB)..."
scp "$LOCAL_PATH/ai-avatar.png" $SERVER_USER@$SERVER_HOST:$SERVER_PATH/

if [ $? -ne 0 ]; then
    echo "错误: ai-avatar.png 上传失败"
    exit 1
fi

echo "ai-avatar.png 上传成功"
echo ""

echo "3. 上传 customer-qrcode.jpg (144KB)..."
scp "$LOCAL_PATH/customer-qrcode.jpg" $SERVER_USER@$SERVER_HOST:$SERVER_PATH/

if [ $? -ne 0 ]; then
    echo "错误: customer-qrcode.jpg 上传失败"
    exit 1
fi

echo "customer-qrcode.jpg 上传成功"
echo ""

# 设置文件权限
echo "4. 设置文件权限..."
ssh $SERVER_USER@$SERVER_HOST "chmod 644 $SERVER_PATH/*.png $SERVER_PATH/*.jpg"

echo ""
echo "========================================="
echo "上传完成！"
echo "========================================="
echo ""
echo "请在浏览器中验证以下URL是否可以访问："
echo "https://tjiajiao91.com/miniprogram/images/ai-avatar.png"
echo "https://tjiajiao91.com/miniprogram/images/customer-qrcode.jpg"
echo ""
echo "验证成功后，可以删除本地的这两个文件以减少小程序包体积。"
