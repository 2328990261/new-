<?php
namespace app\controller\api;

use app\BaseController;
use app\service\OfficialTokenService;

/**
 * 内部统一 token 接口（给同机/内网其它 PHP 脚本调用）
 *
 * 安全策略：
 * - IP 白名单：OFFICIAL_TOKEN_ALLOWED_IPS（逗号分隔），为空则仅允许 127.0.0.1/::1
 * - 签名：sha256(appid + "\n" + ts + "\n" + nonce + "\n" + secret)
 *   其中 secret=OFFICIAL_TOKEN_API_SECRET
 */
class OfficialToken extends BaseController
{
    private function allowedIps(): array
    {
        $raw = trim((string)env('OFFICIAL_TOKEN_ALLOWED_IPS', ''));
        if ($raw === '') {
            return ['127.0.0.1', '::1'];
        }
        $items = array_filter(array_map('trim', explode(',', $raw)), function ($v) {
            return $v !== '';
        });
        return array_values(array_unique($items));
    }

    private function isIpAllowed(string $ip): bool
    {
        $allow = $this->allowedIps();
        if (in_array($ip, $allow, true)) {
            return true;
        }
        // 支持简单的 CIDR（仅 IPv4）
        foreach ($allow as $a) {
            if (strpos($a, '/') !== false && $this->ipInCidr($ip, $a)) {
                return true;
            }
        }
        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) return false;
        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int)$mask;
        if ($mask < 0 || $mask > 32) return false;
        $ipLong = ip2long($ip);
        $subLong = ip2long($subnet);
        if ($ipLong === false || $subLong === false) return false;
        $maskLong = -1 << (32 - $mask);
        return (($ipLong & $maskLong) === ($subLong & $maskLong));
    }

    private function jsonFail(string $msg, int $code = 403, array $extra = [])
    {
        $payload = ['success' => false, 'message' => $msg] + $extra;
        return json($payload, $code);
    }

    private function verify(): ?\think\Response
    {
        $ip = (string)$this->request->ip();
        if (!$this->isIpAllowed($ip)) {
            return $this->jsonFail('IP不在白名单', 403, ['ip' => $ip]);
        }

        $appid = trim((string)$this->request->param('appid', ''));
        $ts = trim((string)$this->request->param('ts', ''));
        $nonce = trim((string)$this->request->param('nonce', ''));
        $sign = trim((string)$this->request->param('sign', ''));
        if ($appid === '' || $ts === '' || $nonce === '' || $sign === '') {
            return $this->jsonFail('缺少签名参数', 400);
        }
        if (!preg_match('/^\d{10,13}$/', $ts)) {
            return $this->jsonFail('ts格式错误', 400);
        }
        $tsInt = (int)substr($ts, 0, 10);
        if (abs(time() - $tsInt) > 300) {
            return $this->jsonFail('签名已过期', 403);
        }

        $secret = (string)env('OFFICIAL_TOKEN_API_SECRET', '');
        if ($secret === '') {
            return $this->jsonFail('服务端未配置 OFFICIAL_TOKEN_API_SECRET', 500);
        }
        $expect = hash('sha256', $appid . "\n" . $ts . "\n" . $nonce . "\n" . $secret);
        if (!hash_equals($expect, $sign)) {
            return $this->jsonFail('签名不正确', 403);
        }
        return null;
    }

    /**
     * GET /api/official/token
     */
    public function token()
    {
        if ($resp = $this->verify()) return $resp;

        try {
            $token = OfficialTokenService::getAccessToken(false);
            return json(['success' => true, 'access_token' => $token]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/official/jsapi-ticket
     */
    public function jsapiTicket()
    {
        if ($resp = $this->verify()) return $resp;

        try {
            $ticket = OfficialTokenService::getJsapiTicket(false);
            return json(['success' => true, 'ticket' => $ticket]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

