-- ============================================
-- 城市点亮规则和等级制度升级
-- 创建时间: 2025-10-05
-- 功能：限制点亮数量、助力机制、等级排行榜
-- ============================================

USE myjiajiao;

-- ============================================
-- 1. 修改城市点亮记录表，添加助力相关字段
-- ============================================

-- 检查并添加 inviter_identifier 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_city_lights' 
  AND COLUMN_NAME = 'inviter_identifier';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_city_lights` ADD COLUMN `inviter_identifier` VARCHAR(64) NULL COMMENT ''邀请人标识（助力来源）'' AFTER `user_identifier`',
  'SELECT ''Column inviter_identifier already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并添加 is_assist 字段
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_city_lights' 
  AND COLUMN_NAME = 'is_assist';

SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `fa_city_lights` ADD COLUMN `is_assist` TINYINT(1) DEFAULT 0 COMMENT ''是否为助力：1是 0否'' AFTER `inviter_identifier`',
  'SELECT ''Column is_assist already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 添加索引（如果不存在）
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_city_lights' 
  AND INDEX_NAME = 'idx_inviter';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_city_lights` ADD INDEX `idx_inviter` (`inviter_identifier`)',
  'SELECT ''Index idx_inviter already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'myjiajiao' 
  AND TABLE_NAME = 'fa_city_lights' 
  AND INDEX_NAME = 'idx_is_assist';

