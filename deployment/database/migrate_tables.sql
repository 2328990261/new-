-- 家教信息管理系统 - 数据库迁移脚本
-- 数据库: myjiajiao
-- 创建时间: 2025-10-03
-- 适用场景: 已有数据库需要升级，保留现有数据

USE myjiajiao;

-- ============================================
-- 检查并添加缺失的字段
-- ============================================

-- 1. 检查并修改 fa_email_config 表
-- 添加 email_template 字段（如果不存在）
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'myjiajiao'
    AND TABLE_NAME = 'fa_email_config'
    AND COLUMN_NAME = 'email_template'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE `fa_email_config` ADD COLUMN `email_template` text COMMENT ''邮件模板'' AFTER `from_name`',
    'SELECT ''email_template 字段已存在'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 更新默认邮件模板（如果email_template为空）
UPDATE `fa_email_config`
SET `email_template` = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .order-info { background-color: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>新家教信息通知</h1>
        </div>
        <div class="content">
            <p>您好！</p>
            <p>根据您的订阅条件，有新的家教信息推送给您：</p>
            <div class="order-info">
                <p><strong>城市区域：</strong>{{city}} {{district}}</p>
                <p><strong>年级科目：</strong>{{grade}} {{subject}}</p>
                <p><strong>课时薪资：</strong>{{salary}}</p>
                <p><strong>详细信息：</strong></p>
                <p>{{content}}</p>
            </div>
            <p>如需查看更多信息，请访问我们的网站。</p>
        </div>
        <div class="footer">
            <p>本邮件由系统自动发送，请勿直接回复</p>
            <p>如需取消订阅，请访问网站进行设置</p>
        </div>
    </div>
</body>
</html>'
WHERE `id` = 1 AND (`email_template` IS NULL OR `email_template` = '');

-- 3. 检查其他可能需要迁移的表结构
-- （根据需要添加更多迁移逻辑）

-- ============================================
-- 完成
-- ============================================
SELECT '数据库迁移完成！现有数据已保留。' AS message;







