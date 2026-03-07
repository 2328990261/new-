# 免费SSL证书申请指南

## 🆓 免费SSL证书方案

本系统支持多种免费SSL证书申请方案，主要推荐使用Let's Encrypt免费证书。

## 一、Let's Encrypt 免费证书

### 1.1 优势特点
- ✅ **完全免费**：无需任何费用
- ✅ **自动续约**：90天有效期，支持自动续约
- ✅ **广泛信任**：被所有主流浏览器信任
- ✅ **申请简单**：验证过程简单快速
- ✅ **通配符支持**：支持*.yourdomain.com格式

### 1.2 使用限制
- 每周最多申请20个证书
- 每个证书最多包含100个域名
- 90天有效期（但可自动续约）
- 需要域名解析到服务器IP

## 二、宝塔面板申请免费证书

### 2.1 通过宝塔面板申请（推荐）

#### 步骤1：进入SSL设置
1. 登录宝塔面板
2. 点击"网站" -> 选择域名 -> "设置"
3. 点击"SSL"选项卡

#### 步骤2：申请Let's Encrypt证书
1. 选择"Let's Encrypt"
2. 填写邮箱地址
3. 勾选域名
4. 点击"申请"

#### 步骤3：开启HTTPS
1. 申请成功后，开启"强制HTTPS"
2. 系统会自动配置Nginx

### 2.2 宝塔面板自动续约
宝塔面板会自动处理Let's Encrypt证书的续约，无需手动操作。

## 三、命令行申请免费证书

### 3.1 安装acme.sh（推荐）

#### 安装acme.sh
```bash
# 下载并安装acme.sh
curl https://get.acme.sh | sh -s email=your-email@example.com

# 重新加载环境变量
source ~/.bashrc
```

#### 申请证书
```bash
# 使用webroot方式申请（推荐）
acme.sh --issue -d www.yourdomain.com --webroot /www/wwwroot/www.yourdomain.com

# 申请通配符证书
acme.sh --issue -d "*.yourdomain.com" -d yourdomain.com --dns dns_cf

# 安装证书到Nginx
acme.sh --install-cert -d www.yourdomain.com \
  --key-file /etc/nginx/ssl/www.yourdomain.com.key \
  --fullchain-file /etc/nginx/ssl/www.yourdomain.com.crt \
  --reloadcmd "systemctl reload nginx"
```

### 3.2 使用certbot

#### 安装certbot
```bash
# CentOS/RHEL
yum install certbot python3-certbot-nginx

# Ubuntu/Debian
apt install certbot python3-certbot-nginx
```

#### 申请证书
```bash
# 使用webroot方式
certbot certonly --webroot -w /www/wwwroot/www.yourdomain.com -d www.yourdomain.com

# 使用Nginx插件（自动配置）
certbot --nginx -d www.yourdomain.com
```

## 四、系统集成免费证书申请

### 4.1 配置系统使用Let's Encrypt

#### 在管理端配置
1. 进入"基础配置" -> "SSL证书管理"
2. 添加域名配置：
   - **域名**：www.yourdomain.com
   - **证书提供商**：Let's Encrypt
   - **联系邮箱**：your-email@example.com
   - **自动续约**：开启

#### 申请证书
1. 点击"申请证书"按钮
2. 系统会自动：
   - 检查域名解析
   - 验证HTTP访问
   - 调用acme.sh或certbot申请证书
   - 更新证书状态

### 4.2 自动续约配置

#### 设置定时任务
```bash
# 编辑crontab
crontab -e

# 添加自动续约任务（每天凌晨2点执行）
0 2 * * * /usr/bin/php /path/to/ssl_auto_renew.php >> /var/log/ssl_renew.log 2>&1
```

#### 系统自动续约
系统会每天检查即将过期的证书（30天内），并自动续约。

## 五、免费证书申请流程

### 5.1 准备工作

#### 域名解析
确保域名已正确解析到服务器IP：
```bash
# 检查域名解析
nslookup www.yourdomain.com
dig www.yourdomain.com
```

#### 端口检查
确保80端口可访问：
```bash
# 检查80端口
telnet yourdomain.com 80
curl -I http://www.yourdomain.com
```

### 5.2 申请步骤

#### 方法1：宝塔面板（最简单）
1. 宝塔面板 -> 网站 -> SSL -> Let's Encrypt
2. 填写邮箱，勾选域名
3. 点击申请，等待完成

