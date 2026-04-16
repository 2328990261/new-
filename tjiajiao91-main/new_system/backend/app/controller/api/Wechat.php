<?php

namespace app\controller\api;

use app\BaseController;
use app\service\OfficialTokenService;
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
        return OfficialTokenService::getJsapiTicket($forceRefresh);
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