-- 检查管理员表结构
DESC fa_admin;

-- 查看当前登录的管理员信息
SELECT id, username, nickname, role, can_access_enterprise FROM fa_admin WHERE id = 1;
