<?php

namespace app\controller\admin;

use app\BaseController;
use app\service\KuaipaoService;
use think\facade\Request;

class KuaipaoImage extends BaseController
{
    /**
     * 线上排查：确认 .env / config 是否读取到快跑配置（仅管理端登录后可访问）
     * GET /admin/api/kuaipao/debug-config
     */
    public function debugConfig()
    {
        $baseUrl = (string)config('kuaipao.base_url', '');
        $apiKey = (string)config('kuaipao.api_key', '');

        $masked = '';
        if ($apiKey !== '') {
            $len = strlen($apiKey);
            $masked = $len <= 10 ? str_repeat('*', $len) : substr($apiKey, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($apiKey, -4);
        }

        return json([
            'success' => true,
            'data' => [
                'KUAIPAO_BASE_URL' => $baseUrl,
                'KUAIPAO_API_KEY_MASKED' => $masked,
                'KUAIPAO_API_KEY_SET' => $apiKey !== '',
            ],
        ]);
    }

    /**
     * 文生图（OpenAI兼容）
     * POST /admin/api/kuaipao/image
     *
     * 入参：
     * - prompt: string 提示词
     * - model: gpt-image-2 / gpt-image-2-pro / gpt-image-1.5
     * - size: 输出尺寸（1024x1024 / 1536x1024 / auto等）
     * - quality: 生成质量（low / medium / high / auto，默认auto）
     * - n: 生成数量（默认1，最多4）
     * - output_format: 输出格式（png / jpeg / webp，默认png）
     * - output_compression: 压缩率（0-100，仅用于jpeg和webp）
     * - background: 背景模式（auto / opaque / transparent，默认auto）
     * - moderation: 审核强度（auto / low，默认auto）
     * - response_format: b64_json 或 url（默认 b64_json）
     */
    public function generate()
    {
        // 文生图可能耗时较长，避免默认 60s 超时导致「页面错误」
        @set_time_limit(180);

        $body = Request::post();
        if (!is_array($body)) {
            $body = [];
        }

        $prompt = trim((string)($body['prompt'] ?? ''));
        if ($prompt === '') {
            return json(['success' => false, 'message' => 'prompt 不能为空']);
        }

        $model = trim((string)($body['model'] ?? 'gpt-image-2'));
        $size = trim((string)($body['size'] ?? 'auto'));
        $quality = trim((string)($body['quality'] ?? 'auto'));
        $outputFormat = trim((string)($body['output_format'] ?? 'png'));
        $background = trim((string)($body['background'] ?? 'auto'));
        $moderation = trim((string)($body['moderation'] ?? 'auto'));
        $n = (int)($body['n'] ?? 1);
        if ($n < 1) $n = 1;
        if ($n > 4) $n = 4;

        $payload = [
            'prompt' => $prompt,
            'model' => $model !== '' ? $model : 'gpt-image-2',
            'size' => $size !== '' ? $size : 'auto',
            'quality' => $quality !== '' ? $quality : 'auto',
            'n' => $n,
            'output_format' => $outputFormat !== '' ? $outputFormat : 'png',
            'background' => $background !== '' ? $background : 'auto',
            'moderation' => $moderation !== '' ? $moderation : 'auto',
            'response_format' => (string)($body['response_format'] ?? 'b64_json'),
        ];

        // 如果是jpeg或webp，添加压缩率参数
        if (($outputFormat === 'jpeg' || $outputFormat === 'webp') && isset($body['output_compression'])) {
            $compression = (int)$body['output_compression'];
            if ($compression >= 0 && $compression <= 100) {
                $payload['output_compression'] = $compression;
            }
        }

        // 记录请求参数
        \think\facade\Log::info('[kuaipao] 文生图请求参数', [
            'prompt_length' => strlen($prompt),
            'model' => $payload['model'],
            'size' => $payload['size'],
            'quality' => $payload['quality'],
            'output_format' => $payload['output_format'],
            'n' => $payload['n'],
        ]);

        $res = KuaipaoService::imageGenerations($payload);
        if (!$res['success']) {
            return json(['success' => false, 'message' => $res['message'] ?? '请求失败']);
        }

        $data = (array)($res['data'] ?? []);
        $imgs = [];
        foreach ((array)($data['data'] ?? []) as $item) {
            if (!is_array($item)) continue;
            
            $imgData = [];
            if (!empty($item['b64_json'])) {
                $imgData['type'] = 'b64_json';
                $imgData['b64'] = (string)$item['b64_json'];
                
                // 尝试从base64数据中获取图片尺寸
                try {
                    $imageData = base64_decode($item['b64_json']);
                    $image = @imagecreatefromstring($imageData);
                    if ($image !== false) {
                        $width = imagesx($image);
                        $height = imagesy($image);
                        $imgData['actual_size'] = "{$width}x{$height}";
                        imagedestroy($image);
                        
                        \think\facade\Log::info('[kuaipao] 文生图实际尺寸', [
                            'requested_size' => $size,
                            'requested_quality' => $quality,
                            'actual_size' => "{$width}x{$height}",
                        ]);
                    }
                } catch (\Throwable $e) {
                    // 忽略尺寸检测错误
                }
            } elseif (!empty($item['url'])) {
                $imgData['type'] = 'url';
                $imgData['url'] = (string)$item['url'];
            }
            
            if (!empty($imgData)) {
                $imgs[] = $imgData;
            }
        }

        return json([
            'success' => true,
            'data' => [
                'images' => $imgs,
                'raw' => $res['data'] ?? [],
            ],
        ]);
    }

    /**
     * 图生图
     * POST /admin/api/kuaipao/image-to-image
     *
     * 入参：
     * - prompt: string 提示词
     * - model: string 模型名称
     * - size: string 尺寸（注意：快跑API的图生图接口可能不支持size参数，实际尺寸由参考图决定）
     * - n: int 生成数量
     * - ref_images: array 参考图数组（base64格式，包含data:image前缀）
     * - response_format: string 返回格式（b64_json/url）
     * 
     * 注意：根据OpenAI API规范，/v1/images/edits端点不支持size参数，
     * 生成图片的尺寸由输入的参考图尺寸决定。如需特定尺寸，建议在提示词中说明。
     */
    public function imageToImage()
    {
        @set_time_limit(180);

        $body = Request::post();
        if (!is_array($body)) {
            $body = [];
        }

        $prompt = trim((string)($body['prompt'] ?? ''));
        if ($prompt === '') {
            return json(['success' => false, 'message' => 'prompt 不能为空']);
        }

        $refImages = $body['ref_images'] ?? [];
        if (!is_array($refImages) || count($refImages) === 0) {
            return json(['success' => false, 'message' => '参考图不能为空']);
        }

        $model = trim((string)($body['model'] ?? 'gpt-image-2'));
        $size = trim((string)($body['size'] ?? 'auto'));
        $n = (int)($body['n'] ?? 1);
        if ($n < 1) $n = 1;
        if ($n > 4) $n = 4;

        // 构建请求payload - 快跑API需要image字段（使用第一张参考图）
        $payload = [
            'prompt' => $prompt,
            'model' => $model !== '' ? $model : 'gpt-image-2',
            'size' => $size !== '' ? $size : 'auto',
            'n' => $n,
            'image' => $refImages[0], // 快跑API使用image字段，传第一张图
            'response_format' => (string)($body['response_format'] ?? 'b64_json'),
        ];

        // 如果有多张参考图，后续的作为额外参数
        if (count($refImages) > 1) {
            $payload['additional_images'] = array_slice($refImages, 1);
        }

        // 记录请求参数（不包含图片数据）
        \think\facade\Log::info('[kuaipao] imageToImage request', [
            'prompt' => $prompt,
            'model' => $model,
            'size' => $size,
            'n' => $n,
            'image_count' => count($refImages),
        ]);

        $res = KuaipaoService::imageToImage($payload);
        if (!$res['success']) {
            return json(['success' => false, 'message' => $res['message'] ?? '请求失败']);
        }

        $data = (array)($res['data'] ?? []);
        
        // 记录响应信息
        \think\facade\Log::info('[kuaipao] imageToImage response', [
            'image_count' => count($data['data'] ?? []),
            'raw_keys' => array_keys($data),
        ]);
        
        $imgs = [];
        foreach ((array)($data['data'] ?? []) as $item) {
            if (!is_array($item)) continue;
            
            $imgData = [];
            if (!empty($item['b64_json'])) {
                $imgData['type'] = 'b64_json';
                $imgData['b64'] = (string)$item['b64_json'];
                
                // 尝试从base64数据中获取图片尺寸
                try {
                    $imageData = base64_decode($item['b64_json']);
                    $image = @imagecreatefromstring($imageData);
                    if ($image !== false) {
                        $width = imagesx($image);
                        $height = imagesy($image);
                        $imgData['actual_size'] = "{$width}x{$height}";
                        imagedestroy($image);
                        
                        \think\facade\Log::info('[kuaipao] 图片实际尺寸', [
                            'requested_size' => $size,
                            'actual_size' => "{$width}x{$height}",
                        ]);
                    }
                } catch (\Throwable $e) {
                    // 忽略尺寸检测错误
                }
            } elseif (!empty($item['url'])) {
                $imgData['type'] = 'url';
                $imgData['url'] = (string)$item['url'];
            }
            
            if (!empty($imgData)) {
                $imgs[] = $imgData;
            }
        }

        return json([
            'success' => true,
            'data' => [
                'images' => $imgs,
                'raw' => $res['data'] ?? [],
            ],
        ]);
    }
}

