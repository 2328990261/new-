# 薪酬管理功能实现说明

## 📋 功能概述

在企业管理模块中新增了**薪酬管理**功能，实现了对员工薪酬的全面管理，包括：

- ✅ 薪酬信息录入与编辑
- ✅ 薪酬历史记录追踪
- ✅ 薪酬统计与分析
- ✅ 多维度查询筛选
- ✅ 自动计算总薪酬

## 🎯 实现方案

采用**独立薪酬表**方案，与人员表关联，支持薪酬历史记录。

### 优势
- 支持薪酬变更历史追踪
- 灵活的薪酬结构设计
- 便于统计分析
- 数据独立，易于维护

## 📁 文件结构

### 后端文件

```
new_system/backend/
├── sql/
│   ├── create_personnel_salary_table.sql      # 数据库表创建脚本
│   ├── test_data_personnel_salary.sql         # 测试数据脚本
│   ├── deploy_personnel_salary.sh             # Linux部署脚本
│   ├── deploy_personnel_salary.bat            # Windows部署脚本
│   └── README_PERSONNEL_SALARY.md             # 详细说明文档
├── app/
│   ├── model/
│   │   └── PersonnelSalary.php                # 薪酬模型
│   └── controller/admin/
│       └── PersonnelSalary.php                # 薪酬控制器
└── route/
    └── admin.php                               # 路由配置（已更新）
```

### 前端文件

```
new_system/frontend/admin/src/
├── api/
│   └── enterprise.js                           # API接口（已更新）
├── components/personnel/
│   └── PersonnelSalaryManage.vue              # 薪酬管理组件
└── views/admin/
    └── EnterpriseManage.vue                    # 企业管理页面（已更新）
```

## 🚀 部署步骤

### 1. 数据库部署

#### 方式一：使用部署脚本（推荐）

**Linux/Mac:**
```bash
cd new_system/backend/sql
chmod +x deploy_personnel_salary.sh
# 编辑脚本，修改数据库配置
vi deploy_personnel_salary.sh
# 执行部署
./deploy_personnel_salary.sh
```

**Windows:**
```cmd
cd new_system\backend\sql
# 编辑脚本，修改数据库配置
notepad deploy_personnel_salary.bat
# 执行部署
deploy_personnel_salary.bat
```

#### 方式二：手动执行SQL

```bash
mysql -u用户名 -p密码 数据库名 < create_personnel_salary_table.sql
```

### 2. 后端部署

后端代码已经完成，无需额外操作。如果使用了缓存或OPcache，建议重启PHP服务：

```bash
# 重启PHP-FPM（根据实际情况选择）
systemctl restart php-fpm
# 或
service php-fpm restart
```

### 3. 前端部署

如果前端是开发模式，会自动热更新。如果是生产环境，需要重新编译：

```bash
cd new_system/frontend/admin
npm run build
```

### 4. 测试数据（可选）

如果需要测试数据，可以执行：

```bash
mysql -u用户名 -p密码 数据库名 < test_data_personnel_salary.sql
```

**注意**：测试数据脚本中的`personnel_id`需要根据实际的人员ID修改。

## 💡 使用说明

### 访问入口

1. 登录管理后台
2. 点击左侧菜单"企业管理"
3. 切换到"薪酬管理"标签页

### 功能操作

#### 1. 查看薪酬列表

- 列表显示所有员工的薪酬信息
- 包含姓名、手机、部门、岗位、各项薪酬明细、总薪酬、生效日期、状态等
- 支持分页显示

#### 2. 新增薪酬

1. 点击"新增薪酬"按钮
2. 选择员工（下拉框会显示所有已录入的员工）
3. 填写薪酬信息：
   - **基本工资**（必填）：员工的基础工资
   - **绩效工资**：根据绩效发放的工资
   - **岗位津贴**：岗位相关的津贴
   - **住房补贴**：住房相关补贴
   - **餐补**：餐饮补贴
   - **交通补贴**：交通相关补贴
   - **其他补贴**：其他类型的补贴
   - **总薪酬**：自动计算，无需手动输入
