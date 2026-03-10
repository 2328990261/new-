<?php
namespace app\service;

use app\model\City;
use app\model\District;
use app\model\Subject;

/**
 * 智能识别服务
 * 迁移自原系统的recognition.php
 */
class RecognitionService
{
    /**
     * 年级模式 - 只识别年级段，不识别具体年级
     */
    private $gradePatterns = [
        '幼儿' => '/幼儿园|幼儿|幼小|学前班|学前教育|大班|中班|小班|托班|幼教/',
        '小学' => '/小学生?|一年级|1年级|小一|二年级|2年级|小二|三年级|3年级|小三|四年级|4年级|小四|五年级|5年级|小五|六年级|6年级|小六|小升初|小学阶段/',
        '初中' => '/初中生?|初一|初1|7年级|七年级|初二|初2|8年级|八年级|初三|初3|9年级|九年级|中考|初升高|初中阶段/',
        '高中' => '/高中生?|高一|高1|高二|高2|高三|高3|高考|高中阶段/',
        '成人' => '/成人|成年人|成人教育|成人培训|成人学习|成人课程|成教|成考|自考|专升本|函授|夜大|成人高考/',
    ];
    
    /**
     * 科目模式（正则匹配）
     */
    private $subjectPatterns = [
        '语文' => '/语文/',
        '数学' => '/数学/',
        '英语' => '/英语|英文/',
        '物理' => '/物理/',
        '化学' => '/化学/',
        '生物' => '/生物/',
        '政治' => '/政治/',
        '历史' => '/历史/',
        '地理' => '/地理/',
        '科学' => '/科学/',
        '音乐' => '/音乐/',
        '美术' => '/美术/',
        '体育' => '/体育/',
    ];
    
    /**
     * 薪资识别模式
     */
    private $salaryPatterns = [
        '/【(?:薪酬|课时费|课酬|薪资|工资|费用)】[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
        '/(?:课费|课时费)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
        '/(?:课时费|课酬|薪资|工资|费用)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:左右|上下)\s*(元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)?/',
        '/(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元每课时|元每小时|元每节课|元每次|元每月|元\/课时|元\/小时|元\/次|元\/月|\/课时|\/小时|\/次|\/月|元|块|块钱)(?!\d)/',
        '/(?:月薪|月工资|月收入)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(?:[-~到至]\s*(\d+(?:[-.．]\d+)?))?\s*(元|块|块钱)?/',
        '/(?:一节课|每节课|每节|每小时|每课时)[：:\s]*(\d+(?:[-.．]\d+)?)\s*(元|块|块钱)?/',
    ];
    
    /**
     * 老师类型关键词
     * professional: 专职老师 (专职、专业老师、在校老师、机构老师)
     * student: 大学生 (其他)
     */
    private $teacherTypePatterns = [
        'professional' => '/专职|专业老师|在校老师|机构老师/',
    ];
    
    /**
     * 教师性别关键词
     * male: 男老师
     * female: 女老师
     * unlimited: 男女不限 (默认)
     */
    private $teacherGenderPatterns = [
        'male' => '/男老师|男教师|男大学生|男生家教|要男|需要男/',
        'female' => '/女老师|女教师|女大学生|女生家教|要女|需要女/',
    ];
    
    /**
     * 批量识别文本
     * @param string $text 待识别文本
     * @return array 识别结果数组
     */
    public function batchRecognize($text)
    {
        // 统一换行符
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        
        // 按空行分割（两个或更多换行符）
        $items = preg_split('/\n{2,}/', $text);
        
        $results = [];
        foreach ($items as $index => $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }
            
            $result = $this->recognizeSingle($item);
            if ($result) {
                $results[] = $result;
            }
        }
        
