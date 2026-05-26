# 教师验证码表部署说明

## 问题描述

用户端教师登录界面报错：
```
SQL STATE[42S02]: Base table or view not found: 1146 Table 'myjiajiao.fa_teacher_verification_codes' doesn't exist
```

这是因为数据库中缺少 `fa_teacher_verification_codes` 表（注意：表名带 `fa_` 前缀）。

## 表的作用

`fa_teacher_verification_codes` 表用于存储教师相关的验证码信息，包括：
- 邮箱验证码（注册时）
- 登录记录
- 密码重置验证码

## 部署步骤

### Windows 系统

1. 打开命令提示符（CMD）
2. 进入 SQL 目录：
   ```cmd
   cd tjiajiao91-main/new_system/backend/sql
   ```
3. 运行部署脚本：
   ```cmd
   deploy_teacher_verification_codes.bat
   ```

### Linux/Mac 系统

1. 打开终端
2. 进入 SQL 目录：
   ```bash
   cd tjiajiao91-main/new_system/backend/sql
   ```
3. 给脚本添加执行权限：
   ```bash
   chmod +x deploy_teacher_verification_codes.sh
   ```
4. 运行部署脚本：
   ```bash
   ./deploy_teacher_verification_codes.sh
   ```

### 手动执行（如果脚本无法运行）

1. 登录 MySQL：
   ```bash
   mysql -u用户名 -p密码 数据库名
   ```

2. 执行 SQL 文件：
   ```sql
   source create_teacher_verification_codes.sql;
   ```

   或者直接复制 `create_teacher_verification_codes.sql` 文件中的 SQL 语句执行。

## 表结构说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int(11) | 主键ID |
| teacher_id | int(11) | 教师ID（登录时记录） |
| email | varchar(100) | 邮箱地址 |
| code | varchar(20) | 验证码 |
| type | varchar(20) | 验证码类型：email-邮箱验证，login-登录记录，reset-密码重置 |
| is_used | tinyint(1) | 是否已使用：0-未使用，1-已使用 |
| expire_time | int(11) | 过期时间（Unix 时间戳） |
| create_time | int(11) | 创建时间（Unix 时间戳） |
| update_time | int(11) | 更新时间（Unix 时间戳） |

## 验证部署

部署完成后，可以通过以下 SQL 验证表是否创建成功：

```sql
SHOW TABLES LIKE 'fa_teacher_verification_codes';
DESC fa_teacher_verification_codes;
```

## 相关文件

- `create_teacher_verification_codes.sql` - 创建表的 SQL（`deploy_teacher_verification_codes.*` 引用）
- `quick_fix_verification_table.sql` - 与 `quick_fix.bat` 配套的同一套建表脚本
- `deploy_teacher_verification_codes.bat` - Windows 部署脚本
- `deploy_teacher_verification_codes.sh` - Linux/Mac 部署脚本
- `quick_fix.bat` - 无需读 `.env` 的本地一键修复入口（请将其中连接参数改为与你的环境一致）
- `手动修复指南.md` - 图形界面 / 手写 SQL 的详细步骤

**仓库更新时间**：2026-05-20（与分支 `91家教最新版` 同步说明）

## 注意事项

1. 确保 `.env` 文件中的数据库配置正确
2. 确保数据库用户有创建表的权限
3. 如果表已存在，SQL 脚本会跳过创建（使用了 `IF NOT EXISTS`）
