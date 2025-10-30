-- ============================================
-- 城市点亮功能数据表
-- 创建时间: 2025-10-05
-- 功能说明: 用户可以点亮未开通的城市，达到1000人自动开通
-- ============================================

USE myjiajiao;

-- ============================================
-- 1. 城市点亮记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `fa_city_lights` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `province_id` int(11) NOT NULL COMMENT '省份ID',
  `city_name` varchar(50) NOT NULL COMMENT '城市名称',
  `city_code` varchar(20) DEFAULT NULL COMMENT '城市代码',
  `user_identifier` varchar(100) NOT NULL COMMENT '用户标识（IP或设备指纹）',
  `user_contact` varchar(100) DEFAULT NULL COMMENT '用户联系方式（可选）',
  `light_count` int(11) DEFAULT 1 COMMENT '点亮次数',
  `status` tinyint(1) DEFAULT 0 COMMENT '城市状态：0-未开通，1-已开通',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次点亮时间',
  `update_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_province_id` (`province_id`),
  KEY `idx_city_name` (`city_name`),
  KEY `idx_city_code` (`city_code`),
  KEY `idx_user_identifier` (`user_identifier`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `uk_city_user` (`province_id`, `city_name`, `user_identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='城市点亮记录表';

-- ============================================
-- 2. 城市点亮统计视图（用于快速查询）
-- ============================================
CREATE OR REPLACE VIEW `v_city_light_stats` AS
SELECT 
  cl.province_id,
  cl.city_name,
  cl.city_code,
  COUNT(DISTINCT cl.user_identifier) AS total_lights,
  cl.status,
  MIN(cl.create_time) AS first_light_time,
  MAX(cl.update_time) AS last_light_time,
  CASE 
    WHEN cl.status = 1 THEN '已开通'
    WHEN COUNT(DISTINCT cl.user_identifier) >= 1000 THEN '可开通'
    ELSE CONCAT(COUNT(DISTINCT cl.user_identifier), '/1000')
  END AS progress_text,
  ROUND(COUNT(DISTINCT cl.user_identifier) / 1000 * 100, 2) AS progress_percent
FROM fa_city_lights cl
GROUP BY cl.province_id, cl.city_name, cl.city_code, cl.status;

-- ============================================
-- 3. 插入测试数据（可选）
-- ============================================
-- INSERT INTO `fa_city_lights` (`province_id`, `city_name`, `city_code`, `user_identifier`, `user_contact`) VALUES
-- (3, '承德市', '130800', '192.168.1.1', 'user1@example.com'),
-- (3, '承德市', '130800', '192.168.1.2', 'user2@example.com'),
-- (3, '沧州市', '130900', '192.168.1.3', 'user3@example.com');

-- ============================================
-- 4. 创建触发器：自动开通城市（达到1000人）
-- ============================================
DELIMITER $$

DROP TRIGGER IF EXISTS `trigger_auto_open_city` $$

CREATE TRIGGER `trigger_auto_open_city`
AFTER INSERT ON `fa_city_lights`
FOR EACH ROW
BEGIN
  DECLARE light_count INT;
  DECLARE city_exists INT;
  
  -- 计算该城市的点亮人数
  SELECT COUNT(DISTINCT user_identifier) INTO light_count
  FROM fa_city_lights
  WHERE province_id = NEW.province_id 
    AND city_name = NEW.city_name
    AND status = 0;
  
  -- 如果达到1000人，自动开通
  IF light_count >= 1000 THEN
    -- 检查城市表中是否已存在该城市
    SELECT COUNT(*) INTO city_exists
    FROM fa_cities
    WHERE name = NEW.city_name AND province_id = NEW.province_id;
    
    -- 如果城市不存在，则自动创建
    IF city_exists = 0 THEN
      INSERT INTO fa_cities (province_id, code, name, level, sort, status, create_time)
      VALUES (
        NEW.province_id,
        NEW.city_code,
        NEW.city_name,
        '三线城市',
        999,
        1,
        NOW()
      );
    ELSE
      -- 如果城市已存在但被禁用，则启用
      UPDATE fa_cities 
      SET status = 1, update_time = NOW()
      WHERE name = NEW.city_name AND province_id = NEW.province_id AND status = 0;
    END IF;
    
    -- 更新点亮记录状态为已开通
    UPDATE fa_city_lights
    SET status = 1, update_time = NOW()
    WHERE province_id = NEW.province_id 
      AND city_name = NEW.city_name;
  END IF;
END $$

DELIMITER ;

-- ============================================
-- 5. 查询语句示例
-- ============================================

-- 查询所有城市点亮统计
-- SELECT * FROM v_city_light_stats ORDER BY total_lights DESC;

-- 查询可开通的城市（达到1000人）
-- SELECT * FROM v_city_light_stats WHERE status = 0 AND total_lights >= 1000;

-- 查询某个省份的点亮城市
-- SELECT * FROM v_city_light_stats WHERE province_id = 3 ORDER BY total_lights DESC;

-- 查询某个城市的点亮用户
-- SELECT user_identifier, user_contact, create_time 
-- FROM fa_city_lights 
-- WHERE city_name = '承德市' 
-- ORDER BY create_time DESC;

SELECT '✅ 城市点亮功能表创建成功！' AS message;
SELECT '📊 可以使用 v_city_light_stats 视图查看统计数据' AS tip1;
SELECT '🎯 达到1000人点亮时会自动开通城市' AS tip2;




