<?php

namespace app\controller\api;

use app\BaseController;
use app\service\WechatNotificationService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class Wechat extends BaseController
{
    /**
     * 获取微信分享配置
     */
    public function shareConfig()
    {
        try {
            $rawUrl = (string)$this->request->param('url', '');
            $url = $this->normalizeJsSdkUrl($rawUrl);

            $nc = Db::name('notification_config')->find(1);
            $appId = trim((string)($nc['wechat_app_id'] ?? ''));
            $appSecret = trim((string)($nc['wechat_app_secret'] ?? ''));

            // 未配置公众号：返回 disabled（前端仍可走 meta 标签兜底，不影响正常浏览）
            if ($appId === '' || $appSecret === '') {
                return json([
                    'success' => true,
                    'data' => [
                        'enabled' => false,
                        'title' => '优质家教信息平台',
                        'desc' => '专业的家教信息平台，为您提供优质的家教服务',
                        'image' => '',
                        'domain' => '',
                    ]
                ]);
            }

            $timestamp = time();
            $nonceStr = $this->generateNonceStr();
            $ticket = $this->getJsApiTicket(false);
            $signature = $this->buildJsSdkSignature($ticket, $nonceStr, $timestamp, $url);

            $callbackDomain = !empty($nc['wechat_callback_domain'])
                ? rtrim((string)$nc['wechat_callback_domain'], '/')
                : rtrim((string)$this->request->domain(), '/');

            // 获取分享图片：优先使用配置的图片，否则使用默认图片
            // 用户端 H5 生产构建 base 为 /user/，public 静态资源在 /user/static/ 下
            $shareImage = !empty($nc['wechat_share_image'])
                ? (string)$nc['wechat_share_image']
                : $callbackDomain . '/user/static/images/share-logo.png';

            $config = [
                'enabled' => true,
                'appId' => $appId,
                'timestamp' => $timestamp,
                'nonceStr' => $nonceStr,
                'signature' => $signature,
                'jsApiList' => [
                    'checkJsApi',
                    'updateAppMessageShareData',
                    'updateTimelineShareData',
                    'onMenuShareTimeline',
                    'onMenuShareAppMessage'
                ],
                // 供前端默认使用（可被页面 setWechatShare 覆盖）
                'title' => '优质家教信息平台',
                'desc' => '专业的家教信息平台，为您提供优质的家教服务',
                'image' => $shareImage,
                'domain' => $callbackDomain
            ];

            return json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            Log::warning('[wechat_share_config_failed] ' . $e->getMessage());
            return json([
                'success' => false,
                'message' => '获取微信配置失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 标准化用于 JS-SDK 签名的 URL
     * - 必须不包含 hash（# 后面的部分）
     * - 为空时回退到当前请求 URL
     */
    private function normalizeJsSdkUrl(string $url): string
    {
        $u = trim($url);
        if ($u === '') {
            $u = (string)$this->request->url(true);
        }
        // JS-SDK 签名不包含 hash
        $pos = strpos($u, '#');
        if ($pos !== false) {
            $u = substr($u, 0, $pos);
        }
        return $u;
    }

    /**
     * 获取 JSAPI Ticket（带缓存）
     */
    private function getJsApiTicket(bool $forceRefresh = false): string
    {
        $cacheKey = 'wechat:official:jsapi_ticket';
        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && trim($cached) !== '') {
                return trim($cached);
            }
        }

        $accessToken = WechatNotificationService::getAccessToken($forceRefresh);
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$accessToken}&type=jsapi";
        $resp = $this->httpGet($url);
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            throw new \Exception('微信返回 ticket 数据解析失败');
        }
        if (isset($data['errcode']) && (int)$data['errcode'] !== 0) {
            // access_token 可能过期：强刷一次重试
            if (in_array((int)$data['errcode'], [40001, 42001], true) && !$forceRefresh) {
                return $this->getJsApiTicket(true);
            }
            throw new \Exception('获取 JSAPI ticket 失败：' . ((string)($data['errmsg'] ?? '未知错误')));
        }

        $ticket = trim((string)($data['ticket'] ?? ''));
        if ($ticket === '') {
            throw new \Exception('微信返回缺少 jsapi_ticket');
        }

        $expiresIn = (int)($data['expires_in'] ?? 7200);
        $ttl = max(60, $expiresIn - 300); // 提前 5 分钟过期
        Cache::set($cacheKey, $ticket, $ttl);

        return $ticket;
    }

    /**
     * 生成 JS-SDK 签名
     */
    private function buildJsSdkSignature(string $ticket, string $nonceStr, int $timestamp, string $url): string
    {
        $plain = "jsapi_ticket={$ticket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        return sha1($plain);
    }

    /**
     * HTTP GET（curl）
     */
    private function httpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('HTTP请求失败: ' . $error);
        }
        curl_close($ch);
        return (string)$result;
    }

    /**
     * 生成随机字符串
     */
    private function generateNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}