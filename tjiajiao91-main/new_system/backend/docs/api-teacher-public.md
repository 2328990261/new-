# 公开接口：教师列表与教师详情

> 对应路由：`route/api.php`  
> 对应控制器：`app/controller/api/Teacher.php`  
> 生产环境 Base URL 示例：`https://t.jiajiao91.com/api`

**说明：** 以下接口均为公开接口，**无需**登录 Token，返回 JSON。

---

## 1. 教师列表

### 基本信息

| 项 | 值 |
|----|-----|
| 方法 | `GET` |
| 路径 | `/teacher/list` |
| 完整 URL 示例 | `https://t.jiajiao91.com/api/teacher/list` |

### 功能说明

分页查询**审核已通过**（`review_status = approved`）的教师，支持关键词、性别、教师类型、科目、城市等筛选；可传用户经纬度用于距离展示与「按距离排序」。

### 请求参数（Query）

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `page` | int | 否 | `1` | 页码 |
| `limit` | int | 否 | `12` | 每页条数 |
| `keyword` | string | 否 | 空 | 模糊搜索：姓名、学校、专业、`subject_names`、自我介绍、教学经历（`experience`） |
| `gender` | string | 否 | 空 | 性别筛选（与库中存储一致，如 `男`、`女`） |
| `teacher_type` | string | 否 | 空 | 教师类型，例如：`undergraduate`、`graduate_student`、`doctoral_student`、`graduated`、`professional`（与注册选项一致） |
| `subjects` | string 或 array | 否 | 空 | 多科目筛选；字符串时为**逗号分隔**的科目名，对 `subject_names` 做 OR 匹配 |
| `subject_id` | int | 否 | `0` | 单科目 ID（兼容旧接口），`>0` 时用 `FIND_IN_SET` 匹配 `subject_ids` |
| `city_id` | int | 否 | `0` | 城市/区域相关 ID，`>0` 时用 `FIND_IN_SET` 匹配 `district_ids` |
| `latitude` | float | 否 | `0` | 用户纬度（WGS84），用于距离与排序 |
| `longitude` | float | 否 | `0` | 用户经度 |
| `sort` | string | 否 | 空 | 排序：`latest` = 按入驻时间最新；`distance` = 距离最近（**需有效经纬度**，否则按默认排序）；其它或空 = **置顶优先**，再按创建时间降序 |

### 成功响应

HTTP `200`，Body 示例结构：

```json
{
  "success": true,
  "data": {
    "list": [],
    "total": 181,
    "page": 1,
    "limit": 12
  }
}
```

#### `data.list[]` 主要字段说明

| 字段 | 说明 |
|------|------|
| `id` | 教师主键 |
| `teacher_no` | 教师编号 |
| `name`, `gender`, `birth_date`, `education`, `school`, `major` | 基本信息 |
| `teacher_type`, `grade_level`, `education_level`, `hourly_rate` | 类型 / 年级 / 学历档位 / 时薪 |
| `subject_names`, `district_names` | 库中原始字段；列表中还会加工出 `subjects`、区域数组等 |
| `photos` | 原始存储（常为 JSON 字符串） |
| `self_intro`, `experience` | 简介与经历（字符串） |
| `advantage_tags` | JSON 解析后为**字符串数组** |
| `is_top`, `status` | 置顶、状态 |
| `real_name_verified`, `education_verified`, `teacher_verified` | 认证标记（0/1） |
| `location_longitude`, `location_latitude`, `location_address` | 位置 |
| `avatar`, `cover` | 由 `photos` 解析并补全域名后的展示图 |
| `subjects` | 优先来自授课信息表 `teacher_teaching_info`，否则来自 `subject_names` 拆分 |
| `city_name`, `district_name` | 由 `district_names` 推导；缺省时可能为「未知城市」「未知区域」 |
| `is_verified` | 任一实名/学历/教师认证为真则为 `true` |
| `distance`, `distance_text` | 传入用户经纬度且教师有坐标时：距离数值与格式化文案；否则 `distance` 为 `null`，`distance_text` 为空字符串 |

### 失败响应

```json
{
  "success": false,
  "error": "错误信息"
}
```

例如教师表不存在、服务端异常等。

---

## 2. 教师详情

### 基本信息

| 项 | 值 |
|----|-----|
| 方法 | `GET` |
| 路径 | `/teacher/detail/:id` |
| 完整 URL 示例 | `https://t.jiajiao91.com/api/teacher/detail/363` |

**重要：** 路径中的 `:id` 必须替换为教师的**数字主键**（如 `363`）。  
若写成 `https://t.jiajiao91.com/api/teacher/detail/id`，会被当作非法 ID，返回「教师不存在」。

### 功能说明

按教师主键返回公开详情字段；**不返回**手机、openid、邮箱、微信等敏感信息。

### 路径参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `id` | int | 是 | 表 `fa_teachers` 的主键 `id` |

### 请求参数（Query）

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `latitude` | float | 否 | `0` | 用户纬度，用于 `distance` / `distance_text` |
| `longitude` | float | 否 | `0` | 用户经度 |

### 成功响应

HTTP `200`，Body 示例结构：

```json
{
  "success": true,
  "data": {
    "id": 363,
    "teacher_no": 1279,
    "name": "",
    "gender": "",
    "birth_date": "",
    "hometown": "",
    "teaching_years": "",
    "education": "",
    "school": "",
    "major": "",
    "teacher_type": "",
    "grade_level": "",
    "education_level": "",
    "hourly_rate": "",
    "subject_ids": "",
    "subject_names": "",
    "district_ids": "",
    "district_names": "",
    "photos": "",
    "self_intro": "",
    "experience": "",
    "advantage_tags": [],
    "is_top": 0,
    "status": "",
    "real_name_verified": 0,
    "education_verified": 0,
    "teacher_verified": 0,
    "location_longitude": "",
    "location_latitude": "",
    "location_address": "",
    "create_time": "",
    "avatar": "https://...",
    "teaching_photos": ["https://..."],
    "subjects": [],
    "experiences": [],
    "is_verified": true,
    "distance": null,
    "distance_text": ""
  }
}
```

#### 字段补充说明

- `avatar`、`teaching_photos`：由 `photos` 解析并补全为绝对 URL。
- `subjects`：由 `subject_names` 按逗号拆分。
- `experiences`：将 `experience` 字段按 JSON 解析得到的数组；解析失败则为 `[]`。
- `distance` / `distance_text`：规则与列表一致（需用户与教师双方有效坐标）。

### 失败响应

```json
{
  "success": false,
  "error": "教师不存在"
}
```

或其它 `success: false` 与 `error` 说明。

---

## 列表与详情的行为差异（对接时注意）

| 项目 | 列表 `/teacher/list` | 详情 `/teacher/detail/:id` |
|------|------------------------|----------------------------|
| 审核状态 | 仅返回 `review_status = approved` | **未**按审核状态过滤，存在即返回 |
| 教学经历 | 返回原始 `experience` 字符串 | 另提供解析后的 `experiences` 数组 |
| 科目来源 | 可能合并 `teacher_teaching_info` | 主要来自 `subject_names` 拆分 |
| 其它 | 含 `cover`、列表用距离等 | 含 `hometown`、`teaching_years`、`subject_ids`、`district_ids`、`create_time` 等 |

---

*文档版本：与仓库内 `Teacher.php` 实现同步，若接口有变更请同步更新本文档。*
