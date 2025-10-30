<?php
namespace app\service;

use think\facade\Db;
use think\facade\Cache;

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
        try {
            // 检查是否启用微信通知
            $config = Db::name('notification_config')->find(1);
            if (!$config || !$config['wechat_enabled']) {
                return ['success' => false, 'message' => '微信通知未启用'];
            }
            
            // 获取模板配置
            $template = Db::name('wechat_templates')
                ->where('template_code', $templateCode)
                ->where('is_enabled', 1)
                ->find();
            
            if (!$template) {
                throw new \Exception("模板 {$templateCode} 不存在或未启用");
            }
            
            // 获取AccessToken
            $accessToken = self::getAccessToken();
            
            // 构建模板数据
            $fieldMapping = json_decode($template['field_mapping'], true);
            $templateData = self::buildTemplateData($fieldMapping, $data);
            
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
            $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
            $response = json_decode($result, true);
            
            // 记录日志
            self::logNotification($openid, $templateCode, $postData, $response);
            
            if (isset($response['errcode']) && $response['errcode'] != 0) {
                // AccessToken可能过期，尝试刷新后重试一次
                if ($response['errcode'] == 40001 || $response['errcode'] == 42001) {
                    $accessToken = self::getAccessToken(true);
                    $apiUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$accessToken}";
                    $result = self::httpPost($apiUrl, json_encode($postData, JSON_UNESCAPED_UNICODE));
                    $response = json_decode($result, true);
                    
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
    public static function sendTestMessage($openid)
    {
        $data = [
            'order_no' => 'TEST' . date('YmdHis'),
            'city' => '北京市',
            'district' => '海淀区',
            'subject' => '数学',
            'salary' => '200元/小时'
        ];
        
        return self::sendTemplateMessage($openid, 'order_notify', $data);
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
}


