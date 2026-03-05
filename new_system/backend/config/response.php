<?php
// ThinkPHP Response 配置
return [
    // JSON 编码参数 - 添加 JSON_INVALID_UTF8_SUBSTITUTE 来自动替换无效字符
    'json_encode_param' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
];
