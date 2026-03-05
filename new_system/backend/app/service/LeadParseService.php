<?php
namespace app\service;

use app\model\City;
use app\model\District;
use app\model\Admin;
use think\facade\Db;

/**
 * 线索文本解析服务
 * 复用RecognitionService的识别逻辑以提高准确性
 */
class LeadParseService
{
    /**
     * 解析线索文本
     * 
     * @param string $content 原始文本内容
     * @param int|null $creatorAdminId 创建者ID（用于默认客服识别）
     * @return array 解析结果
     * 
     * 示例输入：2501，深圳南山区初三数学需要物理老师，15986845083孙先生，指派给小刘
     * 或：2501，广州番禺南天名苑，小四英 男孩 80 来分 ，普通学校，  严厉点，幽默风趣，男女不限，一周3-5次，7点半-9点半，160-200/2小时 ，15986845083孙先生，指派给小刘
     */
    public static function parse($content, $creatorAdminId = null)
    {
        $result = [
            'lead_no'       => null,
            'city_id'       => null,
            'city_name'     => null,
            'district_id'   => null,
            'district_name' => null,
            'grade'         => null,
            'subject'       => null,
            'phone'         => null,
            'contact_name'  => null,
            'assigned_admin_id' => null,
            'assigned_admin_name' => null,
            'channel'       => null,
            'raw_content'   => $content,
        ];
        
        // 1. 解析线索编号（开头的4位数字）
        if (preg_match('/^(\d{4})/', $content, $matches)) {
            $result['lead_no'] = $matches[1];
        }
        
        // 2. 解析电话号码
        if (preg_match('/1[3-9]\d{9}/', $content, $matches)) {
            $result['phone'] = $matches[0];
            
            // 3. 解析联系人姓名（支持先生、女士、姐等称呼）
            $phonePos = strpos($content, $result['phone']);
            $afterPhone = substr($content, $phonePos + 11);
            // 匹配姓名，支持：先生、女士、姐、老师、家长等称呼
            if (preg_match('/^([^\s，,。；;]+(?:先生|女士|姐|老师|家长|小姐|大姐)?)/u', $afterPhone, $nameMatches)) {
                $result['contact_name'] = trim($nameMatches[1], '，,。；; ');
            }
        }
        
        // 4. 解析指派客服（智能识别）
        $result = array_merge($result, self::parseAssignedAdmin($content, $creatorAdminId));
        
        // 5. 解析城市和区域
        $locationResult = self::parseLocation($content);
        $result = array_merge($result, $locationResult);
        
        // 6. 解析年级
        $result['grade'] = self::parseGrade($content);
        
        // 7. 解析科目
        $result['subject'] = self::parseSubject($content);
        
        // 8. 解析渠道
        $result['channel'] = self::parseChannel($content);
        // 确保 channel 值在有效枚举范围内
        if (empty($result['channel']) || !in_array($result['channel'], ['美团', '58同城', '表单', '渠道生源', '其他'])) {
            $result['channel'] = '其他';
        }
        
        return $result;
    }
    
    /**
     * 解析渠道
     * 根据文本内容识别渠道类型
     * 优先级：表单（关键词或行数>5） > 美团 > 58同城 > 渠道生源 > 其他
     */
    private static function parseChannel($content)
    {
        // 1. 优先检查是否为表单（根据关键词或行数）
        // 先检查表单关键词
        $formKeywords = ['表单', '在线咨询', '网站咨询', '官网咨询', '官网表单', '网页咨询'];
        foreach ($formKeywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                return '表单';
            }
        }
        
        // 再检查内容行数是否超过5行，如果是则归类为表单
        $lines = explode("\n", $content);
        // 去除空行
        $lines = array_filter($lines, function($line) {
            return trim($line) !== '';
        });
        if (count($lines) > 5) {
            return '表单';
        }
        
        // 2. 其他渠道关键词匹配（优先级从高到低）
        $channelMap = [
            '美团'     => ['美团', 'meituan', 'MT', '美团咨询', '美团订单'],
            '58同城'   => ['58同城', '58', '五八同城', '58咨询', '58订单'],
            '渠道生源' => ['渠道生源', '推广渠道', '代理渠道', '渠道推广', '代理商'],
        ];
        
