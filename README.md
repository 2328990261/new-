# 91家教（tjiajiao91）

91家教是一套面向家教中介/平台的综合业务系统，包含 **PHP 后端（ThinkPHP 6）**、**管理端与用户端 Vue 前台**、**微信/支付宝 uni-app 小程序** 等模块。

| 项目 | 地址 |
|------|------|
| **GitHub（主仓库）** | https://github.com/2328990261/new- |
| Gitee（历史） | https://gitee.com/yuchendeloy/tjiajiao91 |

---

## 文档导航

| 文档 | 说明 |
|------|------|
| [docs/项目文档.md](./docs/项目文档.md) | 系统架构、目录结构、功能模块、部署与配置 |
| [docs/工作文档.md](./docs/工作文档.md) | 日常开发流程、联调测试、上线与排错手册 |
| [UPLOAD_FIX_GUIDE.md](./UPLOAD_FIX_GUIDE.md) | 教师注册图片上传 404 排查指南 |

---

## 快速开始

### 克隆代码

```bash
git clone https://github.com/2328990261/new-.git
cd new-
git checkout "91家教最新版"   # 推荐分支
```

> 根目录下一层为 **`tjiajiao91-main/`**，业务代码在该目录内。

### 后端

```bash
cd tjiajiao91-main/new_system/backend
composer install
cp .env.example .env   # 配置数据库等
```

Web 根目录指向 `backend/public/`，验证：`http://localhost/api/cities`

### 前端

```bash
# 管理端
cd tjiajiao91-main/new_system/frontend/admin
npm install && npm run dev

# 用户站
cd tjiajiao91-main/new_system/frontend/user
npm install && npm run dev
```

详细步骤见 [项目文档 — 快速部署](./docs/项目文档.md#6-快速部署)。

---

## 目录导引

| 路径 | 说明 |
|------|------|
| `tjiajiao91-main/new_system/backend/` | PHP 后端、路由、控制器、模型、`public/` 站点入口 |
| `tjiajiao91-main/new_system/backend/sql/` | 数据库脚本与部署工具（`README_*.md`、`deploy_*.bat/.sh`） |
| `tjiajiao91-main/new_system/frontend/admin/` | 管理端前端（Vue 3 + Element Plus） |
| `tjiajiao91-main/new_system/frontend/user/` | 用户站（Vue 3 + Vite） |
| `tjiajiao91-main/微信小程序/预约家教小程序/` | 微信 uni-app 小程序 |
| `tjiajiao91-main/预约家教小程序(支付宝)/` | 支付宝端 uni-app 小程序 |

---

## 技术栈

- **后端**：PHP 7.4+ · ThinkPHP 6 · MySQL
- **Web 前端**：Vue 3 · Vite 7 · Element Plus · Pinia
- **小程序**：uni-app · uni-ui

---

## 数据库脚本

位于 `tjiajiao91-main/new_system/backend/sql/`：

| 脚本 | 说明 |
|------|------|
| `create_teacher_verification_codes.sql` | 教师验证码表 |
| `README_TEACHER_VERIFICATION.md` | 部署说明 |
| `create_personnel_salary_table.sql` | 人事薪酬表 |
| `README_PERSONNEL_SALARY.md` | 薪酬模块说明 |

执行前请备份数据库，并配置好 `.env` 中的数据库连接。

---

## 运维与排错

| 文档 | 内容 |
|------|------|
| [UPLOAD_FIX_GUIDE.md](./UPLOAD_FIX_GUIDE.md) | 图片上传 404 / 重写 / 前端代理 |
| [404错误诊断和解决.md](./404错误诊断和解决.md) | 路由 404 通用排查 |
| [为什么需要CORS响应头.md](./为什么需要CORS响应头.md) | 跨域原理与配置 |
| [中间件工作原理详解.md](./中间件工作原理详解.md) | 认证、CORS 中间件 |
| [HTTP请求头和响应头说明.md](./HTTP请求头和响应头说明.md) | HTTP 头参考 |

---

## 分支说明

| 分支 | 说明 |
|------|------|
| **`91家教最新版`** | 当前推荐开发快照（后端 + 多端前端 + SQL/运维文档） |
| `main` | 主分支（与最新版同步） |

---

## 更新记录

| 日期 | 说明 |
|------|------|
| 2026-05-26 | 新增 GitHub 仓库说明、项目文档与工作文档 |
| 2026-05-20 | Gitee 分支「91家教最新版」聚合后端、多端前端与 SQL 运维脚本 |

---

**开发团队**：91家教  
**许可证**：仅供学习与内部使用
