# 教师库功能说明

## 📋 功能概述

教师库页面从数据库 `fa_teachers` 表中获取真实的教师数据并渲染展示。

## 🔧 已完成的修改

### 1. 后端API
- **文件**: `tjiajiao91/backend/app/controller/api/Teacher.php`
- **接口**: `GET /api/teacher/list`
- **功能**: 
  - 从 `fa_teachers` 表查询教师数据
  - 支持关键词搜索（姓名、学校、专业、科目、简介）
  - 支持筛选（性别、教师类型、科目）
  - 支持分页
  - 只显示审核通过的教师 (`review_status = 'approved'`)

### 2. 前端页面
- **文件**: `miniprogram/pages/teacher-library/index.vue`
- **修改**:
  - ✅ 移除了模拟数据方法 `getMockTeacherData()`
  - ✅ 使用真实API调用 `teacherApi.getList()`
  - ✅ 添加了详细的日志输出
  - ✅ 优化了数据加载逻辑

## 📊 数据字段映射

### 后端返回字段
```javascript
{
  id: 1,                          // 教师ID
  name: "张老师",                  // 姓名
  gender: "男",                    // 性别
  avatar: "/uploads/...",         // 头像
  school: "北京大学",              // 学校
  major: "数学与应用数学",         // 专业
  teacher_type: "undergraduate",  // 教师类型
  grade_level: "junior",          // 年级（在读学生）
  education_level: "bachelor",    // 学历（毕业生/专职）
  subjects: ["数学", "物理"],      // 授课科目
  advantage_tags: ["耐心细致"],    // 优势标签
  is_top: true,                   // 是否置顶
  is_verified: true,              // 是否认证
  self_intro: "...",              // 自我介绍
  experience: "...",              // 教学经历
  hourly_rate: 150,               // 时薪
  district_names: ["北京", "海淀区"] // 区域
}
```

### 前端显示内容
- **第一行**: 姓名 + 认证标签 | 性别 | 身份类型
- **第二行**: 学历/年级 | 学校 | 专业
- **第三行**: 授课科目标签
- **第四行**: 优势标签（最多3个）
- **底部**: 预约按钮

## 🎨 UI特性

### 1. 精选标签
- 左上角红色渐变标签
- 带星星图标
- 条件: `is_top = true`

### 2. 认证标签
- 姓名旁边的橙色标签
- 条件: `real_name_verified` 或 `education_verified` 或 `teacher_verified` 任一为true

### 3. 筛选功能
- **类型筛选**: 本科生、研究生、博士生、毕业生、专职教师
- **性别筛选**: 男、女
- **科目筛选**: 多选，支持18个科目

### 4. 搜索功能
- 支持搜索: 姓名、学校、专业、科目、简介内容

## 🔄 数据流程

```
用户操作 → 前端发起请求 → 后端查询数据库 → 返回数据 → 前端渲染
```

### 请求示例
```javascript
GET /api/teacher/list?page=1&limit=10&keyword=数学&gender=男&teacher_type=undergraduate&subjects=数学,物理
```

### 响应示例
```json
{
  "success": true,
  "data": {
    "list": [...],
    "total": 50,
    "page": 1,
    "limit": 10
  }
}
```

## 📝 注意事项

### 1. 数据库要求
- 表名: `fa_teachers`
- 必须有审核通过的教师数据 (`review_status = 'approved'`)
- 照片字段 `photos` 格式: JSON `{"avatar": "url", "teaching_photos": [...]}`

### 2. 图片处理
- 头像优先从 `photos.avatar` 获取
- 如果没有头像，显示默认图标
- 图片加载失败会显示占位符

### 3. 性能优化
- 分页加载，每页10条
- 下拉刷新
- 上拉加载更多
- 防止重复请求

## 🐛 调试信息

前端会输出详细日志：
```javascript
console.log('请求教师列表，参数:', params)
console.log('教师列表响应:', response)
console.log('教师列表加载成功，当前数量:', count, '总数:', total)
```

可以在微信开发者工具的控制台查看。

## 🚀 测试步骤

1. **确保后端运行**: http://localhost:8000/
2. **确保数据库有数据**: `fa_teachers` 表中有审核通过的教师
3. **打开小程序**: 进入教师库页面
4. **查看控制台**: 检查API请求和响应
5. **测试功能**:
   - 下拉刷新
   - 上拉加载更多
   - 搜索功能
   - 筛选功能
   - 点击教师卡片跳转详情

## 📞 相关文件

- 前端页面: `miniprogram/pages/teacher-library/index.vue`
- 后端控制器: `tjiajiao91/backend/app/controller/api/Teacher.php`
- API工具: `miniprogram/utils/api.js`
- 数据库表: `fa_teachers`

## ✅ 完成状态

- ✅ 后端API已实现
- ✅ 前端页面已修改
- ✅ 移除模拟数据
- ✅ 添加日志输出
- ✅ 优化数据加载
- ✅ 支持筛选和搜索

现在可以在小程序中测试教师库功能了！
