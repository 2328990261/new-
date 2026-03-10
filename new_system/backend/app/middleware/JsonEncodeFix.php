<?php
declare (strict_types = 1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * JSON 编码修复中间件
 * 用途：处理 UTF-8 编码问题，防止 json_encode 失败
 */
class JsonEncodeFix
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // 只处理 JSON 响应
        if ($response->getHeader('Content-Type') === 'application/json') {
            $content = $response->getContent();
            
            // 如果内容为空或已经是有效 JSON，直接返回
            if (empty($content) || $this->isValidJson($content)) {
                return $response;
            }
            
            // 尝试修复并重新编码
            try {
                $data = json_decode($content, true);
                if ($data !== null) {
                    // 清理数据中的无效 UTF-8 字符
                    $cleanData = $this->cleanUtf8($data);
                    $newContent = json_encode($cleanData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    
                    if ($newContent !== false) {
                        $response = Response::create($newContent, 'json');
                    }
                }
            } catch (\Exception $e) {
                // 记录错误但不中断响应
                trace('JSON encode fix error: ' . $e->getMessage(), 'error');
            }
        }
        
        return $response;
    }
    
    /**
     * 检查是否为有效的 JSON
     *
     * @param string $string
     * @return bool
     */
    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * 递归清理数据中的无效 UTF-8 字符
     *
     * @param mixed $data
     * @return mixed
     */
    private function cleanUtf8($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8'], $data);
        }
        
        if (is_string($data)) {
            // 方法 1: 使用 mb_convert_encoding 清理
            $clean = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            
            // 方法 2: 如果还有问题，使用更激进的清理
            if (!mb_check_encoding($clean, 'UTF-8')) {
                // 移除所有非 UTF-8 字符
                $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean);
                // 再次转换
                $clean = mb_convert_encoding($clean, 'UTF-8', 'UTF-8');
            }
            
            return $clean;
        }
        
        return $data;
    }
}
