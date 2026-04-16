<?php
namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use app\service\MiniProgramConfigService;

/**
 * 小程序订阅消息服务
 */
class SubscribeMessageService
{
    // 模板ID
    const TEMPLATE_ID = 'szFjrvi1RabxvzvKV-zxkAHyb2aeu3wT46IzM3t8fHo';
    // 简历审核结果通知（小程序订阅消息模板）
    const RESUME_REVIEW_TEMPLATE_ID = 'TGeivjshmTSB45SgtaLUORhwkDifcz9rsrdDZjy_aAU';

    /**
     * 按业务代码读取已启用的小程序订阅模板ID（表不可用时回退到上方常量）
     */
    public static function getTemplateIdByCode(string $code): string
    {
        $code = trim($code);
        if ($code === '') {
            return self::TEMPLATE_ID;
        }
        try {
            $row = Db::name('mini_subscribe_templates')
                ->where('template_code', $code)
                ->where('is_enabled', 1)
                ->find();
            if ($row && !empty($row['template_id'])) {
                return (string)$row['template_id'];
            }
        } catch (\Throwable $e) {
            trace('读取 mini_subscribe_templates 失败: ' . $e->getMessage(), 'warning');
        }
        if ($code === 'resume_review') {
            return self::RESUME_REVIEW_TEMPLATE_ID;
        }

        return self::TEMPLATE_ID;
    }

    /**
     * 供小程序拉取：已启用模板 code => template_id，缺省键用常量补齐
     *
     * @return array<string, string>
     */
    public static function getEnabledTemplatesMap(): array
    {
        $out = [];
        try {
            $rows = Db::name('mini_subscribe_templates')->where('is_enabled', 1)->select();
            foreach ($rows as $r) {
                $c = (string)($r['template_code'] ?? '');
                $tid = (string)($r['template_id'] ?? '');
                if ($c !== '' && $tid !== '') {
                    $out[$c] = $tid;
                }
            }
        } catch (\Throwable $e) {
            trace('读取 mini_subscribe_templates 列表失败: ' . $e->getMessage(), 'warning');
        }
        if (!isset($out['tutor_recommend'])) {
            $out['tutor_recommend'] = self::TEMPLATE_ID;
        }
        if (!isset($out['resume_review'])) {
            $out['resume_review'] = self::RESUME_REVIEW_TEMPLATE_ID;
        }

        return $out;
    }

    /**
     * 将配置 env_version 映射为微信订阅消息投递环境
     * - release / formal => formal
     * - trial => trial
     * - develop / dev / developer => developer
     */
    private static function resolveMiniProgramState(string $envVersion): string
    {
        $v = strtolower(trim($envVersion));
        if ($v === 'trial') {
            return 'trial';
        }
        if ($v === 'develop' || $v === 'dev' || $v === 'developer') {
            return 'developer';
        }
        return 'formal';
    }

