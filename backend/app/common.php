<?php
// 应用公共函数文件

use app\Json;

// 引入辅助函数文件
require_once __DIR__ . '/helper.php';

if (!function_exists('safe_json')) {
    /**
     * 安全的 JSON 响应
     * 自动清理 UTF-8 字符
     * 
     * @param mixed $data 返回的数据
     * @param int $code HTTP 状态码
     * @param array $header 额外的响应头
     * @param array $options JSON 编码选项
     * @return Json
     */
    function safe_json($data = [], int $code = 200, array $header = [], array $options = []): Json
    {
        return Json::create($data, 'json', $code, $header, $options);
    }
}
