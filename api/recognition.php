<?php
/**
 * 智能识别接口 - 使用完整的全国城市区县数据
 * 从 fa_area.json 加载数据
 */

// 加载城市区域数据
function loadCityDataFromJson() {
    $jsonFile = __DIR__ . '/../fa_area.json';
    if (!file_exists($jsonFile)) {
        error_log('城市数据文件不存在: ' . $jsonFile);
        return null;
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (!$data || !is_array($data)) {
        error_log('城市数据解析失败');
        return null;
    }
    
    // 找到fa_area表的数据
    $areaData = null;
    foreach ($data as $item) {
        if (isset($item['type']) && $item['type'] === 'table' && 
            isset($item['name']) && $item['name'] === 'fa_area' && 
            isset($item['data'])) {
            $areaData = $item['data'];
            break;
        }
    }
    
    if (!$areaData) {
        error_log('未找到fa_area表数据');
        return null;
    }
    
    // 构建城市-区域映射关系
    $cityMap = [];
    $districtMap = [];
    
    // 第一遍：建立所有地区的映射
    $allAreas = [];
    foreach ($areaData as $area) {
        $allAreas[$area['id']] = $area;
    }
    
    // 第二遍：构建城市和区域关系
    foreach ($areaData as $area) {
        $level = intval($area['level']);
        
        // level=2 是市级
        if ($level == 2) {
            $cityMap[$area['id']] = [
                'id' => $area['id'],
                'name' => $area['name'],
                'shortname' => $area['shortname'],
                'districts' => []
            ];
        }
        
        // level=3 是区县级
        if ($level == 3) {
            $districtMap[$area['id']] = [
                'id' => $area['id'],
                'name' => $area['name'],
                'shortname' => $area['shortname'],
                'city_id' => $area['pid']
            ];
            
            // 添加到对应城市的区域列表
            if (isset($cityMap[$area['pid']])) {
                $cityMap[$area['pid']]['districts'][] = [
                    'id' => $area['id'],
                    'name' => $area['name'],
                    'shortname' => $area['shortname']
                ];
            }
        }
    }
    
    return [
        'cities' => $cityMap,
        'districts' => $districtMap
    ];
}

// 缓存城市数据
static $cityDataCache = null;

// 特殊地名映射
$specialLocations = [
    '海上世界' => ['city' => '深圳', 'district' => '南山区'],
    '乐从镇' => ['city' => '佛山', 'district' => '顺德区'],
    '大浪' => ['city' => '深圳', 'district' => '龙华区']
];

// 年级模式
$gradePatterns = [
    "幼儿" => "/幼儿园|幼儿|幼小|学前班|大班|中班|小班|托班/",
    "小学" => "/小学|一年级|1年级|小一|二年级|2年级|小二|三年级|3年级|小三|四年级|4年级|小四|五年级|5年级|小五|六年级|6年级|小六/",
    "初中" => "/初中|初一|初1|7年级|七年级|初二|初2|8年级|八年级|初三|初3|9年级|九年级/",
    "高中" => "/高中|高一|高1|高二|高2|高三|高3/"
];

// 科目模式
$subjectPatterns = [
    "语文" => "/语文/",
    "数学" => "/数学/",
    "英语" => "/英语|英文/",
    "物理" => "/物理/",
    "化学" => "/化学/",
    "生物" => "/生物/",
    "政治" => "/政治/",
    "历史" => "/历史/",
    "地理" => "/地理/",
    "科学" => "/科学/"
];

// 薪资识别模式
$salaryPatterns = [
    // 匹配薪酬标签中的内容
    '/【(?:薪酬|课时费|课酬|薪资|工资|费用)】[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
    // 匹配课费/课时费格式
    '/(?:课费|课时费)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
    // 匹配左右/上下格式
    '/(?:课时费|课酬|薪资|工资|费用)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:左右|上下)\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
    // 匹配纯数字+单位格式
    '/(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元每课时|元每小时|元每节课|元每次|元每月|元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)(?!\d)/',
    // 匹配月薪格式
    '/(?:月薪|月工资|月收入)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元|块|块钱)?/',
    // 匹配特殊格式（如：一节课100）
    '/(?:一节课|每节课|每节|每小时|每课时)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(元|块|块钱)?/',
    // 匹配中文数字
    '/(?:课时费|课酬|薪资|工资|费用)[：:\s]*([一二三四五六七八九十百千万]+)\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
    // 匹配数字+单位（无前缀）
    '/(\d+(?:[-.．]\d+)?)\s*(元|块|块钱)(?:\/|每)?(?:课时|小时|节课|次|月)?/'
];

// 中文数字映射
$chineseNumberMap = [
    '一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5,
    '六' => 6, '七' => 7, '八' => 8, '九' => 9, '十' => 10,
    '百' => 100, '千' => 1000, '万' => 10000
];

// 解析中文数字
function parseChineseNumber($str) {
    global $chineseNumberMap;
    $result = 0;
    $temp = 0;
    $unit = 1;
    
    for ($i = strlen($str) - 1; $i >= 0; $i--) {
        $char = $str[$i];
        $num = $chineseNumberMap[$char] ?? 0;
        
        if ($num >= 10) {
            $unit = $num;
            if ($temp === 0) $temp = 1;
            $result += $temp * $unit;
            $temp = 0;
        } else {
            $temp += $num;
            if ($i === 0) {
                $result += $temp * $unit;
            }
        }
    }
    
    return $result ?: intval($str);
}

// 标准化单位
function normalizeUnit($unit, $text) {
    // 先尝试从薪酬标签中提取单位
    if (preg_match('/【(?:薪酬|课时费|课酬|薪资|工资|费用)】[^】]*?(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*\d+(?:[-.．]\d+)?)?(?:左右|上下)?\s*([\/每]?(?:\d+)?(?:小时|h|H|次|月|课时|节课?)|一个?小时|每小时|每节课?|\/节|一节课?)/', $text, $matches)) {
        return standardizeUnit($matches[2]);
    }

    // 尝试从数字后面直接提取单位
    if (preg_match('/\d+(?:[-.．]\d+)?(?:左右|上下)?\s*(?:[-~到至]\s*\d+(?:[-.．]\d+)?)?(?:左右|上下)?\s*([\/每]?(?:\d+)?(?:小时|h|H|次|月|课时|节课?)|一个?小时|每小时|每节课?|\/节|一节课?)/', $text, $matches)) {
        return standardizeUnit($matches[1]);
    }

    // 如果有明确的单位文本，直接标准化
    if ($unit) {
        return standardizeUnit($unit);
    }

    // 尝试从整个文本中查找时间相关的关键词
    if (preg_match('/(?:一节课|每节课|每节|每小时|每课时|一小时|一堂课|每堂课)/', $text, $matches)) {
        return standardizeUnit($matches[0]);
    }

    // 如果文本中包含"月"相关字样，返回月单位
    if (preg_match('/(?:月薪|月工资|每月|\/月)/', $text)) {
        return '/月';
    }

    // 如果文本中包含"课时"相关字样，返回课时单位
    if (preg_match('/(?:课时|课节|节课)/', $text)) {
        return '/课时';
    }

    // 默认返回每次
    return '/次';
}

// 标准化单位显示
function standardizeUnit($unitText) {
    // 处理带数字的小时格式
    if (preg_match('/(?:\/)?(\d+)(?:小时|h|H)/i', $unitText, $matches)) {
        return "/{$matches[1]}小时";
    }

    // 处理"一小时"或"一个小时"或"每小时"格式
    if (preg_match('/[一1](?:个)?小时|每小时/', $unitText)) {
        return '/小时';
    }

    // 处理"一节课"或"每节课"格式
    if (preg_match('/[一1]节课|每节课?|一堂课|每堂课/', $unitText)) {
        return '/次';
    }

    // 标准化其他单位
    if (preg_match('/小时|h|H/i', $unitText)) return '/小时';
    if (preg_match('/月薪|月工资|每月|\/月/', $unitText)) return '/月';
    if (preg_match('/课时/', $unitText)) return '/课时';
    if (preg_match('/[\/每]节|课节/', $unitText)) return '/次';
    
    return '/次';
}

// 识别城市和区域（使用完整数据）
function findCityAndDistrict($text) {
    global $cityDataCache, $specialLocations;
    
    try {
        // 加载城市数据
        if ($cityDataCache === null) {
            $cityDataCache = loadCityDataFromJson();
            if (!$cityDataCache) {
                error_log('无法加载城市数据');
                return ['city' => null, 'district' => null];
            }
        }
        
        $cities = $cityDataCache['cities'];
        $districts = $cityDataCache['districts'];
        
        // 预处理文本
        $text = preg_replace('/[【】\[\]]/', '', $text);
        $text = preg_replace('/^[sS]/', '', $text);
        
        // 检查是否为线上家教
        if (preg_match('/(线上|网络|远程|在线|视频|网课)/', $text)) {
            return ['city' => '全国', 'district' => '线上'];
        }

        // 检查特殊地名
        foreach ($specialLocations as $location => $info) {
            if (mb_strpos($text, $location) !== false) {
                return $info;
            }
        }

        // 构建匹配候选列表
        $matchResults = [];
        
        // 遍历所有区域进行匹配
        foreach ($districts as $districtId => $district) {
            $cityId = $district['city_id'];
            if (!isset($cities[$cityId])) {
                continue;
            }
            
            $city = $cities[$cityId];
            $cityName = $city['name'];
            $districtName = $district['name'];
            
            // 生成城市名称变体
            $cityVariants = getCityNameVariants($cityName);
            
            // 生成区域名称变体  
            $districtVariants = getDistrictNameVariants($districtName);
            
            // 检查匹配情况
            $cityMatched = false;
            $districtMatched = false;
            $matchScore = 0;
            
            // 检查城市匹配
            foreach ($cityVariants as $variant) {
                if ($variant && mb_strpos($text, $variant) !== false) {
                    $cityMatched = true;
                    $matchScore += mb_strlen($variant) * 100; // 城市匹配权重高
                    break;
                }
            }
            
            // 检查区域匹配
            foreach ($districtVariants as $variant) {
                if ($variant && mb_strpos($text, $variant) !== false) {
                    $districtMatched = true;
                    $matchScore += mb_strlen($variant) * 10; // 区域匹配权重
                    break;
                }
            }
            
            // 如果匹配到区域（无论是否匹配到城市）
            if ($districtMatched) {
                // 判断城市等级权重
                $levelWeight = getCityLevelWeight($cityName);
                
                $matchResults[] = [
                    'city' => $cityName,
                    'district' => $districtName,
                    'score' => $matchScore,
                    'level_weight' => $levelWeight,
                    'both_matched' => $cityMatched && $districtMatched,
                ];
            }
        }
        
        // 如果没有匹配到区域，尝试只匹配城市
        if (empty($matchResults)) {
            foreach ($cities as $cityId => $city) {
                $cityName = $city['name'];
                $cityVariants = getCityNameVariants($cityName);
                
                foreach ($cityVariants as $variant) {
                    if ($variant && mb_strpos($text, $variant) !== false) {
                        return ['city' => $cityName, 'district' => null];
                    }
                }
            }
            return ['city' => null, 'district' => null];
        }
        
        // 按优先级排序
        usort($matchResults, function($a, $b) {
            // 1. 城市和区域都匹配优先级最高
            if ($a['both_matched'] != $b['both_matched']) {
                return $b['both_matched'] - $a['both_matched'];
            }
            
            // 2. 如果都只匹配区域，按城市等级排序（一线城市优先）
            if (!$a['both_matched'] && !$b['both_matched']) {
                if ($a['level_weight'] != $b['level_weight']) {
                    return $b['level_weight'] - $a['level_weight'];
                }
            }
            
            // 3. 最后按匹配得分排序
            return $b['score'] - $a['score'];
        });
        
        // 返回最佳匹配
        $bestMatch = $matchResults[0];
        return [
            'city' => $bestMatch['city'],
            'district' => $bestMatch['district']
        ];
        
    } catch (Exception $e) {
        error_log('城市区域识别出错: ' . $e->getMessage());
        return ['city' => null, 'district' => null];
    }
}

// 获取城市名称变体
function getCityNameVariants($cityName) {
    $variants = [$cityName]; // 完整名称
    
    // 去掉"市"
    $nameWithoutSuffix = str_replace('市', '', $cityName);
    if ($nameWithoutSuffix != $cityName) {
        $variants[] = $nameWithoutSuffix;
    }
    
    return array_unique($variants);
}

// 获取区域名称变体
function getDistrictNameVariants($districtName) {
    $variants = [$districtName]; // 完整名称优先
    
    // 去掉常见后缀（但只有当名称足够长时）
    $suffixes = ['区', '县', '市', '镇', '街道', '旗'];
    
    foreach ($suffixes as $suffix) {
        if (mb_substr($districtName, -1) === $suffix) {
            $nameWithoutSuffix = mb_substr($districtName, 0, -1);
            // 只有当去除后缀后的名称长度>=3时才添加（避免太短的匹配）
            if (mb_strlen($nameWithoutSuffix) >= 3) {
                $variants[] = $nameWithoutSuffix;
            }
        }
    }
    
    return array_unique($variants);
}

// 获取城市等级权重（简单判断）
function getCityLevelWeight($cityName) {
    // 一线城市
    $tier1 = ['北京市', '上海市', '广州市', '深圳市'];
    if (in_array($cityName, $tier1)) {
        return 1000;
    }
    
    // 新一线城市
    $tier15 = ['成都市', '杭州市', '重庆市', '武汉市', '西安市', '苏州市', '南京市', '天津市', '郑州市', '长沙市', '东莞市', '沈阳市', '青岛市', '合肥市', '佛山市'];
    if (in_array($cityName, $tier15)) {
        return 500;
    }
    
    // 其他城市
    return 100;
}

// 识别年级
function findGrade($text) {
    global $gradePatterns;
    foreach ($gradePatterns as $grade => $pattern) {
        if (preg_match($pattern, $text)) {
            return $grade;
        }
    }
    return null;
}

// 识别科目
function findSubject($text) {
    global $subjectPatterns;
    foreach ($subjectPatterns as $subject => $pattern) {
        if (preg_match($pattern, $text)) {
            return $subject;
        }
    }
    return null;
}

// 识别薪资
function parseSalary($text) {
    global $salaryPatterns;
    if (!$text) return null;
    
    foreach ($salaryPatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $amount1 = $matches[1];
            $amount2 = isset($matches[2]) ? $matches[2] : null;
            
            // 处理中文数字
            if (preg_match('/[一二三四五六七八九十百千万]/', $amount1)) {
                $amount1 = parseChineseNumber($amount1);
            }
            if ($amount2 && preg_match('/[一二三四五六七八九十百千万]/', $amount2)) {
                $amount2 = parseChineseNumber($amount2);
            }
            
            // 获取标准化单位
            $normalizedUnit = normalizeUnit($matches[3] ?? '', $text);
            
            // 构建薪资字符串
            if ($amount2) {
                return "{$amount1}-{$amount2}{$normalizedUnit}";
            } else {
                if (strpos($text, '左右') !== false) {
                    return "{$amount1}左右{$normalizedUnit}";
                } elseif (strpos($text, '上下') !== false) {
                    return "{$amount1}上下{$normalizedUnit}";
                } else {
                    return "{$amount1}{$normalizedUnit}";
                }
            }
        }
    }
    
    // 尝试提取纯数字
    if (preg_match('/\d+(?:[-.．]\d+)?/', $text, $matches)) {
        $amount = $matches[0];
        $unit = normalizeUnit('', $text);
        return "{$amount}{$unit}";
    }
    
    return null;
} 