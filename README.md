# 91家教管理系统

一个功能完整的家教信息管理平台，包含管理后台、用户前端和微信小程序。

## 项目简介

91家教管理系统是一个基于 Vue 3 + ThinkPHP 6 开发的现代化家教信息管理平台，提供家教信息发布、订单管理、支付管理、线索管理等完整功能。

## 技术栈

### 前端
- **管理后台**: Vue 3 + Element Plus + Vite
- **用户前端**: Vue 3 + Element Plus + Vite
- **微信小程序**: uni-app

### 后端
- **框架**: ThinkPHP 6.1
- **数据库**: MySQL 5.7+
- **PHP版本**: 7.4+

## 主要功能

### 管理后台
- 📊 数据面板 - 实时统计和数据可视化
- 👥 用户管理 - 管理员、教师、学生信息管理
- 📝 订单管理 - 家教订单、预约订单管理
- 💰 支付管理 - 支付记录、退款管理、支付配置
- 🎯 线索管理 - 客户线索跟进和转化
- 📧 邮件管理 - 邮件发送和日志查询
- ⚙️ 系统配置 - 支付配置、协议管理、SEO配置

### 用户前端
- 🔍 家教搜索 - 按城市、科目、年级搜索
- 📱 在线预约 - 快速提交家教需求
- 💳 在线支付 - 微信支付（H5/二维码）
- 📄 协议查看 - 服务协议、隐私政策
- 🔄 退款申请 - 在线申请退款

### 微信小程序
- 🎓 教师库 - 浏览和搜索教师信息
- 📝 在线预约 - 快速预约家教服务
- 🤖 AI预约 - 智能推荐匹配
- 👤 个人中心 - 订单查询、个人信息管理

## 项目结构

```
new_system/
├── backend/                 # 后端代码
│   ├── app/                # 应用目录
│   │   ├── controller/     # 控制器
│   │   │   ├── admin/      # 管理后台控制器
│   │   │   └── api/        # API控制器
│   │   ├── model/          # 模型
│   │   ├── service/        # 服务层
│   │   └── middleware/     # 中间件
│   ├── config/             # 配置文件
│   ├── route/              # 路由配置
│   ├── public/             # 公共资源
│   └── database/           # 数据库文件
│
├── frontend/               # 前端代码
│   ├── admin/              # 管理后台
│   │   ├── src/
│   │   │   ├── views/      # 页面组件
│   │   │   ├── components/ # 公共组件
│   │   │   ├── api/        # API接口
│   │   │   └── utils/      # 工具函数
│   │   └── vite.config.js
│   │
│   └── user/               # 用户前端
│       ├── src/
│       │   ├── views/      # 页面组件
│       │   ├── components/ # 公共组件
│       │   ├── api/        # API接口
│       │   └── utils/      # 工具函数
│       └── vite.config.js
│
└── 预约家教小程序/          # 微信小程序
    ├── pages/              # 页面
    ├── components/         # 组件
    ├── utils/              # 工具函数
    └── manifest.json       # 配置文件
```

## 快速开始

### 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- Node.js >= 16.0
- Composer
- npm 或 yarn

### 安装步骤

#### 1. 克隆项目

```bash
git clone git@gitee.com:yuchendeloy/tjiajiao91.git
cd tjiajiao91/new_system
```

#### 2. 后端配置

```bash
cd backend

# 安装依赖
composer install

# 复制环境配置文件
cp .example.env .env

# 编辑 .env 文件，配置数据库信息
# DB_HOST = 127.0.0.1
# DB_NAME = myjiajiao
# DB_USER = root
# DB_PASSWORD = your_password
```

#### 3. 数据库初始化

```bash
# 导入数据库文件
mysql -u root -p myjiajiao < database/create_complete_booking_system.sql

# 或者逐个导入所需的表结构
mysql -u root -p myjiajiao < database/create_users_table.sql
mysql -u root -p myjiajiao < database/create_payments_table.sql
# ... 其他表结构文件
```

#### 4. 前端配置

**管理后台：**

```bash
cd frontend/admin

# 安装依赖
npm install

# 启动开发服务器
npm run dev

# 访问 http://localhost:3002
```

**用户前端：**

```bash
cd frontend/user

# 安装依赖
npm install

# 启动开发服务器
npm run dev

# 访问 http://localhost:3001
```

#### 5. 微信小程序

使用微信开发者工具打开 `预约家教小程序` 目录，配置 AppID 后即可预览。

### 一键启动（Windows）

```bash
# 在项目根目录执行
启动服务.bat
```

这将自动启动管理后台和用户前端的开发服务器。

## 生产部署

### 编译前端

```bash
# 在项目根目录执行
编译生产文件.bat
```

或手动编译：

```bash
# 管理后台
cd frontend/admin
npm run build

# 用户前端
cd frontend/user
npm run build
```

### 生成部署包

```bash
# 在项目根目录执行
生成部署包.bat
```

这将生成一个包含所有必要文件的 `deployment` 目录。

### Nginx 配置

参考 `nginx_production.conf` 和 `thinkphp_rewrite.conf` 文件进行配置。

## 支付配置

系统支持微信支付，包括：
- H5 支付（手机浏览器）
- Native 支付（二维码扫码）
- JSAPI 支付（微信内浏览器）

### 配置步骤

1. 登录管理后台
2. 进入"支付配置"页面
3. 填写微信支付配置信息：
   - AppID
   - 商户号
   - API密钥
   - 证书文件路径
   - 回调地址

4. 点击"测试配置"验证配置是否正确

### 本地测试

本地开发环境无法接收微信支付回调，系统提供了手动确认功能：

1. 扫描二维码完成支付
2. 点击"我已完成支付"按钮
3. 系统会手动更新支付状态

## 常见问题

### 1. 支付接口 500 错误

**原因**：数据库字段缺失或路由配置问题

**解决方案**：
- 检查数据库表结构是否完整
- 运行 `database/add_dispatcher_id_to_payments.sql`
- 清除缓存：`php think clear`

### 2. 前端无法连接后端

**原因**：代理配置错误

**解决方案**：
- 检查 `vite.config.js` 中的 proxy 配置
- 确保后端服务运行在正确的端口（默认 8080）

### 3. 微信支付回调失败

**原因**：本地环境无法接收外网回调

**解决方案**：
- 使用内网穿透工具（如 ngrok）
- 或使用手动确认功能进行测试

## 更新日志

查看 `deployment/更新说明.md` 了解详细的更新记录。

## 技术支持

如有问题，请提交 Issue 或联系开发团队。

## 许可证

本项目仅供学习和研究使用。

## 贡献指南

欢迎提交 Pull Request 或 Issue！

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

## 开发团队

- 后端开发：ThinkPHP 6 + MySQL
- 前端开发：Vue 3 + Element Plus
- 小程序开发：uni-app

---

**注意**：本项目包含敏感配置信息（如数据库密码、支付密钥等），请勿将 `.env` 文件提交到版本控制系统。
