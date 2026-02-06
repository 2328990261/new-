# 修复API路径重复问题

## 问题描述

线上部署后，API请求出现路径重复问题：

```
错误URL: https://t.jiajiao91.com/api/api/payment/create
正确URL: https://t.jiajiao91.com/api/payment/create
```

## 原因分析

在 `request.js` 中已经配置了 `baseURL: '/api'`，但在发送请求时又在路径前加了 `/api/`，导致最终URL变成了 `/api/api/...`。

### 错误示例

```javascript
// request.js 中配置
const request = axios.create({
  baseURL: '/api',  // 基础路径已经是 /api
  // ...
})

// Payment.vue 中使用（错误）
const response = await request.post('/api/payment/create', data)
// 最终URL: /api + /api/payment/create = /api/api/payment/create ❌
```

### 正确示例

```javascript
// Payment.vue 中使用（正确）
const response = await request.post('/payment/create', data)
// 最终URL: /api + /payment/create = /api/payment/create ✅
```

## 修复内容

### 1. Payment.vue

**修复前：**
```javascript
const response = await request.post('/api/payment/create', {
  // ...
})

const response = await request.get('/api/agreement/payment')
```

**修复后：**
```javascript
const response = await request.post('/payment/create', {
  // ...
})

const response = await request.get('/agreement/payment')
```

### 2. TeacherRegister.vue

**修复前：**
```javascript
const res = await request.get('/api/cities')
const res = await request.get('/api/subjects')
const res = await request.post('/api/teachers/register', form)
```

**修复后：**
```javascript
const res = await request.get('/cities')
const res = await request.get('/subjects')
const res = await request.post('/teachers/register', form)
```

## 规范说明

### 使用 request 实例发送请求时的路径规范

由于 `request.js` 中已经配置了 `baseURL: '/api'`，所以：

✅ **正确写法：**
```javascript
request.get('/tutor/list')           // → /api/tutor/list
request.post('/payment/create')      // → /api/payment/create
request.get('/agreement/payment')    // → /api/agreement/payment
```

❌ **错误写法：**
```javascript
request.get('/api/tutor/list')       // → /api/api/tutor/list
request.post('/api/payment/create')  // → /api/api/payment/create
request.get('/api/agreement/payment')// → /api/api/agreement/payment
```

### 特殊情况

如果需要请求非 `/api` 开头的路径，可以使用完整URL：

```javascript
// 请求其他域名
request.get('https://other-domain.com/data')

// 请求根路径
axios.get('/public/file.json')  // 不使用 request 实例
```

## 检查清单

在开发时，请确保：

- [ ] 使用 `request` 实例时，路径不要以 `/api/` 开头
- [ ] 路径应该以 `/` 开头（相对于 baseURL）
- [ ] 检查浏览器控制台，确认请求URL正确
- [ ] 测试所有API接口是否正常工作

## 测试方法

### 1. 本地测试

```bash
cd new_system/frontend/user
npm run dev
```

打开浏览器控制台（F12），查看 Network 标签：
- 检查请求URL是否正确
- 确认没有 `/api/api/` 的重复路径

### 2. 生产环境测试

```bash
npm run build
```

将编译后的文件部署到服务器，测试：
- 支付页面是否正常
- 协议加载是否正常
- 教师注册是否正常

## 相关文件

### 修改的文件

- `new_system/frontend/user/src/views/Payment.vue`
- `new_system/frontend/user/src/views/TeacherRegister.vue`

### 配置文件

- `new_system/frontend/user/src/utils/request.js` - axios 实例配置
- `new_system/frontend/user/.env.development` - 开发环境配置
- `new_system/frontend/user/.env.production` - 生产环境配置

## 部署说明

### 1. 重新编译

```bash
cd new_system/frontend/user
npm run build
```

### 2. 上传文件

将 `dist/` 目录下的文件上传到服务器的 `public/` 目录。

### 3. 清除浏览器缓存

由于修改了JavaScript文件，用户需要清除浏览器缓存或强制刷新（Ctrl+F5）。

### 4. 测试验证

访问以下页面测试：
- 支付页面：`https://yourdomain.com/payment`
- 教师注册：`https://yourdomain.com/teacher/register`

检查浏览器控制台，确认：
- ✅ 没有404错误
- ✅ API请求路径正确
- ✅ 功能正常工作

## 预防措施

### 1. 代码审查

在提交代码前，检查：
- 是否使用了 `request` 实例
- 路径是否以 `/api/` 开头（应该去掉）

### 2. 开发规范

团队开发时，统一规范：
```javascript
// 统一使用相对路径（相对于 baseURL）
request.get('/endpoint')      // ✅ 推荐
request.get('/api/endpoint')  // ❌ 避免
```

### 3. 工具辅助

可以在 `request.js` 中添加拦截器检查：

```javascript
request.interceptors.request.use(
  config => {
    // 检查路径是否重复
    if (config.url.startsWith('/api/')) {
      console.warn('⚠️ URL路径可能重复:', config.url)
      console.warn('提示: baseURL已经是/api，请去掉路径中的/api/')
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)
```

## 总结

通过修复API路径重复问题，确保了：

✅ 支付接口正常工作
✅ 协议加载正常
✅ 教师注册功能正常
✅ 所有API请求路径正确

记住：使用 `request` 实例时，路径不要以 `/api/` 开头！
