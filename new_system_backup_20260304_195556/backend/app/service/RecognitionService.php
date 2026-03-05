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
     * 年级模式
     */
    private $gradePatterns = [
        '幼儿' => '/幼儿园|幼儿|幼小|学前班|大班|中班|小班|托班/',
        '小学' => '/小学|一年级|1年级|小一|二年级|2年级|小二|三年级|3年级|小三|四年级|4年级|小四|五年级|5年级|小五|六年级|6年级|小六/',
        '初中' => '/初中|初一|初1|7年级|七年级|初二|初2|8年级|八年级|初三|初3|9年级|九年级/',
        '高中' => '/高中|高一|高1|高二|高2|高三|高3/',
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
     * 批量识别文本
     * @param string $text 待识别文本
     * @return array 识别结果数组
     */
    public function batchRecognize($text)
    {
        // 分割文本（按两个或更多换行符）
        $items = preg_split('/\n{2,}/', trim($text));
        $results = [];
        
        foreach ($items as $item) {
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
            'content'     => $text,
            'city_id'     => null,
            'district_id' => null,
            'grade'       => null,
            'subject_id'  => null,
            'salary'      => null,
        ];
        
        // 识别城市和区域
        $location = $this->recognizeLocation($text);
        if ($location) {
            $result['city_id'] = $location['city_id'];
            $result['district_id'] = $location['district_id'];
        }
        
        // 识别年级
        $result['grade'] = $this->recognizeGrade($text);
        
        // 识别科目
        $subject = $this->recognizeSubject($text);
        if ($subject) {
            $result['subject_id'] = $subject['id'];
        }
        
        // 识别薪资
        $result['salary'] = $this->recognizeSalary($text);
        
        return $result;
    }
    
    /**
     * 识别城市和区域
     * @param string $text 文本
     * @return array|null ['city_id' => xxx, 'district_id' => xxx]
     */
    public function recognizeLocation($text)
    {
        // 预处理文本
        $text = preg_replace('/[【】\[\]]/', '', $text);
        
        // 检查是否为线上家教
        if (preg_match('/(线上|网络|远程|在线|视频|网课)/', $text)) {
            // 查找"全国"城市
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
        
        // 获取所有启用的城市和区域
        $cities = City::where('status', 1)->select()->toArray();
        $foundCity = null;
        $foundDistrict = null;
        
        // 1. 先尝试精确匹配：城市+区域
        foreach ($cities as $city) {
            $cityName = $city['name'];
            $cityNameShort = str_replace('市', '', $cityName);
            
            // 检查文本中是否包含城市名
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
                    
                    if (strpos($text, $districtNameShort) !== false || strpos($text, $districtName) !== false) {
                        $foundDistrict = $district;
                        break 2; // 找到城市和区域，跳出所有循环
                    }
                }
            }
        }
        
        // 2. 如果只找到城市没找到区域，尝试查找所有区域
        if ($foundCity && !$foundDistrict) {
            $districts = District::where('city_id', $foundCity['id'])
                ->where('status', 1)
                ->select()
                ->toArray();
            
            foreach ($districts as $district) {
                $districtName = $district['name'];
                $districtNameShort = str_replace(['区', '县', '镇', '街道'], '', $districtName);
                
                if (strpos($text, $districtNameShort) !== false || strpos($text, $districtName) !== false) {
                    $foundDistrict = $district;
                    break;
                }
            }

        }
        
        // 3. 如果没找到城市，尝试只匹配区域，然后反查城市
        if (!$foundCity) {
            $allDistricts = District::where('status', 1)->select()->toArray();
            
            foreach ($allDistricts as $district) {
                $districtName = $district['name'];
                $districtNameShort = str_replace(['区', '县', '镇', '街道'], '', $districtName);
                
                if (strpos($text, $districtNameShort) !== false || strpos($text, $districtName) !== false) {
                    $foundDistrict = $district;
                    // 反查城市
                    $foundCity = City::find($district['city_id']);
                    if ($foundCity) {
                        $foundCity = $foundCity->toArray();
                    }
                    break;
                }

            }
        }
        
        if ($foundCity || $foundDistrict) {
            return [
                'city_id'     => $foundCity ? $foundCity['id'] : null,
                'district_id' => $foundDistrict ? $foundDistrict['id'] : null,
            ];
        }
        
        return null;
    }
    
    /**
     * 识别年级
     * @param string $text 文本
     * @return string|null 年级
     */
    public function recognizeGrade($text)
    {
        foreach ($this->gradePatterns as $grade => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $matches[0]; // 返回匹配到的具体年级文本
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
}


