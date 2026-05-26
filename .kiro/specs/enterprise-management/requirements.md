# 需求文档 - 企业管理模块

## 简介

企业管理模块是管理端的一个新功能模块，用于管理企业人员信息和薪酬数据。该模块包含两个子功能：人员管理和薪酬管理，通过顶部Tab切换栏进行切换。模块采用与现有"基础配置"模块相同的UI风格，提供完整的CRUD（创建、读取、更新、删除）操作。

## 术语表

- **Enterprise_Management_Module**: 企业管理模块，包含人员管理和薪酬管理两个子模块
- **Personnel_Management**: 人员管理子模块，用于管理员工的基本信息、在职状态和雇佣类型
- **Salary_Management**: 薪酬管理子模块，用于管理员工的月度薪资信息
- **Tab_Switcher**: 顶部切换栏组件，用于在人员管理和薪酬管理之间切换
- **Admin_User**: 管理端用户，具有访问和操作企业管理模块的权限
- **Personnel_Record**: 人员记录，包含员工的姓名、状态、类型等信息
- **Salary_Record**: 薪酬记录，包含员工ID、所属月份、薪资金额等信息
- **Employment_Status**: 雇佣状态，包括"在职"和"离职"两种状态
- **Employment_Type**: 雇佣类型，包括"全职"和"兼职"两种类型

## 需求

### 需求 1: 模块导航与Tab切换

**用户故事:** 作为管理员，我希望能够在企业管理模块的两个子功能之间快速切换，以便高效地管理人员和薪酬数据。

#### 验收标准

1. THE Enterprise_Management_Module SHALL display a Tab_Switcher at the top of the page with two tabs labeled "人员管理" and "薪酬管理"
2. WHEN the Admin_User clicks on the "人员管理" tab, THE Enterprise_Management_Module SHALL display the Personnel_Management interface
3. WHEN the Admin_User clicks on the "薪酬管理" tab, THE Enterprise_Management_Module SHALL display the Salary_Management interface
4. THE Tab_Switcher SHALL highlight the currently active tab with a visual indicator
5. WHEN switching between tabs, THE Enterprise_Management_Module SHALL preserve the current page number and filter state of each tab

### 需求 2: 人员信息管理

**用户故事:** 作为管理员，我希望能够录入、查看、编辑和删除人员信息，以便维护准确的员工数据库。

#### 验收标准

1. THE Personnel_Management SHALL display a table showing all Personnel_Records with columns for name, Employment_Status, Employment_Type, and action buttons
2. WHEN the Admin_User clicks the "新增" button, THE Personnel_Management SHALL display a dialog form for creating a new Personnel_Record
3. WHEN the Admin_User submits a valid new Personnel_Record, THE Personnel_Management SHALL save the record to the database and refresh the table
4. WHEN the Admin_User clicks the "编辑" button for a Personnel_Record, THE Personnel_Management SHALL display a dialog form pre-filled with the existing record data
5. WHEN the Admin_User submits valid changes to a Personnel_Record, THE Personnel_Management SHALL update the record in the database and refresh the table
6. WHEN the Admin_User clicks the "删除" button for a Personnel_Record, THE Personnel_Management SHALL display a confirmation dialog
7. WHEN the Admin_User confirms deletion, THE Personnel_Management SHALL remove the Personnel_Record from the database and refresh the table

### 需求 3: 雇佣状态管理

**用户故事:** 作为管理员，我希望能够设置和更新员工的在职状态，以便区分当前在职和已离职的员工。

#### 验收标准

1. THE Personnel_Management SHALL provide a dropdown field in the Personnel_Record form with options "在职" and "离职"
2. WHEN creating or editing a Personnel_Record, THE Admin_User SHALL select one Employment_Status from the available options
3. THE Personnel_Management SHALL display the Employment_Status in the table with a visual indicator such as a colored tag
4. THE Personnel_Management SHALL allow filtering Personnel_Records by Employment_Status
5. WHEN the Admin_User selects an Employment_Status filter, THE Personnel_Management SHALL display only Personnel_Records matching the selected status

### 需求 4: 雇佣类型管理