4. 设置**生效日期**（必填）
5. 可选设置**结束日期**（不填表示长期有效）
6. 选择**状态**：
   - 有效：当前生效的薪酬
   - 失效：已过期或被替换的薪酬
7. 填写**备注**（可选）
8. 点击"保存"

**重要提示**：
- 设置为"有效"时，该员工的其他有效薪酬会自动设为"失效"
- 每个员工同一时间只能有一条有效的薪酬记录

#### 3. 编辑薪酬

1. 在列表中找到要编辑的薪酬记录
2. 点击"编辑"按钮
3. 修改薪酬信息
4. 点击"保存"

**注意**：编辑时不能更改员工

#### 4. 删除薪酬

1. 在列表中找到要删除的薪酬记录
2. 点击"删除"按钮
3. 确认删除

**注意**：删除采用软删除机制，数据仍保留在数据库中

#### 5. 查询筛选

- **关键词搜索**：输入姓名或手机号进行搜索
- **状态筛选**：筛选有效或失效的薪酬记录
- 点击"查询"按钮执行搜索
- 点击"重置"按钮清空筛选条件

#### 6. 查看统计

页面顶部显示三个统计卡片：
- **在职人员**：当前有效薪酬的人员数量
- **平均薪酬**：所有有效薪酬的平均值
- **薪酬总额**：所有有效薪酬的总和

## 📊 数据库表结构

### fa_personnel_salary 表

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int(11) | 主键ID |
| personnel_id | int(11) | 人员ID（关联fa_personnel表） |
| base_salary | decimal(10,2) | 基本工资 |
| performance_salary | decimal(10,2) | 绩效工资 |
| post_allowance | decimal(10,2) | 岗位津贴 |
| housing_allowance | decimal(10,2) | 住房补贴 |
| meal_allowance | decimal(10,2) | 餐补 |
| transport_allowance | decimal(10,2) | 交通补贴 |
| other_allowance | decimal(10,2) | 其他补贴 |
| total_salary | decimal(10,2) | 总薪酬（自动计算） |
| effective_date | date | 生效日期 |
| end_date | date | 结束日期（NULL表示长期有效） |
| status | tinyint(1) | 状态（1=有效，0=失效） |
| remark | varchar(500) | 备注 |
| create_time | int(11) | 创建时间 |
| update_time | int(11) | 更新时间 |
| delete_time | int(11) | 软删除时间 |

### 索引

- PRIMARY KEY (`id`)
- KEY `idx_personnel_id` (`personnel_id`)
- KEY `idx_effective_date` (`effective_date`)
- KEY `idx_status` (`status`)

## 🔌 API接口

### 后端接口

**路由前缀**: `/admin/api/personnel-salary`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /personnel-salary | 获取薪酬列表 |
| GET | /personnel-salary/:id | 获取薪酬详情 |
| GET | /personnel-salary/current/:personnelId | 获取人员当前有效薪酬 |
| POST | /personnel-salary | 创建薪酬记录 |
| PUT | /personnel-salary/:id | 更新薪酬记录 |
| DELETE | /personnel-salary/:id | 删除薪酬记录 |
| GET | /personnel-salary/statistics | 获取薪酬统计 |
| GET | /personnel-salary/personnel-options | 获取人员选项 |

### 请求示例

#### 创建薪酬记录

```json
POST /admin/api/personnel-salary

{
  "personnel_id": 1,
  "base_salary": 8000.00,
  "performance_salary": 2000.00,
  "post_allowance": 1000.00,
  "housing_allowance": 1500.00,
  "meal_allowance": 500.00,
  "transport_allowance": 300.00,
  "other_allowance": 200.00,
  "effective_date": "2024-01-01",
  "end_date": null,
  "status": 1,
  "remark": "2024年薪酬标准"
}
```

