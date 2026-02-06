<?php
/**
 * 辅助函数文件
 * 用于清理 UTF-8 编码问题
 */

if (!function_exists('clean_utf8_data')) {
    /**
     * 清理数据中的无效 UTF-8 字符
     * 
     * @param mixed $data 要清理的数据
     * @return mixed 清理后的数据
     */
    function clean_utf8_data($data)
    {
        if (is_array($data)) {
            array_walk_recursive($data, function(&$value) {
                if (is_string($value)) {
                    // 使用 mb_convert_encoding 清理无效字符
                    $value = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    
                    // 如果还有问题，使用 iconv
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
                    }
                }
            });
            return $data;
        }
        
        if (is_string($data)) {
            $data = @mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            if (!mb_check_encoding($data, 'UTF-8')) {
                $data = @iconv('UTF-8', 'UTF-8//IGNORE', $data);
            }
            return $data;
        }
        
        return $data;
    }
}
