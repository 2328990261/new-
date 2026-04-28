# 线上发布（面板/FTP 上传）推荐流程：可回滚版本化

适用场景：你当前通过宝塔/FTP/面板上传后端文件到线上；希望避免“覆盖式上传”带来的不可回滚与半更新问题。

## 目录结构（服务器）

建议把站点目录组织为 3 块：

- `releases/`：每次发布一个全新目录（时间戳命名）
- `shared/`：只放一份、跨版本共享的文件/目录
- `current`：指向当前线上版本的软链接（切换它即可发布/回滚）

示例：

```text
/www/wwwroot/tj91-backend/
  releases/
    20260421153000/
    20260422101010/
  shared/
    .env
    runtime/
    public/
      uploads/
  current -> /www/wwwroot/tj91-backend/releases/20260422101010/
```

> 站点根目录建议指到 `current/public`（ThinkPHP6 入口在 `public/index.php`）。

## shared 目录怎么准备

- **环境变量**：把 `backend/.env` 放在 `shared/.env`（不要入库）\n- **运行目录**：`shared/runtime/`（日志/缓存/session/temp 都在这里）\n- **上传目录**：`shared/public/uploads/`（用户上传与后台上传都在这里，发布不覆盖）

确保权限归属到 PHP-FPM/Nginx 用户（常见 `www` 或 `www-data`），并保证可写。

## 每次发布步骤（面板上传）

1. **打包本次发布的后端代码**（本地）\n   - 建议只打包 `new_system/backend/` 内容\n   - 不包含：`runtime/`、`.env`、`public/uploads/`\n\n2. **上传到新的 release 目录**（面板）\n   - 例如上传到 `releases/20260422101010/`\n\n3. **在服务器上把 shared 链接进新 release**\n   - `ln -sfn ../shared/.env .env`\n   - `rm -rf runtime && ln -sfn ../shared/runtime runtime`\n   - `rm -rf public/uploads && ln -sfn ../shared/public/uploads public/uploads`\n\n4. **如果服务器能跑 composer（推荐）**\n   - 在新 release 目录执行：`composer install --no-dev --prefer-dist --optimize-autoloader`\n\n5. **切换 current（原子发布）**\n   - `ln -sfn /www/wwwroot/tj91-backend/releases/20260422101010 /www/wwwroot/tj91-backend/current`\n\n6. **重载 PHP-FPM（可选）**\n   - `systemctl reload php-fpm` 或 `systemctl reload php8.2-fpm`（按你的环境）\n\n7. **健康检查**\n   - 打开 `/admin/api/info` 或你常用的健康接口验证（状态码 + 关键功能）\n+
## 回滚（秒级）

把 `current` 软链接切回上一个 release 即可（不需要重新上传）：

- `ln -sfn /www/wwwroot/tj91-backend/releases/<上一个时间戳> /www/wwwroot/tj91-backend/current`

## 特别提醒

- **不要把 `runtime/`、`vendor/`、`public/uploads/` 当成源码一起覆盖上传**。\n- 如果线上无法安装 composer：你可以在本地/构建机先 `composer install --no-dev` 后再上传整个 `backend/`（仍然保持 `.env/runtime/uploads` 用 shared 持久化）。\n- 前端 `dist/` 不建议入库；建议走“构建产物发布”，不要把构建产物当源码维护。

