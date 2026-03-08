# 91家教预约小程序

基于 uni-app 开发的微信小程序，提供家教预约、教师查询、AI智能匹配等功能。

## ⚠️ 重要说明

**本项目在 miniprogram 分支**

- 主系统代码（后端+前端）在 **main 分支**
- 小程序代码在 **miniprogram 分支**（当前分支）

### 获取代码

```bash
# 克隆小程序分支
git clone -b miniprogram https://gitee.com/yuchendeloy/tjiajiao91.git

# 或者在已克隆的仓库中切换分支
git checkout miniprogram
```

## 项目简介

91家教预约小程序是91家教管理系统的移动端应用，为用户提供便捷的家教预约服务。

## 技术栈

- **框架**: uni-app
- **开发工具**: HBuilderX 或 微信开发者工具
- **UI组件**: uni-ui
- **状态管理**: Vuex
- **网络请求**: uni.request 封装

## 主要功能

### 用户端功能

- 🔐 **微信登录** - 一键授权登录
- 👨‍🏫 **教师库** - 浏览和搜索教师信息
- 📝 **在线预约** - 快速提交家教需求
- 🤖 **AI预约** - 智能推荐匹配教师
- 📋 **订单管理** - 查看预约订单状态
- 👤 **个人中心** - 管理个人信息
- 📄 **协议查看** - 服务协议、隐私政策

### 教师端功能

- 📝 **教师注册** - 在线提交教师资料
- 📄 **简历预览** - 查看个人简历
- 📊 **订单查询** - 查看接单记录

## 项目结构

```
预约家教小程序/
├── pages/                    # 页面目录
│   ├── login/               # 登录页
│   ├── teacher-library/     # 教师库
│   ├── teacher-detail/      # 教师详情
│   ├── teacher-booking/     # 教师预约
│   ├── ai-booking/          # AI智能预约
│   ├── teacher-register/    # 教师注册
│   ├── teacher-resume-preview/ # 简历预览
│   ├── tutor-detail/        # 家教详情
│   ├── profile/             # 个人中心
│   ├── agreement/           # 协议页面
│   └── privacy-policy/      # 隐私政策
│
├── components/              # 组件目录
│   ├── teacher-card/        # 教师卡片
│   └── ...
│
├── utils/                   # 工具函数
│   ├── api.js              # API接口
│   ├── request.js          # 网络请求封装
│   └── logger.js           # 日志工具
│
├── config/                  # 配置文件
│   └── env.js              # 环境配置
│
├── static/                  # 静态资源
│   ├── images/             # 图片资源
│   └── icons/              # 图标资源
│
├── pages.json              # 页面配置
├── manifest.json           # 应用配置
├── App.vue                 # 应用入口
└── main.js                 # 入口文件
```

## 快速开始

### 环境要求

- **HBuilderX 3.0+**（推荐）或 微信开发者工具
- **Node.js 14+**（如果使用 CLI 方式）
- **微信小程序账号**

### 方法 1: 使用 HBuilderX（推荐）

#### 1. 安装 HBuilderX

下载地址：https://www.dcloud.io/hbuilderx.html

选择"标准版"或"App开发版"

#### 2. 导入项目

1. 打开 HBuilderX
2. 文件 → 导入 → 从本地目录导入
3. 选择本项目目录

#### 3. 配置 AppID

编辑 `manifest.json`：

```json
{
  "mp-weixin": {
    "appid": "你的微信小程序AppID"
  }
}
```

#### 4. 配置 API 地址

编辑 `config/env.js`：

```javascript
// 开发环境
const dev = {
  baseUrl: 'http://localhost:8080'  // 本地后端地址
}

// 生产环境
const prod = {
  baseUrl: 'https://api.yourdomain.com'  // 线上后端地址
}

export default process.env.NODE_ENV === 'development' ? dev : prod
```

#### 5. 运行项目

1. 在 HBuilderX 中点击"运行" → "运行到小程序模拟器" → "微信开发者工具"
2. 首次运行会自动打开微信开发者工具
3. 在微信开发者工具中查看效果

### 方法 2: 使用微信开发者工具

#### 1. 安装微信开发者工具

下载地址：https://developers.weixin.qq.com/miniprogram/dev/devtools/download.html

#### 2. 导入项目

1. 打开微信开发者工具
2. 选择"导入项目"
3. 选择本项目目录
4. 填写 AppID
5. 点击"导入"

#### 3. 配置说明

同上面的步骤 3-4

### 详细安装步骤

1. **克隆项目**
```bash
# 克隆 miniprogram 分支
git clone -b miniprogram https://gitee.com/yuchendeloy/tjiajiao91.git
cd tjiajiao91
```

2. **配置 AppID**

编辑 `manifest.json`，配置你的微信小程序 AppID：
```json
{
  "mp-weixin": {
    "appid": "你的AppID"
  }
}
```

3. **配置 API 地址**

编辑 `config/env.js`，配置后端 API 地址：
```javascript
const baseUrl = 'https://your-api-domain.com'
```

4. **使用 HBuilderX 打开项目**

- 打开 HBuilderX
- 文件 -> 打开目录 -> 选择项目目录

5. **运行到微信开发者工具**

- 运行 -> 运行到小程序模拟器 -> 微信开发者工具

## 配置说明

### 1. 微信小程序配置

在 `manifest.json` 中配置：

