# 数据库更新日志

## 2026-02-14 投递管理功能

### 新增表
- `fa_resume_application` - 简历投递记录表

### 表结构说明
```sql
-- 投递记录表
CREATE TABLE `fa_resume_application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL COMMENT '教师ID',
  `tutor_id` int(11) NOT NULL COMMENT '家教订单ID',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `apply_time` datetime NOT NULL COMMENT '投递时间',
  `review_time` datetime DEFAULT NULL COMMENT '审核时间',
  `admin_remark` text COMMENT '管理员备注',
  PRIMARY KEY (`id`),
  KEY `idx_teacher_id` (`teacher_id`),
  KEY `idx_tutor_id` (`tutor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_apply_time` (`apply_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 重要说明
1. 使用的家教订单表是 `fa_tutor_orders_new`（不是 `fa_tutor_orders`）
2. 使用的教师表是 `fa_teachers`（不是 `fa_teacher`）
3. 投递记录表名为 `fa_resume_application`（单数，不是复数）

### 安装步骤
1. 执行 `database/create_resume_application_table.sql` 创建投递表
2. 或者导入完整的数据库备份文件

### 相关功能
- 小程序端：投递简历、查看投递列表、查看投递详情
- 后台管理：查看投递列表、审核投递（通过/拒绝）、查看投递详情