    /** 订阅消息 phrase 类型常见上限约 5 个字符 */
    private static function limitPhraseValue(string $s): string
    {
        $s = trim($s);
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, 5, 'UTF-8');
        }

        return strlen($s) > 15 ? substr($s, 0, 15) : $s;
    }

    /** 订阅消息 thing 类型常见上限约 20 个字符 */
    private static function limitThingValue(string $s): string
    {
        $s = trim($s);
        if (function_exists('mb_substr')) {
            return mb_substr($s, 0, 20, 'UTF-8');
        }

        return strlen($s) > 60 ? substr($s, 0, 60) : $s;
    }

    /**
     * 订阅消息 amount 类型：尽量提取数字，兜底 0（避免因格式不合规导致发送失败）
     * 微信模板的 amount 字段通常要求“数字/金额”，不建议带过多文字。
     */
    private static function normalizeAmountValue($raw): string
    {
        $s = trim((string)$raw);
        if ($s === '') {
            return '0';
        }
        // 常见形态：130-150元/小时、150元、150
        if (preg_match('/(\d+(?:\.\d+)?)/', $s, $m)) {
            return (string)$m[1];
        }
        return '0';
    }

    /**
     * 从家教单 content 中抽取「时间频率」与「授课方式」等关键信息（适配多种录入模板）
     * @return array{frequency:string, teaching_method:string}
     */
    private static function extractTutorHintsFromContent($content): array
    {
        $content = (string)$content;
        $frequency = '';
        $teachingMethod = '';

        // 频率：优先匹配【时间】行
        if (preg_match('/【\s*时间\s*】\s*([^\r\n]+)/u', $content, $m)) {
            $frequency = trim((string)$m[1]);
        } elseif (preg_match('/【\s*时间频率\s*】\s*([^\r\n]+)/u', $content, $m)) {
            $frequency = trim((string)$m[1]);
        } elseif (preg_match('/【\s*频率\s*】\s*([^\r\n]+)/u', $content, $m)) {
            $frequency = trim((string)$m[1]);
        }
        // 兜底：常见“一周X次”
        if ($frequency === '' && preg_match('/一周\s*\d+\s*[-—~]?\s*\d*\s*次/u', $content, $m)) {
            $frequency = trim((string)$m[0]);
        }

        // 授课方式：匹配“线上/网课/上门/到店”等关键词
        if (preg_match('/(线上授课|线上|网课|上门辅导|上门授课|上门|到店|机构|面授)/u', $content, $m)) {
            $kw = (string)$m[1];
            if (mb_strpos($kw, '线上') !== false || mb_strpos($kw, '网课') !== false) {
                $teachingMethod = '线上授课';
            } elseif (mb_strpos($kw, '到店') !== false || mb_strpos($kw, '机构') !== false) {
                $teachingMethod = '到店授课';
            } else {
                $teachingMethod = '上门授课';
            }
        }

        return [
            'frequency' => $frequency,
            'teaching_method' => $teachingMethod,
        ];
    }
    
    /**
     * 发送家教推荐订阅消息
     * 
     * @param string $openid 用户OpenID
     * @param array $data 消息数据
     * @return array
     */
    public static function sendTutorRecommendMessage($openid, $data)
    {
        try {
            $tplId = self::getTemplateIdByCode('tutor_recommend');
            // 检查用户是否有可用的订阅（若缺表则跳过检查，直接尝试发送，由微信侧校验订阅状态）
            $subscribe = null;
            try {
                $subscribe = Db::name('user_subscribe')
                    ->where('openid', $openid)
                    ->where('template_id', $tplId)
                    ->where('is_used', 0)
                    ->order('subscribe_time', 'desc')
                    ->find();
            } catch (\Throwable $e) {
                // 常见：线上缺少 fa_user_subscribe 表
                trace('读取 user_subscribe 失败，跳过订阅检查：' . $e->getMessage(), 'warning');
            }
            $subscribeId = $subscribe ? (int)($subscribe['id'] ?? 0) : 0;
            $subscribeUserId = $subscribe ? ($subscribe['user_id'] ?? null) : null;
            
            // 获取小程序配置（统一从运行配置读取，支持多小程序）
            $runtimeConfig = (new MiniProgramConfigService())->getRuntimeConfig('wechat');
            $appId = (string)($runtimeConfig['app_id'] ?? '');
            $appSecret = (string)($runtimeConfig['app_secret'] ?? '');
            $miniState = self::resolveMiniProgramState((string)($runtimeConfig['env_version'] ?? 'release'));
            
            if (!$appId || !$appSecret) {
                throw new \Exception('小程序配置未完成');
            }
            
            /**
             * 重要：你当前只能使用「公共模板库」模板，因此这里必须与所选模板关键词完全匹配。
             * 你截图里的模板为：
             * - 科目 {{thing1.DATA}}
             * - 课时费 {{amount2.DATA}}
             * - 辅导频率 {{thing3.DATA}}
             * - 辅导地点 {{thing4.DATA}}
             * - 辅导方式 {{thing6.DATA}}
             */
            $gradeSubject = trim((string)($data['grade'] ?? '')) . ' ' . trim((string)($data['subject'] ?? ''));
            $location = trim((string)($data['city'] ?? '') . ' ' . (string)($data['district'] ?? ''));
            $fee = self::normalizeAmountValue($data['salary'] ?? ($data['fee'] ?? ''));

            // 从 content 提取更真实的频率/授课方式（适配你们录入模板）
            $hints = self::extractTutorHintsFromContent($data['content'] ?? '');
            $teachingMethod = trim((string)($data['teaching_method'] ?? ''));
            if ($teachingMethod === '' && $hints['teaching_method'] !== '') {
                $teachingMethod = $hints['teaching_method'];
            }
            if ($teachingMethod === '') {
                $teachingMethod = '上门授课';
            }
            $frequency = trim((string)($data['frequency'] ?? ''));
            if ($frequency === '' && $hints['frequency'] !== '') {
                $frequency = $hints['frequency'];
            }
            if ($frequency === '') {
                $frequency = '有新家教订单';
            }

            // 构建消息数据（字段 key 必须与模板一致）
            $messageData = [
                'thing1' => [   // 科目（可放“年级+科目”）
                    'value' => self::limitThingValue(trim($gradeSubject))
                ],
                'amount2' => [  // 课时费（金额字段）
                    'value' => $fee
                ],
                'thing3' => [   // 辅导频率（优先从 content 的【时间】解析）
                    'value' => self::limitThingValue($frequency)
                ],
                'thing4' => [   // 辅导地点
                    'value' => self::limitThingValue($location !== '' ? $location : '请进入小程序查看')
                ],
                'thing6' => [   // 辅导方式
                    'value' => self::limitThingValue($teachingMethod !== '' ? $teachingMethod : '上门授课')
                ],
            ];
            
            // 构建请求数据
            $postData = [
                'touser' => $openid,
                'template_id' => $tplId,
                'page' => 'pages/tutor-detail/index?id=' . ($data['tutor_id'] ?? ''),
                'data' => $messageData,
                'miniprogram_state' => $miniState
            ];
            
            $response = self::postSubscribeMessageSend($appId, $appSecret, $postData);
            
            // 判断发送结果
            $success = isset($response['errcode']) && $response['errcode'] == 0;
            
            // 记录日志
            $logId = self::logMessage($subscribeUserId, $openid, $postData, $response);
            
            // 如果发送成功，标记订阅为已使用
            if ($success) {
                if ($subscribeId > 0) {
                    try {
                        Db::name('user_subscribe')
                            ->where('id', $subscribeId)
                            ->update([
                                'is_used' => 1,
                                'used_time' => date('Y-m-d H:i:s')
                            ]);
                    } catch (\Throwable $e) {
                        // 缺表/更新失败不影响发送成功
                    }
                }
            }
            
            if (!$success) {
                throw new \Exception($response['errmsg'] ?? '发送失败');
            }
            
            return [
                'success' => true,
                'message' => '发送成功',
                'log_id' => $logId
            ];
            
        } catch (\Exception $e) {
            trace('订阅消息发送失败: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 批量发送家教推荐消息
     * 
     * @param array $users 用户列表 [{openid, user_id}, ...]
     * @param array $data 消息数据
     * @return array
     */
    public static function batchSendTutorRecommendMessage($users, $data)
    {
        $successCount = 0;
        $failCount = 0;
        $results = [];
        
        foreach ($users as $user) {
            $result = self::sendTutorRecommendMessage($user['openid'], $data);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
            
            $results[] = [
                'openid' => $user['openid'],
                'user_id' => $user['user_id'] ?? null,
                'success' => $result['success'],
                'message' => $result['message']
            ];
        }
        
        return [
            'success' => true,
            'total' => count($users),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results
        ];
    }

    /**
     * 发送简历审核结果订阅消息
     *
     * @param string $openid 用户OpenID
     * @param array $data 消息数据 { result, review_time, remark, page? }
     * @return array
     */
    public static function sendResumeReviewMessage($openid, $data)
    {
        try {
            $tplId = self::getTemplateIdByCode('resume_review');
            $subscribe = null;
            try {
                $subscribe = Db::name('user_subscribe')
                    ->where('openid', $openid)
                    ->where('template_id', $tplId)
                    ->where('is_used', 0)
                    ->order('subscribe_time', 'desc')
                    ->find();
            } catch (\Throwable $e) {
                trace('读取 user_subscribe（简历审核）失败，跳过订阅检查：' . $e->getMessage(), 'warning');
            }
            // 兜底：即使订阅记录未写入数据库，也尝试发送一次（微信侧会校验用户是否订阅）
            // 仅当有 subscribe 记录时才会在发送成功后标记 is_used=1
            $subscribeId = $subscribe ? (int)($subscribe['id'] ?? 0) : 0;
            $subscribeUserId = $subscribe ? ($subscribe['user_id'] ?? null) : null;

            // 获取小程序配置（统一从运行配置读取，支持多小程序）
            $runtimeConfig = (new MiniProgramConfigService())->getRuntimeConfig('wechat');
            $appId = (string)($runtimeConfig['app_id'] ?? '');
            $appSecret = (string)($runtimeConfig['app_secret'] ?? '');
            $miniState = self::resolveMiniProgramState((string)($runtimeConfig['env_version'] ?? 'release'));

            if (!$appId || !$appSecret) {
                throw new \Exception('小程序配置未完成');
            }

            $resultText = self::limitPhraseValue((string)($data['result'] ?? ''));
            if ($resultText === '') {
                $resultText = '待通知';
            }
            $reviewTime = trim((string)($data['review_time'] ?? date('Y-m-d H:i:s')));
            $remark = self::limitThingValue((string)($data['remark'] ?? ''));
            if ($remark === '') {
                $remark = self::limitThingValue('请进入小程序查看');
            }

            // 注意：字段 key 必须与小程序订阅消息模板关键词一致
            // 你的模板为：
            // 审核结果 {{phrase2.DATA}}
            // 备注 {{thing3.DATA}}
            // 审核时间 {{time4.DATA}}
            // phrase / thing 超出字数会导致接口报错（常见 errcode 47003）
            $messageData = [
                'phrase2' => [
                    'value' => $resultText
                ],
                'thing3' => [
                    'value' => $remark
                ],
                'time4' => [
                    'value' => $reviewTime
                ]
            ];

            $page = $data['page'] ?? 'pages/teacher-resume-preview/index?readonly=true';

            $postData = [
                'touser' => $openid,
                'template_id' => $tplId,
                'page' => $page,
                'data' => $messageData,
                'miniprogram_state' => $miniState
            ];

            $response = self::postSubscribeMessageSend($appId, $appSecret, $postData);

            $success = isset($response['errcode']) && $response['errcode'] == 0;

            self::logMessage($subscribeUserId, $openid, $postData, $response);

            if ($success) {
                if ($subscribeId > 0) {
                    try {
                        Db::name('user_subscribe')
                            ->where('id', $subscribeId)
                            ->update([
                                'is_used' => 1,
                                'used_time' => date('Y-m-d H:i:s')
                            ]);
                    } catch (\Throwable $e) {
                        trace('更新 user_subscribe 失败: ' . $e->getMessage(), 'warning');
                    }
                }
            } else {
                $ec = (int)($response['errcode'] ?? 0);
                $em = (string)($response['errmsg'] ?? '发送失败');
                throw new \Exception($em . ($ec ? " (errcode={$ec})" : ''));
            }

            return [
                'success' => true,
                'message' => '发送成功'
            ];
        } catch (\Exception $e) {
            trace('简历审核订阅消息发送失败: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 记录用户订阅
     * 
     * @param int $userId 用户ID
     * @param string $openid 用户OpenID
     * @param string $templateId 模板ID
     * @return bool
     */
    public static function recordSubscribe($userId, $openid, $templateId = null)
    {
        try {
            if (!$templateId) {
                $templateId = self::getTemplateIdByCode('tutor_recommend');
            }
            
            try {
                Db::name('user_subscribe')->insert([
                    'user_id' => $userId,
                    'openid' => $openid,
                    'template_id' => $templateId,
                    'subscribe_time' => date('Y-m-d H:i:s'),
                    'is_used' => 0
                ]);
            } catch (\Throwable $e) {
                // 线上缺表时不抛出致命错误，直接返回 false（前端会忽略）
                trace('记录订阅失败（可能缺表 user_subscribe）: ' . $e->getMessage(), 'warning');
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            trace('记录订阅失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    private static function miniStableTokenCacheKey(string $appId): string
    {
        return 'mini_stable_access_token_' . md5($appId);
    }

    /**
     * 调用订阅消息发送；若返回 40001 则清空本地 token 缓存并 force_refresh 再试一次
     */
    private static function postSubscribeMessageSend(string $appId, string $appSecret, array $postData): array
    {
        $accessToken = self::getAccessToken($appId, $appSecret);
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $accessToken;
        $json = json_encode($postData, JSON_UNESCAPED_UNICODE);
        $response = json_decode(self::httpPost($url, $json), true) ?: [];

        if ((int)($response['errcode'] ?? 0) === 40001) {
            try {
                Cache::delete(self::miniStableTokenCacheKey($appId));
            } catch (\Throwable $e) {
                trace('清除 stable token 缓存失败: ' . $e->getMessage(), 'warning');
            }
            $accessToken2 = self::getAccessToken($appId, $appSecret, true);
            $url2 = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $accessToken2;
            $response = json_decode(self::httpPost($url2, $json), true) ?: [];
        }

        return $response;
    }

    /**
     * 获取小程序 access_token（优先稳定版接口，避免多实例互刷导致 40001 invalid credential）
     * @see https://developers.weixin.qq.com/miniprogram/dev/server/API/mp-access-token/api_getstableaccesstoken.html
     */
    private static function getAccessToken($appId, $appSecret, bool $forceRefresh = false)
    {
        $cacheKey = self::miniStableTokenCacheKey($appId);
        if (!$forceRefresh) {
            $cached = cache($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $stableUrl = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        $body = json_encode([
            'grant_type' => 'client_credential',
            'appid' => $appId,
            'secret' => $appSecret,
            'force_refresh' => $forceRefresh,
        ], JSON_UNESCAPED_UNICODE);

        $result = self::httpPost($stableUrl, $body);
        $data = json_decode($result, true) ?: [];

        if (!empty($data['access_token'])) {
            $expiresIn = (int)($data['expires_in'] ?? 7200);
            $ttl = max(60, $expiresIn - 300);
            cache($cacheKey, $data['access_token'], $ttl);

            return $data['access_token'];
        }

        // 兜底：旧版接口（与 stable 隔离，仅作兼容）
        $classicUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $result2 = self::httpGet($classicUrl);
        $data2 = json_decode($result2, true) ?: [];
        if (!empty($data2['access_token'])) {
            $expiresIn = (int)($data2['expires_in'] ?? 7200);
            cache($cacheKey, $data2['access_token'], max(60, $expiresIn - 300));

            return $data2['access_token'];
        }

        $msg = ($data['errmsg'] ?? '') ?: ($data2['errmsg'] ?? '未知错误');
        $code = (int)($data['errcode'] ?? $data2['errcode'] ?? 0);

        throw new \Exception('获取AccessToken失败：' . $msg . ($code ? " (errcode={$code})" : ''));
    }
    
    /**
     * 记录消息日志
     */
    private static function logMessage($userId, $openid, $postData, $response)
    {
        try {
            $logData = [
                'user_id' => $userId,
                'openid' => $openid,
                'template_id' => $postData['template_id'],
                'template_name' => '家教推荐通知',
                'page' => $postData['page'] ?? '',
                'data' => json_encode($postData, JSON_UNESCAPED_UNICODE),
                'status' => (isset($response['errcode']) && $response['errcode'] == 0) ? 1 : 0,
                'error_msg' => $response['errmsg'] ?? '',
                'send_time' => date('Y-m-d H:i:s')
            ];
            
            return Db::name('subscribe_message_log')->insertGetId($logData);
        } catch (\Exception $e) {
            trace('记录消息日志失败: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
    
    /**
     * HTTP GET请求
     */
    private static function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('HTTP请求失败: ' . $error);
        }
        
        curl_close($ch);
        return $result;
    }
    
    /**
     * HTTP POST请求
     */
    private static function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data)
        ]);
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('HTTP请求失败: ' . $error);
        }
        
        curl_close($ch);
        return $result;
    }
}
