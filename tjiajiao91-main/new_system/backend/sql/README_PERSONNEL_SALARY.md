# 人员薪酬管理功能说明

## 功能概述

在企业管理模块中新增了"薪酬管理"功能，用于管理员工的薪酬信息，支持薪酬历史记录和变更追踪。

## 数据库部署

### 1. 创建薪酬表

执行以下SQL文件创建人员薪酬表：

```bash
mysql -u用户名 -p密码 数据库名 < create_personnel_salary_table.sql
```

或者在MySQL客户端中执行：

```sql
source /path/to/create_personnel_salary_table.sql;
```

### 2. 表结构说明

**表名**: `fa_personnel_salary`

**字段说明**:
- `id`: 主键ID
- `personnel_id`: 人员ID，关联fa_personnel表
- `base_salary`: 基本工资
- `performance_salary`: 绩效工资
- `post_allowance`: 岗位津贴
- `housing_allowance`: 住房补贴
- `meal_allowance`: 餐补
- `transport_allowance`: 交通补贴
- `other_allowance`: 其他补贴
- `total_salary`: 总薪酬（自动计算）
- `effective_date`: 生效日期
- `end_date`: 结束日期（NULL表示当前有效）
- `status`: 状态（1=有效，0=失效）
- `remark`: 备注
- `create_time`: 创建时间
- `update_time`: 更新时间
- `delete_time`: 软删除时间

## 功能特性

### 1. 薪酬管理

- **新增薪酬**: 为员工设置薪酬信息，包括基本工资、绩效工资、各类补贴等
- **编辑薪酬**: 修改员工的薪酬信息
- **删除薪酬**: 删除薪酬记录（软删除）
- **薪酬历史**: 支持查看员工的薪酬变更历史
- **自动计算**: 总薪酬自动计算，无需手动输入

### 2. 薪酬状态管理

- **有效/失效**: 每个薪酬记录都有状态标识
- **自动失效**: 设置新的有效薪酬时，该员工的其他有效薪酬自动失效
- **生效日期**: 支持设置薪酬的生效日期和结束日期

### 3. 统计功能

- **在职人员数**: 统计当前有效薪酬的人员数量
- **平均薪酬**: 计算所有有效薪酬的平均值
- **薪酬总额**: 统计所有有效薪酬的总和
- **最高/最低薪酬**: 显示薪酬范围

### 4. 查询筛选

- **关键词搜索**: 支持按姓名、手机号搜索
- **状态筛选**: 可筛选有效或失效的薪酬记录
- **分页显示**: 支持分页查看，可调整每页显示数量

## 使用说明

### 1. 访问入口

登录管理后台 → 企业管理 → 薪酬管理标签页

### 2. 新增薪酬

1. 点击"新增薪酬"按钮
2. 选择员工（从人员管理中已录入的员工）
3. 填写薪酬信息：
   - 基本工资（必填）
   - 绩效工资
   - 岗位津贴
   - 住房补贴
   - 餐补
   - 交通补贴
   - 其他补贴
4. 设置生效日期（必填）
5. 选择状态（有效/失效）
6. 填写备注（可选）
7. 点击"保存"

### 3. 编辑薪酬

1. 在列表中找到要编辑的薪酬记录
2. 点击"编辑"按钮
3. 修改薪酬信息
4. 点击"保存"

### 4. 删除薪酬

1. 在列表中找到要删除的薪酬记录
2. 点击"删除"按钮
3. 确认删除

## API接口

### 后端接口

**路由前缀**: `/admin/api/personnel-salary`

- `GET /personnel-salary` - 获取薪酬列表
- `GET /personnel-salary/:id` - 获取薪酬详情
- `GET /personnel-salary/current/:personnelId` - 获取人员当前有效薪酬
- `POST /personnel-salary` - 创建薪酬记录
- `PUT /personnel-salary/:id` - 更新薪酬记录
- `DELETE /personnel-salary/:id` - 删除薪酬记录
- `GET /personnel-salary/statistics` - 获取薪酬统计
- `GET /personnel-salary/personnel-options` - 获取人员选项

### 前端API

位置: `frontend/admin/src/api/enterprise.js`

```javascript
// 获取人员薪酬列表
getPersonnelSalaryList(params)

// 获取人员薪酬详情
getPersonnelSalaryDetail(id)

// 根据人员ID获取当前有效薪酬
getCurrentSalaryByPersonnel(personnelId)

// 创建人员薪酬记录
createPersonnelSalary(data)

// 更新人员薪酬记录
updatePersonnelSalary(id, data)

// 删除人员薪酬记录
deletePersonnelSalary(id)

// 获取人员选项
getPersonnelOptions()

// 获取薪酬统计
getPersonnelSalaryStatistics()
```

## 文件清单

### 后端文件

1. **数据库**
   - `backend/sql/create_personnel_salary_table.sql` - 数据库表创建脚本

2. **Model**
   - `backend/app/model/PersonnelSalary.php` - 薪酬模型

3. **Controller**
   - `backend/app/controller/admin/PersonnelSalary.php` - 薪酬控制器

4. **路由**
   - `backend/route/admin.php` - 添加了薪酬管理路由

### 前端文件

1. **API**
   - `frontend/admin/src/api/enterprise.js` - 添加了薪酬管理API

2. **组件**
   - `frontend/admin/src/components/personnel/PersonnelSalaryManage.vue` - 薪酬管理组件

3. **页面**
   - `frontend/admin/src/views/admin/EnterpriseManage.vue` - 添加了薪酬管理标签页

## 注意事项

1. **权限控制**: 薪酬管理功能继承企业管理模块的权限设置
2. **数据关联**: 薪酬记录与人员表关联，删除人员时需要考虑薪酬记录的处理
3. **状态管理**: 设置新的有效薪酬时，系统会自动将该员工的其他有效薪酬设为失效
4. **软删除**: 薪酬记录采用软删除机制，删除后数据仍保留在数据库中
5. **金额精度**: 所有金额字段保留2位小数

## 后续优化建议

1. **薪酬调整审批**: 添加薪酬调整的审批流程
2. **薪酬报表**: 增加薪酬统计报表和图表展示
3. **批量导入**: 支持批量导入薪酬数据
4. **薪酬模板**: 支持创建薪酬模板，快速应用到多个员工
5. **薪酬计算器**: 提供薪酬计算工具，包括五险一金等扣除项
6. **薪酬对比**: 支持不同时期的薪酬对比分析
7. **导出功能**: 支持导出薪酬数据为Excel