#### 响应示例

```json
{
  "success": true,
  "message": "创建成功",
  "data": {
    "id": 1
  }
}
```

## 🎨 界面截图说明

### 薪酬管理主界面

- 顶部：统计卡片（在职人员、平均薪酬、薪酬总额）
- 中部：搜索筛选工具栏
- 下部：薪酬列表表格
- 底部：分页组件

### 新增/编辑薪酬弹窗

- 员工选择下拉框
- 薪酬信息输入区（基本工资、绩效工资、各类补贴）
- 总薪酬自动计算显示
- 生效日期和结束日期选择
- 状态选择（有效/失效）
- 备注输入框

## ⚠️ 注意事项

1. **权限控制**
   - 薪酬管理功能继承企业管理模块的权限设置
   - 只有具有企业管理权限的管理员才能访问

2. **数据关联**
   - 薪酬记录与人员表关联
   - 删除人员前需要考虑薪酬记录的处理

3. **状态管理**
   - 每个员工同一时间只能有一条有效的薪酬记录
   - 设置新的有效薪酬时，系统会自动将该员工的其他有效薪酬设为失效

4. **软删除**
   - 薪酬记录采用软删除机制
   - 删除后数据仍保留在数据库中，可以恢复

5. **金额精度**
   - 所有金额字段保留2位小数
   - 总薪酬自动计算，无需手动输入

6. **日期管理**
   - 生效日期为必填项
   - 结束日期为可选项，不填表示长期有效

## 🔧 故障排查

### 1. 无法访问薪酬管理页面

**可能原因**：
- 没有企业管理权限
- 前端代码未更新

**解决方法**：
- 检查管理员账号是否有企业管理权限
- 重新编译前端代码：`npm run build`

### 2. 保存薪酬时提示"人员不存在"

**可能原因**：
- 选择的人员ID不存在
- 人员已被删除

**解决方法**：
- 确认人员管理中存在该员工
- 重新选择有效的员工

### 3. 统计数据不准确

**可能原因**：
- 数据库中存在异常数据
- 缓存未更新

**解决方法**：
- 检查数据库中的薪酬记录
- 清除缓存后重试

### 4. 列表显示为空

**可能原因**：
- 数据库表未创建
- 没有薪酬数据
- API接口错误

**解决方法**：
- 检查数据库表是否存在
- 查看浏览器控制台是否有错误信息
- 检查后端日志

## 🚀 后续优化建议

1. **薪酬调整审批**
   - 添加薪酬调整的审批流程
   - 支持多级审批

2. **薪酬报表**
   - 增加薪酬统计报表
   - 图表展示薪酬分布
   - 薪酬趋势分析

3. **批量操作**
   - 批量导入薪酬数据
   - 批量调整薪酬
   - 批量导出薪酬数据

4. **薪酬模板**
   - 创建薪酬模板
   - 快速应用到多个员工
   - 模板管理功能

5. **薪酬计算器**
   - 提供薪酬计算工具
   - 包括五险一金等扣除项
   - 实发工资计算

6. **薪酬对比**
   - 不同时期的薪酬对比
   - 员工之间的薪酬对比
   - 部门薪酬对比

7. **导出功能**
   - 导出为Excel
   - 导出为PDF
   - 自定义导出字段

8. **权限细化**
   - 薪酬查看权限
   - 薪酬编辑权限
   - 薪酬审批权限

## 📞 技术支持

如有问题，请查看：
- 详细说明文档：`new_system/backend/sql/README_PERSONNEL_SALARY.md`
- 测试数据脚本：`new_system/backend/sql/test_data_personnel_salary.sql`

## 📝 更新日志

### v1.0.0 (2024-01-01)
- ✅ 初始版本发布
- ✅ 实现薪酬基本管理功能
- ✅ 支持薪酬历史记录
- ✅ 提供统计分析功能
- ✅ 完善的查询筛选功能
