# 微信小程序获取 UnionID 完整实现方案

## 一、问题分析

你目前无法获取 unionid 的原因可能是以下几种：

1. **小程序和公众号未绑定到同一开放平台** - 这是最常见的原因
2. **用户未关注公众号** - 即使绑定了开放平台，如果用户没关注公众号，微信也可能不返回 unionid
3. **开放平台账号未完成企业认证** - 个人账号无法使用开放平台

## 二、获取 UnionID 的三种方案

### 方案一：微信开放平台绑定（推荐 ⭐⭐⭐⭐⭐）

这是最稳定、最推荐的方式。

#### 实施步骤：

1. **注册/登录微信开放平台**
   - 访问：https://open.weixin.qq.com/
   - 需要企业认证（个人无法使用）
   - 认证费用：300元/年

2. **绑定小程序和公众号**
   - 在开放平台创建移动应用/网站应用
   - 将你的小程序绑定到开放平台
   - 将你的公众号绑定到开放平台
   - **重要：必须绑定到同一个开放平台账号**

3. **验证绑定**
   - 绑定成功后，用户在小程序登录时，`wx.login()` 返回的 code 换取 session 时会自动包含 unionid
   - 无需额外开发，微信会自动返回

4. **代码已实现**
   ```php
   // 在 WechatMiniProgramService.php 中
   private function getOpenidByCode($code)
   {
       // 调用微信接口
       $result = // ... 微信接口返回
       
       return [
           'openid' => $result['openid'],
           'session_key' => $result['session_key'],
           'unionid' => $result['unionid'] ?? '' // 绑定开放平台后会自动返回
       ];
   }
   ```

#### 优点：
- ✅ 最稳定可靠
- ✅ 无需用户额外操作
- ✅ 自动获取，无需开发额外逻辑
- ✅ 适用于所有用户

#### 缺点：
- ❌ 需要企业认证
- ❌ 需要支付认证费用

---

### 方案二：公众号扫码关注绑定（推荐 ⭐⭐⭐⭐）

如果暂时无法完成开放平台绑定，可以通过引导用户关注公众号来获取 unionid。

#### 实施步骤：

1. **生成带参二维码**
   ```javascript
   // 小程序端调用
   wx.request({
     url: 'https://你的域名/api/wechat/official/qrcode',
     method: 'POST',
     data: {
       mini_openid: '用户的小程序openid'
     },
     success: (res) => {
       // res.data.qrcode_url 是二维码图片地址
       // 展示给用户扫码关注
     }
   });
   ```

2. **用户扫码关注**
   - 在小程序中展示二维码
   - 引导用户长按识别关注公众号
   - 用户关注后，公众号会收到事件推送

3. **后端自动绑定**
   ```php
   // WechatOfficial.php 已实现
   // 用户关注时会自动：
   // 1. 获取公众号 openid
   // 2. 通过公众号接口获取用户信息（包含 unionid）
   // 3. 将 unionid 写入 wechat_openid_bindings 表
   // 4. 关联小程序 openid 和公众号 openid
   ```

4. **前端轮询检查绑定状态**
   ```javascript
   // 小程序端轮询
   const checkBinding = () => {
     wx.request({
       url: 'https://你的域名/api/wechat/official/bind-status',
       data: {
         mini_openid: '用户的小程序openid'
       },
       success: (res) => {
         if (res.data.is_bound && res.data.is_subscribed) {
           // 绑定成功，已获取 unionid
           console.log('UnionID:', res.data.unionid);
         } else {
           // 继续轮询
           setTimeout(checkBinding, 2000);
         }
       }
     });
   };
   ```

#### 优点：
- ✅ 无需开放平台认证
- ✅ 可以立即实施
- ✅ 代码已完整实现

#### 缺点：
- ❌ 需要用户手动关注公众号
- ❌ 用户体验略差
- ❌ 只对关注公众号的用户有效

---

### 方案三：公众号 OAuth 授权（推荐 ⭐⭐⭐）

通过公众号网页授权获取 unionid。

#### 实施步骤：

1. **获取授权链接**
   ```javascript
   // 小程序端
   wx.request({
     url: 'https://你的域名/api/wechat/official/bind-auth-url',
     data: {
       mini_openid: '用户的小程序openid'
     },
     success: (res) => {
       // res.data.auth_url 是授权链接
       // 复制链接让用户在浏览器中打开
     }
   });
   ```

2. **用户授权**
   - 用户在浏览器中打开授权链接
   - 微信会引导用户授权
   - 授权后自动跳转回调地址

3. **后端处理回调**
   ```php
   // WechatOfficial.php 已实现
   // 回调接口：/api/wechat/official/bind-callback
   // 会自动：
   // 1. 用 code 换取公众号 openid 和 unionid
   // 2. 绑定到小程序 openid
   // 3. 写入数据库
   ```

#### 优点：
- ✅ 无需开放平台认证
- ✅ 可以获取未关注用户的 unionid（snsapi_userinfo 作用域）
- ✅ 代码已实现

#### 缺点：
- ❌ 需要跳转到浏览器
- ❌ 用户体验较差
- ❌ 需要配置公众号网页授权域名

---

## 三、代码改进说明

我已经对你的代码进行了以下改进：

### 1. 增强日志记录

在 `getOpenidByCode()` 方法中添加了详细日志：

