-- 数据库配置模板
-- 请根据实际情况修改以下配置

-- 创建数据库
CREATE DATABASE IF NOT EXISTS `jiajiao_system` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `jiajiao_system`;

-- 创建管理员用户（可选）
-- 如果需要创建专用的数据库用户，请取消注释以下代码
/*
CREATE USER 'jiajiao_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON jiajiao_system.* TO 'jiajiao_user'@'localhost';
FLUSH PRIVILEGES;
*/

-- 数据库配置说明：
-- 1. 数据库名称：jiajiao_system
-- 2. 字符集：utf8mb4
-- 3. 排序规则：utf8mb4_unicode_ci
-- 4. 建议使用专用数据库用户，不要使用root用户

-- 配置完成后，请运行以下脚本导入数据：
-- 1. create_tables.sql - 创建表结构
-- 2. import_pca_complete_full.sql - 导入省市区数据
-- 3. preset_teachers_data.sql - 导入预设教师数据（可选）
-- 4. create_admin.sql - 创建管理员账号
