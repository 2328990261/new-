<?php
declare (strict_types = 1);

namespace app;

use think\Response;

/**
 * 自定义 JSON 响应类
 * 用途：修复 UTF-8 编码问题
 */
class Json extends Response
{
    /**
     * 输出数据
     * @access protected
     * @param  mixed $data 要输出的数据
     * @return string
     */
    protected function output($data): string
    {
        try {
            // 清理数据中的无效 UTF-8 字符
            $data = $this->cleanUtf8Recursive($data);
            
            // 使用更宽松的 JSON 编码选项
            $options = $this->options['json_encode_param'] ?? 
                       (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
            
            $jsonData = json_encode($data, $options);
            
            // 如果还是失败，尝试更激进的清理
            if ($jsonData === false) {
                $data = $this->forceCleanUtf8($data);
                $jsonData = json_encode($data, $options);
            }
            
            // 如果还是失败，返回错误信息
            if ($jsonData === false) {
                $error = json_last_error_msg();
                return json_encode([
                    'code' => 500,
                    'msg' => 'JSON encode error: ' . $error,
                    'data' => null
                ]);
            }
            
            return $jsonData;
            
        } catch (\Exception $e) {
            return json_encode([
                'code' => 500,
                'msg' => 'Response error: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 递归清理 UTF-8 字符（温和方式）
     */
    private function cleanUtf8Recursive($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8Recursive'], $data);
        }
        
        if (is_object($data)) {
            $data = (array)$data;
            return (object)array_map([$this, 'cleanUtf8Recursive'], $data);
        }
        
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * 强制清理 UTF-8 字符（激进方式）
     */
    private function forceCleanUtf8($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'forceCleanUtf8'], $data);
        }
        
        if (is_object($data)) {
            $data = (array)$data;
            return (object)array_map([$this, 'forceCleanUtf8'], $data);
        }
        
        if (is_string($data)) {
            // 移除控制字符
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            
            // 使用 iconv 强制转换
            $data = @iconv('UTF-8', 'UTF-8//IGNORE', $data);
            
            // 如果还有问题，使用 mb_convert_encoding
            if ($data === false || !mb_check_encoding($data, 'UTF-8')) {
                $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            }
            
            return $data;
        }
        
        return $data;
    }
}