```php
\think\facade\Log::info('请求微信 jscode2session', [
    'appid' => $this->appId,
    'code' => substr($code, 0, 10) . '...'
]);

\think\facade\Log::info('微信 jscode2session 响应', [
    'has_openid' => isset($result['openid']),
    'has_session_key' => isset($result['session_key']),
    'has_unionid' => isset($result['unionid']),
    'errcode' => $result['errcode'] ?? 'none',
    'errmsg' => $result['errmsg'] ?? 'none'
]);
```

这样你可以在日志中看到微信是否返回了 unionid。

### 2. 增强 syncMiniUnionid 方法

改进了 unionid 同步逻辑：

```php
private function syncMiniUnionid($miniOpenid, $unionid, $source = 'mini_login')
{
    // 如果微信未返回 unionid，尝试从其他来源获取
    if ($miniOpenid !== '' && $unionid === '') {
        // 1. 从绑定表查询
        $bind = Db::name('wechat_openid_bindings')
            ->where('mini_openid', $miniOpenid)
            ->find();
        
        if ($bind && !empty($bind['unionid'])) {
            $unionid = $bind['unionid'];
        }
        
        // 2. 如果有公众号 openid，通过公众号接口获取
        if ($unionid === '' && !empty($bind['mp_openid'])) {
            $userInfo = $this->getOfficialAccountUserInfo($bind['mp_openid']);
            if (!empty($userInfo['unionid'])) {
                $unionid = $userInfo['unionid'];
            }
        }
    }
    
    // 保存到数据库...
}
```

### 3. 新增公众号用户信息获取方法

```php
private function getOfficialAccountUserInfo($mpOpenid)
{
    try {
        $result = \app\service\WechatNotificationService::getUserInfo($mpOpenid);
        
        if (!empty($result['success']) && !empty($result['data'])) {
            return $result['data'];
        }
        
        return [];
    } catch (\Throwable $e) {
        \think\facade\Log::warning('获取公众号用户信息失败: ' . $e->getMessage());
        return [];
    }
}
```

---

## 四、诊断工具

运行诊断脚本检查当前状态：

```bash
php check-unionid-status.php
```

这个脚本会检查：
1. 微信小程序配置
2. 微信公众号配置
3. 用户 unionid 获取率
4. 绑定表状态
5. 最近用户的 unionid 情况

---

## 五、推荐实施路径

### 短期方案（立即可用）：

1. **使用方案二（公众号扫码）**
   - 在小程序中添加"关注公众号"入口
   - 引导用户扫码关注
   - 代码已完整实现，可直接使用

### 长期方案（最佳实践）：

1. **申请微信开放平台认证**
   - 准备企业资质
   - 支付 300 元认证费
   - 完成企业认证

2. **绑定小程序和公众号**
   - 将两者绑定到同一开放平台
   - 绑定后自动获取 unionid

3. **保留公众号扫码功能**
   - 作为备用方案
   - 增加公众号粉丝

---

## 六、常见问题

### Q1: 为什么微信不返回 unionid？

**A:** 主要原因：
1. 小程序和公众号未绑定到同一开放平台
2. 开放平台账号未完成认证
3. 用户从未关注过同主体的公众号

### Q2: 如何验证是否绑定成功？

**A:** 
1. 登录微信开放平台 https://open.weixin.qq.com/
2. 查看"管理中心" -> "公众账号/小程序"
3. 确认小程序和公众号都在列表中

### Q3: 绑定开放平台后多久生效？

**A:** 通常立即生效，最多等待 5-10 分钟。

### Q4: 个人开发者可以使用吗？

**A:** 
- 开放平台绑定：❌ 需要企业认证
- 公众号扫码：✅ 可以使用（需要认证服务号）
- OAuth 授权：✅ 可以使用（需要认证服务号）

---

## 七、测试步骤

1. **运行诊断脚本**
   ```bash
   php check-unionid-status.php
   ```

2. **查看日志**
   ```bash
   tail -f tjiajiao91-main/new_system/backend/runtime/log/202604/02.log
   ```

3. **测试登录**
   - 在小程序中登录
   - 查看日志中是否有 "has_unionid: true"
   - 检查数据库 `fa_wechat_users` 表的 `unionid` 字段

4. **测试公众号绑定**
   - 调用 `/api/wechat/official/qrcode` 生成二维码
   - 扫码关注公众号
   - 调用 `/api/wechat/official/bind-status` 检查状态

---

## 八、数据库表说明

### fa_wechat_users
存储微信用户基本信息
- `openid`: 小程序或公众号 openid
- `unionid`: 用户的 unionid（关键字段）
- `subscribe`: 是否关注公众号

### fa_wechat_openid_bindings
存储小程序和公众号的绑定关系
- `mini_openid`: 小程序 openid
- `mp_openid`: 公众号 openid
- `unionid`: 用户的 unionid
- `is_subscribed`: 是否关注公众号
- `scene_key`: 绑定场景标识

---

## 九、联系支持

如果遇到问题：

1. 查看日志文件：`runtime/log/`
2. 运行诊断脚本：`php check-unionid-status.php`
3. 检查微信开放平台绑定状态
4. 确认公众号配置正确

---

**最后建议：优先完成微信开放平台绑定，这是最稳定和推荐的方案！**
