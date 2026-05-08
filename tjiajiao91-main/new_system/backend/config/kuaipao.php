<?php
// +----------------------------------------------------------------------
// | 快跑（kuaipao.ai）中转 API 配置
// +----------------------------------------------------------------------
// 说明：
// - base_url: 中转服务地址（不带尾部 /）
// - api_key: 你的密钥（不要提交到仓库）
return [
    'base_url' => rtrim((string)env('KUAIPAO_BASE_URL', 'https://kuaipao.ai'), '/'),
    'api_key'  => (string)env('KUAIPAO_API_KEY', ''),
];

