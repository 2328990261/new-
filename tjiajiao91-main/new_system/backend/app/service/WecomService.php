<?php
namespace app\service;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 企业微信（WeCom）基础服务
 * - access_token 缓存
 * - 客户群入群方式（客户联系能力）创建 + 获取二维码
 */
class WecomService
{
    /**
     * 读取企业微信配置（id=1），允许为空；调用方自行决定必填项
     */
    public static function getConfig(): array
    {
        try {
            $cfg = Db::name('wecom_config')->find(1);
            return is_array($cfg) ? $cfg : [];
        } catch (\Throwable $e) {
            Log::warning('load wecom_config failed: ' . $e->getMessage());
            return [];
        }
    }

    private static function cacheKey(string $corpId, string $secret, string $suffix): string
    {
        return 'wecom_' . $suffix . '_' . md5($corpId . '|' . $secret);
    }

    /**
     * 获取企业微信 access_token（用于客户群“加入群聊”接口）
     *
     * 说明（按 99546）：
     * - 调用方需要使用“配置到 客户联系-可调用接口的应用”中的自建应用 secret 获取 token
     * - 为兼容不同企业的配置习惯：优先使用 contact_secret，若为空则回退使用 secret
     */
    public static function getAccessTokenForExternalContact(bool $forceRefresh = false): string
    {
        $cfg = self::getConfig();
        $corpId = trim((string)($cfg['corp_id'] ?? ''));
        $secret = trim((string)($cfg['contact_secret'] ?? ''));
        if ($secret === '') {
            $secret = trim((string)($cfg['secret'] ?? ''));
        }
        if ($corpId === '' || $secret === '') {
            throw new \Exception('企业微信配置未完成（CORP_ID/SECRET）');
        }

        $kToken = self::cacheKey($corpId, $secret, 'access_token');
        $kExpire = self::cacheKey($corpId, $secret, 'access_token_expire');

        if (!$forceRefresh) {
            $token = (string)(Cache::get($kToken) ?? '');
            $exp = (int)(Cache::get($kExpire) ?? 0);
            if ($token !== '' && $exp > time()) {
                return $token;
            }
        }

        $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid='
            . urlencode($corpId) . '&corpsecret=' . urlencode($secret);
        $raw = self::httpGet($url);
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['access_token'])) {
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            throw new \Exception('获取企业微信access_token失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }

        $token = (string)$data['access_token'];
        $expiresIn = (int)($data['expires_in'] ?? 7200);
        $expireAt = time() + max(0, $expiresIn - 300);

        Cache::set($kToken, $token, $expiresIn);
        Cache::set($kExpire, $expireAt, $expiresIn);
        return $token;
    }

    /**
     * 创建客户群入群方式（返回 config_id）
     *
     * @param array $payload add_join_way 请求体
     */
    public static function addGroupJoinWay(array $payload): string
    {
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/add_join_way?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, $payload);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            // token 失效：清缓存并强刷一次
            if ($errcode === 40014 || $errcode === 42001) {
                self::getAccessTokenForExternalContact(true);
            }
            // 40058：mark_source 仅“获客助手”应用可用；自动降级：去掉 mark_source 后重试一次
            if ($errcode === 40058 && array_key_exists('mark_source', $payload)) {
                $payload2 = $payload;
                unset($payload2['mark_source']);
                $raw2 = self::httpPostJson($url, $payload2);
                $data2 = json_decode($raw2, true);
                if (is_array($data2) && (int)($data2['errcode'] ?? -1) === 0) {
                    $configId2 = (string)($data2['config_id'] ?? '');
                    if ($configId2 !== '') {
                        return $configId2;
                    }
                    throw new \Exception('创建入群方式失败：未返回config_id');
                }
                $errcode2 = is_array($data2) ? (int)($data2['errcode'] ?? $errcode) : $errcode;
                $errmsg2 = is_array($data2) ? (string)($data2['errmsg'] ?? $errmsg) : $errmsg;
                throw new \Exception('创建入群方式失败：' . $errmsg2 . ($errcode2 ? ('(' . $errcode2 . ')') : ''));
            }
            throw new \Exception('创建入群方式失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        $configId = (string)($data['config_id'] ?? '');
        if ($configId === '') {
            throw new \Exception('创建入群方式失败：未返回config_id');
        }
        return $configId;
    }

    /**
     * 获取入群方式详情（包含 qr_code）
     */
    public static function getGroupJoinWay(string $configId): array
    {
        $configId = trim($configId);
        if ($configId === '') {
            throw new \Exception('缺少config_id');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/get_join_way?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, ['config_id' => $configId]);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('获取入群方式失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        return $data;
    }

    /**
     * 删除客户群入群方式配置（del_join_way）
     * 文档：客户群「加入群聊」管理
     */
    public static function deleteGroupJoinWay(string $configId): array
    {
        $configId = trim($configId);
        if ($configId === '') {
            throw new \Exception('缺少config_id');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/del_join_way?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, ['config_id' => $configId]);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \Exception('删除入群方式失败：响应无效');
        }
        $errcode = (int)($data['errcode'] ?? -1);
        if ($errcode === 40014 || $errcode === 42001) {
            self::getAccessTokenForExternalContact(true);
        }
        return $data;
    }

    /**
     * 配置“联系我”二维码（add_contact_way），返回 config_id
     *
     * 文档：92228（客户联系「联系我」管理）
     * 说明：
     * - scene=2 时返回二维码链接 qr_code
     * - type=1 单人、type=2 多人；user/party 需在应用可见范围内
     */
    public static function addContactWay(array $payload): string
    {
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_contact_way?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, $payload);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            if ($errcode === 40014 || $errcode === 42001) {
                self::getAccessTokenForExternalContact(true);
            }
            throw new \Exception('创建联系我失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        $configId = (string)($data['config_id'] ?? '');
        if ($configId === '') {
            throw new \Exception('创建联系我失败：未返回config_id');
        }
        return $configId;
    }

    /**
     * 获取“联系我”详情（包含 qr_code）
     */
    public static function getContactWay(string $configId): array
    {
        $configId = trim($configId);
        if ($configId === '') {
            throw new \Exception('缺少config_id');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_contact_way?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, ['config_id' => $configId]);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('获取联系我失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        return $data;
    }

    /**
     * 删除“联系我”配置（del_contact_way）
     * 文档：客户联系「联系我」管理
     */
    public static function deleteContactWay(string $configId): array
    {
        $configId = trim($configId);
        if ($configId === '') {
            throw new \Exception('缺少config_id');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/del_contact_way?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, ['config_id' => $configId]);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \Exception('删除联系我失败：响应无效');
        }
        $errcode = (int)($data['errcode'] ?? -1);
        if ($errcode === 40014 || $errcode === 42001) {
            self::getAccessTokenForExternalContact(true);
        }
        return $data;
    }

    /**
     * 获取客户群列表（chat_id 列表）
     *
     * 文档：客户联系-客户群管理-获取客户群列表
     * 说明：该接口需要传 owner_filter.userid_list，故需要配置 owner_userids（一次配置，全城复用）
     *
     * @return array{chat_id_list: array<int,string>, next_cursor: string}
     */
    public static function listGroupChats(array $ownerUserids, string $cursor = '', int $limit = 100): array
    {
        $ownerUserids = array_values(array_filter($ownerUserids, function ($v) {
            return is_string($v) && trim($v) !== '';
        }));
        if (empty($ownerUserids)) {
            throw new \Exception('企业微信未配置 owner_userids（用于拉取客户群列表）');
        }

        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/list?access_token=' . urlencode($token);
        $payload = [
            'status_filter' => 0,
            'owner_filter' => [
                'userid_list' => $ownerUserids
            ],
            'cursor' => $cursor,
            'limit' => $limit
        ];
        $raw = self::httpPostJson($url, $payload);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('获取客户群列表失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        $list = $data['group_chat_list'] ?? [];
        $chatIdList = [];
        if (is_array($list)) {
            foreach ($list as $it) {
                if (is_array($it) && !empty($it['chat_id'])) {
                    $chatIdList[] = (string)$it['chat_id'];
                }
            }
        }
        return [
            'chat_id_list' => $chatIdList,
            'next_cursor' => (string)($data['next_cursor'] ?? ''),
        ];
    }

    /**
     * 获取客户群详情（含群名称）
     */
    public static function getGroupChatDetail(string $chatId): array
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            throw new \Exception('缺少 chat_id');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/get?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, ['chat_id' => $chatId]);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('获取客户群详情失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        return $data;
    }

    /**
     * 发送新客户欢迎语（依赖客户联系事件里的 welcome_code，20 秒内仅可调用一次）
     *
     * @param array $body welcome_code + 可选 text / attachments，结构同官方文档
     * @return array 原始响应 JSON
     */
    public static function sendWelcomeMsg(array $body): array
    {
        $do = function (bool $forceToken) use ($body) {
            $token = self::getAccessTokenForExternalContact($forceToken);
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/send_welcome_msg?access_token=' . urlencode($token);
            return self::httpPostJson($url, $body);
        };
        $raw = $do(false);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \Exception('发送欢迎语失败：响应无效');
        }
        $errcode = (int)($data['errcode'] ?? -1);
        if ($errcode === 40014 || $errcode === 42001) {
            $raw2 = $do(true);
            $data2 = json_decode($raw2, true);
            if (is_array($data2)) {
                return $data2;
            }
        }
        return $data;
    }

    /**
     * 确保指定城市已生成客户群「加入群聊」二维码（无则创建并入库）
     *
     * @return array{city_id:int,city_name:string,group_name:string,qr_code:string}
     */
    public static function ensureJoinWayQrForCity(int $cityId): array
    {
        if ($cityId <= 0) {
            throw new \Exception('城市ID无效');
        }
        $city = Db::name('cities')->where('id', $cityId)->find();
        $cityName = $city ? trim((string)($city['name'] ?? '')) : '';
        if ($cityName === '') {
            throw new \Exception('城市不存在');
        }
        $groupName = '【91家教】' . $cityName . '家教群';

        $row = Db::name('wecom_city_groups')->where('city_id', $cityId)->find();
        if ($row && !empty($row['qr_code'])) {
            return [
                'city_id' => $cityId,
                'city_name' => $cityName,
                'group_name' => trim((string)($row['group_name'] ?? $groupName)) ?: $groupName,
                'qr_code' => (string)$row['qr_code'],
            ];
        }

        $cfg = self::getConfig();
        $corpId = trim((string)($cfg['corp_id'] ?? ''));
        $secret = trim((string)($cfg['contact_secret'] ?? ''));
        if ($secret === '') {
            $secret = trim((string)($cfg['secret'] ?? ''));
        }
        if ($corpId === '' || $secret === '') {
            throw new \Exception('企业微信未配置CORP_ID/SECRET');
        }

        $chatIdList = [];
        if ($row && !empty($row['chat_id_list'])) {
            $decoded = json_decode((string)$row['chat_id_list'], true);
            if (is_array($decoded)) {
                $chatIdList = array_values(array_filter($decoded, function ($v) {
                    return is_string($v) && trim($v) !== '';
                }));
            }
        }
        if (empty($chatIdList)) {
            $chatIdList = self::discoverChatIdListByCity($cityName);
            if (empty($chatIdList)) {
                throw new \Exception('未找到客户群 chat_id，请在管理端为该城市配置 chat_id_list 或填写 owner_userids 以自动匹配');
            }
        }

        $payload = [
            'scene' => (int)($cfg['join_way_scene'] ?? 2) ?: 2,
            'remark' => $groupName,
            'state' => ('city_' . $cityId),
            'auto_create_room' => (int)($cfg['join_way_auto_create_room'] ?? 1),
            'room_base_name' => (string)($cfg['join_way_room_base_name'] ?? ''),
            'room_base_id' => (int)($cfg['join_way_room_base_id'] ?? 0),
            'chat_id_list' => $chatIdList,
        ];
        if ($payload['room_base_name'] === '') {
            $payload['room_base_name'] = $groupName;
        }
        if (empty($payload['room_base_id'])) {
            $payload['room_base_id'] = $cityId;
        }
        $payload['mark_source'] = (int)($cfg['join_way_mark_source'] ?? 1) ? true : false;

        $configId = self::addGroupJoinWay($payload);
        $detail = self::getGroupJoinWay($configId);
        $joinWay = (array)($detail['join_way'] ?? []);
        $qrCode = (string)($joinWay['qr_code'] ?? '');
        if ($qrCode === '') {
            throw new \Exception('企业微信未返回群二维码');
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'city_id' => $cityId,
            'city_name' => $cityName,
            'group_name' => $groupName,
            'chat_id_list' => json_encode($chatIdList, JSON_UNESCAPED_UNICODE),
            'join_way_config_id' => $configId,
            'qr_code' => $qrCode,
            'member_count' => (int)($row['member_count'] ?? 0),
            'status' => 1,
            'update_time' => $now,
        ];
        if ($row) {
            Db::name('wecom_city_groups')->where('id', $row['id'])->update($data);
        } else {
            $data['create_time'] = $now;
            Db::name('wecom_city_groups')->insert($data);
        }

        return [
            'city_id' => $cityId,
            'city_name' => $cityName,
            'group_name' => $groupName,
            'qr_code' => $qrCode,
        ];
    }

    /**
     * 从「联系我」state 解析城市 ID（与 cityContactWay 中 state=city_{id} 一致）
     */
    public static function parseCityIdFromContactState(string $state): int
    {
        $state = trim($state);
        if ($state === '') {
            return 0;
        }
        if (preg_match('/^city_(\d+)$/i', $state, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    /**
     * 根据城市名自动匹配一个客户群 chat_id（按群名称包含城市名与“家教群”）
     *
     * @return array<int,string> chat_id_list
     */
    public static function discoverChatIdListByCity(string $cityName): array
    {
        $cityName = trim($cityName);
        if ($cityName === '') {
            return [];
        }
        $cfg = self::getConfig();
        $ownerRaw = (string)($cfg['owner_userids'] ?? '');
        $ownerList = json_decode($ownerRaw, true);
        $ownerUserids = is_array($ownerList) ? $ownerList : [];

        $cursor = '';
        $maxPages = 10;
        for ($i = 0; $i < $maxPages; $i++) {
            $res = self::listGroupChats($ownerUserids, $cursor, 100);
            $chatIds = $res['chat_id_list'] ?? [];
            foreach ($chatIds as $cid) {
                try {
                    $detail = self::getGroupChatDetail($cid);
                    $gc = (array)($detail['group_chat'] ?? []);
                    $name = trim((string)($gc['name'] ?? ''));
                    if ($name !== '' && mb_strpos($name, $cityName) !== false && mb_strpos($name, '家教') !== false) {
                        return [$cid];
                    }
                } catch (\Throwable $e) {
                    // 忽略单条失败
                }
            }
            $cursor = (string)($res['next_cursor'] ?? '');
            if ($cursor === '') {
                break;
            }
        }
        return [];
    }

    /**
     * 根据手机号换取成员 userid（通讯录接口）
     * 文档：通讯录管理-手机号获取 userid
     */
    public static function getUseridByMobile(string $mobile): string
    {
        $mobile = trim($mobile);
        // 兼容用户粘贴：去掉空格、短横线、+86 等，只保留数字
        $digits = preg_replace('/\D+/', '', $mobile);
        if (is_string($digits)) {
            $mobile = $digits;
        }
        // 兼容 86 开头的 13 位：例如 8613812345678
        if (strlen($mobile) === 13 && str_starts_with($mobile, '86')) {
            $mobile = substr($mobile, 2);
        }
        if ($mobile === '') {
            throw new \Exception('缺少手机号');
        }
        // 企业微信此接口通常要求大陆 11 位手机号
        if (!preg_match('/^\d{11}$/', $mobile)) {
            throw new \Exception('手机号格式不正确，请输入 11 位数字手机号（可带+86/空格/短横线，系统会自动处理）');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserid?access_token=' . urlencode($token);
        $raw = self::httpPostJson($url, ['mobile' => $mobile]);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('手机号获取userid失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        $userid = trim((string)($data['userid'] ?? ''));
        if ($userid === '') {
            throw new \Exception('手机号获取userid失败：未返回userid');
        }
        return $userid;
    }

    /**
     * 获取成员详情（通讯录接口）
     */
    public static function getUser(string $userid): array
    {
        $userid = trim($userid);
        if ($userid === '') {
            throw new \Exception('缺少 userid');
        }
        $token = self::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . urlencode($token)
            . '&userid=' . urlencode($userid);
        $raw = self::httpGet($url);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('获取成员详情失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        return $data;
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

    private static function httpPostJson(string $url, array $payload): string
    {
        $ch = curl_init();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
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

