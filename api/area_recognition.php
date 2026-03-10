<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 错误处理
function handleError($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// 清理文本
function cleanText($text) {
    // 统一全角字符到半角
    $text = mb_convert_kana($text, 'as', 'UTF-8');
    
    // 统一中文标点到英文标点
    $text = strtr($text, array(
        '【' => '[',
        '】' => ']',
        '（' => '(',
        '）' => ')',
        '，' => ',',
        '。' => '.',
        '：' => ':',
        '；' => ';',
        '"' => '"',
        '"' => '"',
        ''' => "'",
        ''' => "'"
    ));
    
    // 移除所有标点符号和空白字符
    $text = preg_replace('/[\[\]（）()\s\p{P}]+/u', '', $text);
    
    // 确保文本编码为UTF-8
    return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
}

// 加载城市数据
function loadCityData() {
    $areaData = file_get_contents(__DIR__ . '/../fa_area.json');
    if ($areaData === false) {
        handleError('无法读取城市数据文件');
    }

    $jsonData = json_decode($areaData, true);
    if ($jsonData === null) {
        handleError('城市数据格式错误');
    }

    // 查找包含实际数据的数组
    $cityData = null;
    foreach ($jsonData as $item) {
        if (isset($item['type']) && $item['type'] === 'table' && isset($item['data'])) {
            $cityData = $item['data'];
            break;
        }
    }

    if ($cityData === null) {
        handleError('城市数据结构错误');
    }

    return $cityData;
}

// 格式化城市数据
function formatCityData($rawData) {
    $formattedData = [];
    $cityMap = []; // 用于存储城市信息的临时映射

    // 第一遍：收集所有城市
    foreach ($rawData as $item) {
        if ($item['level'] === '2') { // 市级
            $cityName = str_replace('市', '', $item['name']); // 移除"市"后缀
            $cityMap[$item['id']] = $cityName;
            $formattedData[$cityName] = [];
        }
    }

    // 第二遍：添加区域
    foreach ($rawData as $item) {
        if ($item['level'] === '3') { // 区级
            $pid = $item['pid'];
            if (isset($cityMap[$pid])) {
                $cityName = $cityMap[$pid];
                $districtName = str_replace(['区', '县', '市'], '', $item['name']); // 移除区/县/市后缀
                if (!in_array($districtName, $formattedData[$cityName])) {
                    $formattedData[$cityName][] = $districtName;
                }
            }
        }
    }

    return $formattedData;
}

// 识别城市和区域
function recognizeCityAndDistrict($text, $cityData) {
    $text = cleanText($text);
    $result = ['city' => '', 'district' => ''];
    
    // 构建格式化的城市数据
    $formattedData = formatCityData($cityData);
    
    // 记录匹配结果的权重
    $maxWeight = 0;
    $bestMatch = $result;
    
    // 先尝试匹配城市
    foreach ($formattedData as $city => $districts) {
        $weight = 0;
        
        // 检查城市名（多种形式）
        if (mb_strpos($text, $city . '市') !== false) {
            $weight = 3; // 完整匹配权重最高
        } else if (mb_strpos($text, $city) !== false) {
            $weight = 2; // 部分匹配权重次之
        }
        
        if ($weight > 0) {
            $currentMatch = ['city' => $city, 'district' => ''];
            
            // 在找到城市后立即查找其下属区域
            foreach ($districts as $district) {
                // 检查区域名（多种形式）
                if (mb_strpos($text, $district . '区') !== false ||
                    mb_strpos($text, $district . '县') !== false ||
                    mb_strpos($text, $district . '市') !== false) {
                    $weight += 2; // 完整区域匹配加权
                    $currentMatch['district'] = $district;
                    break;
                } else if (mb_strpos($text, $district) !== false) {
                    $weight += 1; // 部分区域匹配加权
                    $currentMatch['district'] = $district;
                    break;
                }
            }
            
            // 更新最佳匹配
            if ($weight > $maxWeight) {
                $maxWeight = $weight;
                $bestMatch = $currentMatch;
            }
        }
    }
    
    // 如果没找到城市但找到了区域，通过区域反查
    if (empty($bestMatch['city'])) {
        foreach ($formattedData as $city => $districts) {
            foreach ($districts as $district) {
                $weight = 0;
                
                // 检查区域名（多种形式）
                if (mb_strpos($text, $district . '区') !== false ||
                    mb_strpos($text, $district . '县') !== false ||
                    mb_strpos($text, $district . '市') !== false) {
                    $weight = 2;
                } else if (mb_strpos($text, $district) !== false) {
                    $weight = 1;
                }
                
                if ($weight > $maxWeight) {
                    $maxWeight = $weight;
                    $bestMatch = [
                        'city' => $city,
                        'district' => $district
                    ];
                }
            }
        }
    }
    
    return $bestMatch;
}

// 添加调试日志
function logDebug($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $logMessage .= ' - ' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logMessage);
}

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logDebug('获取城市数据');
    // 返回格式化的城市数据
    $cityData = loadCityData();
    $formattedData = formatCityData($cityData);
    logDebug('返回格式化的城市数据', array_keys($formattedData));
    echo json_encode([
        'success' => true,
        'data' => $formattedData
    ], JSON_UNESCAPED_UNICODE);
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理城市识别请求
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['text'])) {
        handleError('缺少文本参数');
    }
    
    logDebug('开始识别城市区域', ['text' => $data['text']]);
    $cityData = loadCityData();
    $result = recognizeCityAndDistrict($data['text'], $cityData);
    logDebug('识别结果', $result);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} else {
    handleError('不支持的请求方法');
} 