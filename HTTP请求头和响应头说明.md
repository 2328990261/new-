# HTTP 请求头和响应头完整说明

## 📖 基础概念

### 1️⃣ 请求头（Request Headers）
- **方向**：前端 → 后端
- **作用**：告诉后端关于请求的信息
- **配置位置**：前端代码

### 2️⃣ 响应头（Response Headers）
- **方向**：后端 → 前端
- **作用**：告诉前端关于响应的信息
- **配置位置**：后端代码

---

## 🎯 你的项目配置详解

### 一、前端请求头配置（发送给后端）

#### 📁 位置1：Admin管理端
**文件**：`new_system/frontend/admin/src/utils/request.js`

```javascript
// 静态请求头（创建axios实例时配置）
headers: {
  'Content-Type': 'application/json',  // 告诉后端：我发送的是JSON格式
  'Cache-Control': 'no-cache',         // 告诉后端：不要缓存
  'Pragma': 'no-cache'                 // 告诉后端：不要缓存（兼容旧浏览器）
}

// 动态请求头（在请求拦截器中添加）
// 管理端使用session认证，不需要额外添加token
```

#### 📁 位置2：User用户端
**文件**：`new_system/frontend/user/src/utils/request.js`

```javascript
// 静态请求头
headers: {
  'Content-Type': 'application/json'
}

// 动态请求头（在请求拦截器中添加）
request.interceptors.request.use(config => {
  const token = localStorage.getItem('teacher_token')
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`  // 添加认证token
  }
  return config
})
```

---

### 二、后端响应头配置（发送给前端）

#### 📁 位置：CORS中间件
**文件**：`new_system/backend/app/middleware/Cors.php`

```php
// 这些响应头会自动添加到每个API响应中
header('Access-Control-Allow-Origin: ' . $origin);           // 允许的前端域名
header('Access-Control-Allow-Credentials: true');            // 允许携带Cookie
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');  // 允许的HTTP方法
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');  // 允许的请求头
header('Access-Control-Max-Age: 86400');                     // 预检请求缓存时间（24小时）
```

#### 📁 位置：控制器中的响应
**文件**：`new_system/backend/app/controller/api/Common.php`（示例）

```php
// 后端返回JSON响应时，ThinkPHP会自动添加响应头：
// Content-Type: application/json

return json([
    'success' => true,
    'data' => $cities
]);
```

---

## 🔄 完整的请求-响应流程

### 场景：用户端获取城市列表

```
┌─────────────┐                                    ┌─────────────┐
│   前端Vue   │                                    │  后端PHP    │
└─────────────┘                                    └─────────────┘
      │                                                    │
      │ 1. 发送请求                                        │
      │ ─────────────────────────────────────────────────>│
      │   请求头（Request Headers）：                      │
      │   - Content-Type: application/json                │
      │   - Authorization: Bearer xxx (如果有token)       │
      │                                                    │
      │                                    2. 处理请求     │
      │                                    3. 查询数据库   │
      │                                                    │
      │                                    4. 返回响应     │
      │<─────────────────────────────────────────────────│
      │   响应头（Response Headers）：                     │
      │   - Content-Type: application/json                │
      │   - Access-Control-Allow-Origin: http://localhost:3000 │
      │   - Access-Control-Allow-Credentials: true        │
      │                                                    │
      │   响应体（Response Body）：                        │
      │   {                                                │
      │     "success": true,                               │
      │     "data": [...]                                  │
      │   }                                                │
      │                                                    │
      │ 5. 接收响应                                        │
      └────────────────────────────────────────────────────┘
```

---

## 💻 如何在前端接收响应头

### 方法1：在响应拦截器中访问（推荐）

**文件**：`request.js`

```javascript
// 响应拦截器
request.interceptors.response.use(
  response => {
    // 访问响应头
    const contentType = response.headers['content-type']
    const customHeader = response.headers['x-custom-header']
    
    console.log('响应头:', response.headers)
    
    // 访问响应数据
    const res = response.data
    return res
  },
  error => {
    // 错误时也可以访问响应头
    if (error.response) {
      console.log('错误响应头:', error.response.headers)
    }
    return Promise.reject(error)
  }
)
```

### 方法2：在具体的API调用中访问

**文件**：`api/enterprise.js`（示例）

```javascript
import request from '@/utils/request'

export function getCities() {
  return request({
    url: '/common/cities',
    method: 'get'
  }).then(response => {
    // 这里的response已经被拦截器处理过了
    // 如果需要访问原始响应（包括响应头），需要修改拦截器
    return response
  })
}