```json
{
  "mp-weixin": {
    "appid": "你的AppID",
    "setting": {
      "urlCheck": false
    },
    "usingComponents": true
  }
}
```

### 2. API 配置

在 `config/env.js` 中配置：

```javascript
// 开发环境
const dev = {
  baseUrl: 'http://localhost:8080'
}

// 生产环境
const prod = {
  baseUrl: 'https://api.yourdomain.com'
}

export default process.env.NODE_ENV === 'development' ? dev : prod
```

### 3. 页面配置

在 `pages.json` 中配置页面路由和导航栏：

```json
{
  "pages": [
    {
      "path": "pages/login/index",
      "style": {
        "navigationBarTitleText": "登录"
      }
    }
  ],
  "tabBar": {
    "list": [
      {
        "pagePath": "pages/teacher-library/index",
        "text": "教师库"
      }
    ]
  }
}
```

## 主要页面说明

### 登录页 (pages/login)
- 微信一键登录
- 手机号授权登录
- 自动跳转到首页

### 教师库 (pages/teacher-library)
- 教师列表展示
- 搜索和筛选
- 查看教师详情

### AI预约 (pages/ai-booking)
- 智能表单填写
- AI推荐匹配
- 快速提交需求

### 教师注册 (pages/teacher-register)
- 多步骤表单
- 资料上传
- 进度保存

### 个人中心 (pages/profile)
- 个人信息管理
- 订单查询
- 设置选项

## API 接口

主要接口列表：

```javascript
// 用户相关
POST /api/wechat/login              // 微信登录
POST /api/wechat/login-phone        // 手机号登录

// 教师相关
GET  /api/teacher/list              // 教师列表
GET  /api/teacher/detail/:id        // 教师详情
POST /api/teacher/book              // 预约教师

// 预约相关
POST /api/mini-booking/create       // 创建预约
GET  /api/mini-booking/my-orders    // 我的订单
GET  /api/mini-booking/detail/:id   // 订单详情

// 教师注册
POST /api/teacher-register/save-progress  // 保存进度
POST /api/teacher-register/submit         // 提交注册
```

## 开发规范

### 代码规范

- 使用 ES6+ 语法
- 组件命名采用 PascalCase
- 文件命名采用 kebab-case
- 遵循 Vue 风格指南

### 提交规范

```bash
feat: 新功能
fix: 修复bug
docs: 文档更新
style: 代码格式调整
refactor: 代码重构
test: 测试相关
chore: 构建/配置相关
```

## 常见问题

### 1. 登录失败

**问题**：提示"登录失败"或"获取用户信息失败"

**解决方案**：
- 检查 AppID 是否配置正确
- 确认后端 API 地址可访问
- 查看微信开发者工具控制台错误信息

### 2. 图片上传失败

**问题**：上传图片时提示失败

**解决方案**：
- 检查图片大小是否超过限制（建议<2MB）
- 确认上传接口配置正确
- 检查服务器上传目录权限

### 3. 页面白屏

**问题**：打开某个页面显示白屏

**解决方案**：
- 检查页面路径是否正确
- 查看控制台是否有 JavaScript 错误
- 确认页面组件是否正确引入

### 4. 网络请求失败

**问题**：API 请求返回失败

**解决方案**：
- 检查 `config/env.js` 中的 baseUrl 配置
- 确认服务器域名已在小程序后台配置
- 开发时可以在微信开发者工具中关闭域名校验

## 发布流程

### 1. 准备工作

- 完成功能开发和测试
- 更新版本号（在 `manifest.json` 中）
- 准备小程序图标和截图

### 2. 使用 HBuilderX 发布

#### 方式 1: 发行到微信小程序

1. 在 HBuilderX 中点击"发行" → "小程序-微信"
2. 填写版本号和项目备注
3. 点击"发行"
4. 等待编译完成
5. 自动打开微信开发者工具

#### 方式 2: 在微信开发者工具中上传

1. 在 HBuilderX 中运行到微信开发者工具
2. 在微信开发者工具中点击"上传"
3. 填写版本号和项目备注
4. 等待上传完成

### 3. 提交审核

1. 登录微信公众平台：https://mp.weixin.qq.com
2. 进入"版本管理"
3. 找到刚上传的版本
4. 点击"提交审核"
5. 填写审核信息：
   - 功能页面截图
   - 测试账号（如需要）
   - 功能描述
6. 提交等待审核

### 4. 发布上线

1. 审核通过后会收到通知
2. 登录微信公众平台
3. 进入"版本管理"
4. 点击"发布"
5. 确认发布信息
6. 等待发布完成（通常几分钟）

### 注意事项

- 首次提交审核可能需要 1-7 天
- 后续更新通常 1-3 天
- 确保小程序符合微信小程序规范
- 准备好相关资质文件（如需要）

## 性能优化

- 图片使用 webp 格式
- 启用分包加载
- 合理使用缓存
- 减少不必要的网络请求
- 优化页面渲染性能

## 安全说明

- 不在代码中硬编码敏感信息
- 使用 HTTPS 协议
- 对用户输入进行验证
- 定期更新依赖包

## 更新日志

查看 [CHANGELOG.md](./CHANGELOG.md) 了解版本更新记录。

## 技术支持

如有问题，请提交 Issue 或联系开发团队。

## 许可证

本项目仅供学习和研究使用。

---

**开发团队**: 91家教
**最后更新**: 2026-02-06
