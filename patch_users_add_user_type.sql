-- 给 fa_users 增加 user_type 字段（家长/老师）
-- 说明：
-- - 先给默认值 parent，避免历史数据为空导致前端判断异常
-- - 如你后续要把微信端迁移过来，可再做一次数据回填/对齐

-- 建议直接执行下面这一行（不要拆行），避免部分工具对换行解析异常
ALTER TABLE fa_users ADD COLUMN user_type VARCHAR(20) NOT NULL DEFAULT 'parent' COMMENT 'user role: parent/teacher';

