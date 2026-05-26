<?php
namespace app\controller\api;

use app\BaseController;
use app\common\WechatOfficialMsgCrypt;
use app\service\WechatNotificationService;
use think\facade\Db;
use think\facade\Log;

/**
 * 微信公众号服务器回调（关注/扫码事件）
 */
class WechatOfficial extends BaseController
{
    /**
     * 粗判是否是公众号/小程序 openid（避免把 openid 误当 unionid）
     */
    private function looksLikeOpenid(string $value): bool
    {
        $v = trim($value);
        if ($v === '') return false;
        // openid 常见以 "o" 开头，长度约 28；这里放宽到 20-40
        return (bool)preg_match('/^o[A-Za-z0-9\-_]{19,39}$/', $v);
    }
    /**
     * 公众号绑定链路调试日志（写入 notification_logs，便于后台直接查看）
     */
    private function debugLog(string $stage, array $context = []): void
    {
        try {
            $payload = array_merge([
                'stage' => $stage,
                'time' => date('Y-m-d H:i:s')
            ], $context);
            Log::info('[wechat_official_debug] ' . json_encode($payload, JSON_UNESCAPED_UNICODE));

            Db::name('notification_logs')->insert([
                'channel' => 'wechat_debug',
                'receiver' => (string)($context['receiver'] ?? ''),
                'template_code' => 'official_bind_debug',
                'send_data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'status' => (int)($context['status'] ?? 1),
                'error_msg' => (string)($context['error_msg'] ?? ''),
                'send_time' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            Log::warning('[wechat_official_debug_log_failed] ' . $e->getMessage());
        }
    }

    /**
     * 获取公众号 OAuth 绑定链接（不依赖服务器消息推送）
     * GET /api/wechat/official/bind-auth-url?mini_openid=xxx
     */
    public function bindAuthUrl()
    {
        $miniOpenid = trim((string)$this->request->get('mini_openid', ''));
        if ($miniOpenid === '') {
            return json(['success' => false, 'message' => '缺少 mini_openid']);
        }

        try {
            $config = Db::name('notification_config')->find(1);
            $appId = trim((string)($config['wechat_app_id'] ?? ''));
            $appSecret = trim((string)($config['wechat_app_secret'] ?? ''));
            if ($appId === '' || $appSecret === '') {
                return json(['success' => false, 'message' => '公众号配置不完整，请先配置AppID/AppSecret']);
            }

            $state = $this->buildBindState($miniOpenid, (string)($config['wechat_token'] ?? ''));
            $callbackDomain = !empty($config['wechat_callback_domain'])
                ? rtrim((string)$config['wechat_callback_domain'], '/')
                : rtrim((string)$this->request->domain(), '/');
            $redirectUri = $callbackDomain . '/api/wechat/official/bind-callback';

            $query = http_build_query([
                'appid' => $appId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'snsapi_base',
                'state' => $state
            ]);
            $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . $query . '#wechat_redirect';

            return json([
                'success' => true,
                'data' => [
                    'auth_url' => $authUrl,
                    'state' => $state
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 查询 mini_openid 的公众号绑定状态（前端轮询用）
     * GET /api/wechat/official/bind-status?mini_openid=xxx
     */
    public function bindStatus()
    {
        $miniOpenid = trim((string)$this->request->get('mini_openid', ''));
        if ($miniOpenid === '') {
            return json(['success' => false, 'message' => '缺少 mini_openid']);
        }

        try {
            $bind = Db::name('wechat_openid_bindings')
                ->where('mini_openid', $miniOpenid)
                ->order('update_time', 'desc')
                ->find();

            $mpOpenid = trim((string)($bind['mp_openid'] ?? ''));
            $unionid = trim((string)($bind['unionid'] ?? ''));
            // 明确修正：unionid 不应与 mp_openid 相同
            if ($mpOpenid !== '' && $unionid !== '' && $unionid === $mpOpenid) {
                $unionid = '';
                if ($bind) {
                    Db::name('wechat_openid_bindings')->where('id', (int)$bind['id'])->update([
                        'unionid' => null,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            // 容错清洗：unionid 被误写成 openid 时直接置空并回写数据库
            if ($this->looksLikeOpenid($unionid)) {
                $unionid = '';
                if ($bind) {
                    Db::name('wechat_openid_bindings')->where('id', (int)$bind['id'])->update([
                        'unionid' => null,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            $isSubscribed = (int)($bind['is_subscribed'] ?? 0) === 1;

            // 若绑定表尚无 mp_openid，尝试用 mini_openid 对应 unionid 自动反查公众号 openid 并补绑
            if ($mpOpenid === '') {
                $miniUnionid = trim((string)(Db::name('wechat_users')->where('openid', $miniOpenid)->value('unionid') ?? ''));
                if ($this->looksLikeOpenid($miniUnionid)) {
                    $miniUnionid = '';
                }
                if ($unionid === '' && $miniUnionid !== '') {
                    $unionid = $miniUnionid;
                }

                // 容错：历史脏数据里 unionid 字段可能误存了公众号 openid（通常以 o 开头）
                if ($mpOpenid === '' && $unionid !== '') {
                    $candMpByOpenid = Db::name('wechat_users')
                        ->where('openid', $unionid)
                        ->where('subscribe', 1)
                        ->find();
                    if (!empty($candMpByOpenid['openid'])) {
                        $mpOpenid = trim((string)$candMpByOpenid['openid']);
                    }
                    if ($mpOpenid === '') {
                        $candBindByMp = Db::name('wechat_openid_bindings')
                            ->where('mp_openid', $unionid)
                            ->order('update_time', 'desc')
                            ->find();
                        if (!empty($candBindByMp['mp_openid'])) {
                            $mpOpenid = trim((string)$candBindByMp['mp_openid']);
                        }
                    }
                }

                if ($miniUnionid !== '') {
                    $candBind = Db::name('wechat_openid_bindings')
                        ->where('unionid', $miniUnionid)
                        ->where('mp_openid', '<>', '')
                        ->order('update_time', 'desc')
                        ->find();
                    if (!empty($candBind['mp_openid'])) {
                        $mpOpenid = trim((string)$candBind['mp_openid']);
                    }
                    if ($mpOpenid === '') {
                        $candMp = Db::name('wechat_users')
                            ->where('unionid', $miniUnionid)
                            ->where('subscribe', 1)
                            ->where('openid', '<>', '')
                            ->order('update_time', 'desc')
                            ->order('create_time', 'desc')
                            ->find();
                        if (!empty($candMp['openid'])) {
                            $mpOpenid = trim((string)$candMp['openid']);
                        }
                    }
                    if ($mpOpenid !== '') {
                        $now = date('Y-m-d H:i:s');
                        if ($bind) {
                            Db::name('wechat_openid_bindings')->where('id', (int)$bind['id'])->update([
                                'mp_openid' => $mpOpenid,
                                'unionid' => $miniUnionid,
                                'scene_key' => (string)($bind['scene_key'] ?? ('union_auto_' . $miniOpenid)),
                                'update_time' => $now
                            ]);
                        } else {
                            Db::name('wechat_openid_bindings')->insert([
                                'mini_openid' => $miniOpenid,
                                'mp_openid' => $mpOpenid,
                                'unionid' => $miniUnionid,
                                'scene_key' => 'union_auto_' . $miniOpenid,
                                'is_subscribed' => 0,
                                'create_time' => $now,
                                'update_time' => $now
                            ]);
                        }
                        $bind = Db::name('wechat_openid_bindings')
                            ->where('mini_openid', $miniOpenid)
                            ->order('update_time', 'desc')
                            ->find();
                        $isSubscribed = (int)($bind['is_subscribed'] ?? 0) === 1;
                    }
                }
            }

            // 兜底：若绑定表里 mp_openid 已有值，但订阅状态未知，实时查一次公众号用户信息
            if ($mpOpenid !== '' && !$isSubscribed) {
                $infoRes = WechatNotificationService::getUserInfo($mpOpenid);
                if (!empty($infoRes['success']) && !empty($infoRes['data']) && is_array($infoRes['data'])) {
                    $sub = (int)($infoRes['data']['subscribe'] ?? 0);
                    $liveUnionid = trim((string)($infoRes['data']['unionid'] ?? ''));
                    if ($this->looksLikeOpenid($liveUnionid)) {
                        $liveUnionid = '';
                    }
                    if ($liveUnionid !== '' && $unionid === '') {
                        $unionid = $liveUnionid;
                    }
                    if ($sub === 1) {
                        $isSubscribed = true;
                        Db::name('wechat_openid_bindings')
                            ->where('id', (int)($bind['id'] ?? 0))
                            ->update([
                                'is_subscribed' => 1,
                                'unionid' => $unionid !== '' ? $unionid : null,
                                'subscribe_time' => (int)($infoRes['data']['subscribe_time'] ?? time()),
                                'update_time' => date('Y-m-d H:i:s')
                            ]);
                    }
                }
            }

            return json([
                'success' => true,
                'data' => [
                    'mini_openid' => $miniOpenid,
                    'mp_openid' => $mpOpenid,
                    'unionid' => $unionid,
                    'is_subscribed' => $isSubscribed ? 1 : 0,
                    'is_bound' => ($mpOpenid !== '' && $isSubscribed) ? 1 : 0,
                    'scene_key' => (string)($bind['scene_key'] ?? ''),
                    'update_time' => (string)($bind['update_time'] ?? ''),
                    'reason' => $mpOpenid === '' ? '尚未收到公众号侧回调/OAuth绑定，且 unionid 反查未命中' : ($isSubscribed ? 'ok' : '公众号未关注或关注状态未同步')
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 公众号扫码事件诊断（返回最近事件与关键阶段，给前端排查用）
     * GET /api/wechat/official/latest-event-debug?mini_openid=xxx
     */
    public function latestEventDebug()
    {
        $miniOpenid = trim((string)$this->request->get('mini_openid', ''));
        try {
            $base = Db::name('notification_logs')
                ->where('channel', 'wechat_debug')
                ->where('template_code', 'official_bind_debug');

            $rows = [];
            $mode = 'global';
            if ($miniOpenid !== '') {
                $rows = (clone $base)
                    ->where(function ($q) use ($miniOpenid) {
                        $q->whereOr('receiver', $miniOpenid)
                          ->whereOr('send_data', 'like', '%' . $miniOpenid . '%');
                    })
                    ->order('id', 'desc')
                    ->limit(30)
                    ->select()
                    ->toArray();
                $mode = 'mini_filtered';
            }
            // 若按 mini_openid 没命中，回退展示全局最近事件，避免误判“完全没回调”
            if (empty($rows)) {
                $rows = (clone $base)
                    ->order('id', 'desc')
                    ->limit(30)
                    ->select()
                    ->toArray();
                $mode = ($miniOpenid !== '') ? 'global_fallback' : 'global';
            }

            $events = [];
            foreach ($rows as $row) {
                $payload = json_decode((string)($row['send_data'] ?? ''), true);
                if (!is_array($payload)) {
                    $payload = [];
                }
                $events[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'send_time' => (string)($row['send_time'] ?? ''),
                    'stage' => (string)($payload['stage'] ?? ''),
                    'event' => (string)($payload['event'] ?? ''),
                    'event_key' => (string)($payload['event_key'] ?? ''),
                    'mini_openid_parsed' => (string)($payload['mini_openid_parsed'] ?? ''),
                    'receiver' => (string)($row['receiver'] ?? ''),
                    'status' => (int)($row['status'] ?? 0),
                    'error_msg' => (string)($row['error_msg'] ?? ''),
                    'raw' => $payload
                ];
            }

            $latest = $events[0] ?? null;
            return json([
                'success' => true,
                'data' => [
                    'mini_openid' => $miniOpenid,
                    'mode' => $mode,
                    'latest' => $latest,
                    'events' => $events
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 公众号 OAuth 回调：code 换 mp_openid 并绑定 mini_openid
     * GET /api/wechat/official/bind-callback
     */
    public function bindCallback()
    {
        try {
            $code = trim((string)$this->request->get('code', ''));
            $state = trim((string)$this->request->get('state', ''));
            if ($code === '' || $state === '') {
                return response($this->renderBindResult(false, '缺少授权参数'), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }

            $config = Db::name('notification_config')->find(1);
            $appId = trim((string)($config['wechat_app_id'] ?? ''));
            $appSecret = trim((string)($config['wechat_app_secret'] ?? ''));
            if ($appId === '' || $appSecret === '') {
                return response($this->renderBindResult(false, '公众号配置不完整'), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }

            $miniOpenid = $this->parseBindState($state, (string)($config['wechat_token'] ?? ''));
            if ($miniOpenid === '') {
                return response($this->renderBindResult(false, 'state校验失败'), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }

            $oauthUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query([
                'appid' => $appId,
                'secret' => $appSecret,
                'code' => $code,
                'grant_type' => 'authorization_code'
            ]);
            $oauthRes = $this->httpGetJson($oauthUrl);
            if (isset($oauthRes['errcode'])) {
                return response($this->renderBindResult(false, '换取openid失败：' . ($oauthRes['errmsg'] ?? 'unknown')), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }
            $mpOpenid = trim((string)($oauthRes['openid'] ?? ''));
            $unionid = trim((string)($oauthRes['unionid'] ?? ''));
            if ($mpOpenid === '') {
                return response($this->renderBindResult(false, '微信未返回openid'), 200, ['Content-Type' => 'text/html; charset=utf-8']);
            }

            $now = date('Y-m-d H:i:s');
            $subscribe = 0;
            $subscribeTime = time();
            $nick = '';
            $head = '';
            $infoRes = WechatNotificationService::getUserInfo($mpOpenid);
            if (!empty($infoRes['success']) && !empty($infoRes['data']) && is_array($infoRes['data'])) {
                $info = $infoRes['data'];
                $subscribe = (int)($info['subscribe'] ?? 0);
                $subscribeTime = (int)($info['subscribe_time'] ?? $subscribeTime);
                $nick = trim((string)($info['nickname'] ?? ''));
                $head = trim((string)($info['headimgurl'] ?? ''));
                if ($unionid === '') {
                    $unionid = trim((string)($info['unionid'] ?? ''));
                }
            }

            // unionid 验证：若小程序侧已有 unionid 且与公众号侧不同，记录告警便于排查主体/开放平台绑定问题
            $miniUnionid = trim((string)(Db::name('wechat_users')->where('openid', $miniOpenid)->value('unionid') ?? ''));
            if ($miniUnionid !== '' && $unionid !== '' && $miniUnionid !== $unionid) {
                Log::warning('unionid_mismatch_detected mini=' . $miniOpenid . ' mp=' . $mpOpenid . ' mini_unionid=' . $miniUnionid . ' mp_unionid=' . $unionid);
            }

            $wu = Db::name('wechat_users')->where('openid', $mpOpenid)->find();
            $wuData = [
                'openid' => $mpOpenid,
                'unionid' => $unionid !== '' ? $unionid : ($wu['unionid'] ?? null),
                'nickname' => $nick !== '' ? $nick : ($wu['nickname'] ?? ''),
                'headimgurl' => $head !== '' ? $head : ($wu['headimgurl'] ?? ''),
                'subscribe' => $subscribe,
                'subscribe_time' => $subscribeTime,
                'update_time' => $now
            ];
            if ($wu) {
                Db::name('wechat_users')->where('id', (int)$wu['id'])->update($wuData);
            } else {
                $wuData['create_time'] = $now;
                Db::name('wechat_users')->insert($wuData);
            }

            $this->upsertBinding($miniOpenid, $mpOpenid, $unionid, 'oauth_bind_' . $miniOpenid, $subscribeTime, $now);
            Db::name('wechat_openid_bindings')
                ->where('mini_openid', $miniOpenid)
                ->update([
                    'is_subscribed' => $subscribe === 1 ? 1 : 0,
                    'update_time' => $now
                ]);

            return response($this->renderBindResult(true, '绑定成功，可返回小程序继续操作'), 200, ['Content-Type' => 'text/html; charset=utf-8']);
        } catch (\Throwable $e) {
            return response($this->renderBindResult(false, '绑定失败：' . $e->getMessage()), 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    /**
     * 生成公众号带参二维码（用于小程序用户与公众号关注绑定）
     * POST /api/wechat/official/qrcode
     */
    public function qrcode()
    {
        $miniOpenid = trim((string)$this->request->post('mini_openid', ''));
        if ($miniOpenid === '') {
            $this->debugLog('qrcode_missing_mini_openid', ['status' => 0, 'error_msg' => '缺少 mini_openid']);
            return json(['success' => false, 'message' => '缺少 mini_openid']);
        }

        // 场景值长度限制（公众号场景值最大 64 字符）
        $scene = 'bind_' . $miniOpenid;
        if (strlen($scene) > 64) {
            $this->debugLog('qrcode_scene_too_long', [
                'receiver' => $miniOpenid,
                'status' => 0,
                'error_msg' => 'mini_openid过长，无法生成二维码',
                'scene' => $scene
            ]);
            return json(['success' => false, 'message' => 'mini_openid过长，无法生成二维码']);
        }

        try {
            $now = date('Y-m-d H:i:s');
            $existingBind = Db::name('wechat_openid_bindings')->where('mini_openid', $miniOpenid)->find();
            $bindSeed = [
                'mini_openid' => $miniOpenid,
                'scene_key' => $scene,
                'is_subscribed' => 0,
                'update_time' => $now
            ];
            if ($existingBind) {
                Db::name('wechat_openid_bindings')->where('id', (int)$existingBind['id'])->update($bindSeed);
            } else {
                $bindSeed['create_time'] = $now;
                Db::name('wechat_openid_bindings')->insert($bindSeed);
            }
            $this->debugLog('qrcode_binding_seed_saved', [
                'receiver' => $miniOpenid,
                'scene' => $scene
            ]);

            $accessToken = WechatNotificationService::getAccessToken();
            $api = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$accessToken}";
            $payload = [
                'expire_seconds' => 604800, // 7天有效
                'action_name' => 'QR_STR_SCENE',
                'action_info' => [
                    'scene' => ['scene_str' => $scene]
                ]
            ];
            $res = $this->httpPostJson($api, $payload);
            if (isset($res['errcode']) && (int)$res['errcode'] !== 0) {
                $this->debugLog('qrcode_wechat_api_failed', [
                    'receiver' => $miniOpenid,
                    'status' => 0,
                    'error_msg' => (string)($res['errmsg'] ?? 'unknown'),
                    'errcode' => (int)($res['errcode'] ?? 0),
                    'scene' => $scene
                ]);
                return json([
                    'success' => false,
                    'message' => '生成二维码失败：' . ($res['errmsg'] ?? 'unknown')
                ]);
            }
            $ticket = trim((string)($res['ticket'] ?? ''));
            if ($ticket === '') {
                $this->debugLog('qrcode_ticket_empty', [
                    'receiver' => $miniOpenid,
                    'status' => 0,
                    'error_msg' => '微信返回缺少ticket',
                    'scene' => $scene
                ]);
                return json(['success' => false, 'message' => '微信返回缺少ticket']);
            }
            $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($ticket);
            $this->debugLog('qrcode_generated_success', [
                'receiver' => $miniOpenid,
                'scene' => $scene,
                'ticket_prefix' => substr($ticket, 0, 16)
            ]);
            return json([
                'success' => true,
                'data' => [
                    'scene' => $scene,
                    'ticket' => $ticket,
                    'url' => $url,
                    'expire_seconds' => 604800
                ]
            ]);
        } catch (\Throwable $e) {
            $this->debugLog('qrcode_exception', [
                'receiver' => $miniOpenid,
                'status' => 0,
                'error_msg' => $e->getMessage()
            ]);
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * 微信公众号服务器入口（GET 验签 + POST 事件）
     * 支持明文模式与兼容/安全模式（消息体加密，需配置 EncodingAESKey + AppID）
     */
    public function server()
    {
        $config = Db::name('notification_config')->find(1);
        $token = trim((string)($config['wechat_token'] ?? ''));
        if ($token === '') {
            $this->debugLog('server_token_not_configured', [
                'status' => 0,
                'error_msg' => 'wechat_token_not_configured'
            ]);
            return response('wechat_token_not_configured', 500);
        }

        $appId = trim((string)($config['wechat_app_id'] ?? ''));
        $encodingAesKey = trim((string)($config['wechat_encoding_aes_key'] ?? ''));

        $signature = (string)$this->request->param('signature', '');
        $msgSignature = (string)$this->request->param('msg_signature', '');
        $timestamp = (string)$this->request->param('timestamp', '');
        $nonce = (string)$this->request->param('nonce', '');
        $echostr = (string)$this->request->param('echostr', '');

        $this->debugLog('server_request_received', [
            'request_method' => $this->request->method(),
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'has_signature' => $signature !== '' ? 1 : 0,
            'has_msg_signature' => $msgSignature !== '' ? 1 : 0,
        ]);

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

        $plainAuth = WechatOfficialMsgCrypt::verifyPlainSignature($token, $signature, $timestamp, $nonce);
        $canCrypt = $encodingAesKey !== '' && $appId !== '' && $msgSignature !== '' && $timestamp !== '' && $nonce !== '';

        $getEncryptAuth = $isGet && $canCrypt && $echostr !== ''
            && WechatOfficialMsgCrypt::verifyEncryptSignature($token, $timestamp, $nonce, $echostr, $msgSignature);

        $postEncryptAuth = !$isGet && $postEncrypt !== '' && $canCrypt
            && WechatOfficialMsgCrypt::verifyEncryptSignature($token, $timestamp, $nonce, $postEncrypt, $msgSignature);

        if ($isGet) {
            if ($getEncryptAuth) {
                $plainEcho = WechatOfficialMsgCrypt::decrypt($encodingAesKey, $appId, $echostr);
                if ($plainEcho === false) {
                    $this->debugLog('server_handshake_decrypt_failed', ['status' => 0, 'error_msg' => 'echostr_decrypt_failed']);
                    return response('decrypt_error', 403);
                }
                $this->debugLog('server_handshake_success', ['mode' => 'encrypt']);
                return response($plainEcho, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            if ($plainAuth) {
                $this->debugLog('server_handshake_success', ['mode' => 'plain']);
                return response($echostr, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            Log::warning('公众号回调验签失败', [
                'signature' => $signature,
                'msg_signature' => $msgSignature,
                'timestamp' => $timestamp,
                'nonce' => $nonce,
                'url' => (string)$this->request->url(true)
            ]);
            $this->debugLog('server_signature_failed', [
                'status' => 0,
                'error_msg' => 'signature_error',
            ]);
            return response('signature_error', 403);
        }

        // POST：存在 Encrypt 节点时必须走 msg_signature + 解密（否则无法得到事件 XML）
        if ($postRaw === '' || trim($postRaw) === '') {
            $this->debugLog('server_empty_post_body', [
                'status' => 0,
                'error_msg' => 'empty_post_body'
            ]);
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $rawXml = $postRaw;
        if ($postEncrypt !== '') {
            if (!$postEncryptAuth) {
                Log::warning('公众号加密消息验签失败', [
                    'msg_signature' => $msgSignature,
                    'has_aes_key' => $encodingAesKey !== '' ? 1 : 0,
                    'url' => (string)$this->request->url(true)
                ]);
                $this->debugLog('server_encrypt_signature_failed', [
                    'status' => 0,
                    'error_msg' => 'encrypt_signature_error',
                    'hint' => '请确认后台已填写与公众平台一致的 EncodingAESKey，且 Token 一致',
                ]);
                return response('signature_error', 403);
            }
            $decrypted = WechatOfficialMsgCrypt::decrypt($encodingAesKey, $appId, $postEncrypt);
            if ($decrypted === false) {
                $this->debugLog('server_body_decrypt_failed', ['status' => 0, 'error_msg' => 'body_decrypt_failed']);
                return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $rawXml = $decrypted;
        } elseif (!$plainAuth) {
            Log::warning('公众号回调验签失败', [
                'signature' => $signature,
                'timestamp' => $timestamp,
                'nonce' => $nonce,
                'url' => (string)$this->request->url(true)
            ]);
            $this->debugLog('server_signature_failed', [
                'status' => 0,
                'error_msg' => 'signature_error',
            ]);
            return response('signature_error', 403);
        }

        $xml = @simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            Log::warning('公众号回调解析XML失败');
            $this->debugLog('server_xml_parse_failed', [
                'status' => 0,
                'error_msg' => 'xml_parse_failed',
                'raw_preview' => substr($rawXml, 0, 300)
            ]);
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $payload = json_decode(json_encode($xml, JSON_UNESCAPED_UNICODE), true);
        $msgType = strtolower(trim((string)($payload['MsgType'] ?? '')));
        $fromOpenid = trim((string)($payload['FromUserName'] ?? ''));
        if ($fromOpenid === '') {
            $this->debugLog('server_from_openid_empty', [
                'status' => 0,
                'error_msg' => 'from_openid_empty'
            ]);
            return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        try {
            if ($msgType === 'event') {
                $event = strtolower(trim((string)($payload['Event'] ?? '')));
                $eventKey = trim((string)($payload['EventKey'] ?? ''));
                Log::info('公众号事件回调接收', [
                    'event' => $event,
                    'event_key' => $eventKey,
                    'from_openid' => $fromOpenid
                ]);
                $this->debugLog('server_event_received', [
                    'receiver' => $fromOpenid,
                    'event' => $event,
                    'event_key' => $eventKey
                ]);

                if ($event === 'subscribe' || $event === 'scan') {
                    $this->syncOfficialWechatUser($fromOpenid, $event, $eventKey);
                } elseif ($event === 'unsubscribe') {
                    Db::name('wechat_users')->where('openid', $fromOpenid)->update([
                        'subscribe' => 0,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                    Db::name('wechat_openid_bindings')->where('mp_openid', $fromOpenid)->update([
                        'is_subscribed' => 0,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('公众号回调处理异常: ' . $e->getMessage());
            $this->debugLog('server_event_exception', [
                'receiver' => $fromOpenid,
                'status' => 0,
                'error_msg' => $e->getMessage()
            ]);
        }

        return response('success', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /**
     * 同步公众号用户信息并按 scene 绑定小程序 openid
     */
    private function syncOfficialWechatUser(string $officialOpenid, string $event, string $eventKey): void
    {
        $subscribeTime = time();
        $now = date('Y-m-d H:i:s');
        $miniOpenid = $this->extractMiniOpenidFromEventKey($eventKey);
        $unionid = '';
        $nickname = '';
        $headimgurl = '';
        $this->debugLog('sync_start', [
            'receiver' => $officialOpenid,
            'event' => $event,
            'event_key' => $eventKey,
            'mini_openid_parsed' => $miniOpenid
        ]);

        $infoRes = WechatNotificationService::getUserInfo($officialOpenid);
        if (!empty($infoRes['success']) && !empty($infoRes['data']) && is_array($infoRes['data'])) {
            $info = $infoRes['data'];
            $unionid = trim((string)($info['unionid'] ?? ''));
            $nickname = trim((string)($info['nickname'] ?? ''));
            $headimgurl = trim((string)($info['headimgurl'] ?? ''));
            $subscribeTime = (int)($info['subscribe_time'] ?? $subscribeTime);
        }

        $existing = Db::name('wechat_users')->where('openid', $officialOpenid)->find();
        $saveData = [
            'openid' => $officialOpenid,
            'unionid' => $unionid !== '' ? $unionid : ($existing['unionid'] ?? null),
            'nickname' => $nickname !== '' ? $nickname : ($existing['nickname'] ?? ''),
            'headimgurl' => $headimgurl !== '' ? $headimgurl : ($existing['headimgurl'] ?? ''),
            'subscribe' => 1,
            'subscribe_time' => $subscribeTime,
            'update_time' => $now
        ];

        if ($existing) {
            Db::name('wechat_users')->where('openid', $officialOpenid)->update($saveData);
        } else {
            $saveData['create_time'] = $now;
            Db::name('wechat_users')->insert($saveData);
        }

        $this->upsertBinding($miniOpenid, $officialOpenid, (string)($saveData['unionid'] ?? ''), $eventKey, $subscribeTime, $now);
        $this->debugLog('sync_binding_upserted', [
            'receiver' => $officialOpenid,
            'mini_openid' => $miniOpenid,
            'mp_openid' => $officialOpenid,
            'unionid_exists' => !empty($saveData['unionid']) ? 1 : 0
        ]);

        Log::info('公众号事件已处理', [
            'event' => $event,
            'official_openid' => $officialOpenid,
            'mini_openid' => $miniOpenid,
            'unionid_exists' => $saveData['unionid'] ? 1 : 0
        ]);
    }

    /**
     * 从 EventKey 解析小程序 openid
     * 支持：
     * - subscribe: qrscene_bind_{mini_openid}
     * - scan: bind_{mini_openid}
     */
    private function extractMiniOpenidFromEventKey(string $eventKey): string
    {
        $key = urldecode(trim($eventKey));
        if ($key === '') {
            return '';
        }
        if (stripos($key, 'qrscene_') === 0) {
            $key = substr($key, 8);
        }
        if (stripos($key, 'bind_') === 0) {
            return trim(substr($key, 5));
        }
        return '';
    }

    private function httpPostJson(string $url, array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        $result = curl_exec($ch);
        if ($result === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP请求失败: ' . $err);
        }
        curl_close($ch);
        $json = json_decode($result, true);
        return is_array($json) ? $json : [];
    }

    private function httpGetJson(string $url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $result = curl_exec($ch);
        if ($result === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP请求失败: ' . $err);
        }
        curl_close($ch);
        $json = json_decode($result, true);
        return is_array($json) ? $json : [];
    }

    private function buildBindState(string $miniOpenid, string $token): string
    {
        $ts = time();
        $sig = substr(sha1($miniOpenid . '|' . $ts . '|' . $token), 0, 16);
        return base64_encode($miniOpenid . '|' . $ts . '|' . $sig);
    }

    private function parseBindState(string $state, string $token): string
    {
        $raw = base64_decode($state, true);
        if ($raw === false) {
            return '';
        }
        $parts = explode('|', $raw);
        if (count($parts) !== 3) {
            return '';
        }
        [$miniOpenid, $ts, $sig] = $parts;
        $miniOpenid = trim((string)$miniOpenid);
        $ts = (int)$ts;
        $sig = trim((string)$sig);
        if ($miniOpenid === '' || $ts <= 0 || $sig === '') {
            return '';
        }
        if (abs(time() - $ts) > 86400 * 2) {
            return '';
        }
        $expect = substr(sha1($miniOpenid . '|' . $ts . '|' . $token), 0, 16);
        return hash_equals($expect, $sig) ? $miniOpenid : '';
    }

    private function renderBindResult(bool $ok, string $msg): string
    {
        $title = $ok ? '公众号绑定成功' : '公众号绑定失败';
        $icon = $ok ? '✅' : '❌';
        $safeMsg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'
            . $title . '</title><style>body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#f5f7fa;padding:24px}.box{max-width:460px;margin:40px auto;background:#fff;border-radius:12px;padding:24px;text-align:center}.icon{font-size:48px}.title{font-size:22px;margin:12px 0}.msg{color:#666;line-height:1.6}.tip{margin-top:16px;color:#999;font-size:13px}#wm-mask{position:fixed;right:0;bottom:0;width:120px;height:48px;background:#f5f7fa;z-index:2147483647;pointer-events:none}</style></head><body><div class="box"><div class="icon">'
            . $icon . '</div><div class="title">' . $title . '</div><div class="msg">' . $safeMsg . '</div><div class="tip">请返回小程序继续操作</div></div><div id="wm-mask"></div></body></html>';
    }

    /**
     * 维护 mini_openid <-> mp_openid 绑定表
     */
    private function upsertBinding(string $miniOpenid, string $mpOpenid, string $unionid, string $eventKey, int $subscribeTime, string $now): void
    {
        if ($mpOpenid === '') {
            return;
        }

        $sceneKey = '';
        $rawKey = trim($eventKey);
        if ($rawKey !== '') {
            if (strpos($rawKey, 'qrscene_') === 0) {
                $rawKey = substr($rawKey, 8);
            }
            $sceneKey = $rawKey;
        } elseif ($miniOpenid !== '') {
            $sceneKey = 'bind_' . $miniOpenid;
        }

        $baseData = [
            'mini_openid' => $miniOpenid !== '' ? $miniOpenid : null,
            'mp_openid' => $mpOpenid,
            'unionid' => $unionid !== '' ? $unionid : null,
            'scene_key' => $sceneKey !== '' ? $sceneKey : null,
            'is_subscribed' => 1,
            'subscribe_time' => $subscribeTime > 0 ? $subscribeTime : time(),
            'update_time' => $now
        ];

        $row = null;
        if ($miniOpenid !== '') {
            $row = Db::name('wechat_openid_bindings')->where('mini_openid', $miniOpenid)->find();
        }
        if (!$row) {
            $row = Db::name('wechat_openid_bindings')->where('mp_openid', $mpOpenid)->find();
        }
        if (!$row && $unionid !== '') {
            $row = Db::name('wechat_openid_bindings')->where('unionid', $unionid)->order('id', 'desc')->find();
        }

        if ($row) {
            Db::name('wechat_openid_bindings')->where('id', (int)$row['id'])->update($baseData);
            return;
        }

        $baseData['create_time'] = $now;
        Db::name('wechat_openid_bindings')->insert($baseData);
    }
}

