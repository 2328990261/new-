# Coze 简历解析配置

教师端「从简历文件导入」会调用 Coze 工作流解析简历，需在**后端**配置 Token。

## 1. 在 Coze 拿到 API Token

- 打开 Coze 部署页 → 执行工作流 → Header 里点击 **「API Token」** → 查看/生成并复制 Token。

## 2. 在后端配置

`.env` 里**默认没有** Coze 的配置项，需要你**自己加**。

1. 打开**后端项目根目录**下的 `.env` 文件（没有的话可复制 `.env.example` 为 `.env`）。
2. 在文件末尾**追加**下面两行（把 `你的Token` 换成在 Coze 部署页复制的 API Token）：

```ini
# Coze 简历智能分析工作流
COZE_RESUME_TOKEN=你的Token
COZE_RESUME_RUN_URL=https://g4w3zctmc2.coze.site/run
```

3. 保存后重启 PHP 或 Web 服务使配置生效。

- `COZE_RESUME_TOKEN`：必填，从 Coze 部署页「API Token」获取。
- `COZE_RESUME_RUN_URL`：可选，默认即上面地址，若你改了部署域名再改这里。

## 3. 接口说明

- **地址**：`POST /api/teacher-register/parse-resume`
- **入参**：
  - 方式一：表单上传文件，字段名 `file` 或 `resume`，支持 pdf、doc、docx、jpg、jpeg、png，不超过 15MB。
  - 方式二：`resume_url`（可公网访问的简历文件 URL）、可选 `file_type`（pdf/doc/image）。
- **返回**：`{ "success": true, "data": <Coze 工作流返回的解析结果> }`，前端可据此回填教师注册表单。
