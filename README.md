# 91家教（tjiajiao91）

91家教是一套面向家教中介/平台的综合业务系统，包含 **PHP 后端（ThinkPHP 6）**、**管理端与用户端 Vue 前台**、**微信/支付宝 uni-app 小程序** 等模块。

| 项目 | 地址 |
|------|------|
| **GitHub（主仓库）** | https://github.com/2328990261/new- |
| Gitee（历史） | https://gitee.com/yuchendeloy/tjiajiao91 |

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

## 分支说明

| 分支 | 说明 |
|------|------|
| **`91家教最新版`** | 当前推荐开发快照（后端 + 多端前端 + SQL/运维脚本） |
| `main` | 主分支 |

---

**开发团队**：91家教  
**许可证**：仅供学习与内部使用
