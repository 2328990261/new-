# SSL证书管理功能 - 快速部署指南

## 部署概述

本指南将帮助您快速部署SSL证书管理功能到现有的家教信息管理系统中。

## 部署步骤

### 1. 数据库升级

#### 1.1 执行数据库升级脚本
```bash
# 在服务器上执行
mysql -u your_username -p your_database < new_system/database/upgrade_ssl_config.sql
```

#### 1.2 验证数据库表创建
```sql
-- 检查表是否创建成功
SHOW TABLES LIKE 'fa_ssl%';

-- 检查表结构
DESCRIBE fa_ssl_config;
DESCRIBE fa_ssl_renew_log;
```

### 2. 后端文件部署

#### 2.1 上传后端控制器
将以下文件上传到服务器：
```
new_system/backend/app/controller/admin/SslConfig.php
```

#### 2.2 更新路由配置
在 `new_system/backend/route/admin.php` 中添加SSL证书管理路由（已包含在文件中）。

#### 2.3 上传自动续约脚本
将以下文件上传到服务器：
```
new_system/backend/ssl_auto_renew.php
```

### 3. 前端文件部署

#### 3.1 上传前端组件
将以下文件上传到服务器：
```
new_system/frontend/admin/src/views/admin/SslConfig.vue
new_system/frontend/admin/src/api/ssl.js
```

#### 3.2 更新路由配置
在 `new_system/frontend/admin/src/router/index.js` 中添加SSL配置路由（已包含在文件中）。

#### 3.3 更新基础配置页面
在 `new_system/frontend/admin/src/views/admin/FieldManage.vue` 中添加SSL证书管理Tab（已包含在文件中）。

### 4. 构建前端项目

#### 4.1 本地构建
```bash
cd new_system/frontend/admin
npm install
npm run build
```

#### 4.2 上传构建文件
将构建后的文件上传到服务器对应目录。

### 5. 配置定时任务

#### 5.1 设置crontab
```bash
# 编辑crontab
crontab -e

# 添加以下行（每天凌晨2点执行自动续约）
0 2 * * * /usr/bin/php /path/to/ssl_auto_renew.php >> /var/log/ssl_renew.log 2>&1
```

#### 5.2 测试定时任务
```bash
# 手动执行一次测试
/usr/bin/php /path/to/ssl_auto_renew.php
```

### 6. 权限配置

#### 6.1 设置文件权限
```bash
# 设置SSL续约脚本执行权限
chmod +x /path/to/ssl_auto_renew.php

# 设置日志目录权限
chmod 755 /var/log/
touch /var/log/ssl_renew.log
chmod 644 /var/log/ssl_renew.log
```

#### 6.2 设置数据库权限
确保数据库用户有执行存储过程的权限：
```sql
GRANT EXECUTE ON PROCEDURE sp_ssl_expire_reminder TO 'your_username'@'localhost';
GRANT EXECUTE ON PROCEDURE sp_ssl_auto_renew TO 'your_username'@'localhost';
```

## 功能测试

### 1. 访问SSL证书管理页面

1. 登录管理端后台
2. 进入"基础配置" -> "SSL证书管理"
3. 确认页面正常加载

### 2. 添加域名配置

1. 点击"添加域名"按钮
2. 填写测试域名信息
3. 保存配置

### 3. 测试证书申请

1. 在域名列表中点击"申请证书"
2. 观察申请过程和结果
3. 检查证书状态更新

### 4. 测试自动续约

1. 确保域名的"自动续约"开关已开启
2. 手动执行续约脚本测试
3. 检查续约日志记录

## 配置说明

### 1. 默认配置

系统会自动创建以下默认配置：
- **域名示例**：www.yourdomain.com, admin.yourdomain.com, api.yourdomain.com
- **证书提供商**：Let's Encrypt
- **自动续约**：已启用
- **联系邮箱**：admin@yourdomain.com

### 2. 自定义配置

您可以根据需要修改以下配置：
- 域名列表
- 证书提供商
- 联系邮箱
- 自动续约设置

### 3. 系统配置

在数据库 `fa_system_config` 表中可以配置：
- `ssl_auto_renew_enabled`：是否启用自动续约
- `ssl_renew_days_before_expire`：过期前多少天续约
- `ssl_provider_default`：默认证书提供商
- `ssl_contact_email_default`：默认联系邮箱

## 监控和维护

### 1. 日志监控

#### 应用日志
```bash
# 查看SSL相关日志
tail -f /path/to/runtime/log/ssl.log
```

#### 系统日志
```bash
# 查看定时任务执行日志
tail -f /var/log/ssl_renew.log
```

#### 数据库日志
```sql
-- 查看续约日志
SELECT * FROM fa_ssl_renew_log ORDER BY execute_time DESC LIMIT 10;
```

### 2. 状态检查

#### 证书状态检查
```sql
-- 查看所有证书状态
SELECT * FROM fa_ssl_status_view;
```

#### 续约统计
```sql
-- 查看续约统计
SELECT * FROM fa_ssl_renew_stats_view;
```

### 3. 故障排除

#### 常见问题
1. **证书申请失败**
   - 检查域名解析
   - 确认HTTP访问可用
   - 查看错误日志

2. **自动续约失败**
   - 检查定时任务配置
   - 确认脚本执行权限
   - 查看续约日志

3. **页面访问异常**
   - 检查前端文件上传
   - 确认路由配置
   - 查看浏览器控制台

## 安全建议

### 1. 访问控制
- 限制SSL管理功能的访问权限
- 使用强密码保护管理账号
- 定期更新系统密码

### 2. 证书安全
- 定期备份证书文件
- 设置适当的文件权限
- 监控证书使用情况

### 3. 日志安全
- 定期清理过期日志
- 保护日志文件访问权限
- 监控异常操作

## 性能优化

### 1. 数据库优化
- 定期清理过期日志
- 优化查询索引
- 监控数据库性能

### 2. 脚本优化
- 设置合理的执行时间
- 避免重复操作
- 优化错误处理

### 3. 缓存策略
- 缓存证书状态信息
- 减少重复检查
- 优化API调用

## 扩展功能

### 1. 多域名支持
- 支持SAN证书
- 通配符证书管理
- 批量域名申请

### 2. 监控告警
- 过期提醒通知
- 异常状态告警
- 健康检查报告

### 3. 集成服务
- CDN服务集成
- 负载均衡配置
- 自动部署证书

## 总结

通过以上步骤，您可以成功部署SSL证书管理功能。该功能将为您提供：

- **自动化证书管理**：减少人工干预
- **多提供商支持**：灵活选择服务
- **实时状态监控**：及时发现问题
- **详细操作日志**：便于问题排查
- **批量操作支持**：提高管理效率

部署完成后，建议进行全面的功能测试，确保所有功能正常工作。如有问题，请参考故障排除部分或查看相关日志文件。