        foreach ($channelMap as $channel => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    return $channel;
                }
            }
        }
        
        return '其他';
    }
    
    /**
     * 解析指派客服（智能识别）
     * 优先级：
     * 1. 文本中明确指派的客服（指派给XXX、指派XXX、分给XXX等）
     * 2. 文本中有"指派"关键字 + 模糊匹配客服昵称（2个字吻合）
     * 3. 文本中提到的客服昵称（完整匹配）
     * 4. 文本中模糊匹配客服昵称（2个字吻合）
     * 5. 录入者本人（如果是客服）
     */
    private static function parseAssignedAdmin($content, $creatorAdminId = null)
    {
        $result = [
            'assigned_admin_id' => null,
            'assigned_admin_name' => null,
        ];
        
        // 获取所有客服列表
        $customerServices = Admin::where('role', 'customer_service')
            ->where('status', 1)
            ->select();
        
        // 1. 优先识别"指派给XXX"、"指派XXX"、"分给XXX"等明确指派格式
        $assignPatterns = [
            '/指派给([^\s，,。；;]+)/u',
            '/指派[给到]([^\s，,。；;]+)/u',
            '/分给([^\s，,。；;]+)/u',
            '/分配给([^\s，,。；;]+)/u',
        ];
        
        foreach ($assignPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $adminNickname = trim($matches[1], '，,。；; ');
                // 精确匹配
                foreach ($customerServices as $admin) {
                    if ($admin->nickname === $adminNickname || mb_strpos($admin->nickname, $adminNickname) !== false) {
                        $result['assigned_admin_id'] = $admin->id;
                        $result['assigned_admin_name'] = $admin->nickname;
                        return $result;
                    }
                }
                // 模糊匹配（2个字吻合）
                foreach ($customerServices as $admin) {
                    if (self::matchTwoChars($adminNickname, $admin->nickname)) {
                        $result['assigned_admin_id'] = $admin->id;
                        $result['assigned_admin_name'] = $admin->nickname;
                        return $result;
                    }
                }
            }
        }
        
        // 2. 检测是否有"指派"关键字，如果有则进行模糊匹配
        if (preg_match('/指派/u', $content)) {
            // 提取"指派"前后的内容进行匹配
            foreach ($customerServices as $admin) {
                $nickname = $admin->nickname;
                // 检查昵称是否在文本中（支持部分匹配）
                if (mb_strpos($content, $nickname) !== false) {
                    $result['assigned_admin_id'] = $admin->id;
                    $result['assigned_admin_name'] = $admin->nickname;
                    return $result;
                }
                // 模糊匹配：检查是否有2个字吻合
                if (self::matchTwoCharsInText($nickname, $content)) {
                    $result['assigned_admin_id'] = $admin->id;
                    $result['assigned_admin_name'] = $admin->nickname;
                    return $result;
                }
            }
        }
        
        // 3. 在文本中完整匹配客服昵称
        foreach ($customerServices as $admin) {
            if (mb_strpos($content, $admin->nickname) !== false) {
                $result['assigned_admin_id'] = $admin->id;
                $result['assigned_admin_name'] = $admin->nickname;
                return $result;
            }
        }
        
        // 4. 模糊匹配客服昵称（2个字吻合）
        foreach ($customerServices as $admin) {
            if (self::matchTwoCharsInText($admin->nickname, $content)) {
                $result['assigned_admin_id'] = $admin->id;
                $result['assigned_admin_name'] = $admin->nickname;
                return $result;
            }
        }
        
        // 5. 如果没有找到，使用录入者（如果录入者是客服）
        if ($creatorAdminId) {
            $creator = Admin::where('id', $creatorAdminId)
                ->where('role', 'customer_service')
                ->find();
            if ($creator) {
                $result['assigned_admin_id'] = $creator->id;
                $result['assigned_admin_name'] = $creator->nickname;
            }
        }
        
        return $result;
    }
    
    /**
     * 检查两个字符串是否有至少2个连续字符相同
     * @param string $str1 字符串1
     * @param string $str2 字符串2
     * @return bool
     */
    private static function matchTwoChars($str1, $str2)
    {
        if (empty($str1) || empty($str2)) {
            return false;
        }
        
        $str1 = mb_convert_encoding($str1, 'UTF-8', 'auto');
        $str2 = mb_convert_encoding($str2, 'UTF-8', 'auto');
        
        $len1 = mb_strlen($str1, 'UTF-8');
        $len2 = mb_strlen($str2, 'UTF-8');
        
        // 检查是否有至少2个连续字符相同
        for ($i = 0; $i <= $len1 - 2; $i++) {
            $twoChars = mb_substr($str1, $i, 2, 'UTF-8');
            if (mb_strpos($str2, $twoChars, 0, 'UTF-8') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查昵称是否在文本中至少有2个字吻合
     * @param string $nickname 昵称
     * @param string $text 文本
     * @return bool
     */
    private static function matchTwoCharsInText($nickname, $text)
    {
        if (empty($nickname) || empty($text)) {
            return false;
        }
        
        $nickname = mb_convert_encoding($nickname, 'UTF-8', 'auto');
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        
        $len = mb_strlen($nickname, 'UTF-8');
        
        // 如果昵称长度小于2，直接完整匹配
        if ($len < 2) {
            return mb_strpos($text, $nickname, 0, 'UTF-8') !== false;
        }
        
        // 检查昵称的每个2字符子串是否在文本中出现
        $matchCount = 0;
        for ($i = 0; $i <= $len - 2; $i++) {
            $twoChars = mb_substr($nickname, $i, 2, 'UTF-8');
            if (mb_strpos($text, $twoChars, 0, 'UTF-8') !== false) {
                $matchCount++;
            }
        }
        
        // 如果有至少2个连续字符吻合，或者匹配的子串数超过一定比例，认为匹配
        return $matchCount >= 1 && ($len <= 3 || $matchCount >= ceil($len / 2));
    }
    
    /**
     * 解析城市和区域
     * 复用家教信息的识别逻辑
     */
    private static function parseLocation($content)
    {
        $result = [
            'city_id'       => null,
            'city_name'     => null,
            'district_id'   => null,
            'district_name' => null,
        ];
        
        // 使用RecognitionService的识别方法
        $recognitionService = new RecognitionService();
        $location = $recognitionService->recognizeLocation($content);
        
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
        
        return $result;
    }
    
    /**
     * 解析年级
     * 复用家教信息的识别逻辑（返回年级段：幼儿、小学、初中、高中、成人）
     */
    private static function parseGrade($content)
    {
        // 使用RecognitionService的识别方法
        $recognitionService = new RecognitionService();
        $grade = $recognitionService->recognizeGrade($content);
        
        return $grade;
    }
    
    /**
     * 解析科目
     * 复用家教信息的识别逻辑（从数据库中匹配科目）
     */
    private static function parseSubject($content)
    {
        // 使用RecognitionService的识别方法
        $recognitionService = new RecognitionService();
        $subjectInfo = $recognitionService->recognizeSubject($content);
        
        // 如果识别到科目，返回科目名称
        if ($subjectInfo) {
            return $subjectInfo['name'];
        }
        
        return null;
    }
    
    /**
     * 生成线索编号
     * 格式：当前日期(MMDD) + 4位随机数
     */
    public static function generateLeadNo()
    {
        $datePrefix = date('md'); // 月日，例如：1019
        $randomNum = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $datePrefix . $randomNum;
    }
    
    /**
     * 生成唯一线索编号
     */
    public static function generateUniqueLeadNo()
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            $leadNo = self::generateLeadNo();
            $exists = Db::name('leads')->where('lead_no', $leadNo)->count() > 0;
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);
        
        if ($exists) {
            // 如果10次尝试都重复，使用时间戳
            $leadNo = date('mdHis');
        }
        
        return $leadNo;
    }
}

