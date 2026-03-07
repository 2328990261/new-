# 微信 IP 白名单配置说明

## 问题描述

线上环境调用微信 API 时报错：
```json
{
  "code": 500,
  "message": "invalid ip 123.207.52.46 ipv6 ::ffff:123.207.52.46, not in whitelist rid: 69a72ba3-53886e83-2c8a08a9"
}
```

这个错误通常出现在以下场景：
1. 管理后台登录时（如果使用了微信授权）
2. 发送微信模板消息时
3. 获取微信用户信息时
4. 任何调用微信公众号 API 的操作

## 原因

微信公众平台为了安全，要求调用 API 的服务器 IP 必须在白名单中。当前服务器 IP `123.207.52.46` 未添加到白名单。

## 解决步骤

### 1. 登录微信公众平台
访问：https://mp.weixin.qq.com
使用管理员账号登录

### 2. 进入基本配置
- 点击左侧菜单"开发" -> "基本配置"
- 或直接访问：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=YOUR_TOKEN&lang=zh_CN

### 3. 配置 IP 白名单
- 找到"IP白名单"部分
- 点击"修改"按钮
- 添加服务器 IP：`123.207.52.46`
- 保存配置

### 4. 验证配置
配置完成后，等待 1-2 分钟生效，然后：
- 访问管理后台的通知配置页面
- 点击"测试微信通知"按钮
- 如果能成功获取 AccessToken，说明配置成功

## 注意事项

1. **最多可添加 10 个 IP**
   - 如果 IP 白名单已满，需要删除不用的 IP

2. **支持 IPv4 和 IPv6**
   - 当前服务器使用 IPv4：`123.207.52.46`
   - 微信会自动识别 IPv6 格式：`::ffff:123.207.52.46`

3. **生效时间**
   - 配置后通常 1-2 分钟生效
   - 如果仍然报错，请等待 5 分钟后重试

4. **多服务器部署**
   - 如果有多台服务器，需要添加所有服务器的 IP
   - 如果使用负载均衡，添加负载均衡的出口 IP

5. **动态 IP 问题**
   - 如果服务器 IP 会变化，建议使用固定 IP 或配置代理

## 相关 API 调用

以下功能需要 IP 白名单：

### 微信公众号 API（需要 IP 白名单）
- 获取 AccessToken：`/cgi-bin/token`
- 发送模板消息：`/cgi-bin/message/template/send`
- 获取用户信息：`/cgi-bin/user/info`
- 获取模板列表：`/cgi-bin/template/get_all_private_template`

### 微信网页授权 API（需要 IP 白名单）
- 获取网页授权 access_token：`/sns/oauth2/access_token`
- 获取用户信息：`/sns/userinfo`

### 不需要 IP 白名单的接口
- 引导用户授权：`/connect/oauth2/authorize`（前端跳转）

## 错误代码参考

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| 40164 | invalid ip, not in whitelist | 添加 IP 到白名单 |
| 40001 | invalid credential, access_token is invalid or not latest | 刷新 AccessToken |
| 42001 | access_token expired | AccessToken 过期，重新获取 |

## 测试方法

### 方法 1：管理后台测试
1. 登录管理后台：https://t.jiajiao91.com/admin
2. 进入"通知配置"页面
3. 点击"测试微信通知"

### 方法 2：API 测试
```bash
# 测试获取 AccessToken
curl "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=YOUR_APPID&secret=YOUR_SECRET"
```

如果返回 `access_token`，说明配置成功。
如果返回错误码 40164，说明 IP 未在白名单中。

## 联系方式

如有问题，请联系：
- 微信公众平台客服
- 技术支持团队
