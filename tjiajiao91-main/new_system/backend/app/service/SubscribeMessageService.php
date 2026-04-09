<?php
namespace app\service;

use think\facade\Db;

/**
 * 小程序订阅消息服务
 */
class SubscribeMessageService
{
    // 模板ID
    const TEMPLATE_ID = 'szFjrvi1RabxvzvKV-zxkAHyb2aeu3wT46IzM3t8fHo';
    // 简历审核结果通知（小程序订阅消息模板）
    const RESUME_REVIEW_TEMPLATE_ID = 'g0ok7utGSFQJsC9_yG0cqGy4tfLrAQ-0GrXcEUMj-lU';
    
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
            // 检查用户是否有可用的订阅
            $subscribe = Db::name('user_subscribe')
                ->where('openid', $openid)
                ->where('template_id', self::TEMPLATE_ID)
                ->where('is_used', 0)
                ->order('subscribe_time', 'desc')
                ->find();
            
            if (!$subscribe) {
                return [
                    'success' => false,
                    'message' => '用户未订阅或订阅已使用'
                ];
            }
            
            // 获取小程序配置
            $appId = env('wechat.mini_app_id');
            $appSecret = env('wechat.mini_app_secret');
            
            if (!$appId || !$appSecret) {
                throw new \Exception('小程序配置未完成');
            }
            
            // 获取AccessToken
            $accessToken = self::getAccessToken($appId, $appSecret);
            
            // 构建消息数据
            $messageData = [
                'character_string1' => [  // 年级科目
                    'value' => $data['grade'] . ' ' . $data['subject']
                ],
                'thing2' => [  // 服务类目
                    'value' => $data['city'] . ' ' . $data['district']
                ],
                'thing4' => [  // 城市区域
                    'value' => $data['teaching_method'] ?? '上门授课'
                ],
                'character_string3' => [  // 订单号
                    'value' => $data['order_no']
                ],
                'thing5' => [  // 温馨提示
                    'value' => '您好，已为您推荐该家教信息，请尽快投递简历，以免错过'
                ]
            ];
            
            // 构建请求数据
            $postData = [
                'touser' => $openid,
                'template_id' => self::TEMPLATE_ID,
                'page' => 'pages/tutor-detail/index?id=' . ($data['tutor_id'] ?? ''),
                'data' => $messageData,
                'miniprogram_state' => 'formal'  // 正式版
            ];
            
            // 发送请求
            $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
            $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
            $response = json_decode($result, true);
            
            // 判断发送结果
            $success = isset($response['errcode']) && $response['errcode'] == 0;
            
            // 记录日志
            $logId = self::logMessage($subscribe['user_id'], $openid, $postData, $response);
            
            // 如果发送成功，标记订阅为已使用
            if ($success) {
                Db::name('user_subscribe')
                    ->where('id', $subscribe['id'])
                    ->update([
                        'is_used' => 1,
                        'used_time' => date('Y-m-d H:i:s')
                    ]);
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
            $subscribe = Db::name('user_subscribe')
                ->where('openid', $openid)
                ->where('template_id', self::RESUME_REVIEW_TEMPLATE_ID)
                ->where('is_used', 0)
                ->order('subscribe_time', 'desc')
                ->find();

            if (!$subscribe) {
                return [
                    'success' => false,
                    'message' => '用户未订阅或订阅已使用'
                ];
            }

            $appId = env('wechat.mini_app_id');
            $appSecret = env('wechat.mini_app_secret');

            if (!$appId || !$appSecret) {
                throw new \Exception('小程序配置未完成');
            }

            $accessToken = self::getAccessToken($appId, $appSecret);

            $resultText = $data['result'] ?? '';
            $reviewTime = $data['review_time'] ?? date('Y-m-d H:i:s');
            $remark = $data['remark'] ?? '';

            // 注意：字段 key 必须与小程序订阅消息模板关键词一致
            // 你的模板为：审核结果 thing1、审核时间 time2、备注 thing3
            $messageData = [
                'thing1' => [
                    'value' => $resultText
                ],
                'time2' => [
                    'value' => $reviewTime
                ],
                'thing3' => [
                    'value' => $remark ?: '请进入小程序查看详情'
                ]
            ];

            $page = $data['page'] ?? 'pages/teacher-resume-preview/index?readonly=true';

            $postData = [
                'touser' => $openid,
                'template_id' => self::RESUME_REVIEW_TEMPLATE_ID,
                'page' => $page,
                'data' => $messageData,
                'miniprogram_state' => 'formal'
            ];

            $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
            $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
            $response = json_decode($result, true);

            $success = isset($response['errcode']) && $response['errcode'] == 0;

            self::logMessage($subscribe['user_id'], $openid, $postData, $response);

            if ($success) {
                Db::name('user_subscribe')
                    ->where('id', $subscribe['id'])
                    ->update([
                        'is_used' => 1,
                        'used_time' => date('Y-m-d H:i:s')
                    ]);
            } else {
                throw new \Exception($response['errmsg'] ?? '发送失败');
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
                $templateId = self::TEMPLATE_ID;
            }
            
            Db::name('user_subscribe')->insert([
                'user_id' => $userId,
                'openid' => $openid,
                'template_id' => $templateId,
                'subscribe_time' => date('Y-m-d H:i:s'),
                'is_used' => 0
            ]);
            
            return true;
        } catch (\Exception $e) {
            trace('记录订阅失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 获取AccessToken
     */
    private static function getAccessToken($appId, $appSecret)
    {
        // 尝试从缓存获取
        $cacheKey = 'mini_access_token_' . $appId;
        $accessToken = cache($cacheKey);
        
        if ($accessToken) {
            return $accessToken;
        }
        
        // 请求新的AccessToken
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $result = self::httpGet($url);
        $data = json_decode($result, true);
        
        if (isset($data['errcode'])) {
            throw new \Exception('获取AccessToken失败：' . ($data['errmsg'] ?? '未知错误'));
        }
        
        $accessToken = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 7200;
        
        // 缓存AccessToken（提前5分钟过期）
        cache($cacheKey, $accessToken, $expiresIn - 300);
        
        return $accessToken;
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
