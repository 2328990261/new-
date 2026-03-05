# 🔧 修复城市点亮功能错误

## ❌ 错误信息
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'is_assist' in 'where clause'
```

## 📋 问题原因
数据库表 `fa_city_lights` 缺少 `is_assist` 和 `inviter_identifier` 两个字段，这两个字段用于支持助力点亮功能。

## ✅ 解决方案

### 方法一：通过phpMyAdmin或Navicat等工具执行SQL（推荐）

1. 打开您的数据库管理工具（phpMyAdmin / Navicat / HeidiSQL 等）
2. 选择数据库 `myjiajiao`
3. 执行以下SQL语句：

```sql
-- 添加助力相关字段
ALTER TABLE `fa_city_lights` 
ADD COLUMN `is_assist` tinyint(1) DEFAULT 0 COMMENT '是否为助力: 0-自己点亮, 1-助力点亮' AFTER `user_contact`,
ADD COLUMN `inviter_identifier` varchar(100) DEFAULT NULL COMMENT '邀请人标识（助力时记录）' AFTER `is_assist`;

-- 添加索引以提高查询效率
ALTER TABLE `fa_city_lights`
ADD KEY `idx_is_assist` (`is_assist`),
ADD KEY `idx_inviter` (`inviter_identifier`);

-- 更新已有数据
UPDATE `fa_city_lights` SET `is_assist` = 0 WHERE `is_assist` IS NULL;
```

### 方法二：通过PHP脚本执行

如果您可以运行PHP命令，请在项目根目录执行：

```bash
php new_system/backend/migrate_assist_fields.php
```

### 方法三：通过MySQL命令行执行

如果MySQL在您的PATH中：

```bash
mysql -u jE2se7DGe5HfE6zL -pmyjiajiao myjiajiao < new_system/database/migration_add_assist_fields.sql
```

## 🎯 验证修复

执行完SQL后，在数据库管理工具中运行以下查询验证：

```sql
SHOW COLUMNS FROM `fa_city_lights`;
```

应该能看到这两个新字段：
- `is_assist` - tinyint(1)
- `inviter_identifier` - varchar(100)

## 📝 字段说明

- **is_assist**: 标识这次点亮是否为助力
  - 0: 用户自己点亮（计入3个城市限制）
  - 1: 好友助力点亮（不计入限制）

- **inviter_identifier**: 邀请人的标识符
  - 用于记录是谁邀请的助力
  - 助力时才会填充这个字段

## 🔄 执行后即可正常使用

修复完成后，刷新页面，城市点亮功能就可以正常工作了！

