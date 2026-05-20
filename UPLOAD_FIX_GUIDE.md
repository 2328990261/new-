# 图片上传404问题修复指南

> 仓库总体说明（分支、目录、数据库脚本）见仓储根目录 [README.md](./README.md)。  
> 更新日期：**2026-05-20**（配合 Gitee 分支 `91家教最新版`）。

## 问题描述
教师注册页面上传图片时返回404错误：`http://localhost:3001/api/teachers/upload`

## 已完成的修复

### 1. 修复localStorage错误
- **问题**: 模板中直接使用 `localStorage.getItem('teacher_token')` 导致错误
- **修复**: 在script中创建computed属性 `uploadHeaders` 来获取token
- **文件**: `tjiajiao91-main/new_system/frontend/user/src/views/TeacherRegister.vue`

### 2. 添加后端日志
- **修复**: 在 `Teacher.php` 的 `upload` 方法中添加详细日志记录
- **文件**: `tjiajiao91-main/new_system/backend/app/controller/api/Teacher.php`

### 3. 创建.htaccess文件
- **修复**: 为后端public目录创建URL重写规则
- **文件**: `tjiajiao91-main/new_system/backend/public/.htaccess`

## 需要检查的配置

### 1. 确认后端访问地址
phpstudy通常有以下几种配置方式：

#### 方式A: 直接访问public目录（推荐）
如果phpstudy的网站根目录设置为 `tjiajiao91-main/new_system/backend/public`，则：
- 后端地址: `http://localhost`
- API地址: `http://localhost/api/teachers/upload`

#### 方式B: 通过子目录访问
如果phpstudy的网站根目录是项目根目录，则：
- 后端地址: `http://localhost/new_system/backend/public`
- API地址: `http://localhost/new_system/backend/public/index.php/api/teachers/upload`

#### 方式C: 使用虚拟主机
如果配置了虚拟主机（如 `t.jiajiao91.local`），则：
- 后端地址: `http://t.jiajiao91.local`
- API地址: `http://t.jiajiao91.local/api/teachers/upload`

### 2. 更新前端配置

根据你的phpstudy配置，修改 `tjiajiao91-main/new_system/frontend/user/.env.development` 文件：

```env
# 方式A（推荐）
VITE_BACKEND_URL=http://localhost

# 方式B
VITE_BACKEND_URL=http://localhost/new_system/backend/public

# 方式C
VITE_BACKEND_URL=http://t.jiajiao91.local
```

### 3. 重启前端开发服务器
修改.env文件后，必须重启Vite开发服务器：

```bash
# 停止当前服务器（Ctrl+C）
# 然后重新启动
cd tjiajiao91-main/new_system/frontend/user
npm run dev
```

## 测试步骤

### 1. 测试后端是否可访问
在浏览器中直接访问以下URL（根据你的配置选择）：

```
http://localhost/api/cities
或
http://localhost/new_system/backend/public/index.php/api/cities
```

如果返回城市列表JSON数据，说明后端配置正确。

### 2. 测试上传功能
1. 登录教师账号
2. 进入"当老师"页面
3. 尝试上传头像
4. 打开浏览器开发者工具（F12）查看Network标签
5. 查看上传请求的详细信息

### 3. 查看后端日志
上传失败时，检查后端日志文件：
- 位置: `tjiajiao91-main/new_system/backend/runtime/log/`
- 查看最新的日志文件，搜索 "Upload" 关键词

## 常见问题

### Q1: 仍然返回404
**可能原因**:
1. 后端URL配置错误
2. .htaccess未生效（需要启用Apache的mod_rewrite模块）
3. 路由未正确加载

**解决方法**:
1. 确认phpstudy中的网站配置
2. 在phpstudy中启用mod_rewrite模块
3. 清除ThinkPHP缓存：删除 `backend/runtime/cache/` 目录下的文件

### Q2: 返回500错误
**可能原因**:
1. 文件上传目录权限不足
2. PHP配置限制

**解决方法**:
1. 确保 `backend/public/uploads/teacher/` 目录有写入权限
2. 检查PHP配置：
   - `upload_max_filesize` >= 5M
   - `post_max_size` >= 5M
   - `file_uploads` = On

### Q3: CORS错误
**可能原因**: 跨域配置问题

**解决方法**: 已在 `app/middleware/Cors.php` 中配置，确保包含了你的前端端口（3001）

## 下一步
如果按照以上步骤仍然无法解决，请提供：
1. phpstudy中的网站配置截图
2. 浏览器Network标签中的完整请求信息
3. 后端日志文件内容
