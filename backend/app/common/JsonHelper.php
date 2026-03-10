<?php
declare (strict_types = 1);

namespace app\common;

/**
 * JSON 编码助手类
 * 用途：安全地处理 JSON 编码，避免 UTF-8 字符问题
 */
class JsonHelper
{
    /**
     * 安全的 JSON 编码
     * 
     * @param mixed $data 要编码的数据
     * @param int $options JSON 编码选项
     * @return string|false
     */
    public static function encode($data, int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    {
        // 先清理数据
        $cleanData = self::cleanUtf8($data);
        
        // 尝试编码
        $json = json_encode($cleanData, $options);
        
        // 如果失败，尝试更激进的清理
        if ($json === false && json_last_error() === JSON_ERROR_UTF8) {
            $cleanData = self::forceCleanUtf8($data);
            $json = json_encode($cleanData, $options);
        }
        
        return $json;
    }
    
    /**
     * 递归清理数据中的无效 UTF-8 字符（温和方式）
     * 
     * @param mixed $data
     * @return mixed
     */
    public static function cleanUtf8($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'cleanUtf8'], $data);
        }
        
        if (is_object($data)) {
            $data = (array)$data;
            return (object)array_map([self::class, 'cleanUtf8'], $data);
        }
        
        if (is_string($data)) {
            // 使用 mb_convert_encoding 清理无效字符
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * 强制清理无效 UTF-8 字符（激进方式）
     * 
     * @param mixed $data
     * @return mixed
     */
    public static function forceCleanUtf8($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'forceCleanUtf8'], $data);
        }
        
        if (is_object($data)) {
            $data = (array)$data;
            return (object)array_map([self::class, 'forceCleanUtf8'], $data);
        }
        
        if (is_string($data)) {
            // 移除控制字符
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
            
            // 转换编码
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            
            // 如果还有问题，使用 iconv
            if (!mb_check_encoding($data, 'UTF-8')) {
                $data = iconv('UTF-8', 'UTF-8//IGNORE', $data);
            }
            
            return $data;
        }
        
        return $data;
    }
    
    /**
     * 检查字符串是否为有效的 UTF-8
     * 
     * @param string $string
     * @return bool
     */
    public static function isValidUtf8(string $string): bool
    {
        return mb_check_encoding($string, 'UTF-8');
    }
}
