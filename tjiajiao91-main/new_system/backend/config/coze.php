<?php
// +----------------------------------------------------------------------
// | Coze 工作流配置（简历智能分析等）
// +----------------------------------------------------------------------
// 请在 .env 中配置 COZE_RESUME_TOKEN，不要将 Token 提交到仓库
return [
    // 简历解析工作流：执行接口地址（.env 中 COZE_RESUME_RUN_URL）
    'resume_run_url' => env('COZE_RESUME_RUN_URL', 'https://g4w3zctmc2.coze.site/run'),
    // 简历解析工作流：API Token（.env 中 COZE_RESUME_TOKEN，在 Coze 部署页「API Token」处查看/生成）
    'resume_token'   => env('COZE_RESUME_TOKEN', ''),
];
