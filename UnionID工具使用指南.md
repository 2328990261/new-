# UnionID 获取工具使用指南

## 📋 目录

1. [快速开始](#快速开始)
2. [工具说明](#工具说明)
3. [使用流程](#使用流程)
4. [常见问题](#常见问题)
5. [技术支持](#技术支持)

---

## 🚀 快速开始

### 第一步：检查配置

运行配置检查脚本：

```bash
php fix-unionid-config.php
```

这个脚本会：
- ✅ 检查小程序和公众号配置
- ✅ 检查数据库表结构
- ✅ 检查数据一致性
- ✅ 自动修复常见问题

### 第二步：诊断状态

运行诊断脚本：

```bash
php check-unionid-status.php
```

这个脚本会显示：
- 📊 用户 unionid 获取率统计
- 📊 绑定表状态统计
- 📊 最近用户的 unionid 情况
- 💡 解决方案建议

### 第三步：测试获取

运行测试脚本：

```bash
php test-unionid-fetch.php
```

需要：
1. 在小程序中调用 `wx.login()` 获取 code
2. 将 code 输入到脚本中
3. 查看微信是否返回 unionid

---

## 🛠 工具说明

### 1. fix-unionid-config.php

**配置检查和修复工具**

功能：
- 检查小程序 AppID 和 Secret 配置
- 检查公众号 AppID 和 Secret 配置
- 检查数据库表结构完整性
- 检查数据一致性
- 自动修复常见问题

使用场景：
- 首次配置时
- 遇到配置问题时
- 数据不一致时

### 2. check-unionid-status.php

**UnionID 状态诊断工具**

功能：
- 统计用户 unionid 获取情况
- 显示绑定表状态
- 列出最近用户的 unionid
- 提供解决方案建议

使用场景：
- 了解整体获取情况
- 排查问题
- 定期检查

### 3. test-unionid-fetch.php

**UnionID 获取测试工具**

功能：
- 直接调用微信接口测试
- 显示详细的请求和响应
- 分析未返回 unionid 的原因
- 可选保存到数据库

使用场景：
- 测试微信接口是否返回 unionid
- 验证开放平台绑定是否生效
- 调试登录问题

### 4. UnionID获取实现方案.md

**完整实现方案文档**

内容：
- 三种获取 unionid 的方案详解
- 代码改进说明
- 推荐实施路径
- 常见问题解答

### 5. 小程序端UnionID获取示例.js

**小程序端示例代码**

内容：
- 登录获取 unionid 的代码
- 公众号扫码绑定的代码
- OAuth 授权的代码
- 完整的页面示例

---

## 📝 使用流程

### 方案一：开放平台绑定（推荐 ⭐⭐⭐⭐⭐）

这是最稳定、最推荐的方式。

#### 步骤：

1. **申请微信开放平台**
   ```
   访问：https://open.weixin.qq.com/
   需要：企业资质、300元/年认证费
   ```

2. **绑定小程序和公众号**
   ```
   在开放平台中：
   - 添加小程序
   - 添加公众号
   - 确保在同一账号下
   ```

3. **验证绑定**
   ```bash
   # 运行测试脚本
   php test-unionid-fetch.php
   
   # 在小程序中获取 code 并输入
   # 查看是否返回 unionid
   ```

4. **部署代码**
   ```
   代码已更新，无需额外开发
   用户登录时会自动获取 unionid
   ```

#### 优点：
- ✅ 自动获取，无需用户操作
- ✅ 最稳定可靠
- ✅ 适用于所有用户

#### 缺点：
- ❌ 需要企业认证
- ❌ 需要支付费用

---

### 方案二：公众号扫码绑定（推荐 ⭐⭐⭐⭐）

如果暂时无法完成开放平台绑定，使用此方案。

#### 步骤：

1. **后端已实现**
   ```
   接口已完整实现：
   - POST /api/wechat/official/qrcode - 生成二维码
   - GET /api/wechat/official/bind-status - 查询绑定状态
   - POST /api/wechat/official/server - 接收公众号事件
   ```

2. **小程序端集成**
   ```javascript
   // 参考：小程序端UnionID获取示例.js
   
   // 1. 生成二维码
   const qrcodeUrl = await generateOfficialQRCode(miniOpenid);
   
   // 2. 展示二维码给用户
   // 3. 轮询检查绑定状态
   pollBindingStatus(miniOpenid, (status) => {
     console.log('绑定成功！', status.unionid);
   });
   ```

3. **配置公众号服务器**
   ```
   在微信公众平台配置：
   服务器地址：https://你的域名/api/wechat/official/server
   Token：在 notification_config 表中配置
   消息加密方式：明文模式或兼容模式
   ```

4. **测试绑定**
   ```
   1. 在小程序中生成二维码
   2. 扫码关注公众号
   3. 查看日志确认绑定成功
   ```

#### 优点：
- ✅ 无需开放平台认证
- ✅ 可以立即实施
- ✅ 代码已完整实现

#### 缺点：
- ❌ 需要用户手动关注
- ❌ 只对关注用户有效

---

### 方案三：OAuth 授权（推荐 ⭐⭐⭐）

通过公众号网页授权获取。

#### 步骤：

1. **配置授权域名**
   ```
   在微信公众平台：
   设置 -> 公众号设置 -> 功能设置
   网页授权域名：你的域名
   ```

2. **小程序端调用**
   ```javascript
   // 参考：小程序端UnionID获取示例.js
   
   // 获取授权链接
   const authUrl = await getOAuthUrl(miniOpenid);
   
   // 复制链接让用户在浏览器打开
   wx.setClipboardData({ data: authUrl });
   ```

3. **用户授权**
   ```
   用户在浏览器中打开链接
   微信引导授权
   授权后自动跳转回调
   ```

#### 优点：
- ✅ 无需开放平台认证
- ✅ 可获取未关注用户的 unionid

#### 缺点：
- ❌ 需要跳转浏览器
- ❌ 用户体验较差

---

## 🔍 常见问题

### Q1: 运行脚本提示找不到文件？

**A:** 确保在项目根目录运行脚本：

```bash
cd /path/to/your/project
php check-unionid-status.php
```

### Q2: 微信接口不返回 unionid？

**A:** 可能的原因：

1. **未绑定开放平台**
   - 解决：完成开放平台绑定
   - 验证：登录 https://open.weixin.qq.com/ 查看

2. **用户未关注公众号**
   - 解决：引导用户关注
   - 使用：公众号扫码绑定方案

3. **配置错误**
   - 解决：运行 `php fix-unionid-config.php`
   - 检查：AppID 和 Secret 是否正确

### Q3: 数据库中有些用户有 unionid，有些没有？

**A:** 这是正常的：

- 有 unionid：用户关注过公众号或开放平台已绑定
- 没有 unionid：用户未关注且开放平台未绑定

解决方案：
1. 完成开放平台绑定（推荐）
2. 引导用户关注公众号
3. 运行 `php fix-unionid-config.php` 补全数据

### Q4: 如何验证开放平台绑定是否成功？

**A:** 三种验证方式：

1. **登录开放平台查看**
   ```
   https://open.weixin.qq.com/
   管理中心 -> 公众账号/小程序
   确认小程序和公众号都在列表中
   ```

2. **运行测试脚本**
   ```bash
   php test-unionid-fetch.php
   # 输入 code 后查看是否返回 unionid
   ```

3. **查看日志**
   ```bash
   tail -f tjiajiao91-main/new_system/backend/runtime/log/$(date +%Y%m)/$(date +%d).log
   # 查找 "has_unionid: true"
   ```

### Q5: 公众号扫码后没有绑定成功？

**A:** 检查以下几点：

1. **公众号服务器配置**
   ```
   微信公众平台 -> 基本配置
   确认服务器地址配置正确
   确认 Token 验证通过
   ```

2. **查看日志**
   ```bash
   tail -f tjiajiao91-main/new_system/backend/runtime/log/$(date +%Y%m)/$(date +%d).log
   # 查找 "公众号事件回调接收"
   ```

3. **检查数据库**
   ```sql
   SELECT * FROM fa_wechat_openid_bindings 
   WHERE mini_openid = '你的小程序openid';
   ```

### Q6: 如何查看详细的调试日志？

**A:** 日志位置：

```bash
# 主日志
tjiajiao91-main/new_system/backend/runtime/log/YYYYMM/DD.log

# 查看实时日志
tail -f tjiajiao91-main/new_system/backend/runtime/log/$(date +%Y%m)/$(date +%d).log

# 搜索 unionid 相关日志
grep -r "unionid" tjiajiao91-main/new_system/backend/runtime/log/
```

关键日志标识：
- `微信 jscode2session 响应` - 登录接口响应
- `syncMiniUnionid` - unionid 同步
- `公众号事件回调接收` - 公众号事件
- `绑定成功` - 绑定完成

---

## 📊 监控和维护

### 定期检查

建议每周运行一次诊断：

```bash
php check-unionid-status.php
```

关注指标：
- UnionID 获取率（目标：>90%）
- 绑定记录数
- 公众号关注数

### 数据修复

如果发现数据不一致：

```bash
php fix-unionid-config.php
```

选择 "y" 执行自动修复。

### 日志清理

定期清理旧日志：

```bash
# 删除 30 天前的日志
find tjiajiao91-main/new_system/backend/runtime/log/ -name "*.log" -mtime +30 -delete
```

---

## 🎯 最佳实践

### 1. 优先使用开放平台绑定

这是最稳定的方案，强烈建议完成：
- 准备企业资质
- 支付 300 元认证费
- 完成绑定

### 2. 保留公众号扫码功能

即使完成开放平台绑定，也建议保留：
- 作为备用方案
- 增加公众号粉丝
- 提供更多服务入口

### 3. 监控获取率

定期检查 unionid 获取率：
- 目标：>90%
- 低于 80% 需要排查原因
- 引导用户关注公众号

### 4. 完善错误处理

在小程序端：
- 登录失败时给出友好提示
- 提供"关注公众号"入口
- 记录错误日志便于排查

### 5. 用户引导

在合适的位置引导用户：
- 个人中心显示"关注公众号"
- 首次登录时提示
- 提供关注的好处说明

---

## 📞 技术支持

### 遇到问题时的排查顺序：

1. **运行诊断脚本**
   ```bash
   php check-unionid-status.php
   ```

2. **运行配置检查**
   ```bash
   php fix-unionid-config.php
   ```

3. **运行测试脚本**
   ```bash
   php test-unionid-fetch.php
   ```

4. **查看日志**
   ```bash
   tail -f tjiajiao91-main/new_system/backend/runtime/log/$(date +%Y%m)/$(date +%d).log
   ```

5. **参考文档**
   - UnionID获取实现方案.md
   - 小程序端UnionID获取示例.js

### 需要帮助？

提供以下信息：
1. 诊断脚本的输出
2. 测试脚本的输出
3. 相关日志片段
4. 问题描述和复现步骤

---

## 📚 相关文档

- [UnionID获取实现方案.md](./UnionID获取实现方案.md) - 完整技术方案
- [小程序端UnionID获取示例.js](./小程序端UnionID获取示例.js) - 前端示例代码
- [微信开放平台文档](https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/2.0/product/UnionID.html)
- [微信小程序登录文档](https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/login.html)

---

## ✅ 检查清单

在开始实施前，确认以下事项：

- [ ] 已配置小程序 AppID 和 Secret
- [ ] 已配置公众号 AppID 和 Secret
- [ ] 数据库表结构完整
- [ ] 已运行配置检查脚本
- [ ] 已运行诊断脚本
- [ ] 已阅读实现方案文档
- [ ] 已测试登录接口
- [ ] 已配置公众号服务器（如使用扫码方案）
- [ ] 已在小程序中集成相关代码

---

**祝你顺利完成 UnionID 获取功能！** 🎉
