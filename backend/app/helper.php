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

if (!function_exists('calculate_distance')) {
    /**
     * 计算两个经纬度坐标之间的距离（单位：公里）
     * 使用 Haversine 公式
     * 
     * @param float $lat1 第一个点的纬度
     * @param float $lon1 第一个点的经度
     * @param float $lat2 第二个点的纬度
     * @param float $lon2 第二个点的经度
     * @return float 距离（公里）
     */
    function calculate_distance($lat1, $lon1, $lat2, $lon2)
    {
        // 地球半径（公里）
        $earthRadius = 6371;
        
        // 将角度转换为弧度
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);
        
        // 计算差值
        $latDiff = $lat2Rad - $lat1Rad;
        $lonDiff = $lon2Rad - $lon1Rad;
        
        // Haversine 公式
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($lonDiff / 2) * sin($lonDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        // 计算距离
        $distance = $earthRadius * $c;
        
        return round($distance, 2);
    }
}

if (!function_exists('format_distance')) {
    /**
     * 格式化距离显示
     * 
     * @param float $distance 距离（公里）
     * @return string 格式化后的距离字符串
     */
    function format_distance($distance)
    {
        if ($distance < 1) {
            // 小于1公里，显示米
            return round($distance * 1000) . 'm';
        } else if ($distance < 10) {
            // 1-10公里，保留1位小数
            return round($distance, 1) . 'km';
        } else {
            // 大于10公里，显示整数
            return round($distance) . 'km';
        }
    }
}
