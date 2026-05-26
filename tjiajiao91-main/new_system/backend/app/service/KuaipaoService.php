<?php

namespace app\service;

use app\model\AiConfig;
use think\facade\Log;

class KuaipaoService
{
    /**
     * 读取 API 配置：优先从数据库，fallback 到 .env / config
     */
    private static function getApiConfig(): array
    {
        try {
            $dbConfig = AiConfig::getConfig();
            if ($dbConfig) {
                $baseUrl = (string)($dbConfig->base_url ?? '');
                $apiKey  = $dbConfig->getRawApiKey();
                if ($baseUrl !== '' && $apiKey !== '') {
                    return [
                        'base_url' => rtrim($baseUrl, '/'),
                        'api_key'  => $apiKey,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[kuaipao] 读取数据库配置失败，fallback 到 env: ' . $e->getMessage());
        }

        // fallback：从 .env / config/kuaipao.php 读取
        return [
            'base_url' => rtrim((string)config('kuaipao.base_url', 'https://kuaipao.ai'), '/'),
            'api_key'  => (string)config('kuaipao.api_key', ''),
        ];
    }

    public static function imageGenerations(array $payload): array
    {
        $cfg     = self::getApiConfig();
        $baseUrl = $cfg['base_url'];
        $apiKey  = $cfg['api_key'];

        if ($apiKey === '') {
            return ['success' => false, 'message' => 'KUAIPAO_API_KEY 未配置'];
        }

        $url = rtrim($baseUrl, '/') . '/v1/images/generations';
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['success' => false, 'message' => '请求体序列化失败'];
        }

        // 记录完整请求信息
        Log::info('[kuaipao] 文生图完整请求', [
            'url' => $url,
            'payload' => $payload,
            'json' => $json,
        ]);

        try {
            $raw = self::httpPostJson($url, $json, [
                'Authorization: Bearer ' . $apiKey,
            ]);
            
            // 记录原始响应（截取前1000字符）
            Log::info('[kuaipao] 文生图原始响应', [
                'response_preview' => substr($raw, 0, 1000),
                'response_length' => strlen($raw),
            ]);
            
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                return ['success' => false, 'message' => '上游返回非 JSON', 'raw' => $raw];
            }
            
            // 记录解析后的响应结构（不包含图片数据）
            $logData = $data;
            if (isset($logData['data']) && is_array($logData['data'])) {
                foreach ($logData['data'] as $idx => $item) {
                    if (isset($item['b64_json'])) {
                        $logData['data'][$idx]['b64_json'] = '[BASE64_DATA_LENGTH:' . strlen($item['b64_json']) . ']';
                    }
                }
            }
            Log::info('[kuaipao] 文生图解析后响应', $logData);
            
            return ['success' => true, 'data' => $data, 'raw' => $raw];
        } catch (\Throwable $e) {
            Log::error('[kuaipao] imageGenerations error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 图生图接口（快跑中转 - 兼容OpenAI格式）
     * 快跑通常使用 /v1/images/edits 端点
     */
    public static function imageToImage(array $payload): array
    {
        $cfg     = self::getApiConfig();
        $baseUrl = $cfg['base_url'];
        $apiKey  = $cfg['api_key'];

        if ($apiKey === '') {
            return ['success' => false, 'message' => 'KUAIPAO_API_KEY 未配置'];
        }

        // 快跑的图生图API端点（兼容OpenAI）
        $url = rtrim($baseUrl, '/') . '/v1/images/edits';
        
        // 记录发送的参数（不包含图片数据）
        Log::info('[kuaipao] imageToImage 发送参数', [
            'url' => $url,
            'model' => $payload['model'] ?? '',
            'size' => $payload['size'] ?? '',
            'n' => $payload['n'] ?? 1,
            'prompt_length' => strlen($payload['prompt'] ?? ''),
            'has_image' => isset($payload['image']),
            'has_additional' => isset($payload['additional_images']),
        ]);
        
        // 将payload转换为multipart/form-data格式
        // 因为OpenAI的图生图API需要使用form-data而不是JSON
        try {
            $raw = self::httpPostMultipart($url, $payload, [
                'Authorization: Bearer ' . $apiKey,
            ]);
            
            // 记录原始响应（截取前1000字符）
            Log::info('[kuaipao] imageToImage 原始响应', [
                'response_preview' => substr($raw, 0, 1000),
                'response_length' => strlen($raw),
            ]);
            
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                return ['success' => false, 'message' => '上游返回非 JSON', 'raw' => $raw];
            }
            
            // 记录解析后的响应结构（不包含图片数据）
            $logData = $data;
            if (isset($logData['data']) && is_array($logData['data'])) {
                foreach ($logData['data'] as $idx => $item) {
                    if (isset($item['b64_json'])) {
                        $logData['data'][$idx]['b64_json'] = '[BASE64_DATA_LENGTH:' . strlen($item['b64_json']) . ']';
                    }
                    if (isset($item['url'])) {
                        $logData['data'][$idx]['url'] = $item['url'];
                    }
                    // 记录图片的实际尺寸（如果API返回了）
                    if (isset($item['revised_prompt'])) {
                        $logData['data'][$idx]['revised_prompt'] = substr($item['revised_prompt'], 0, 100) . '...';
                    }
                }
            }
            Log::info('[kuaipao] imageToImage 解析后响应', $logData);
            
            return ['success' => true, 'data' => $data, 'raw' => $raw];
        } catch (\Throwable $e) {
            Log::error('[kuaipao] imageToImage error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 发送multipart/form-data请求（用于图生图）
     */
    private static function httpPostMultipart(string $url, array $data, array $extraHeaders = []): string
    {
        $boundary = '----WebKitFormBoundary' . uniqid();
        $body = '';

        foreach ($data as $key => $value) {
            if ($key === 'image') {
                // 处理主图片（必需）
                if (strpos($value, 'data:image') === 0) {
                    // 提取base64数据
                    $parts = explode(',', $value, 2);
                    if (count($parts) === 2) {
                        $decoded = base64_decode($parts[1]);
                        $mimeType = 'image/png';
                        
                        // 从data URL中提取mime type
                        if (preg_match('/data:(image\/[^;]+)/', $parts[0], $matches)) {
                            $mimeType = $matches[1];
                        }
                        
                        $filename = "image.png";
                        $body .= "--{$boundary}\r\n";
                        $body .= "Content-Disposition: form-data; name=\"image\"; filename=\"{$filename}\"\r\n";
                        $body .= "Content-Type: {$mimeType}\r\n\r\n";
                        $body .= $decoded . "\r\n";
                    }
                }
            } elseif ($key === 'additional_images' && is_array($value)) {
                // 处理额外的参考图（可选）
                foreach ($value as $idx => $imageData) {
                    if (strpos($imageData, 'data:image') === 0) {
                        $parts = explode(',', $imageData, 2);
                        if (count($parts) === 2) {
                            $decoded = base64_decode($parts[1]);
                            $mimeType = 'image/png';
                            
                            if (preg_match('/data:(image\/[^;]+)/', $parts[0], $matches)) {
                                $mimeType = $matches[1];
                            }
                            
                            $filename = "additional_image_{$idx}.png";
                            $body .= "--{$boundary}\r\n";
                            $body .= "Content-Disposition: form-data; name=\"additional_images[]\"; filename=\"{$filename}\"\r\n";
                            $body .= "Content-Type: {$mimeType}\r\n\r\n";
                            $body .= $decoded . "\r\n";
                        }
                    }
                }
            } else {
                // 普通字段
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
                $body .= $value . "\r\n";
            }
        }
        
        $body .= "--{$boundary}--\r\n";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $headers = array_merge([
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Content-Length: ' . strlen($body),
        ], $extraHeaders);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP请求失败: ' . $error);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status >= 400) {
            throw new \RuntimeException('上游HTTP错误: ' . $status . ' ' . (string)$result);
        }
        return (string)$result;
    }

    private static function httpPostJson(string $url, string $json, array $extraHeaders = []): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 文生图耗时可能较长
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $headers = array_merge([
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'Content-Length: ' . strlen($json),
        ], $extraHeaders);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP请求失败: ' . $error);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status >= 400) {
            throw new \RuntimeException('上游HTTP错误: ' . $status . ' ' . (string)$result);
        }
        return (string)$result;
    }
}

