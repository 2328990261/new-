# 薪酬管理功能 - 快速开始

## 🚀 5分钟快速部署

### 第一步：创建数据库表

```bash
# 进入SQL目录
cd new_system/backend/sql

# 执行SQL脚本（根据你的系统选择）
# Linux/Mac:
mysql -u用户名 -p密码 数据库名 < create_personnel_salary_table.sql

# Windows:
mysql -u用户名 -p密码 数据库名 < create_personnel_salary_table.sql
```

### 第二步：重启服务（可选）

如果后端使用了缓存，建议重启：

```bash
# PHP-FPM
systemctl restart php-fpm

# 或者 Apache
systemctl restart apache2
```

### 第三步：访问功能

1. 登录管理后台
2. 点击"企业管理"
3. 切换到"薪酬管理"标签页
4. 开始使用！

## 📝 快速使用

### 新增员工薪酬

1. 点击"新增薪酬"
2. 选择员工
3. 填写基本工资（必填）
4. 填写其他薪酬项（可选）
5. 设置生效日期
6. 保存

### 查看薪酬统计

页面顶部自动显示：
- 在职人员数
- 平均薪酬
- 薪酬总额

## 🎯 核心功能

✅ 薪酬录入与编辑  
✅ 薪酬历史追踪  
✅ 自动计算总薪酬  
✅ 统计分析  
✅ 查询筛选  

## 📚 详细文档

查看完整文档：[SALARY_MANAGEMENT_GUIDE.md](./SALARY_MANAGEMENT_GUIDE.md)

## ❓ 常见问题

**Q: 找不到薪酬管理入口？**  
A: 确保你有企业管理权限，在企业管理页面切换到"薪酬管理"标签页。

**Q: 保存时提示"人员不存在"？**  
A: 先在"人员管理"中录入员工信息。

**Q: 如何查看薪酬历史？**  
A: 在列表中可以看到同一员工的所有薪酬记录，包括已失效的历史记录。

## 🎉 完成！

现在你可以开始管理员工薪酬了！