SET @sql = IF(@index_exists = 0, 
  'ALTER TABLE `fa_city_lights` ADD INDEX `idx_is_assist` (`is_assist`)',
  'SELECT ''Index idx_is_assist already exists'' AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. 创建用户点亮统计表
-- ============================================
CREATE TABLE IF NOT EXISTS `fa_city_light_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_identifier` VARCHAR(64) NOT NULL COMMENT '用户唯一标识',
  `user_contact` VARCHAR(100) DEFAULT NULL COMMENT '用户联系方式',
  `total_lights` INT(11) DEFAULT 0 COMMENT '总点亮城市数（包括自己点亮+助力）',
  `self_lights` INT(11) DEFAULT 0 COMMENT '自己点亮的城市数',
  `assist_lights` INT(11) DEFAULT 0 COMMENT '获得的助力数',
  `level` VARCHAR(20) DEFAULT '新手' COMMENT '用户等级：新手/青铜/皇冠/荣耀',
  `level_score` INT(11) DEFAULT 0 COMMENT '等级分数',
  `rank_position` INT(11) DEFAULT 0 COMMENT '排行榜位置',
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_identifier` (`user_identifier`),
  KEY `idx_level` (`level`),
  KEY `idx_level_score` (`level_score`),
  KEY `idx_rank_position` (`rank_position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='城市点亮用户统计表';

-- ============================================
-- 3. 创建用户点亮排行榜视图
-- ============================================
CREATE OR REPLACE VIEW `v_city_light_ranking` AS
SELECT 
  u.user_identifier,
  u.user_contact,
  u.total_lights,
  u.self_lights,
  u.assist_lights,
  u.level,
  u.level_score,
  @rank := @rank + 1 AS rank_position,
  u.create_time
FROM fa_city_light_users u,
     (SELECT @rank := 0) r
ORDER BY u.level_score DESC, u.total_lights DESC, u.create_time ASC;

-- ============================================
-- 4. 创建触发器：更新用户统计
-- ============================================

-- 删除旧触发器
DROP TRIGGER IF EXISTS `trigger_update_user_stats_after_light`;

DELIMITER $$

CREATE TRIGGER `trigger_update_user_stats_after_light`
AFTER INSERT ON `fa_city_lights`
FOR EACH ROW
BEGIN
  DECLARE user_self_count INT;
  DECLARE user_assist_count INT;
  DECLARE user_total INT;
  DECLARE user_level VARCHAR(20);
  DECLARE user_score INT;
  
  -- 计算该用户自己点亮的城市数（去重）
  SELECT COUNT(DISTINCT CONCAT(province_id, '-', city_name)) INTO user_self_count
  FROM fa_city_lights
  WHERE user_identifier = NEW.user_identifier AND is_assist = 0;
  
  -- 计算该用户获得的助力数（如果是邀请人）
  SELECT COUNT(*) INTO user_assist_count
  FROM fa_city_lights
  WHERE inviter_identifier = NEW.user_identifier AND is_assist = 1;
  
  -- 总点亮数
  SET user_total = user_self_count + user_assist_count;
  
  -- 计算等级和分数
  -- 自己点亮: 10分/城市，助力: 5分/次
  SET user_score = user_self_count * 10 + user_assist_count * 5;
  
  -- 根据分数确定等级
  IF user_score >= 100 THEN
    SET user_level = '荣耀';
  ELSEIF user_score >= 50 THEN
    SET user_level = '皇冠';
  ELSEIF user_score >= 20 THEN
    SET user_level = '青铜';
  ELSE
    SET user_level = '新手';
  END IF;
  
  -- 更新或插入用户统计
  INSERT INTO fa_city_light_users (
    user_identifier, 
    user_contact, 
    total_lights, 
    self_lights, 
    assist_lights, 
    level, 
    level_score
  ) VALUES (
    NEW.user_identifier,
    NEW.user_contact,
    user_total,
    user_self_count,
    user_assist_count,
    user_level,
    user_score
  ) ON DUPLICATE KEY UPDATE
    user_contact = VALUES(user_contact),
    total_lights = VALUES(total_lights),
    self_lights = VALUES(self_lights),
    assist_lights = VALUES(assist_lights),
    level = VALUES(level),
    level_score = VALUES(level_score),
    update_time = NOW();
  
  -- 如果是助力，同时更新邀请人的统计
  IF NEW.is_assist = 1 AND NEW.inviter_identifier IS NOT NULL THEN
    -- 重新计算邀请人的统计
    SELECT COUNT(DISTINCT CONCAT(province_id, '-', city_name)) INTO user_self_count
    FROM fa_city_lights
    WHERE user_identifier = NEW.inviter_identifier AND is_assist = 0;
    
    SELECT COUNT(*) INTO user_assist_count
    FROM fa_city_lights
    WHERE inviter_identifier = NEW.inviter_identifier AND is_assist = 1;
    
    SET user_total = user_self_count + user_assist_count;
    SET user_score = user_self_count * 10 + user_assist_count * 5;
    
    IF user_score >= 100 THEN
      SET user_level = '荣耀';
    ELSEIF user_score >= 50 THEN
      SET user_level = '皇冠';
    ELSEIF user_score >= 20 THEN
      SET user_level = '青铜';
    ELSE
      SET user_level = '新手';
    END IF;
    
    INSERT INTO fa_city_light_users (
      user_identifier, 
      total_lights, 
      self_lights, 
      assist_lights, 
      level, 
      level_score
    ) VALUES (
      NEW.inviter_identifier,
      user_total,
      user_self_count,
      user_assist_count,
      user_level,
      user_score
    ) ON DUPLICATE KEY UPDATE
      total_lights = VALUES(total_lights),
      self_lights = VALUES(self_lights),
      assist_lights = VALUES(assist_lights),
      level = VALUES(level),
      level_score = VALUES(level_score),
      update_time = NOW();
  END IF;
END$$

DELIMITER ;

-- ============================================
-- 5. 创建等级配置说明
-- ============================================
-- 新手: 0-19分 (点亮0-1城市)
-- 青铜: 20-49分 (点亮2-4城市或有助力)
-- 皇冠: 50-99分 (点亮5-9城市或有较多助力)
-- 荣耀: 100+分 (点亮10+城市或有大量助力)
-- 
-- 计分规则:
-- - 自己点亮1个城市 = 10分
-- - 获得1次助力 = 5分
-- 
-- 限制规则:
-- - 每个用户最多只能自己点亮3个不同的城市
-- - 但可以通过分享获得无限助力
-- - 助力也会计入点亮总数和等级分数

SELECT '✅ 城市点亮规则和等级制度升级完成！' AS message;
SELECT '📊 等级划分：新手(0-19分) / 青铜(20-49分) / 皇冠(50-99分) / 荣耀(100+分)' AS tip1;
SELECT '🎯 规则：自己点亮最多3城市(10分/城市)，助力无限(5分/次)' AS tip2;

