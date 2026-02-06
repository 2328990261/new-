<?php
namespace app\service;

use app\model\Lead;

/**
 * 线索转家教单格式服务
 */
class LeadToTutorOrderService
{
    /**
     * 将线索转换为家教单格式
     * 
     * @param Lead $lead 线索对象
     * @return string 家教单格式文本
     */
    public static function convert(Lead $lead)
    {
        try {
            $lines = [];
            
            // 1. 标题：【城市区域年级科目】
            $title = self::buildTitle($lead);
            if ($title) {
                $lines[] = $title;
            }
            
            // 2. 学生信息：【学生】性别，情况说明
            $student = self::buildStudent($lead);
            if ($student) {
                $lines[] = "【学生】" . $student;
            }
            
            // 3. 时间安排：【时间】频率，具体时间，时长
            $time = self::buildTime($lead);
            if ($time) {
                $lines[] = "【时间】" . $time;
            }
            
            // 4. 薪酬：【薪酬】金额/次或/小时，每次时长
            $salary = self::buildSalary($lead);
            if ($salary) {
                $lines[] = "【薪酬】" . $salary;
            }
            
            // 5. 要求：【要求】对老师的要求
            $requirement = self::buildRequirement($lead);
            if ($requirement) {
                $lines[] = "【要求】" . $requirement;
            }
            
            return implode("\n", $lines);
        } catch (\Exception $e) {
            trace('转换家教单格式失败: ' . $e->getMessage(), 'error');
            throw new \Exception('转换失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 从原始内容直接转换为家教单格式（用于录入时转换）
     * 
     * @param string $rawContent 原始内容
     * @param array $parsedData 已解析的数据（可选，包含城市、区域、年级、科目等）
     * @return string 家教单格式文本
     */
    public static function convertFromContent($rawContent, $parsedData = [])
    {
        try {
            $lines = [];
            
            // 1. 标题：【城市区域年级科目】
            $title = self::buildTitleFromData($rawContent, $parsedData);
            $lines[] = $title; // 总是添加标题，即使为空
            
            // 2. 学生信息：【学生】性别，情况说明，成绩如何，几个小孩，一男一女，比较爱玩，班上等
            $student = self::buildStudentFromContent($rawContent);
            $lines[] = "【学生】" . ($student ?: ''); // 总是添加【学生】，即使内容为空
            
            // 3. 时间安排：【时间】频率，具体时间，时长，一周多少次，每次多长时间，周几，星期几，周末
            $time = self::buildTimeFromContent($rawContent);
            $lines[] = "【时间】" . ($time ?: ''); // 总是添加【时间】，即使内容为空
            
            // 4. 薪酬：【薪酬】金额/次或/小时或一小时，每次多长时间
            $salary = self::buildSalaryFromContent($rawContent);
            $lines[] = "【薪酬】" . ($salary ?: ''); // 总是添加【薪酬】，即使内容为空
            
            // 5. 要求：【要求】对老师的要求，老师性别要求，要求二字之后
            $requirement = self::buildRequirementFromContent($rawContent);
            $lines[] = "【要求】" . ($requirement ?: ''); // 总是添加【要求】，即使内容为空
            
            return implode("\n", $lines);
        } catch (\Exception $e) {
            trace('转换家教单格式失败: ' . $e->getMessage(), 'error');
            throw new \Exception('转换失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 构建标题：【城市区域年级科目】
     */
    private static function buildTitle(Lead $lead)
    {
        $parts = [];
        
        // 城市
        if ($lead->city) {
            $parts[] = $lead->city->name;
        }
        
        // 区域
        if ($lead->district) {
            $parts[] = $lead->district->name;
        }
        
        // 地址（从原始内容中提取，避免与区域重复）
        $address = self::extractAddress($lead->raw_content);
        if ($address && mb_strlen(trim($address)) > 0) {
            $address = trim($address);
            // 如果区域名称与地址不同，才添加地址
            $districtName = '';
            if ($lead->district && isset($lead->district->name) && $lead->district->name) {
                $districtName = trim($lead->district->name);
            }
            
            // 检查是否需要添加地址（避免与区域重复）
            $shouldAddAddress = false;
            
            // 如果没有区域，直接添加地址
            if (empty($districtName) || mb_strlen($districtName) == 0) {
                $shouldAddAddress = true;
            } 
            // 如果地址与区域完全相同，不添加
            elseif ($address === $districtName) {
                $shouldAddAddress = false;
            }
            // 如果地址与区域不同，添加地址（简化逻辑，不再检查包含关系，避免空字符串问题）
            else {
                $shouldAddAddress = true;
            }
            
            if ($shouldAddAddress) {
                $parts[] = $address;
            }
        }
        
        // 年级
        if ($lead->grade) {
            $parts[] = self::formatGrade($lead->grade);
        }
        
        // 科目
        if ($lead->subject) {
            $parts[] = $lead->subject;
        }
        
        $title = implode('', $parts);
        return "【{$title}】";
    }
    
    /**
     * 构建学生信息
     */
    private static function buildStudent(Lead $lead)
    {
        $parts = [];
        $content = $lead->raw_content;
        
        // 性别
        $gender = self::extractGender($content);
        if ($gender) {
            $parts[] = $gender;
        }
        
        // 学生情况（从原始内容提取）
        $situation = self::extractStudentSituation($content);
        if ($situation) {
            $parts[] = $situation;
        }
        
        return implode('，', array_filter($parts));
    }
    
    /**
     * 构建时间安排
     */
    private static function buildTime(Lead $lead)
    {
        $parts = [];
        $content = $lead->raw_content;
        
        // 频率
        $frequency = self::extractFrequency($content);
        if ($frequency) {
            $parts[] = $frequency;
        }
        
        // 具体时间
        $specificTime = self::extractSpecificTime($content);
        if ($specificTime) {
            $parts[] = $specificTime;
        }
        
        // 时长
        $duration = self::extractDuration($content);
        if ($duration) {
            $parts[] = $duration;
        }
        
        return implode('，', array_filter($parts));
    }
    
    /**
     * 构建薪酬
     */
    private static function buildSalary(Lead $lead)
    {
        $content = $lead->raw_content;
        
        // 从原始内容提取薪酬信息
        $salary = self::extractSalary($content);
        return $salary;
    }
    
    /**
     * 构建要求
     */
    private static function buildRequirement(Lead $lead)
    {
        $content = $lead->raw_content;
        
        $requirements = [];
        
        // 老师性别要求
        $teacherGender = self::extractTeacherGender($content);
        if ($teacherGender) {
            $requirements[] = $teacherGender;
        }
        
        // 老师类型要求
        $teacherType = self::extractTeacherType($content);
        if ($teacherType) {
            $requirements[] = $teacherType;
        }
        
        // 其他要求
        $otherReq = self::extractOtherRequirement($content);
        if ($otherReq) {
            $requirements[] = $otherReq;
        }
        
        return implode('，', array_filter($requirements));
    }
    
    /**
     * 提取地址
     */
    private static function extractAddress($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        // 匹配常见地址格式：XX路、XX街、XX花园、XX小区、XX地铁站等
        $patterns = [
            '/([^\s，,。；;]+(?:路|街|大道|花园|小区|村|地铁站|附近))/u',
            '/([^\s，,。；;]+(?:附近|周边))/u',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                if (preg_match($pattern, $content, $matches)) {
                    $address = trim($matches[1], '，,。；; ');
                    // 过滤掉太短或不符合地址格式的内容
                    if (mb_strlen($address) >= 3 && mb_strlen($address) <= 30) {
                        return $address;
                    }
                }
            } catch (\Exception $e) {
                // 跳过错误的模式，继续下一个
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * 提取性别
     */
    private static function extractGender($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:性别|学生性别|性别要求)[：:]\s*(男|女|男孩|女孩|男生|女生)/u', $content, $matches)) {
                return $matches[1];
            }
            if (preg_match('/(?:男孩|女孩|男生|女生|男|女)(?:，|,|。|；|;|\$)/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取学生情况
     */
    private static function extractStudentSituation($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        $patterns = [
            '/学生情况[：:]\s*([^\n]+)/u',
            '/(?:基础|成绩|情况)[：:]?\s*(?:比较|很)?(?:差|中等|一般|好|优秀)/u',
            '/(?:就读|学习|需要辅导)[^\n，,。；;]+/u',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                if (preg_match($pattern, $content, $matches)) {
                    $situation = trim($matches[1] ?? $matches[0], '，,。；; ');
                    if (mb_strlen($situation) > 5 && mb_strlen($situation) < 100) {
                        return $situation;
                    }
                }
            } catch (\Exception $e) {
                // 跳过错误的模式，继续下一个
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * 提取频率
     */
    private static function extractFrequency($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            // 优先匹配明确字段
            if (preg_match('/(?:频率|次数|辅导次数|时间安排及频率)[：:]\s*([^\n，,。；;]+)/u', $content, $matches)) {
                $freq = trim($matches[1]);
                // 如果已经是完整描述，直接返回
                if (preg_match('/(?:一周|每周|每星期)/u', $freq)) {
                    return $freq;
                }
                // 如果是数字，转换为文字
                if (preg_match('/(\d+)/u', $freq, $numMatches)) {
                    $num = intval($numMatches[1]);
                    $freqMap = [1 => '一次', 2 => '两次', 3 => '三次', 4 => '四次', 5 => '五次', 6 => '六次', 7 => '七次'];
                    if (isset($freqMap[$num])) {
                        return "一周" . $freqMap[$num];
                    }
                    return "一周{$num}次";
                }
                return $freq;
            }
            // 匹配：一周1-2次
            if (preg_match('/(?:一周|每周|每星期)(\d+)[\-]?(\d+)?次/u', $content, $matches)) {
                $times = $matches[1];
                if (isset($matches[2]) && $matches[2]) {
                    return "一周{$times}-{$matches[2]}次";
                }
                return "一周{$times}次";
            }
            // 匹配：一周一次、一周两次等
            if (preg_match('/一周(一次|两次|三次|四次|五次|六次|七次)/u', $content, $matches)) {
                return "一周" . $matches[1];
            }
            // 匹配：周一到周五、周末等
            if (preg_match('/(周[一到日]+|周末|平日|工作日)/u', $content, $matches)) {
                return $matches[1];
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取具体时间（增强版：提取更多时间信息）
     */
    private static function extractSpecificTime($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            // 优先匹配明确的时间字段（包括"时间"二字之后的内容）
            if (preg_match('/(?:可辅导时间|辅导时间|时间)[：:]\s*([^\n]+)/u', $content, $matches)) {
                $timeText = trim($matches[1], '，,。；; ');
                // 如果包含完整的时间描述，直接返回
                if (mb_strlen($timeText) > 5) {
                    return $timeText;
                }
            }
            
            // 匹配：周一到周五、周一至周五、周一到周几
            if (preg_match('/(周[一到日]+)[到至](周[一到日]+)/u', $content, $matches)) {
                return $matches[1] . '到' . $matches[2];
            }
            
            // 匹配：周一到周五晚上七点到九点
            if (preg_match('/(?:周[一到日]+|周末|平日|工作日)(?:，|,|、)?(?:晚上|早上|下午|中午)?(?:[七八九十]|\d{1,2})[点:]?(?:到|至)(?:[七八九十]|\d{1,2})[点:]/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
            
            // 匹配：几点到几点（更通用的匹配）
            if (preg_match('/(\d{1,2})[点:](?:到|至)(\d{1,2})[点:]/u', $content, $matches)) {
                return $matches[1] . '点到' . $matches[2] . '点';
            }
            
            // 匹配：下午几点开始、晚上几点开始等
            if (preg_match('/(?:早上|上午|中午|下午|晚上)(\d{1,2})[点:]?开始/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
            
            // 匹配：周二17点或周日9点开始
            if (preg_match('/(?:周[一到日]+|周末)(?:，|,|、)?(\d{1,2})[点:]?开始/u', $content, $matches)) {
                return $matches[0];
            }
            
            // 匹配时间点：17点、9点等
            if (preg_match('/(周[一到日]+)(?:，|,|、|或)?(\d{1,2})[点:]/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
            
            // 匹配：下午几点、晚上几点等
            if (preg_match('/(?:早上|上午|中午|下午|晚上)(\d{1,2})[点:]/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取时长
     */
    private static function extractDuration($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            // 优先匹配"每次辅导时长"、"时长"等明确字段
            if (preg_match('/(?:时长|每次|每次辅导)[：:]\s*([^\n，,。；;]+)/u', $content, $matches)) {
                $duration = trim($matches[1]);
                // 如果包含小时，添加h/次后缀
                if (preg_match('/(\d+(?:\.\d+)?)[小时hH时]/u', $duration, $dMatches)) {
                    return $dMatches[1] . "h/次";
                }
                return $duration;
            }
            // 匹配：2小时、1.5小时、2h等
            if (preg_match('/(\d+(?:\.\d+)?)[小时hH时]/u', $content, $matches)) {
                return $matches[1] . "h/次";
            }
            // 匹配：2h/次
            if (preg_match('/(\d+)[hH]\/次/u', $content, $matches)) {
                return $matches[1] . "h/次";
            }
            // 匹配：两个小时的表达
            if (preg_match('/(?:每次|每次辅导)(\d+)(?:小时|h)/u', $content, $matches)) {
                return $matches[1] . "h/次";
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取薪酬
     */
    private static function extractSalary($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            // 匹配：240/次、400/两个小时、180-220/两个小时、80-100元等
            // 优先匹配明确的薪酬字段
            if (preg_match('/(?:薪酬|愿付|愿意支付|时薪|价格)[：:]\s*([^\n]+)/u', $content, $matches)) {
                $salaryText = trim($matches[1]);
                // 尝试从明确的薪酬文本中提取
                if (preg_match('/(\d+)-(\d+)[元\/]/u', $salaryText, $sMatches)) {
                    // 判断是范围还是金额/时长
                    if (strpos($salaryText, '/') !== false) {
                        return "{$sMatches[1]}-{$sMatches[2]}/次";
                    }
                    return "{$sMatches[1]}-{$sMatches[2]}元";
                }
                if (preg_match('/(\d+)[\/](\d+)(?:小时|h)/u', $salaryText, $sMatches)) {
                    return "{$sMatches[1]}/次，每次{$sMatches[2]}小时";
                }
            }
            
            // 匹配常见格式
            $patterns = [
                '/(\d+)-(\d+)[\/](\d+)(?:小时|h)/u', // 180-220/两个小时
                '/(\d+)[\/](\d+)(?:小时|h)/u', // 400/两个小时
                '/(\d+)-(\d+)[元\/]/u', // 180-220/次 或 80-100元
                '/(\d+)[\/]次/u', // 240/次
                '/(\d+)-(\d+)元/u', // 80-100元
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content, $matches)) {
                    if (count($matches) >= 4 && isset($matches[3]) && is_numeric($matches[3])) {
                        // 金额/时长格式：400/两个小时
                        return "{$matches[1]}/次，每次{$matches[3]}小时";
                    } elseif (count($matches) >= 3 && isset($matches[2]) && is_numeric($matches[2])) {
                        // 范围格式
                        if (strpos($content, '/') !== false && !preg_match('/小时|h/i', $matches[0])) {
                            return "{$matches[1]}-{$matches[2]}/次";
                        }
                        if (preg_match('/小时|h/i', $content)) {
                            return "{$matches[1]}-{$matches[2]}/小时";
                        }
                        if (strpos($matches[0], '元') !== false) {
                            return "{$matches[1]}-{$matches[2]}元/小时";
                        }
                        return "{$matches[1]}-{$matches[2]}/次";
                    } else {
                        // 单一金额格式
                        if (preg_match('/小时|h/i', $content)) {
                            return "{$matches[1]}/小时";
                        }
                        return "{$matches[1]}/次";
                    }
                }
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取老师性别要求
     */
    private static function extractTeacherGender($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:老师性别|性别要求|要求)[：:]?\s*(男女不限|不限|男老师|女老师|男|女)/u', $content, $matches)) {
                return trim($matches[1], '，,。；; ');
            }
            if (preg_match('/(?:男女不限|不限|男老师|女老师)/u', $content, $matches)) {
                return $matches[0];
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取老师类型要求
     */
    private static function extractTeacherType($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:专业老师|专职老师|大学生|在职老师|有经验的|经验丰富)/u', $content, $matches)) {
                return $matches[0];
            }
        } catch (\Exception $e) {
            // 返回 null，表示提取失败
        }
        
        return null;
    }
    
    /**
     * 提取其他要求
     */
    private static function extractOtherRequirement($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        $patterns = [
            '/对老师要求[：:]\s*([^\n]+)/u',
            '/要求说明[：:]\s*([^\n]+)/u',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                if (preg_match($pattern, $content, $matches)) {
                    $req = trim($matches[1], '，,。；; ');
                    if (mb_strlen($req) > 3 && mb_strlen($req) < 50) {
                        return $req;
                    }
                }
            } catch (\Exception $e) {
                // 跳过错误的模式，继续下一个
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * 格式化年级（小一、小二等不识别成年级段，保持原样）
     */
    private static function formatGrade($grade)
    {
        if (empty($grade)) {
            return '';
        }
        
        // 如果年级是小一、小二、小三等具体年级，直接返回，不做任何转换
        // 这里不识别成年级段，保持原样
        if (preg_match('/^(小[一二三四五六]|初[一二三]|高[一二三])$/u', $grade)) {
            return $grade;
        }
        
        // 如果年级包含"小"、"初"、"高"等年级段标识，直接返回
        if (preg_match('/[小初高]/u', $grade)) {
            return $grade;
        }
        
        // 否则保持原样
        return $grade;
    }
    
    /**
     * 从数据构建标题：【城市区域年级科目】
     */
    private static function buildTitleFromData($rawContent, $parsedData = [])
    {
        $parts = [];
        
        // 城市
        if (!empty($parsedData['city_name'])) {
            $parts[] = $parsedData['city_name'];
        }
        
        // 区域
        if (!empty($parsedData['district_name'])) {
            $parts[] = $parsedData['district_name'];
        }
        
        // 年级
        if (!empty($parsedData['grade'])) {
            $parts[] = self::formatGrade($parsedData['grade']);
        }
        
        // 科目
        if (!empty($parsedData['subject'])) {
            $parts[] = $parsedData['subject'];
        }
        
        $title = implode('', $parts);
        return "【{$title}】";
    }
    
    /**
     * 从内容构建学生信息（更详细的提取，提取所有匹配项）
     */
    private static function buildStudentFromContent($content)
    {
        if (empty($content) || !is_string($content)) {
            return '';
        }
        
        $parts = [];
        
        // 性别（提取所有匹配项）
        $genders = self::extractAllGender($content);
        if ($genders) {
            $parts = array_merge($parts, $genders);
        }
        
        // 学生情况（更详细的提取，提取所有匹配项）
        $situations = self::extractAllStudentSituationDetail($content);
        if ($situations) {
            $parts = array_merge($parts, $situations);
        }
        
        // 成绩（提取所有匹配项）
        $gradeLevels = self::extractAllGradeLevel($content);
        if ($gradeLevels) {
            $parts = array_merge($parts, $gradeLevels);
        }
        
        // 几个小孩（提取所有匹配项）
        $childrenCounts = self::extractAllChildrenCount($content);
        if ($childrenCounts) {
            $parts = array_merge($parts, $childrenCounts);
        }
        
        // 一男一女等（提取所有匹配项）
        $childrenGenders = self::extractAllChildrenGender($content);
        if ($childrenGenders) {
            $parts = array_merge($parts, $childrenGenders);
        }
        
        // 男生女生
        $maleFemale = self::extractMaleFemale($content);
        if ($maleFemale) {
            $parts[] = $maleFemale;
        }
        
        // 性格特点：比较爱玩、班上等（提取所有匹配项）
        $characters = self::extractAllCharacter($content);
        if ($characters) {
            $parts = array_merge($parts, $characters);
        }
        
        // 去重并保持顺序
        $parts = array_unique($parts);
        return implode('，', array_filter($parts));
    }
    
    /**
     * 从内容构建时间安排（更详细的提取，提取所有匹配项）
     */
    private static function buildTimeFromContent($content)
    {
        if (empty($content) || !is_string($content)) {
            return '';
        }
        
        $parts = [];
        
        // 优先提取"时间"二字之后的内容（如果存在）
        $timeAfterWord = self::extractTimeAfterWord($content);
        if ($timeAfterWord) {
            $parts[] = $timeAfterWord;
        }
        
        // 频率（一周多少次，提取所有匹配项）
        if (!$timeAfterWord) { // 如果已经有"时间"之后的内容，不再提取频率，避免重复
            $frequencies = self::extractAllFrequency($content);
            if ($frequencies) {
                $parts = array_merge($parts, $frequencies);
            }
        }
        
        // 具体时间（周几，星期几，周末，周几到周几，几点到几点，下午几点开始等，提取所有匹配项，包括带-的范围）
        if (!$timeAfterWord) { // 如果已经有"时间"之后的内容，不再提取具体时间，避免重复
            $specificTimes = self::extractAllSpecificTime($content);
            if ($specificTimes) {
                $parts = array_merge($parts, $specificTimes);
            }
        }
        
        // 时长（每次多长时间，提取所有匹配项）
        $durations = self::extractAllDuration($content);
        if ($durations) {
            $parts = array_merge($parts, $durations);
        }
        
        // 去重并保持顺序
        $parts = array_unique($parts);
        return implode('，', array_filter($parts));
    }
    
    /**
     * 提取"时间"二字之后的内容
     */
    private static function extractTimeAfterWord($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            // 匹配"时间"之后的内容（不包括已有其他匹配的字段）
            if (preg_match('/时间[：:]\s*([^\n，,。；;]+)/u', $content, $matches)) {
                $timeText = trim($matches[1], '，,。；; ');
                // 如果包含完整的时间描述，直接返回
                if (mb_strlen($timeText) > 3 && mb_strlen($timeText) < 200) {
                    return $timeText;
                }
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 从内容构建薪酬（提取所有匹配项）
     */
    private static function buildSalaryFromContent($content)
    {
        if (empty($content) || !is_string($content)) {
            return '';
        }
        
        $salaries = self::extractAllSalary($content);
        if ($salaries) {
            return implode('，', array_unique($salaries));
        }
        
        return '';
    }
    
    /**
     * 从内容构建要求（提取要求二字之后的内容，提取所有匹配项）
     */
    private static function buildRequirementFromContent($content)
    {
        if (empty($content) || !is_string($content)) {
            return '';
        }
        
        $requirements = [];
        
        // 提取"要求"二字之后的内容（提取所有匹配项）
        $requirementAfterWords = self::extractAllRequirementAfterWord($content);
        if ($requirementAfterWords) {
            $requirements = array_merge($requirements, $requirementAfterWords);
        }
        
        // 老师性别要求（提取所有匹配项）
        $teacherGenders = self::extractAllTeacherGender($content);
        if ($teacherGenders) {
            $requirements = array_merge($requirements, $teacherGenders);
        }
        
        // 老师类型要求（特殊处理：如果同时有大学生和专职老师，合并为"大学生专职老师均可"）
        $hasStudent = false;
        $hasProfessional = false;
        
        // 检查是否有大学生
        if (preg_match('/(?:大学生|在校大学生)/u', $content, $matches)) {
            $hasStudent = true;
        }
        
        // 检查是否有专职/专业老师
        if (preg_match('/(?:专职老师|专业老师|在职老师)/u', $content, $matches)) {
            $hasProfessional = true;
        }
        
        if ($hasStudent && $hasProfessional) {
            $requirements[] = '大学生专职老师均可';
        } else {
            // 单独提取老师类型要求（提取所有匹配项）
            $teacherTypes = self::extractAllTeacherType($content);
            if ($teacherTypes) {
                $requirements = array_merge($requirements, $teacherTypes);
            }
        }
        
        // 其他要求（提取所有匹配项）
        $otherReqs = self::extractAllOtherRequirement($content);
        if ($otherReqs) {
            $requirements = array_merge($requirements, $otherReqs);
        }
        
        // 去重并保持顺序
        $requirements = array_unique($requirements);
        return implode('，', array_filter($requirements));
    }
    
    /**
     * 提取学生情况（更详细）
     */
    private static function extractStudentSituationDetail($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        $patterns = [
            '/学生情况[：:]\s*([^\n]+)/u',
            '/(?:就读|学习|需要辅导)[^\n，,。；;]+/u',
            '/(?:基础|成绩|情况)[：:]?\s*(?:比较|很)?(?:差|中等|一般|好|优秀)/u',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                if (preg_match($pattern, $content, $matches)) {
                    $situation = trim($matches[1] ?? $matches[0], '，,。；; ');
                    if (mb_strlen($situation) > 3 && mb_strlen($situation) < 150) {
                        return $situation;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * 提取成绩
     */
    private static function extractGradeLevel($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:成绩|成绩如何|成绩怎么样)[：:]?\s*([^\n，,。；;]+)/u', $content, $matches)) {
                return trim($matches[1], '，,。；; ');
            }
            if (preg_match('/(?:成绩)(?:比较|很)?(?:差|中等|一般|好|优秀|很好)/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 提取几个小孩
     */
    private static function extractChildrenCount($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:几个|有多少个|有几个)(?:小孩|孩子|学生|个)[：:]?\s*([一二三四五六七八九十\d]+)/u', $content, $matches)) {
                return trim($matches[0], '，,。；; ');
            }
            if (preg_match('/([一二三四五六七八九十\d]+)(?:个小孩|个孩子|个学生)/u', $content, $matches)) {
                return $matches[0];
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 提取小孩性别（一男一女等）
     */
    private static function extractChildrenGender($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(一男一女|两男|两女|一男|一女)/u', $content, $matches)) {
                return $matches[1];
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 提取性格特点（比较爱玩、班上等）
     */
    private static function extractCharacter($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:比较|很|有点)(?:爱玩|活泼|文静|调皮|内向|外向)/u', $content, $matches)) {
                return $matches[0];
            }
            if (preg_match('/(?:班上|班级)(?:第[一二三四五六七八九十\d]+|前[一二三四五六七八九十\d]+|中等|一般)/u', $content, $matches)) {
                return $matches[0];
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 提取"要求"二字之后的内容
     */
    private static function extractRequirementAfterWord($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            // 匹配"要求"之后的内容
            if (preg_match('/要求[：:]?\s*([^\n]+)/u', $content, $matches)) {
                $req = trim($matches[1], '，,。；; ');
                if (mb_strlen($req) > 2 && mb_strlen($req) < 100) {
                    return $req;
                }
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 提取所有性别匹配项（排除"男女不限"，因为这是老师要求）
     */
    private static function extractAllGender($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            // 匹配所有性别描述，排除"男女不限"
            if (preg_match_all('/(?:性别|学生性别|性别要求)[：:]\s*(男|女|男孩|女孩|男生|女生)/u', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    // 排除"男女不限"
                    if (trim($match) !== '男女不限' && trim($match) !== '不限') {
                        $results[] = $match;
                    }
                }
            }
            if (preg_match_all('/(?:男孩|女孩|男生|女生|男|女)(?:，|,|。|；|;|\$)/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $gender = trim($match, '，,。；; ');
                    // 排除"男女不限"
                    if ($gender !== '男女不限' && $gender !== '不限' && mb_strpos($content, '男女不限') === false) {
                        $results[] = $gender;
                    }
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有学生情况（包括培优、补差等）
     */
    private static function extractAllStudentSituationDetail($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        $patterns = [
            '/学生情况[：:]\s*([^\n]+)/u',
            '/(?:就读|学习|需要辅导)[^\n，,。；;]+/u',
            '/(?:基础|成绩|情况)[：:]?\s*(?:比较|很)?(?:差|中等|一般|好|优秀)/u',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] ?? $matches[0] as $match) {
                        $situation = trim($match, '，,。；; ');
                        if (mb_strlen($situation) > 3 && mb_strlen($situation) < 150) {
                            $results[] = $situation;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // 提取培优、补差等
        try {
            if (preg_match_all('/(?:培优|补差|巩固|提升)/u', $content, $matches)) {
                $results = array_merge($results, $matches[0]);
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有成绩匹配项
     */
    private static function extractAllGradeLevel($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/(?:成绩|成绩如何|成绩怎么样)[：:]?\s*([^\n，,。；;]+)/u', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
            if (preg_match_all('/(?:成绩)(?:比较|很)?(?:差|中等|一般|好|优秀|很好)/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有几个小孩匹配项
     */
    private static function extractAllChildrenCount($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/(?:几个|有多少个|有几个)(?:小孩|孩子|学生|个)[：:]?\s*([一二三四五六七八九十\d]+)/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
            if (preg_match_all('/([一二三四五六七八九十\d]+)(?:个小孩|个孩子|个学生)/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = $match;
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有小孩性别匹配项（一男一女等）
     */
    private static function extractAllChildrenGender($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/(一男一女|两男|两女|一男|一女)/u', $content, $matches)) {
                $results = array_merge($results, $matches[1]);
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取男生女生
     */
    private static function extractMaleFemale($content)
    {
        if (empty($content) || !is_string($content)) {
            return null;
        }
        
        try {
            if (preg_match('/(?:男生女生|男女)/u', $content, $matches)) {
                return $matches[1] ?? $matches[0];
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    /**
     * 提取所有性格特点匹配项
     */
    private static function extractAllCharacter($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/(?:比较|很|有点)(?:爱玩|活泼|文静|调皮|内向|外向)/u', $content, $matches)) {
                $results = array_merge($results, $matches[0]);
            }
            if (preg_match_all('/(?:班上|班级)(?:第[一二三四五六七八九十\d]+|前[一二三四五六七八九十\d]+|中等|一般)/u', $content, $matches)) {
                $results = array_merge($results, $matches[0]);
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有频率匹配项
     */
    private static function extractAllFrequency($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            // 匹配：一周一次、一周两次等
            if (preg_match_all('/一周(一次|两次|三次|四次|五次|六次|七次)/u', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $results[] = "一周" . $match;
                }
            }
            // 匹配：周一到周五、周末等
            if (preg_match_all('/(周[一到日]+|周末|平日|工作日)/u', $content, $matches)) {
                $results = array_merge($results, $matches[1]);
            }
            // 匹配：一周1-2次
            if (preg_match_all('/(?:一周|每周|每星期)(\d+)[\-]?(\d+)?次/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = $match;
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有具体时间匹配项（包括带-的范围）
     */
    private static function extractAllSpecificTime($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            // 匹配：周一到周五、周一至周五、周一到周几（带-或到）
            if (preg_match_all('/(周[一到日]+)[\-到至](周[一到日]+)/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $time = str_replace(['-', '至'], '到', $match);
                    $results[] = $time;
                }
            }
            
            // 匹配：周一到周五晚上七点到九点
            if (preg_match_all('/(?:周[一到日]+|周末|平日|工作日)(?:，|,|、)?(?:晚上|早上|下午|中午)?(?:[七八九十]|\d{1,2})[点:]?(?:到|至|\-)(?:[七八九十]|\d{1,2})[点:]/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = trim(str_replace('-', '到', $match), '，,。；; ');
                }
            }
            
            // 匹配：几点到几点、几点-几点（更通用的匹配，保留-）
            if (preg_match_all('/(\d{1,2})[点:](?:到|至|\-)(\d{1,2})[点:]/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $time = str_replace(['-', '至'], '到', $match);
                    $results[] = $time;
                }
            }
            
            // 匹配：下午几点开始、晚上几点开始等
            if (preg_match_all('/(?:早上|上午|中午|下午|晚上)(\d{1,2})[点:]?开始/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
            
            // 匹配：周二17点或周日9点开始
            if (preg_match_all('/(?:周[一到日]+|周末)(?:，|,|、)?(\d{1,2})[点:]?开始/u', $content, $matches)) {
                $results = array_merge($results, $matches[0]);
            }
            
            // 匹配时间点：17点、9点等
            if (preg_match_all('/(周[一到日]+)(?:，|,|、|或)?(\d{1,2})[点:]/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
            
            // 匹配：下午几点、晚上几点等
            if (preg_match_all('/(?:早上|上午|中午|下午|晚上)(\d{1,2})[点:]/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有时长匹配项
     */
    private static function extractAllDuration($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            // 匹配：2小时、1.5小时、2h等
            if (preg_match_all('/(\d+(?:\.\d+)?)[小时hH时]/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    if (preg_match('/(\d+(?:\.\d+)?)/u', $match, $dMatches)) {
                        $results[] = $dMatches[1] . "h/次";
                    }
                }
            }
            // 匹配：2h/次
            if (preg_match_all('/(\d+)[hH]\/次/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    if (preg_match('/(\d+)/u', $match, $dMatches)) {
                        $results[] = $dMatches[1] . "h/次";
                    }
                }
            }
            // 匹配：两个小时的表达
            if (preg_match_all('/(?:每次|每次辅导)(\d+)(?:小时|h)/u', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    if (preg_match('/(\d+)/u', $match, $dMatches)) {
                        $results[] = $dMatches[1] . "h/次";
                    }
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有"要求"二字之后的内容
     */
    private static function extractAllRequirementAfterWord($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/要求[：:]?\s*([^\n]+)/u', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $req = trim($match, '，,。；; ');
                    if (mb_strlen($req) > 2 && mb_strlen($req) < 100) {
                        $results[] = $req;
                    }
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有老师性别要求匹配项
     */
    private static function extractAllTeacherGender($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/(?:老师性别|性别要求|要求)[：:]?\s*(男女不限|不限|男老师|女老师|男|女)/u', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $results[] = trim($match, '，,。；; ');
                }
            }
            if (preg_match_all('/(?:男女不限|不限|男老师|女老师)/u', $content, $matches)) {
                $results = array_merge($results, $matches[0]);
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有老师类型要求匹配项
     */
    private static function extractAllTeacherType($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            if (preg_match_all('/(?:专业老师|专职老师|大学生|在校大学生|在职老师|有经验的|经验丰富)/u', $content, $matches)) {
                $results = array_merge($results, $matches[0]);
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有其他要求匹配项
     */
    private static function extractAllOtherRequirement($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        $patterns = [
            '/对老师要求[：:]\s*([^\n]+)/u',
            '/要求说明[：:]\s*([^\n]+)/u',
        ];
        
        foreach ($patterns as $pattern) {
            try {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        $req = trim($match, '，,。；; ');
                        if (mb_strlen($req) > 3 && mb_strlen($req) < 50) {
                            $results[] = $req;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return array_unique($results);
    }
    
    /**
     * 提取所有薪酬匹配项
     */
    private static function extractAllSalary($content)
    {
        if (empty($content) || !is_string($content)) {
            return [];
        }
        
        $results = [];
        try {
            // 匹配：240/次、400/两个小时、180-220/两个小时、80-100元等
            // 优先匹配明确的薪酬字段
            if (preg_match_all('/(?:薪酬|愿付|愿意支付|时薪|价格)[：:]\s*([^\n]+)/u', $content, $matches)) {
                foreach ($matches[1] as $salaryText) {
                    $salaryText = trim($salaryText);
                    // 尝试从明确的薪酬文本中提取
                    if (preg_match('/(\d+)-(\d+)[元\/]/u', $salaryText, $sMatches)) {
                        // 判断是范围还是金额/时长
                        if (strpos($salaryText, '/') !== false) {
                            $results[] = "{$sMatches[1]}-{$sMatches[2]}/次";
                        } else {
                            $results[] = "{$sMatches[1]}-{$sMatches[2]}元";
                        }
                    } elseif (preg_match('/(\d+)[\/](\d+)(?:小时|h)/u', $salaryText, $sMatches)) {
                        $results[] = "{$sMatches[1]}/次，每次{$sMatches[2]}小时";
                    }
                }
            }
            
            // 匹配常见格式（提取所有匹配项）
            $patterns = [
                '/(\d+)-(\d+)[\/](\d+)(?:小时|h)/u', // 180-220/两个小时
                '/(\d+)[\/](\d+)(?:小时|h)/u', // 400/两个小时
                '/(\d+)-(\d+)[元\/]/u', // 180-220/次 或 80-100元
                '/(\d+)[\/]次/u', // 240/次
                '/(\d+)-(\d+)元/u', // 80-100元
            ];
            
            foreach ($patterns as $pattern) {
                try {
                    if (preg_match_all($pattern, $content, $matches)) {
                        foreach ($matches[0] as $idx => $match) {
                            if (count($matches) >= 4 && isset($matches[3][$idx]) && is_numeric($matches[3][$idx])) {
                                // 金额/时长格式：400/两个小时
                                $results[] = "{$matches[1][$idx]}/次，每次{$matches[3][$idx]}小时";
                            } elseif (count($matches) >= 3 && isset($matches[2][$idx]) && is_numeric($matches[2][$idx])) {
                                // 范围格式
                                if (strpos($content, '/') !== false && !preg_match('/小时|h/i', $match)) {
                                    $results[] = "{$matches[1][$idx]}-{$matches[2][$idx]}/次";
                                } elseif (preg_match('/小时|h/i', $content)) {
                                    $results[] = "{$matches[1][$idx]}-{$matches[2][$idx]}/小时";
                                } elseif (strpos($match, '元') !== false) {
                                    $results[] = "{$matches[1][$idx]}-{$matches[2][$idx]}元/小时";
                                } else {
                                    $results[] = "{$matches[1][$idx]}-{$matches[2][$idx]}/次";
                                }
                            } else {
                                // 单一金额格式
                                if (preg_match('/小时|h/i', $content)) {
                                    $results[] = "{$matches[1][$idx]}/小时";
                                } else {
                                    $results[] = "{$matches[1][$idx]}/次";
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        } catch (\Exception $e) {
        }
        
        return array_unique($results);
    }
}

