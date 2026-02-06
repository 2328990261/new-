# Git 上传到 Gitee 指南

## 仓库信息
- 仓库地址: `git@gitee.com:yuchendeloy/tjiajiao91.git`
- 项目名称: 91家教管理系统

## 上传步骤

### 1. 初始化 Git 仓库（如果还没有初始化）

```bash
cd e:\123.207.52.46_8088\new_system
git init
```

### 2. 添加远程仓库

```bash
git remote add origin git@gitee.com:yuchendeloy/tjiajiao91.git
```

如果已经添加过，可以更新：
```bash
git remote set-url origin git@gitee.com:yuchendeloy/tjiajiao91.git
```

### 3. 检查 .gitignore 是否生效

```bash
git status
```

确保以下文件/目录不在待提交列表中：
- `.env` 文件
- `node_modules/` 目录
- `backend/vendor/` 目录
- `backend/runtime/` 目录
- 证书文件 `*.pem`

### 4. 添加所有文件

```bash
git add .
```

### 5. 提交更改

```bash
git commit -m "初始提交：91家教管理系统完整代码"
```

或者更详细的提交信息：
```bash
git commit -m "feat: 91家教管理系统初始版本

功能特性：
- 管理后台（Vue3 + Element Plus）
- 用户前端（Vue3 + Element Plus）
- 微信小程序（uni-app）
- 后端API（ThinkPHP 6）
- 支付系统（微信支付）
- 订单管理
- 线索管理
- 邮件系统
"
```

### 6. 推送到 Gitee

首次推送：
```bash
git push -u origin master
```

或者如果主分支是 main：
```bash
git branch -M main
git push -u origin main
```

后续推送：
```bash
git push
```

## 常见问题

### 1. SSH 密钥未配置

如果提示权限错误，需要配置 SSH 密钥：

```bash
# 生成 SSH 密钥
ssh-keygen -t rsa -C "your_email@example.com"

# 查看公钥
cat ~/.ssh/id_rsa.pub
```

然后将公钥添加到 Gitee：
1. 登录 Gitee
2. 进入"设置" -> "SSH公钥"
3. 粘贴公钥内容并保存

### 2. 文件过大

如果有大文件无法上传，可以使用 Git LFS：

```bash
git lfs install
git lfs track "*.psd"
git lfs track "*.zip"
git add .gitattributes
git commit -m "配置 Git LFS"
```

### 3. 已经提交了敏感文件

如果不小心提交了 `.env` 等敏感文件：

```bash
# 从 Git 历史中删除文件
git rm --cached backend/.env
git commit -m "删除敏感配置文件"

# 确保 .gitignore 包含该文件
echo "backend/.env" >> .gitignore
git add .gitignore
git commit -m "更新 .gitignore"
```

### 4. 强制推送（谨慎使用）

如果需要覆盖远程仓库：

```bash
git push -f origin master
```

**警告**：强制推送会覆盖远程仓库的历史记录，请谨慎使用！

## 后续更新

当有新的更改时：

```bash
# 查看更改
git status

# 添加更改
git add .

# 提交更改
git commit -m "描述你的更改"

# 推送到远程
git push
```

## 分支管理

创建开发分支：

```bash
# 创建并切换到开发分支
git checkout -b develop

# 推送开发分支
git push -u origin develop
```

合并分支：

```bash
# 切换到主分支
git checkout master

# 合并开发分支
git merge develop

# 推送更改
git push
```

## 检查清单

上传前请确认：

- [ ] `.gitignore` 文件已创建
- [ ] `.env` 文件未被跟踪
- [ ] 证书文件（.pem）未被跟踪
- [ ] `node_modules/` 未被跟踪
- [ ] `vendor/` 未被跟踪
- [ ] `runtime/` 未被跟踪
- [ ] README.md 已完善
- [ ] 敏感信息已移除或替换为占位符

## 团队协作

如果是团队开发：

1. 克隆仓库：
```bash
git clone git@gitee.com:yuchendeloy/tjiajiao91.git
```

2. 创建自己的分支：
```bash
git checkout -b feature/your-feature-name
```

3. 提交更改：
```bash
git add .
git commit -m "描述更改"
git push origin feature/your-feature-name
```

4. 在 Gitee 上创建 Pull Request

## 注意事项

1. **永远不要提交敏感信息**：
   - 数据库密码
   - API 密钥
   - 微信支付证书
   - 其他密钥和令牌

2. **定期提交**：
   - 每完成一个功能就提交
   - 提交信息要清晰明确

3. **拉取最新代码**：
   ```bash
   git pull origin master
   ```

4. **解决冲突**：
   如果有冲突，手动编辑文件解决后：
   ```bash
   git add .
   git commit -m "解决合并冲突"
   git push
   ```

---

**完成上传后，访问**: https://gitee.com/yuchendeloy/tjiajiao91
