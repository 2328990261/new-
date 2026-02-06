# SSH 密钥配置步骤

## ✅ SSH 密钥已生成

我已经为你生成了 SSH 密钥，现在需要将公钥添加到 Gitee。

## 📋 你的 SSH 公钥

```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDXer563qtbvB0UZgVcLehRB62Hauy2isrAepJt1hfeiGgeysxcct2QNbaKWo4uD0V8Gvu5/pofdX17X04+vrsAq9To3GOD+m4C5AP24t7/AE3fJQJBlbilboyNY4NBRO6V80ly5yG1ByQrUZDyfkLH1gZq+S1AFeDogDx7BZqFI1gvqVZX4nUai2UwYSxY3k8uEJ+RSP4tw3pNt33nBfQdZ1tDIr1wwsS8nJkcQ/js4H3CJ7UXsftM8EfeHlws+Df1UHA0w4jj2xY+wjfLsUsxoY6GnwquE76+qH36o28LsHjBkCinO9ZCvHB3QsjY7hIPNer64SIZb3p6SXHhmYkTeNHnoWK+M9yDzBxetPsJ5wjjYZIpD2rFG5DO8kj4DNkyNYT3tH1xY4sy+2ygp+R5aBcfkA5qlaBWqKN6ktKZurs3V/Yzq0w5346ZDDOUUYeHdphIqMi23maJ07RwphZdNLmjunMNy/aBNlEV83EeKMrIyIsX2syCEG/CXjUOLkpecAyzZ8m9MjiNHPH66QenViXdj4/0rkc2BGPBHtXQrakujgo9Rt4kpRhRbBMTijkqKkMxUnm+jUr9uau9UqUXAE6nYnmR78VPz0QaYLfAyrW3cE1eyBIJrqQ/G1DKjN7+o00aKyg3NbxSI1Gx5DrfuVCAS8jdX0CoY0ufHyyokw== gitee_key
```

## 🔧 添加到 Gitee 的步骤

### 1. 复制上面的公钥

选中上面的整个公钥内容（从 `ssh-rsa` 开始到 `gitee_key` 结束），复制。

### 2. 登录 Gitee

打开浏览器，访问：https://gitee.com

使用你的账号登录。

### 3. 进入 SSH 公钥设置

- 点击右上角头像
- 选择"设置"
- 在左侧菜单中选择"SSH公钥"
- 或直接访问：https://gitee.com/profile/sshkeys

### 4. 添加公钥

1. 在"标题"框中输入：`My Computer` 或任何你喜欢的名称
2. 在"公钥"框中粘贴刚才复制的公钥内容
3. 点击"确定"按钮

### 5. 验证配置

添加成功后，在命令行中测试连接：

```bash
ssh -T git@gitee.com
```

如果看到类似以下信息，说明配置成功：
```
Hi [你的用户名]! You've successfully authenticated, but Gitee.com does not provide shell access.
```

## 🚀 配置完成后上传

SSH 密钥配置完成后，就可以上传代码了：

### 方法 1: 使用批处理脚本

```
双击运行: 上传到Gitee.bat
```

### 方法 2: 手动命令

打开命令行（CMD 或 PowerShell）：

```bash
cd e:\123.207.52.46_8088\new_system

git add . 2>nul

git commit -m "feat: 91家教管理系统初始版本"

git push -u origin main
```

## 📝 如果公钥复制不完整

可以使用命令查看完整公钥：

```bash
type C:\Users\sunky\.ssh\id_rsa.pub
```

或者在 PowerShell 中：

```powershell
Get-Content $env:USERPROFILE\.ssh\id_rsa.pub
```

## ⚠️ 注意事项

1. **公钥必须完整复制**：从 `ssh-rsa` 开始到 `gitee_key` 结束
2. **不要复制私钥**：私钥文件是 `id_rsa`（没有 .pub 后缀），千万不要泄露
3. **一个公钥可以用于多个仓库**：配置一次即可

## 🆘 常见问题

### Q: 提示 "Permission denied (publickey)"

A: 说明公钥还没有添加到 Gitee，或者添加的公钥不完整。请重新添加。

### Q: 提示 "Host key verification failed"

A: 第一次连接时会提示，输入 `yes` 确认即可。

### Q: 如何查看我的 Gitee 用户名？

A: 登录 Gitee 后，点击右上角头像，可以看到你的用户名。

---

## ✨ 下一步

1. ✅ SSH 密钥已生成
2. ⏳ 将公钥添加到 Gitee（按照上面的步骤）
3. ⏳ 测试 SSH 连接
4. ⏳ 上传代码到 Gitee

**完成第 2 步后，就可以顺利上传代码了！**