#### 方法2：系统管理界面
1. 管理端 -> 基础配置 -> SSL证书管理
2. 添加域名配置
3. 点击"申请证书"

#### 方法3：命令行
```bash
# 使用acme.sh
acme.sh --issue -d www.yourdomain.com --webroot /www/wwwroot/www.yourdomain.com

# 使用certbot
certbot certonly --webroot -w /www/wwwroot/www.yourdomain.com -d www.yourdomain.com
```

## 六、证书验证和安装

### 6.1 验证证书申请

#### 检查证书文件
```bash
# 查看证书信息
openssl x509 -in /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem -text -noout

# 检查证书有效期
openssl x509 -in /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem -dates -noout
```

#### 测试HTTPS访问
```bash
# 测试HTTPS连接
curl -I https://www.yourdomain.com

# 检查SSL评级
# 访问 https://www.ssllabs.com/ssltest/
```

### 6.2 配置Nginx

#### 宝塔面板自动配置
宝塔面板申请证书后会自动配置Nginx，无需手动操作。

#### 手动配置Nginx
```nginx
server {
    listen 443 ssl http2;
    server_name www.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/www.yourdomain.com/privkey.pem;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE:ECDH:AES:HIGH:!NULL:!aNULL:!MD5:!ADH:!RC4;
    ssl_prefer_server_ciphers on;
    
    # 其他配置...
}

# HTTP重定向到HTTPS
server {
    listen 80;
    server_name www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

## 七、常见问题解决

### 7.1 申请失败问题

#### 域名解析问题
```bash
# 检查域名解析
nslookup www.yourdomain.com
# 确保返回正确的服务器IP
```

#### 80端口访问问题
```bash
# 检查防火墙
firewall-cmd --list-ports
# 开放80端口
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --reload
```

#### 验证文件访问问题
```bash
# 检查网站根目录权限
ls -la /www/wwwroot/www.yourdomain.com/
# 确保web服务器可以读取文件
```

### 7.2 续约失败问题

#### 检查定时任务
```bash
# 查看crontab
crontab -l
# 检查定时任务日志
tail -f /var/log/cron
```

#### 检查证书状态
```bash
# 检查证书有效期
openssl x509 -in /etc/letsencrypt/live/www.yourdomain.com/fullchain.pem -dates -noout
```

### 7.3 证书安装问题

#### 检查证书文件
```bash
# 检查证书文件是否存在
ls -la /etc/letsencrypt/live/www.yourdomain.com/
```

#### 检查Nginx配置
```bash
# 测试Nginx配置
nginx -t
# 重新加载Nginx
systemctl reload nginx
```

## 八、最佳实践

### 8.1 安全建议

#### 证书管理
- 定期检查证书有效期
- 设置自动续约
- 备份证书文件
- 监控证书状态

#### 服务器安全
- 使用强密码
- 定期更新系统
- 配置防火墙
- 监控访问日志

### 8.2 性能优化

#### SSL配置优化
```nginx
# 启用HTTP/2
listen 443 ssl http2;

# 优化SSL配置
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;

# 启用OCSP Stapling
ssl_stapling on;
ssl_stapling_verify on;
```

#### 证书缓存
- 使用CDN缓存静态资源
- 配置浏览器缓存
- 启用Gzip压缩

## 九、免费证书对比

| 特性 | Let's Encrypt | 阿里云免费 | 腾讯云免费 |
|------|---------------|------------|------------|
| 费用 | 完全免费 | 免费 | 免费 |
| 有效期 | 90天 | 1年 | 1年 |
| 自动续约 | 支持 | 支持 | 支持 |
| 通配符 | 支持 | 不支持 | 不支持 |
| 申请限制 | 每周20个 | 无限制 | 无限制 |
| 申请难度 | 简单 | 中等 | 中等 |

## 十、总结

### 推荐方案
1. **宝塔面板用户**：直接使用宝塔面板的Let's Encrypt功能
2. **命令行用户**：使用acme.sh申请和管理证书
3. **系统集成**：使用本系统的SSL证书管理功能

### 关键要点
- Let's Encrypt是最简单、最可靠的免费证书方案
- 宝塔面板提供了最便捷的申请和管理方式
- 系统集成的SSL管理功能提供了统一的管理界面
- 自动续约功能确保证书不会过期

通过以上方案，您可以完全免费获得可靠的SSL证书，为网站提供HTTPS安全保障。
