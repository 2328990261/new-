-- ============================================================
-- 教师表新增 teacher_no 字段（对外展示编号 T1000、T1001...）
-- 根据当前已有记录数，从 1000 开始按 id 顺序填充
-- 执行前请确认数据库为 myjiajiao（或你的实际库名），表前缀为 fa_
-- ============================================================

-- 1. 新增字段（先允许 NULL，便于回填）
ALTER TABLE `fa_teachers`
ADD COLUMN `teacher_no` INT NULL COMMENT '对外展示编号，从1000起，如 T1000' AFTER `id`;

-- 2. 按 id 顺序从 1000 开始回填（有几条就从 1000 往后填）
SET @rn := 999;
UPDATE `fa_teachers` SET `teacher_no` = (@rn := @rn + 1) ORDER BY `id`;

-- 3. 改为 NOT NULL 并加唯一索引
ALTER TABLE `fa_teachers`
MODIFY COLUMN `teacher_no` INT NOT NULL COMMENT '对外展示编号，从1000起，如 T1000',
ADD UNIQUE KEY `uk_teacher_no` (`teacher_no`);

-- 执行完成后，现有教师 id 与 teacher_no 对应示例：
-- id=11 -> teacher_no=1000, id=12 -> 1001, id=13 -> 1002, ...
-- 后续新教师需在业务代码中插入时设置 teacher_no = (SELECT COALESCE(MAX(teacher_no),999)+1 FROM fa_teachers)