// 如果需要访问完整响应（包括响应头），可以这样：
export function getCitiesWithHeaders() {
  return axios({
    url: '/api/common/cities',
    method: 'get'
  }).then(response => {
    console.log('响应头:', response.headers)
    console.log('响应数据:', response.data)
    return response
  })
}
```

---

## 🛠️ 如何查看请求头和响应头

### 方法1：浏览器开发者工具（最常用）

1. 打开浏览器（Chrome/Edge/Firefox）
2. 按 `F12` 打开开发者工具
3. 切换到 **Network（网络）** 标签
4. 刷新页面或触发API请求
5. 点击任意请求，查看：
   - **Headers** 标签：查看请求头和响应头
   - **Response** 标签：查看响应数据
   - **Preview** 标签：格式化的响应数据

```
Network 标签示例：
┌─────────────────────────────────────────┐
│ Name          Status  Type      Size    │
├─────────────────────────────────────────┤
│ cities        200     xhr       1.2KB   │ ← 点击这里
└─────────────────────────────────────────┘

点击后显示：
┌─────────────────────────────────────────┐
│ Headers | Preview | Response | Timing  │
├─────────────────────────────────────────┤
│ General                                 │
│   Request URL: http://localhost:8088... │
│   Request Method: GET                   │
│   Status Code: 200 OK                   │
│                                         │
│ Response Headers                        │
│   content-type: application/json        │
│   access-control-allow-origin: *        │
│   access-control-allow-credentials: true│
│                                         │
│ Request Headers                         │
│   content-type: application/json        │
│   authorization: Bearer eyJhbGc...      │
└─────────────────────────────────────────┘
```

### 方法2：在代码中打印

```javascript
// 在响应拦截器中
request.interceptors.response.use(
  response => {
    console.log('=== 响应信息 ===')
    console.log('URL:', response.config.url)
    console.log('响应头:', response.headers)
    console.log('响应数据:', response.data)
    return response.data
  }
)
```

---

## 📝 常见响应头说明

| 响应头 | 作用 | 示例值 |
|--------|------|--------|
| `Content-Type` | 响应数据的格式 | `application/json` |
| `Access-Control-Allow-Origin` | 允许跨域的域名 | `http://localhost:3000` |
| `Access-Control-Allow-Credentials` | 是否允许携带Cookie | `true` |
| `Access-Control-Allow-Methods` | 允许的HTTP方法 | `GET, POST, PUT, DELETE` |
| `Access-Control-Allow-Headers` | 允许的请求头 | `Content-Type, Authorization` |
| `Cache-Control` | 缓存策略 | `no-cache`, `max-age=3600` |
| `Set-Cookie` | 设置Cookie | `session_id=abc123; HttpOnly` |
| `Authorization` | 认证信息（请求头） | `Bearer eyJhbGc...` |

---

## ⚠️ 常见问题

### Q1: 为什么我看不到某些响应头？
**A**: 浏览器出于安全考虑，默认只暴露部分响应头。如果需要访问自定义响应头，后端需要设置：
```php
header('Access-Control-Expose-Headers: X-Custom-Header, X-Another-Header');
```

### Q2: 请求头和响应头会自动生成吗？
**A**: 
- **部分会自动生成**：如 `Content-Type`、`User-Agent`、`Host` 等
- **部分需要手动配置**：如 `Authorization`、`X-Custom-Header` 等
- **CORS相关响应头必须后端配置**：如 `Access-Control-Allow-Origin`

### Q3: 如何添加自定义请求头？
**A**: 在前端 `request.js` 中：
```javascript
// 方法1：在axios实例中添加（所有请求都会带上）
headers: {
  'X-Custom-Header': 'my-value'
}

// 方法2：在请求拦截器中动态添加
request.interceptors.request.use(config => {
  config.headers['X-Custom-Header'] = 'my-value'
  return config
})

// 方法3：在具体请求中添加（只对该请求有效）
request({
  url: '/api/test',
  method: 'get',
  headers: {
    'X-Custom-Header': 'my-value'
  }
})
```

### Q4: 如何添加自定义响应头？
**A**: 在后端控制器或中间件中：
```php
// 方法1：在中间件中（所有响应都会带上）
header('X-Custom-Header: my-value');

// 方法2：在控制器中
return json($data)->header([
    'X-Custom-Header' => 'my-value'
]);
```

---

## 🎓 总结

1. **请求头**：前端配置，在 `request.js` 中设置
2. **响应头**：后端配置，在 `Cors.php` 中间件或控制器中设置
3. **查看方式**：浏览器F12 → Network标签 → 点击请求 → Headers
4. **接收响应头**：在axios响应拦截器中通过 `response.headers` 访问
5. **常见用途**：
   - 请求头：认证（Authorization）、内容类型（Content-Type）
   - 响应头：跨域（CORS）、缓存（Cache-Control）、内容类型

---

## 📚 相关文件位置

### 前端请求头配置
- `new_system/frontend/admin/src/utils/request.js`
- `new_system/frontend/admin/src/utils/userRequest.js`
- `new_system/frontend/user/src/utils/request.js`

### 后端响应头配置
- `new_system/backend/app/middleware/Cors.php` ← **最重要**
- `new_system/backend/app/middleware.php`
- `new_system/backend/config/middleware.php`

### 后端控制器示例
- `new_system/backend/app/controller/api/Common.php`
- `new_system/backend/app/controller/admin/Dashboard.php`
