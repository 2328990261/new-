<?php
namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 服务号 access_token / jsapi_ticket 统一管理（Redis 缓存）
 * - token 来源：notification_config(id=1) 的 wechat_app_id / wechat_app_secret
 * - 缓存 key：按 appid 维度隔离
 * - 容错：遇到 40001/42001 清缓存并强刷一次
 */
class OfficialTokenService
{
    private static function loadOfficialConfig(): array
    {
        $cfg = Db::name('notification_config')->find(1) ?: [];
        $appId = trim((string)($cfg['wechat_app_id'] ?? ''));
        $secret = trim((string)($cfg['wechat_app_secret'] ?? ''));
        if ($appId === '' || $secret === '') {
            throw new \Exception('微信服务号配置未完成（AppID/AppSecret）');
        }
        return [$appId, $secret];
    }

    private static function cacheKey(string $appId, string $suffix): string
    {
        return 'oa_' . $suffix . '_' . md5($appId);
    }

    public static function getAccessToken(bool $forceRefresh = false): string
    {
        [$appId, $secret] = self::loadOfficialConfig();
        $kToken = self::cacheKey($appId, 'access_token');
        $kExpire = self::cacheKey($appId, 'access_token_expire');

        if (!$forceRefresh) {
            $token = (string)(Cache::get($kToken) ?? '');
            $exp = (int)(Cache::get($kExpire) ?? 0);
            if ($token !== '' && $exp > time()) {
                return $token;
            }
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='
            . urlencode($appId) . '&secret=' . urlencode($secret);
        $raw = self::httpGet($url);
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['access_token'])) {
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            throw new \Exception('获取AccessToken失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }

        $token = (string)$data['access_token'];
        $expiresIn = (int)($data['expires_in'] ?? 7200);
        $expireAt = time() + max(0, $expiresIn - 300); // 提前 5 分钟

        Cache::set($kToken, $token, $expiresIn);
        Cache::set($kExpire, $expireAt, $expiresIn);
        return $token;
    }

    public static function getJsapiTicket(bool $forceRefresh = false): string
    {
        [$appId, ] = self::loadOfficialConfig();
        $kTicket = self::cacheKey($appId, 'jsapi_ticket');
        $kExpire = self::cacheKey($appId, 'jsapi_ticket_expire');

        if (!$forceRefresh) {
            $ticket = (string)(Cache::get($kTicket) ?? '');
            $exp = (int)(Cache::get($kExpire) ?? 0);
            if ($ticket !== '' && $exp > time()) {
                return $ticket;
            }
        }

        // 先拿 token
        $token = self::getAccessToken(false);
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='
            . urlencode($token) . '&type=jsapi';
        $raw = self::httpGet($url);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0 || empty($data['ticket'])) {
            $errcode = (int)($data['errcode'] ?? 0);
            if ($errcode === 40001 || $errcode === 42001) {
                // token 失效：清 token 缓存并强刷一次再取 ticket
                self::clearAccessTokenCache($appId);
                $token2 = self::getAccessToken(true);
                $url2 = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='
                    . urlencode($token2) . '&type=jsapi';
                $raw2 = self::httpGet($url2);
                $data2 = json_decode($raw2, true);
                if (is_array($data2) && (int)($data2['errcode'] ?? -1) === 0 && !empty($data2['ticket'])) {
                    $ticket2 = (string)$data2['ticket'];
                    $expiresIn2 = (int)($data2['expires_in'] ?? 7200);
                    $expireAt2 = time() + max(0, $expiresIn2 - 300);
                    Cache::set($kTicket, $ticket2, $expiresIn2);
                    Cache::set($kExpire, $expireAt2, $expiresIn2);
                    return $ticket2;
                }
            }

            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('获取jsapi_ticket失败：' . $errmsg);
        }

        $ticket = (string)$data['ticket'];
        $expiresIn = (int)($data['expires_in'] ?? 7200);
        $expireAt = time() + max(0, $expiresIn - 300);
        Cache::set($kTicket, $ticket, $expiresIn);
        Cache::set($kExpire, $expireAt, $expiresIn);
        return $ticket;
    }

    public static function clearAccessTokenCache(string $appId = ''): void
    {
        try {
            if ($appId === '') {
                [$appId, ] = self::loadOfficialConfig();
            }
            Cache::delete(self::cacheKey($appId, 'access_token'));
            Cache::delete(self::cacheKey($appId, 'access_token_expire'));
        } catch (\Throwable $e) {
            Log::warning('clearAccessTokenCache failed: ' . $e->getMessage());
        }
    }

    /**
     * 对公众号用户信息接口的统一封装：自动处理 token 失效重试一次
     */
    public static function getUserInfo(string $openid): array
    {
        $openid = trim($openid);
        if ($openid === '') {
            return ['success' => false, 'message' => '缺少 openid'];
        }

        try {
            $token = self::getAccessToken(false);
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . urlencode($token)
                . '&openid=' . urlencode($openid) . '&lang=zh_CN';
            $raw = self::httpGet($url);
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['errcode']) && (int)$data['errcode'] !== 0) {
                $errcode = (int)$data['errcode'];
                if ($errcode === 40001 || $errcode === 42001) {
                    self::clearAccessTokenCache();
                    $token2 = self::getAccessToken(true);
                    $url2 = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . urlencode($token2)
                        . '&openid=' . urlencode($openid) . '&lang=zh_CN';
                    $raw2 = self::httpGet($url2);
                    $data2 = json_decode($raw2, true);
                    if (is_array($data2) && (!isset($data2['errcode']) || (int)($data2['errcode'] ?? 0) === 0)) {
                        return ['success' => true, 'data' => $data2];
                    }
                    $msg2 = is_array($data2) ? (string)($data2['errmsg'] ?? 'unknown') : 'invalid response';
                    return ['success' => false, 'message' => $msg2];
                }

                return ['success' => false, 'message' => (string)($data['errmsg'] ?? '获取用户信息失败')];
            }

            if (!is_array($data)) {
                return ['success' => false, 'message' => '微信返回异常'];
            }

            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function httpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception('HTTP请求失败: ' . $err);
        }
        curl_close($ch);
        return (string)$result;
    }
}

