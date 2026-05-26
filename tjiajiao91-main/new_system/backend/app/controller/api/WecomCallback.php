<?php
namespace app\controller\api;

use app\BaseController;
use app\common\WechatOfficialMsgCrypt;
use app\service\WecomService;
use think\facade\Db;
use think\facade\Log;

/**
 * 企业微信客户联系回调：加好友后自动发欢迎语 + 进群引导链接
 *
 * 企业微信后台「客户联系 - 客户」API 接收事件服务器 URL 填写：
 * {公网根}/api/wecom/callback
 */
class WecomCallback extends BaseController
{
    private function xmlField(array $payload, string $key): string
    {
        $v = $payload[$key] ?? '';
        if (is_array($v)) {
            return trim((string)($v[0] ?? ''));
        }
        return trim((string)$v);
    }

    /**
     * 进群引导落地页（客户在微信内置浏览器打开）
     * GET /api/wecom/join-landing?city_id=1
     */
    public function joinLanding()
    {
        $cityId = (int)$this->request->param('city_id', 0);
        $title = '加入家教群';
        $html = '';
        try {
            if ($cityId <= 0) {
                throw new \Exception('参数 city_id 无效');
            }
            $info = WecomService::ensureJoinWayQrForCity($cityId);
            $title = '【91家教】' . ($info['city_name'] ?? '') . ' — 扫码入群';
            $qr = htmlspecialchars((string)($info['qr_code'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $name = htmlspecialchars((string)($info['group_name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"/>'
                . '<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1"/>'
                . '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>'
                . '<style>body{font-family:system-ui,-apple-system,sans-serif;padding:16px;text-align:center;background:#f7f8fa;}'
                . 'h1{font-size:18px;margin:12px 0 8px;} .card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.06);}'
                . 'img{max-width:280px;width:100%;height:auto;} .tip{color:#666;font-size:14px;line-height:1.6;margin-top:12px;}</style></head><body>'
                . '<div class="card"><h1>' . $name . '</h1>'
                . '<p class="tip">请长按下图识别二维码，按提示加入企业微信群。</p>'
                . '<p><img src="' . $qr . '" alt="群二维码"/></p></div></body></html>';
        } catch (\Throwable $e) {
            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"/>'
                . '<meta name="viewport" content="width=device-width,initial-scale=1"/>'
                . '<title>' . $title . '</title></head><body style="padding:16px;font-family:sans-serif;">'
                . '<p>暂时无法展示入群二维码。</p><p style="color:#888;font-size:14px;">' . $msg . '</p></body></html>';
        }
        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 客户联系回调：GET 验证 URL，POST 接收事件
     */
    public function receive()
    {
        $cfg = WecomService::getConfig();
        $token = trim((string)($cfg['callback_token'] ?? ''));
        $encodingAesKey = trim((string)($cfg['callback_encoding_aes_key'] ?? ''));
        $corpId = trim((string)($cfg['corp_id'] ?? ''));

        if ($token === '' || $encodingAesKey === '' || $corpId === '') {
            Log::warning('wecom callback not configured');
            return response('wecom_callback_not_configured', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $msgSignature = (string)$this->request->param('msg_signature', '');
        $timestamp = (string)$this->request->param('timestamp', '');
        $nonce = (string)$this->request->param('nonce', '');
        $echostr = (string)$this->request->param('echostr', '');

        $isGet = $this->request->isGet();
        $postRaw = '';
        if (!$isGet) {
            $postRaw = (string)file_get_contents('php://input');
        }

        $postEncrypt = '';
        if ($postRaw !== '') {
            $xmlTmp = @simplexml_load_string($postRaw, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xmlTmp !== false) {
                $tmp = json_decode(json_encode($xmlTmp, JSON_UNESCAPED_UNICODE), true);
                $enc = $tmp['Encrypt'] ?? '';
                if (is_array($enc)) {
                    $enc = (string)reset($enc);
                } else {
                    $enc = (string)$enc;
                }
                $postEncrypt = trim($enc);
            }
        }

        $canCrypt = $msgSignature !== '' && $timestamp !== '' && $nonce !== '';
        $getEncryptAuth = $isGet && $canCrypt && $echostr !== ''
            && WechatOfficialMsgCrypt::verifyEncryptSignature($token, $timestamp, $nonce, $echostr, $msgSignature);
        $postEncryptAuth = !$isGet && $postEncrypt !== '' && $canCrypt
            && WechatOfficialMsgCrypt::verifyEncryptSignature($token, $timestamp, $nonce, $postEncrypt, $msgSignature);

        if ($isGet) {
            if ($getEncryptAuth) {
                $plainEcho = WechatOfficialMsgCrypt::decrypt($encodingAesKey, $corpId, $echostr);
                if ($plainEcho === false) {
                    return response('decrypt_error', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
                }
                return response($plainEcho, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            return response('signature_error', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($postRaw === '') {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $rawXml = $postRaw;
        if ($postEncrypt !== '') {
            if (!$postEncryptAuth) {
                Log::warning('wecom callback encrypt signature failed');
                return response('signature_error', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $decrypted = WechatOfficialMsgCrypt::decrypt($encodingAesKey, $corpId, $postEncrypt);
            if ($decrypted === false) {
                Log::warning('wecom callback body decrypt failed');
                return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $rawXml = $decrypted;
        } else {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $xml = @simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $payload = json_decode(json_encode($xml, JSON_UNESCAPED_UNICODE), true);
        if (!is_array($payload)) {
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $msgType = strtolower($this->xmlField($payload, 'MsgType'));
        $event = strtolower($this->xmlField($payload, 'Event'));
        $changeType = strtolower($this->xmlField($payload, 'ChangeType'));

        if ($msgType === 'event' && $event === 'change_external_contact' && $changeType === 'add_external_contact') {
            $this->handleAddExternalContact($payload, $cfg);
        }

        return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function handleAddExternalContact(array $payload, array $cfg): void
    {
        $welcomeCode = $this->xmlField($payload, 'WelcomeCode');
        if ($welcomeCode === '') {
            Log::info('wecom add_external_contact: no WelcomeCode (check: 成员是否已在企微后台配置欢迎语；配置了则不会下发 code)', [
                'user_id' => $this->xmlField($payload, 'UserID'),
                'state' => $this->xmlField($payload, 'State'),
            ]);
            return;
        }

        $state = $this->xmlField($payload, 'State');
        $cityId = WecomService::parseCityIdFromContactState($state);
        if ($cityId <= 0) {
            Log::info('wecom add_external_contact: state has no city id, skip welcome', ['state' => $state]);
            return;
        }

        $base = trim((string)($cfg['welcome_public_base_url'] ?? ''));
        if ($base === '') {
            $base = rtrim((string)$this->request->domain(), '/');
        } else {
            $base = rtrim($base, '/');
        }
        if ($base === '') {
            Log::warning('wecom welcome skipped: welcome_public_base_url empty and request domain unknown');
            return;
        }

        $linkUrl = $base . '/api/wecom/join-landing?city_id=' . $cityId;
        $defaultText = '您好！感谢添加～请点击下面卡片打开页面，长按识别二维码加入同城【91家教】微信群。';
        $text = trim((string)($cfg['welcome_after_contact_text'] ?? ''));
        if ($text === '') {
            $text = $defaultText;
        }
        $linkTitle = trim((string)($cfg['welcome_link_title'] ?? ''));
        if ($linkTitle === '') {
            $linkTitle = '加入同城家教群';
        }

        $body = [
            'welcome_code' => $welcomeCode,
            'text' => ['content' => $text],
            'attachments' => [[
                'msgtype' => 'link',
                'link' => [
                    'title' => $linkTitle,
                    'desc' => '长按识别二维码加入企业微信客户群',
                    'url' => $linkUrl,
                ],
            ]],
        ];

        try {
            $res = WecomService::sendWelcomeMsg($body);
            $code = (int)($res['errcode'] ?? -1);
            if ($code === 0) {
                Log::info('wecom welcome sent', ['city_id' => $cityId, 'state' => $state]);
                return;
            }
            if ($code === 41096) {
                usleep(300000);
                $res2 = WecomService::sendWelcomeMsg($body);
                $code2 = (int)($res2['errcode'] ?? -1);
                if ($code2 === 0) {
                    Log::info('wecom welcome sent after retry', ['city_id' => $cityId]);
                    return;
                }
                Log::warning('wecom welcome retry failed', ['errcode' => $code2, 'errmsg' => (string)($res2['errmsg'] ?? '')]);
                return;
            }
            if ($code === 41051) {
                Log::info('wecom welcome already sent (41051)', ['city_id' => $cityId]);
                return;
            }
            Log::warning('wecom welcome send failed', ['errcode' => $code, 'errmsg' => (string)($res['errmsg'] ?? '')]);
        } catch (\Throwable $e) {
            Log::error('wecom welcome exception: ' . $e->getMessage());
        }
    }
}