**用户故事:** 作为管理员，我希望能够标记员工的雇佣类型，以便区分全职和兼职员工。

#### 验收标准

1. THE Personnel_Management SHALL provide a dropdown field in the Personnel_Record form with options "全职" and "兼职"
2. WHEN creating or editing a Personnel_Record, THE Admin_User SHALL select one Employment_Type from the available options
3. THE Personnel_Management SHALL display the Employment_Type in the table
4. THE Personnel_Management SHALL allow filtering Personnel_Records by Employment_Type
5. WHEN the Admin_User selects an Employment_Type filter, THE Personnel_Management SHALL display only Personnel_Records matching the selected type

### 需求 5: 薪酬记录管理

**用户故事:** 作为管理员，我希望能够录入、查看、编辑和删除薪酬记录，以便管理员工的月度薪资信息。

#### 验收标准

1. THE Salary_Management SHALL display a table showing all Salary_Records with columns for employee name, month, salary amount, and action buttons
2. WHEN the Admin_User clicks the "新增" button, THE Salary_Management SHALL display a dialog form for creating a new Salary_Record
3. WHEN the Admin_User submits a valid new Salary_Record, THE Salary_Management SHALL save the record to the database and refresh the table
4. WHEN the Admin_User clicks the "编辑" button for a Salary_Record, THE Salary_Management SHALL display a dialog form pre-filled with the existing record data
5. WHEN the Admin_User submits valid changes to a Salary_Record, THE Salary_Management SHALL update the record in the database and refresh the table
6. WHEN the Admin_User clicks the "删除" button for a Salary_Record, THE Salary_Management SHALL display a confirmation dialog
7. WHEN the Admin_User confirms deletion, THE Salary_Management SHALL remove the Salary_Record from the database and refresh the table

### 需求 6: 薪酬月份管理

**用户故事:** 作为管理员，我希望能够为每条薪酬记录指定所属月份，以便按时间维度管理薪资数据。

#### 验收标准

1. THE Salary_Management SHALL provide a month picker field in the Salary_Record form
2. WHEN creating or editing a Salary_Record, THE Admin_User SHALL select a month in YYYY-MM format
3. THE Salary_Management SHALL display the month in the table in a readable format
4. THE Salary_Management SHALL allow filtering Salary_Records by month
5. WHEN the Admin_User selects a month filter, THE Salary_Management SHALL display only Salary_Records for the selected month
6. THE Salary_Management SHALL sort Salary_Records by month in descending order by default

### 需求 7: 薪资金额管理

**用户故事:** 作为管理员，我希望能够准确录入和显示薪资金额，以便维护正确的薪酬数据。

#### 验收标准

1. THE Salary_Management SHALL provide a numeric input field for salary amount in the Salary_Record form
2. WHEN the Admin_User enters a salary amount, THE Salary_Management SHALL validate that the value is a positive number
3. THE Salary_Management SHALL display salary amounts in the table formatted with two decimal places
4. THE Salary_Management SHALL display salary amounts with thousand separators for readability
5. IF the Admin_User enters an invalid salary amount, THEN THE Salary_Management SHALL display an error message and prevent form submission

### 需求 8: 查询和筛选功能

**用户故事:** 作为管理员，我希望能够快速查询和筛选人员及薪酬记录，以便找到特定的数据。

#### 验收标准

1. THE Personnel_Management SHALL provide filter controls for Employment_Status and Employment_Type above the table
2. THE Salary_Management SHALL provide filter controls for employee name and month above the table
3. WHEN the Admin_User clicks the "查询" button, THE system SHALL apply the selected filters and refresh the table
4. WHEN the Admin_User clicks the "重置" button, THE system SHALL clear all filters and display all records
5. THE system SHALL display a "暂无数据" message when no records match the current filters

### 需求 9: 分页功能

**用户故事:** 作为管理员，我希望能够分页浏览大量记录，以便提高页面加载性能和浏览体验。

#### 验收标准

