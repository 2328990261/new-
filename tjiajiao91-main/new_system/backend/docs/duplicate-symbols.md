# 后端重复类/文件清单（自动扫描）

本文件由 `tools/scan-php-duplicates.php` 扫描 `backend/app/` 目录得到，用于定位 **同 namespace 同类名** 的重复定义（高风险：会造成“改了不生效/加载歧义”）。

## 扫描方式

在 `new_system/backend/` 下执行：

```bash
php tools/scan-php-duplicates.php app
```

## 扫描结果摘要

当前发现重复符号 **32 组**，其中绝大多数来自 `app/app/`（legacy 副本目录）与 `app/` 的成对重复；另有少量来自同目录下的历史副本文件（例如 `Payment_new.php`、`RecognitionService-副本.php`）。

> 详细清单请直接运行脚本获取最新输出（避免本文因代码变更而过期）。

