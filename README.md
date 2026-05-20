# 91家教（tjiajiao91）

本仓库托管 91家教 业务相关代码：**PHP 后端（ThinkPHP）**、**管理端与用户端 Vue 前台**、**uni-app 微信/支付宝小程序** 等。

- **远程仓库**：<https://gitee.com/yuchendeloy/tjiajiao91>
- **说明**：根目录下一层为 **`tjiajiao91-main/`**，请进入该目录查看 `new_system`、小程序等资源。

---

## 推荐分支：`91家教最新版`

与本机当前开发同步的一体化快照（后端 + 多端前端 + SQL/部署脚本 + 运维说明）。

```bash
git clone https://gitee.com/yuchendeloy/tjiajiao91.git
cd tjiajiao91
git checkout "91家教最新版"
```

> 亦可继续使用历史分支（例如 `main`、小程序独立分支）；以本 README 与各子目录文档为准对齐环境。

---

## 目录导引

| 路径 | 说明 |
|------|------|
| `tjiajiao91-main/new_system/backend/` | PHP 后端、路由、控制器、模型、`public/` 站点入口 |
| `tjiajiao91-main/new_system/backend/sql/` | **数据库脚本与说明**（建表、`deploy_*.bat` / `.sh`、`README_*.md`） |
| `tjiajiao91-main/new_system/frontend/admin/` | 管理端前端（Vue） |
| `tjiajiao91-main/new_system/frontend/user/` | 用户站教师/家长相关页面（Vue + Vite） |
| `tjiajiao91-main/微信小程序/预约家教小程序/` | 微信 uni-app 小程序 |
| `tjiajiao91-main/预约家教小程序(支付宝)/` | 支付宝端 uni-app 小程序 |

更细的小程序介绍见：`tjiajiao91-main/new_system/README.md`（部分内容随仓库合并已包含小程序目录，以实际路径为准）。

---

## 数据库与部署（SQL）

- **教师验证码表**：`tjiajiao91-main/new_system/backend/sql/`  
  - `create_teacher_verification_codes.sql` — MySQL 建表（与脚本、手动文档一致）  
  - `README_TEACHER_VERIFICATION.md` — 用途与部署步骤  
  - `deploy_teacher_verification_codes.bat` / `.sh` — 从 `.env` 读配置后导入 `create_teacher_verification_codes.sql`  
  - `quick_fix.bat` / `quick_fix_verification_table.sql` — 本地一键修复脚本与对应 SQL  

- **工资等其它脚本**：同目录下的 `README_PERSONNEL_SALARY.md`、`deploy_personnel_salary.*`  

执行前请在服务器上配置好 **MySQL、`.env` 中 DATABASE_*** ，并备份数据库。

---

## 常见问题与运维文档

| 文档 | 内容 |
|------|------|
| `UPLOAD_FIX_GUIDE.md` | 教师注册图片上传 **404 / 重写 / 前端代理** 等排查与 `.htaccess`、`VITE_BACKEND_URL` 配置 |

---

## 更新时间

**2026-05-20**：新增 Gitee 说明、统一 SQL 文件名与脚本引用，并用于分支 **`91家教最新版`** 发布快照。
