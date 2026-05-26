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

if (!function_exists('new_system_public_path')) {
    /**
     * 获取上移后的 public 物理路径（new_system/public）
     * @param string $path 相对 public 的子路径（如：'uploads/avatars/202501/a.jpg'）
     */
    function new_system_public_path(string $path = ''): string
    {
        // app()->getRootPath() 通常指向 new_system/backend/
        $backendRoot = app()->getRootPath();
        $backendRoot = rtrim((string)$backendRoot, "/\\");

        // 上一级即 new_system/
        $newSystemRoot = dirname($backendRoot);
        $newSystemRoot = rtrim((string)$newSystemRoot, "/\\") . DIRECTORY_SEPARATOR;

        $publicRoot = $newSystemRoot . 'public' . DIRECTORY_SEPARATOR;

        $path = ltrim((string)$path, "/\\");
        return $path === '' ? $publicRoot : ($publicRoot . $path);
    }
}

if (!function_exists('normalize_public_media_url')) {
    /**
     * 将数据库里各种“上传资源 URL/路径”归一化为站点根相对路径 `/uploads/...`
     *
     * 不改库，只在接口返回层做字符串归一化，避免前端在 `/admin/`、本地 dev server 端口等场景拼错路径。
     *
     * 兼容常见形态：
     * - `/uploads/xxx`
     * - `uploads/xxx`
     * - `https://t.jiajiao91.com/uploads/xxx`
     * - `http://t.jiajiao91.com/uploads/xxx`
     * - `//t.jiajiao91.com/uploads/xxx`
     *
     * 其它域名/外链：原样返回（避免误伤）。
     */
    function normalize_public_media_url($value)
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            return $value;
        }

        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        // 小程序临时文件：不做改写
        if (stripos($raw, 'wxfile://') === 0) {
            return $raw;
        }

        if (strpos($raw, '/uploads/') === 0) {
            return $raw;
        }

        if (stripos($raw, 'uploads/') === 0) {
            return '/' . $raw;
        }

        if (stripos($raw, '//') === 0) {
            $u = parse_url('https:' . $raw);
        } else {
            $u = parse_url($raw);
        }

        if (is_array($u) && !empty($u['scheme']) && !empty($u['host']) && !empty($u['path'])) {
            $host = strtolower((string) $u['host']);
            if ($host === 't.jiajiao91.com') {
                $path = (string) $u['path'];
                if (strpos($path, '/uploads/') === 0) {
                    return $path;
                }
            }
        }

        return $raw;
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