1. THE Personnel_Management SHALL display a pagination control below the table
2. THE Salary_Management SHALL display a pagination control below the table
3. THE pagination control SHALL display the total number of records
4. THE pagination control SHALL allow the Admin_User to select page size from options including 10, 20, 50, and 100
5. WHEN the Admin_User changes the page number or page size, THE system SHALL load and display the corresponding records
6. THE system SHALL default to page 1 with 20 records per page

### 需求 10: 表单验证

**用户故事:** 作为管理员，我希望系统能够验证我输入的数据，以便防止无效数据进入数据库。

#### 验收标准

1. WHEN the Admin_User submits a Personnel_Record form, THE Personnel_Management SHALL validate that all required fields are filled
2. WHEN the Admin_User submits a Salary_Record form, THE Salary_Management SHALL validate that all required fields are filled
3. IF a required field is empty, THEN THE system SHALL display an error message next to the field and prevent form submission
4. IF the salary amount is not a positive number, THEN THE Salary_Management SHALL display an error message and prevent form submission
5. THE system SHALL display field-level validation errors in real-time as the Admin_User fills the form

### 需求 11: UI样式一致性

**用户故事:** 作为管理员，我希望企业管理模块的界面风格与现有模块保持一致，以便获得统一的用户体验。

#### 验收标准

1. THE Enterprise_Management_Module SHALL use Element Plus components consistent with existing admin modules
2. THE Enterprise_Management_Module SHALL use the same color scheme and typography as the PaymentConfig and MiniProgramConfigManage modules
3. THE Enterprise_Management_Module SHALL use el-card component to wrap the main content area
4. THE Enterprise_Management_Module SHALL use el-table with border and stripe attributes for data display
5. THE Enterprise_Management_Module SHALL use el-dialog for create and edit forms
6. THE Enterprise_Management_Module SHALL use el-button with type="primary" for primary actions and type="danger" for delete actions

### 需求 12: 数据持久化

**用户故事:** 作为管理员，我希望我创建和修改的数据能够被永久保存，以便下次访问时仍然可用。

#### 验收标准

1. WHEN the Admin_User creates a new Personnel_Record, THE system SHALL persist the record to the MySQL database
2. WHEN the Admin_User updates a Personnel_Record, THE system SHALL persist the changes to the MySQL database
3. WHEN the Admin_User deletes a Personnel_Record, THE system SHALL remove the record from the MySQL database
4. WHEN the Admin_User creates a new Salary_Record, THE system SHALL persist the record to the MySQL database
5. WHEN the Admin_User updates a Salary_Record, THE system SHALL persist the changes to the MySQL database
6. WHEN the Admin_User deletes a Salary_Record, THE system SHALL remove the record from the MySQL database
7. WHEN the Admin_User reloads the page, THE system SHALL retrieve and display all records from the MySQL database

### 需求 13: 错误处理和用户反馈

**用户故事:** 作为管理员，我希望在操作成功或失败时能够收到明确的反馈，以便了解操作结果。

#### 验收标准

1. WHEN a create operation succeeds, THE system SHALL display a success message using ElMessage
2. WHEN an update operation succeeds, THE system SHALL display a success message using ElMessage
3. WHEN a delete operation succeeds, THE system SHALL display a success message using ElMessage
4. IF a create operation fails, THEN THE system SHALL display an error message with failure details using ElMessage
5. IF an update operation fails, THEN THE system SHALL display an error message with failure details using ElMessage
6. IF a delete operation fails, THEN THE system SHALL display an error message with failure details using ElMessage
7. WHILE a data loading operation is in progress, THE system SHALL display a loading indicator on the table

### 需求 14: 员工关联

**用户故事:** 作为管理员，我希望薪酬记录能够关联到具体的员工，以便准确追踪每个员工的薪资历史。

#### 验收标准

1. THE Salary_Record form SHALL provide a dropdown field to select an employee from existing Personnel_Records
2. WHEN creating or editing a Salary_Record, THE Admin_User SHALL select one employee from the dropdown
3. THE Salary_Management SHALL display the employee name in the table by joining with Personnel_Records
4. THE system SHALL prevent deletion of a Personnel_Record that has associated Salary_Records
5. IF the Admin_User attempts to delete a Personnel_Record with associated Salary_Records, THEN THE system SHALL display an error message explaining the constraint