        return $results;
    }
    
    /**
     * 识别单条文本
     * @param string $text 待识别文本
     * @return array|null 识别结果
     */
    public function recognizeSingle($text)
    {
        if (empty($text)) {
            return null;
        }
        
        $result = [
            'content'       => $text,
            'city_id'       => null,
            'city_name'     => null,
            'district_id'   => null,
            'district_name' => null,
            'grade'         => null,
            'subject_id'    => null,
            'subject_name'  => null,
            'salary'        => null,
            'teacher_type'  => null,
        ];
        
        // 识别城市和区域
        $location = $this->recognizeLocation($text);
        if ($location) {
            $result['city_id'] = $location['city_id'];
            $result['district_id'] = $location['district_id'];
            
            // 获取城市名称
            if ($location['city_id']) {
                $city = City::find($location['city_id']);
                if ($city) {
                    $result['city_name'] = $city->name;
                }
            }
            
            // 获取区域名称
            if ($location['district_id']) {
                $district = District::find($location['district_id']);
                if ($district) {
                    $result['district_name'] = $district->name;
                }
            }
        }
        
        // 识别年级
        $result['grade'] = $this->recognizeGrade($text);
        
        // 识别科目
        $subject = $this->recognizeSubject($text);
        if ($subject) {
            $result['subject_id'] = $subject['id'];
            $result['subject_name'] = $subject['name'];
        }
        
        // 识别薪资
        $result['salary'] = $this->recognizeSalary($text);
        
        // 识别老师类型
        $result['teacher_type'] = $this->recognizeTeacherType($text);
        
        // 识别教师性别
        $result['teacher_gender'] = $this->recognizeTeacherGender($text);
        
        return $result;
    }
    
    /**
     * 识别城市和区域
     * @param string $text 文本
     * @return array|null ['city_id' => xxx, 'district_id' => xxx]
     */
    public function recognizeLocation($text)
    {
        // 预处理文本 - 移除方括号
        $text = str_replace(['【', '】', '[', ']'], '', $text);
        
        // 检查是否为线上家教
        if (preg_match('/(线上|网络|远程|在线|视频|网课)/', $text)) {
            $city = City::where('name', '全国')->find();
            if ($city) {
                $district = District::where('city_id', $city->id)
                    ->where('name', 'like', '%线上%')
                    ->find();
                return [
                    'city_id' => $city->id,
                    'district_id' => $district ? $district->id : null
                ];
            }
            return null;
        }
        
        // 🔥 定义sort阈值：小于此值为延迟匹配城市（如香港、澳门等）
        $DELAY_MATCH_THRESHOLD = 50;
        
        // ========================================
        // 第一阶段：匹配优先级城市（sort >= 50）
        // ========================================
        $result = $this->matchCitiesBySort($text, $DELAY_MATCH_THRESHOLD, '>=');
        if ($result) {
            return $result;
        }
        
        // ========================================
        // 第二阶段：匹配延迟城市（sort < 50）
        // 条件：文本中必须包含特殊关键词
        // ========================================
        
        // 香港：必须包含"香港"关键词
        if (strpos($text, '香港') !== false) {
            $result = $this->matchCitiesBySort($text, $DELAY_MATCH_THRESHOLD, '<');
            if ($result) {
                return $result;
            }
        }
        
        // 澳门：必须包含"澳门"关键词
        if (strpos($text, '澳门') !== false) {
            $result = $this->matchCitiesBySort($text, $DELAY_MATCH_THRESHOLD, '<');
            if ($result) {
                return $result;
            }
        }
        
        return null;
    }
    
    /**
     * 根据sort值范围匹配城市
     * @param string $text 文本
     * @param int $threshold sort阈值
     * @param string $operator 比较运算符：'>=' 或 '<'
     * @return array|null
     */
    private function matchCitiesBySort($text, $threshold, $operator)
    {
        // 获取指定sort范围的城市
        $query = City::where('status', 1);
        if ($operator === '>=') {
            $query->where('sort', '>=', $threshold);
        } else {
            $query->where('sort', '<', $threshold);
        }
        $cities = $query->select()->toArray();
        
        if (empty($cities)) {
            return null;
        }
        
        $foundCity = null;
        $foundDistrict = null;
        
        // 步骤1: 精确匹配城市+区域
        foreach ($cities as $city) {
            $cityName = $city['name'];
            $cityNameShort = str_replace('市', '', $cityName);
            
            if (strpos($text, $cityNameShort) !== false || strpos($text, $cityName) !== false) {
                $foundCity = $city;
                
                // 在该城市下查找区域
                $districts = District::where('city_id', $city['id'])
                    ->where('status', 1)
                    ->select()
                    ->toArray();
                
                foreach ($districts as $district) {
                    $districtName = $district['name'];
                    $districtNameShort = str_replace(['区', '县', '镇', '街道'], '', $districtName);
                    
                    if (strpos($text, $districtNameShort) !== false || 
                        strpos($text, $districtName) !== false) {
                        $foundDistrict = $district;
                        break 2;
                    }
                }
            }
        }
        
        // 步骤2: 只找到城市，补充查找区域
        if ($foundCity && !$foundDistrict) {
            $districts = District::where('city_id', $foundCity['id'])
                ->where('status', 1)
                ->select()
                ->toArray();
            
            foreach ($districts as $district) {
                $districtName = $district['name'];
                $districtNameShort = str_replace(['区', '县', '镇', '街道'], '', $districtName);
                
                if (strpos($text, $districtNameShort) !== false || 
                    strpos($text, $districtName) !== false) {
                    $foundDistrict = $district;
                    break;
                }
            }
        }
        
        // 步骤3: 没找到城市，尝试只匹配区域反查
        if (!$foundCity) {
            $cityIds = array_column($cities, 'id');
            $allDistricts = District::where('status', 1)
                ->whereIn('city_id', $cityIds)
                ->select()
                ->toArray();
            
            $matchedResults = [];
            
            foreach ($allDistricts as $district) {
                $districtName = $district['name'];
                $districtNameShort = str_replace(['区', '县', '镇', '街道'], '', $districtName);
                
                if (strpos($text, $districtNameShort) !== false || 
                    strpos($text, $districtName) !== false) {
                    // 反查城市
                    $city = array_filter($cities, function($c) use ($district) {
                        return $c['id'] == $district['city_id'];
                    });
                    
                    if (!empty($city)) {
                        $matchedResults[] = [
                            'district' => $district,
                            'city' => reset($city)
                        ];
                    }
                }
            }
            
            // 多个匹配结果，按优先级排序
            if (!empty($matchedResults)) {
                $levelPriority = [
                    '一线城市' => 1,
                    '新一线城市' => 2,
                    '二线城市' => 3,
                    '三线城市' => 4,
                ];
                
                usort($matchedResults, function($a, $b) use ($levelPriority) {
                    $levelA = $a['city']['level'] ?? '';
                    $levelB = $b['city']['level'] ?? '';
                    
                    $priorityA = $levelPriority[$levelA] ?? 999;
                    $priorityB = $levelPriority[$levelB] ?? 999;
                    
                    if ($priorityA != $priorityB) {
                        return $priorityA - $priorityB;
                    }
                    
                    // 🔥 关键：sort大的优先（因为大数字=高优先级）
                    return ($b['city']['sort'] ?? 0) - ($a['city']['sort'] ?? 0);
                });
                
                $bestMatch = $matchedResults[0];
                $foundDistrict = $bestMatch['district'];
                $foundCity = $bestMatch['city'];
            }
        }
        
        // 返回结果
        if ($foundCity || $foundDistrict) {
            return [
                'city_id'     => $foundCity ? $foundCity['id'] : null,
                'district_id' => $foundDistrict ? $foundDistrict['id'] : null,
            ];
        }
        
        return null;
    }
    
    /**
     * 识别年级段
     * @param string $text 文本
     * @return string|null 年级段（幼儿、小学、初中、高中、成人）
     */
    public function recognizeGrade($text)
    {
        foreach ($this->gradePatterns as $grade => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $grade; // 返回年级段，不是具体年级
            }
        }
        
        return null;
    }
    
    /**
     * 识别科目
     * @param string $text 文本
     * @return array|null 科目信息
     */
    public function recognizeSubject($text)
    {
        // 先从数据库中匹配
        $subjects = Subject::where('status', 1)->select();
        
        foreach ($subjects as $subject) {
            if (strpos($text, $subject->name) !== false) {
                return $subject->toArray();
            }
        }
        
        // 如果数据库没匹配到，使用正则匹配
        foreach ($this->subjectPatterns as $subjectName => $pattern) {
            if (preg_match($pattern, $text)) {
                // 尝试从数据库找到对应科目
                $subject = Subject::where('name', $subjectName)->where('status', 1)->find();
                if ($subject) {
                    return $subject->toArray();
                }
            }
        }
        
        return null;
    }
    
    /**
     * 识别薪资
     * @param string $text 文本
     * @return string|null 薪资字符串
     */
    public function recognizeSalary($text)
    {
        foreach ($this->salaryPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount1 = $matches[1] ?? '';
                $amount2 = $matches[2] ?? '';
                $unit = $matches[3] ?? '';
                
                // 标准化单位
                $unit = $this->normalizeUnit($unit, $text);
                
                // 格式化输出
                if ($amount2 && $amount1 != $amount2) {
                    return $amount1 . '-' . $amount2 . $unit;
                } else {
                    return $amount1 . $unit;
                }
            }
        }
        
        return null;
    }
    
    /**
     * 标准化单位
     * @param string $unit 单位文本
     * @param string $text 完整文本
     * @return string 标准化后的单位
     */
    private function normalizeUnit($unit, $text)
    {
        // 处理小时格式
        if (preg_match('/小时|h|H/i', $unit)) {
            return '/小时';
        }
        
        // 处理月薪格式
        if (preg_match('/月薪|月工资|每月|\/月/', $unit) || preg_match('/月薪|月工资/', $text)) {
            return '/月';
        }
        
        // 处理课时格式
        if (preg_match('/课时/', $unit) || preg_match('/课时费/', $text)) {
            return '/课时';
        }
        
        // 处理次/节课格式
        if (preg_match('/[\/每]节|课节|次/', $unit) || preg_match('/一节课|每节课/', $text)) {
            return '/次';
        }
        
        // 默认返回/次
        return '/次';
    }
    
    /**
     * 识别老师类型
     * @param string $text 文本
     * @return string 老师类型 (professional: 专职老师, student: 大学生)
     */
    private function recognizeTeacherType($text)
    {
        // 检查是否包含专职老师关键词
        if (preg_match($this->teacherTypePatterns['professional'], $text, $matches)) {
            return 'professional';
        }
        
        // 默认为大学生
        return 'student';
    }
    
    /**
     * 识别教师性别
     * @param string $text 文本
     * @return string 教师性别 (male: 男老师, female: 女老师, unlimited: 男女不限)
     */
    private function recognizeTeacherGender($text)
    {
        // 检查是否包含女老师关键词
        if (preg_match($this->teacherGenderPatterns['female'], $text)) {
            return 'female';
        }
        
        // 检查是否包含男老师关键词
        if (preg_match($this->teacherGenderPatterns['male'], $text)) {
            return 'male';
        }
        
        // 默认为男女不限
        return 'unlimited';
    }
}


