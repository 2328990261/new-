# 管理端配置说明

## API路径配置

当前配置已硬编码为 `/admin/api`，适合大多数场景。

### 如需灵活配置（可选）

如果需要在不同环境使用不同的API路径：

1. **修改 `src/utils/request.js`：**
```javascript
// 从：
const apiBaseUrl = '/admin/api'

// 改为：
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/admin/api'
```

2. **创建环境配置文件：**

**.env.development** (开发环境)
```
VITE_API_BASE_URL=/admin/api
```

**.env.production** (生产环境)
```
VITE_API_BASE_URL=/admin/api
```

3. **构建时会自动使用对应环境的配置**

## 调试日志

- 开发环境 (`npm run dev`)：显示完整的请求调试信息
- 生产环境 (`npm run build`)：自动移除所有调试日志

通过 `import.meta.env.DEV` 自动判断，无需手动配置。

## 安全建议

1. ✅ 生产环境务必使用 `npm run build` 构建
2. ✅ 不要在生产环境使用 `npm run dev`
3. ✅ 定期更新依赖包：`npm update`
4. ✅ 配置CSP（Content Security Policy）
5. ✅ 启用HTTPS

## 性能优化

当前 `vite.config.js` 已配置：
- ✅ 代码分割（chunk splitting）
- ✅ Tree shaking（自动移除未使用的代码）
- ✅ 压缩混淆（terser）
- ✅ 缓存优化

