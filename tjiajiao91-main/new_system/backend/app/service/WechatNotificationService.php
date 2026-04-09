<?php
namespace app\service;

use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

/**
 * 微信服务号通知服务
 */
class WechatNotificationService
{
    /**
     * 获取AccessToken（带缓存）
     */
    public static function getAccessToken($forceRefresh = false)
    {
        try {
            // 获取配置
            $config = Db::name('notification_config')->find(1);
            
            if (!$config || !$config['wechat_app_id'] || !$config['wechat_app_secret']) {
                throw new \Exception('微信服务号配置未完成');
            }
            
            // 检查缓存的AccessToken是否有效
            if (!$forceRefresh && 
                !empty($config['wechat_access_token']) && 
                $config['wechat_access_token_expire'] > time()) {
                return $config['wechat_access_token'];
            }
            
            // 请求新的AccessToken
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$config['wechat_app_id']}&secret={$config['wechat_app_secret']}";
            
            $result = self::httpGet($url);
            $data = json_decode($result, true);
            
            if (isset($data['errcode'])) {
                throw new \Exception('获取AccessToken失败：' . ($data['errmsg'] ?? '未知错误'));
            }
            
            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in'] ?? 7200;
            
            // 缓存AccessToken（提前5分钟过期）
            $expireTime = time() + $expiresIn - 300;
            Db::name('notification_config')
                ->where('id', 1)
                ->update([
                    'wechat_access_token' => $accessToken,
                    'wechat_access_token_expire' => $expireTime
                ]);
            
            return $accessToken;
            
        } catch (\Exception $e) {
            trace('获取AccessToken失败: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 发送模板消息
     * 
     * @param string $openid 用户OpenID
     * @param string $templateCode 模板代码（如：order_notify）
     * @param array $data 模板数据
     * @param string $url 跳转链接（可选）
     * @param array $miniprogram 小程序配置（可选）
     * @return array
     */
    public static function sendTemplateMessage($openid, $templateCode, $data, $url = '', $miniprogram = null)
    {
        $debugContext = [
            'template_code' => (string)$templateCode,
            'openid_masked' => self::maskValue((string)$openid, 6, 6),
            'data_keys' => is_array($data) ? array_keys($data) : [],
            'url' => (string)$url,
            'has_miniprogram' => $miniprogram ? 1 : 0,
            'request_time' => date('Y-m-d H:i:s')
        ];
        self::logTemplateDebug('send_template_message_start', $debugContext);

        try {
            // 检查是否启用微信通知
            $config = Db::name('notification_config')->find(1);
            if (!$config || !$config['wechat_enabled']) {
                self::logTemplateDebug('send_template_message_skip_not_enabled', $debugContext + [
                    'wechat_enabled' => (int)($config['wechat_enabled'] ?? 0)
                ]);
                return ['success' => false, 'message' => '微信通知未启用'];
            }
            self::logTemplateDebug('send_template_message_config_loaded', $debugContext + [
                'wechat_enabled' => (int)($config['wechat_enabled'] ?? 0),
                'wechat_app_id' => (string)($config['wechat_app_id'] ?? '')
            ]);
            
            // 获取模板配置
            $template = Db::name('wechat_templates')
                ->where('template_code', $templateCode)
                ->where('is_enabled', 1)
                ->find();
            
            if (!$template) {
                self::logTemplateDebug('send_template_message_template_not_found', $debugContext + [
                    'template_code' => (string)$templateCode
                ]);
                throw new \Exception("模板 {$templateCode} 不存在或未启用");
            }
            self::logTemplateDebug('send_template_message_template_loaded', $debugContext + [
                'template_id' => (string)($template['template_id'] ?? ''),
                'template_name' => (string)($template['template_name'] ?? ''),
                'field_mapping_raw' => (string)($template['field_mapping'] ?? '')
            ]);
            
            // 获取AccessToken
            $accessToken = self::getAccessToken();
            self::logTemplateDebug('send_template_message_access_token_loaded', $debugContext + [
                'access_token_masked' => self::maskValue((string)$accessToken, 8, 6)
            ]);
            
            // 构建模板数据
            $fieldMapping = json_decode($template['field_mapping'], true);
            if (!is_array($fieldMapping)) {
                self::logTemplateDebug('send_template_message_invalid_field_mapping', $debugContext + [
                    'field_mapping_raw' => (string)($template['field_mapping'] ?? '')
                ]);
                $fieldMapping = [];
            }
            $templateData = self::buildTemplateData($fieldMapping, $data);
            self::logTemplateDebug('send_template_message_template_data_built', $debugContext + [
                'template_data' => $templateData
            ]);
            
            // 构建请求数据
            $postData = [
                'touser' => $openid,
                'template_id' => $template['template_id'],
                'data' => $templateData
            ];
            
            // 添加跳转链接
            if (!empty($url)) {
                $postData['url'] = $url;
            } elseif (!empty($template['url'])) {
                $postData['url'] = self::replaceVariables($template['url'], $data);
            }
            
            // 添加小程序配置
            if ($miniprogram) {
                $postData['miniprogram'] = $miniprogram;
            } elseif (!empty($template['miniprogram_appid'])) {
                $postData['miniprogram'] = [
                    'appid' => $template['miniprogram_appid'],
                    'pagepath' => self::replaceVariables($template['miniprogram_pagepath'], $data)
                ];
            }
            
            // 添加颜色
            if (!empty($template['color'])) {
                foreach ($templateData as $key => $value) {
                    if (!isset($templateData[$key]['color'])) {
                        $templateData[$key]['color'] = $template['color'];
                    }
                }
                $postData['data'] = $templateData;
            }
            
            // 发送请求
            $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
            self::logTemplateDebug('send_template_message_request', $debugContext + [
                'api' => 'message/template/send',
                'wechat_app_id' => (string)($config['wechat_app_id'] ?? ''),
                'template_id' => (string)($template['template_id'] ?? ''),
                'post_data' => $postData
            ]);
            $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
            $response = json_decode($result, true);
            self::logTemplateDebug('send_template_message_response', $debugContext + [
                'api' => 'message/template/send',
                'response_raw' => $result,
                'response' => is_array($response) ? $response : []
            ]);

            // const 字段值不合法时，自动尝试常见候选值兜底重试
            if (self::isConstFieldInvalid($response)) {
                $retryResult = self::retryWithConstCandidates($apiUrl, $postData, $response);
                $response = $retryResult['response'];
                $postData = $retryResult['post_data'];
                self::logTemplateDebug('send_template_message_const_retry_result', $debugContext + [
                    'final_response' => is_array($response) ? $response : [],
                    'final_post_data' => $postData
                ]);
            }
            
            // 记录日志
            self::logNotification($openid, $templateCode, $postData, $response);
            
            if (isset($response['errcode']) && $response['errcode'] != 0) {
                // AccessToken可能过期，尝试刷新后重试一次
                if ($response['errcode'] == 40001 || $response['errcode'] == 42001) {
                    self::logTemplateDebug('send_template_message_retry_access_token', $debugContext + [
                        'errcode' => (int)$response['errcode'],
                        'errmsg' => (string)($response['errmsg'] ?? '')
                    ]);
                    $accessToken = self::getAccessToken(true);
                    $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
                    $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
                    $response = json_decode($result, true);
                    self::logTemplateDebug('send_template_message_retry_response', $debugContext + [
                        'api' => 'message/template/send',
                        'response_raw' => $result,
                        'response' => is_array($response) ? $response : []
                    ]);
                    
                    if (isset($response['errcode']) && $response['errcode'] != 0) {
                        throw new \Exception($response['errmsg'] ?? '发送失败');
                    }
                } else {
                    throw new \Exception($response['errmsg'] ?? '发送失败');
                }
            }
            
            return [
                'success' => true,
                'message' => '发送成功',
                'msgid' => $response['msgid'] ?? ''
            ];
            
        } catch (\Exception $e) {
            self::logTemplateDebug('send_template_message_exception', $debugContext + [
                'error' => $e->getMessage()
            ]);
            trace('微信模板消息发送失败: ' . $e->getMessage(), 'error');
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 构建模板数据
     */
    private static function buildTemplateData($fieldMapping, $data)
    {
        $templateData = [];
        
        foreach ($fieldMapping as $key => $value) {
            // 替换变量
            $content = self::replaceVariables($value, $data);
            // const 字段通常是枚举常量，这里做一次结果值归一化，避免 47003
            if (preg_match('/^const\d+$/', (string)$key)) {
                $content = self::normalizeConstValue($content);
            }
            
            $templateData[$key] = [
                'value' => $content
            ];
        }
        
        return $templateData;
    }
    
    /**
     * 替换变量
     */
    private static function replaceVariables($template, $data)
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            return $data[$key] ?? '';
        }, $template);
    }

    /**
     * 将常量字段值归一化到常见审核枚举
     */
    private static function normalizeConstValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $value;
        }
        if (
            (mb_strpos($value, '通过') !== false && mb_strpos($value, '不') === false) ||
            mb_strpos($value, '成功') !== false
        ) {
            return '审核成功';
        }
        if (
            mb_strpos($value, '驳回') !== false ||
            mb_strpos($value, '拒绝') !== false ||
            mb_strpos($value, '未通过') !== false ||
            mb_strpos($value, '不通过') !== false ||
            mb_strpos($value, '失败') !== false
        ) {
            return '审核拒绝';
        }
        return $value;
    }

    /**
     * 是否为 const 字段值不合法错误
     */
    private static function isConstFieldInvalid($response)
    {
        if (!is_array($response)) {
            return false;
        }
        $errcode = (int)($response['errcode'] ?? 0);
        $errmsg = (string)($response['errmsg'] ?? '');
        return $errcode === 47003 && mb_strpos($errmsg, 'data.const') !== false && mb_strpos($errmsg, 'invalid') !== false;
    }

    /**
     * const 字段自动候选值重试
     */
    private static function retryWithConstCandidates($apiUrl, array $postData, array $firstResponse)
    {
        $constKeys = [];
        foreach ((array)($postData['data'] ?? []) as $k => $v) {
            if (preg_match('/^const\d+$/', (string)$k)) {
                $constKeys[] = (string)$k;
            }
        }
        if (empty($constKeys)) {
            return ['response' => $firstResponse, 'post_data' => $postData];
        }

        $candidates = ['审核成功', '审核拒绝', '通过', '不通过', '审核通过', '审核驳回', '成功', '失败'];
        // 针对订单状态枚举字段 const14，优先尝试更贴近模板的候选值
        if (in_array('const14', $constKeys, true)) {
            $candidates = array_merge(
                ['已通过', '待接单', '审核中', '已接单', '审核成功', '审核拒绝'],
                $candidates
            );
            $candidates = array_values(array_unique($candidates));
        }
        $lastResponse = $firstResponse;
        $basePostData = $postData;

        foreach ($candidates as $candidate) {
            $trial = $basePostData;
            foreach ($constKeys as $ck) {
                $trial['data'][$ck]['value'] = $candidate;
            }
            $raw = self::httpPost($apiUrl, json_encode($trial, JSON_UNESCAPED_UNICODE));
            $resp = json_decode($raw, true);
            $lastResponse = is_array($resp) ? $resp : ['errcode' => -1, 'errmsg' => 'invalid response'];

            self::logTemplateDebug('send_template_message_const_retry_try', [
                'candidate' => $candidate,
                'const_keys' => $constKeys,
                'response' => $lastResponse
            ]);

            if (!isset($lastResponse['errcode']) || (int)$lastResponse['errcode'] === 0) {
                return ['response' => $lastResponse, 'post_data' => $trial];
            }
        }

        return ['response' => $lastResponse, 'post_data' => $postData];
    }
    
    /**
     * 获取用户信息
     */
    public static function getUserInfo($openid)
    {
        try {
            $accessToken = self::getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$accessToken}&openid={$openid}&lang=zh_CN";
            
            $result = self::httpGet($url);
            $data = json_decode($result, true);
            
            if (isset($data['errcode']) && $data['errcode'] != 0) {
                throw new \Exception($data['errmsg'] ?? '获取用户信息失败');
            }
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 发送测试消息
     */
    public static function sendTestMessage($openid, $options = [])
    {
        try {
            $config = Db::name('notification_config')->find(1);
            if (!$config || !$config['wechat_enabled']) {
                return ['success' => false, 'message' => '微信通知未启用'];
            }

            $accessToken = self::getAccessToken();
            $apiType = $options['api_type'] ?? 'auto';
            if (!in_array($apiType, ['auto', 'template', 'subscribe'], true)) {
                $apiType = 'auto';
            }
            $templateIdInput = trim((string)($options['template_id'] ?? ''));
            $templateId = $templateIdInput;
            $templateSource = 'input';
            if ($templateId === '') {
                // 优先使用业务模板配置，避免默认测试模板与当前公众号不匹配
                $bizTpl = Db::name('wechat_templates')
                    ->where('template_code', 'resume_review_notify')
                    ->where('is_enabled', 1)
                    ->field('template_id')
                    ->find();
                $templateId = trim((string)($bizTpl['template_id'] ?? ''));
                $templateSource = 'db:resume_review_notify';
            }
            if ($templateId === '') {
                return [
                    'success' => false,
                    'message' => '未找到可用模板ID：请在测试弹窗填写模板ID，或启用 template_code=resume_review_notify 的模板配置',
                    'debug' => [
                        'api' => 'message/template/send',
                        'template_id_input' => $templateIdInput,
                        'template_source' => $templateSource,
                        'template_id_used' => ''
                    ]
                ];
            }

            $testBizData = [
                'result' => '审核成功',
                'review_time' => date('Y-m-d H:i:s'),
                'remark' => '无'
            ];
            $testTemplateData = self::buildTemplateDataForTemplateId($templateId, $testBizData);
            if (empty($testTemplateData)) {
                $testTemplateData = [
                    'thing1' => ['value' => '审核成功'],
                    'time2' => ['value' => date('Y-m-d H:i:s')],
                    'thing3' => ['value' => '无']
                ];
            }

            $postData = [
                'touser' => $openid,
                'template_id' => $templateId,
                'data' => $testTemplateData
            ];

            if ($apiType === 'subscribe') {
                $subscribePayload = [
                    'touser' => $openid,
                    'template_id' => $templateId,
                    'scene' => 1000,
                    'title' => '后台测试通知',
                    'data' => $postData['data']
                ];
                $subscribeUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
                $subscribeResult = self::httpPost($subscribeUrl, json_encode($subscribePayload, JSON_UNESCAPED_UNICODE));
                $subscribeResponse = json_decode($subscribeResult, true);
                self::logNotification($openid, 'admin_test_subscribe', $subscribePayload, $subscribeResponse);

                if (isset($subscribeResponse['errcode']) && $subscribeResponse['errcode'] != 0) {
                    return [
                        'success' => false,
                        'message' => '微信返回错误(' . $subscribeResponse['errcode'] . ')：' . ($subscribeResponse['errmsg'] ?? '发送失败'),
                        'debug' => self::buildTestDebugInfo(
                            'message/subscribe/send',
                            $openid,
                            $templateId,
                            $config['wechat_app_id'] ?? '',
                            $subscribeResponse,
                            [
                                'template_id_input' => $templateIdInput,
                                'template_source' => $templateSource
                            ]
                        )
                    ];
                }

                return [
                    'success' => true,
                    'message' => '发送成功（手动选择订阅通知接口）',
                    'msgid' => $subscribeResponse['msgid'] ?? '',
                    'debug' => self::buildTestDebugInfo(
                        'message/subscribe/send',
                        $openid,
                        $templateId,
                        $config['wechat_app_id'] ?? '',
                        $subscribeResponse,
                        [
                            'template_id_input' => $templateIdInput,
                            'template_source' => $templateSource
                        ]
                    )
                ];
            }

            $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
            $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
            $response = json_decode($result, true);

            // 如果模板ID与 template/send 接口不匹配，自动尝试订阅通知接口
            if ($apiType === 'auto' && isset($response['errcode']) && (int)$response['errcode'] === 40037) {
                $subscribePayload = [
                    'touser' => $openid,
                    'template_id' => $templateId,
                    'scene' => 1000,
                    'title' => '后台测试通知',
                    'data' => $postData['data']
                ];
                $subscribeUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
                $subscribeResult = self::httpPost($subscribeUrl, json_encode($subscribePayload, JSON_UNESCAPED_UNICODE));
                $subscribeResponse = json_decode($subscribeResult, true);
                self::logNotification($openid, 'admin_test_subscribe', $subscribePayload, $subscribeResponse);

                if (isset($subscribeResponse['errcode']) && $subscribeResponse['errcode'] != 0) {
                    return [
                        'success' => false,
                        'message' => '微信返回错误(' . $subscribeResponse['errcode'] . ')：' . ($subscribeResponse['errmsg'] ?? '发送失败'),
                        'debug' => self::buildTestDebugInfo(
                            'message/subscribe/send',
                            $openid,
                            $templateId,
                            $config['wechat_app_id'] ?? '',
                            $subscribeResponse,
                            [
                                'template_id_input' => $templateIdInput,
                                'template_source' => $templateSource
                            ]
                        )
                    ];
                }

                return [
                    'success' => true,
                    'message' => '发送成功（已自动切换订阅通知接口）',
                    'msgid' => $subscribeResponse['msgid'] ?? '',
                    'debug' => self::buildTestDebugInfo(
                        'message/subscribe/send',
                        $openid,
                        $templateId,
                        $config['wechat_app_id'] ?? '',
                        $subscribeResponse,
                        [
                            'template_id_input' => $templateIdInput,
                            'template_source' => $templateSource
                        ]
                    )
                ];
            }

            self::logNotification($openid, 'admin_test', $postData, $response);

            if (isset($response['errcode']) && $response['errcode'] != 0) {
                return [
                    'success' => false,
                    'message' => '微信返回错误(' . $response['errcode'] . ')：' . ($response['errmsg'] ?? '发送失败'),
                    'debug' => self::buildTestDebugInfo(
                        'message/template/send',
                        $openid,
                        $templateId,
                        $config['wechat_app_id'] ?? '',
                        $response,
                        [
                            'template_id_input' => $templateIdInput,
                            'template_source' => $templateSource
                        ]
                    )
                ];
            }

            return [
                'success' => true,
                'message' => '发送成功',
                'msgid' => $response['msgid'] ?? '',
                'debug' => self::buildTestDebugInfo(
                    'message/template/send',
                    $openid,
                    $templateId,
                    $config['wechat_app_id'] ?? '',
                    $response,
                    [
                        'template_id_input' => $templateIdInput,
                        'template_source' => $templateSource
                    ]
                )
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 记录通知日志
     */
    private static function logNotification($openid, $templateCode, $postData, $response)
    {
        try {
            Db::name('notification_logs')->insert([
                'channel' => 'wechat',
                'receiver' => $openid,
                'template_code' => $templateCode,
                'send_data' => json_encode($postData, JSON_UNESCAPED_UNICODE),
                'status' => (isset($response['errcode']) && $response['errcode'] == 0) ? 1 : 0,
                'error_msg' => $response['errmsg'] ?? '',
                'send_time' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            trace('记录通知日志失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 组装测试推送调试信息（避免泄露敏感字段）
     */
    private static function buildTestDebugInfo($api, $openid, $templateId, $appId, $response, array $extra = [])
    {
        return array_merge([
            'api' => $api,
            'errcode' => $response['errcode'] ?? null,
            'errmsg' => $response['errmsg'] ?? '',
            'msgid' => $response['msgid'] ?? '',
            'template_id' => $templateId,
            'openid_masked' => self::maskValue($openid, 6, 6),
            'app_id_masked' => self::maskValue($appId, 6, 4),
            'time' => date('Y-m-d H:i:s')
        ], $extra);
    }

    private static function maskValue($value, $head = 6, $tail = 4)
    {
        $value = (string)$value;
        $len = strlen($value);
        if ($len <= $head + $tail) {
            return $value;
        }
        return substr($value, 0, $head) . '...' . substr($value, -$tail);
    }

    private static function logTemplateDebug($stage, array $context = [])
    {
        try {
            Log::info('[wechat_template_debug] ' . $stage . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // ignore logging failures
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
    
    /**
     * 获取所有模板列表
     */
    public static function getTemplateList()
    {
        try {
            $accessToken = self::getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$accessToken}";
            
            $result = self::httpGet($url);
            $data = json_decode($result, true);
            
            if (isset($data['errcode']) && $data['errcode'] != 0) {
                throw new \Exception($data['errmsg'] ?? '获取模板列表失败');
            }
            
            return [
                'success' => true,
                'data' => $data['template_list'] ?? []
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 诊断模板消息接口权限与可用性
     */
    public static function debugTemplatePermission($openid = '', $templateId = '')
    {
        $templateId = trim((string)$templateId);
        $report = [
            'time' => date('Y-m-d H:i:s'),
            'app_id_masked' => '',
            'openid_masked' => self::maskValue((string)$openid, 6, 6),
            'template_id_input' => $templateId,
            'template_id_used' => $templateId,
            'steps' => []
        ];

        try {
            $config = Db::name('notification_config')->find(1);
            $appId = (string)($config['wechat_app_id'] ?? '');
            $report['app_id_masked'] = self::maskValue($appId, 6, 4);

            if (empty($appId) || empty($config['wechat_app_secret'])) {
                $report['steps'][] = [
                    'name' => 'config_check',
                    'ok' => false,
                    'errmsg' => '微信服务号配置未完成（AppID/AppSecret）'
                ];
                return [
                    'success' => false,
                    'message' => '微信服务号配置未完成（AppID/AppSecret）',
                    'data' => $report
                ];
            }

            $accessToken = self::getAccessToken(true);
            $report['steps'][] = [
                'name' => 'get_access_token',
                'ok' => !empty($accessToken),
                'access_token_masked' => self::maskValue((string)$accessToken, 8, 6)
            ];

            $callbackUrl = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token={$accessToken}";
            $callbackRaw = self::httpGet($callbackUrl);
            $callbackRes = json_decode($callbackRaw, true);
            $report['steps'][] = [
                'name' => 'getcallbackip',
                'ok' => !isset($callbackRes['errcode']) || (int)$callbackRes['errcode'] === 0,
                'errcode' => $callbackRes['errcode'] ?? 0,
                'errmsg' => $callbackRes['errmsg'] ?? 'ok'
            ];

            $listUrl = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$accessToken}";
            $listRaw = self::httpGet($listUrl);
            $listRes = json_decode($listRaw, true);
            $templateCount = is_array($listRes['template_list'] ?? null) ? count($listRes['template_list']) : 0;
            $report['steps'][] = [
                'name' => 'get_all_private_template',
                'ok' => !isset($listRes['errcode']) || (int)$listRes['errcode'] === 0,
                'errcode' => $listRes['errcode'] ?? 0,
                'errmsg' => $listRes['errmsg'] ?? 'ok',
                'template_count' => $templateCount
            ];

            $finalTemplateId = $templateId;
            if ($finalTemplateId === '') {
                $bizTpl = Db::name('wechat_templates')
                    ->where('template_code', 'resume_review_notify')
                    ->where('is_enabled', 1)
                    ->field('template_id')
                    ->find();
                $finalTemplateId = trim((string)($bizTpl['template_id'] ?? ''));
            }
            $report['template_id_used'] = $finalTemplateId;

            if (!empty($openid) && !empty($finalTemplateId)) {
                $debugBizData = [
                    'result' => '审核通过',
                    'review_time' => date('Y-m-d H:i:s'),
                    'remark' => '无',
                    'order_status' => '待接单'
                ];
                $debugTemplateData = self::buildTemplateDataForTemplateId($finalTemplateId, $debugBizData);
                if (empty($debugTemplateData)) {
                    $debugTemplateData = [
                        'thing1' => ['value' => '审核通过'],
                        'time2' => ['value' => date('Y-m-d H:i:s')],
                        'thing3' => ['value' => '无']
                    ];
                }

                $sendPayload = [
                    'touser' => $openid,
                    'template_id' => $finalTemplateId,
                    'data' => $debugTemplateData
                ];
                $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
                $sendRaw = self::httpPost($sendUrl, json_encode($sendPayload, JSON_UNESCAPED_UNICODE));
                $sendRes = json_decode($sendRaw, true);
                if (self::isConstFieldInvalid($sendRes)) {
                    $retryResult = self::retryWithConstCandidates($sendUrl, $sendPayload, is_array($sendRes) ? $sendRes : []);
                    $sendRes = $retryResult['response'];
                }

                $report['steps'][] = [
                    'name' => 'message_template_send',
                    'ok' => !isset($sendRes['errcode']) || (int)$sendRes['errcode'] === 0,
                    'errcode' => $sendRes['errcode'] ?? 0,
                    'errmsg' => $sendRes['errmsg'] ?? 'ok',
                    'msgid' => $sendRes['msgid'] ?? ''
                ];
            } else {
                $report['steps'][] = [
                    'name' => 'message_template_send',
                    'ok' => false,
                    'skipped' => true,
                    'reason' => 'openid 或 template_id 为空，跳过发送测试'
                ];
            }

            return [
                'success' => true,
                'message' => '诊断完成',
                'data' => $report
            ];
        } catch (\Exception $e) {
            $report['steps'][] = [
                'name' => 'exception',
                'ok' => false,
                'errmsg' => $e->getMessage()
            ];
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $report
            ];
        }
    }

    /**
     * 根据模板ID读取 field_mapping 并组装测试数据
     */
    private static function buildTemplateDataForTemplateId($templateId, array $bizData)
    {
        $template = Db::name('wechat_templates')
            ->where('template_id', (string)$templateId)
            ->field('field_mapping,content')
            ->find();

        $mapping = json_decode((string)($template['field_mapping'] ?? ''), true);
        $templateData = [];
        if (is_array($mapping) && !empty($mapping)) {
            $templateData = self::buildTemplateData($mapping, $bizData);
        }

        // 兜底：从模板内容中抽取字段，避免测试时因未配置映射导致某字段为空
        $content = (string)($template['content'] ?? '');
        if ($content !== '') {
            if (preg_match_all('/\{\{([a-zA-Z_]+\d+)\.DATA\}\}/', $content, $matches)) {
                $keys = array_unique($matches[1]);
                foreach ($keys as $key) {
                    if (!isset($templateData[$key]) || trim((string)($templateData[$key]['value'] ?? '')) === '') {
                        $templateData[$key] = ['value' => self::defaultValueForFieldKey((string)$key, $bizData)];
                    }
                }
            }
        }

        return $templateData;
    }

    /**
     * 按字段类型生成测试默认值
     */
    private static function defaultValueForFieldKey($key, array $bizData = [])
    {
        $key = (string)$key;
        $now = date('Y-m-d H:i:s');

        // 你当前测试模板：订单状态字段 const14，优先使用已生效的枚举值
        if ($key === 'const14') {
            return (string)($bizData['order_status'] ?? '待接单');
        }
        if ($key === 'phrase2') {
            return (string)($bizData['order_type'] ?? '家教订单');
        }
        if ($key === 'thing12') {
            return (string)($bizData['service_item'] ?? '上门家教');
        }

        if (preg_match('/^const\d+$/', $key)) {
            return self::normalizeConstValue((string)($bizData['result'] ?? '审核成功'));
        }
        if (preg_match('/^(time|date)\d+$/', $key)) {
            return (string)($bizData['review_time'] ?? $now);
        }
        if (preg_match('/^phrase\d+$/', $key)) {
            return '审核成功';
        }
        if (preg_match('/^(thing|character_string|name)\d+$/', $key)) {
            return (string)($bizData['remark'] ?? '无');
        }
        if (preg_match('/^(number|amount)\d+$/', $key)) {
            return '1';
        }
        if (preg_match('/^phone_number\d+$/', $key)) {
            return '13800000000';
        }
        return (string)($bizData['remark'] ?? '无');
    }
}


