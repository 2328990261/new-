<?php
namespace app\controller\admin;

use app\BaseController;
use app\service\WecomService;
use app\service\WecomGroupSendService;
use think\facade\Db;

/**
 * 企业微信同城家教群（管理端）
 */
class WecomGroup extends BaseController
{
    private function ensureAdminLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        return null;
    }

    /**
     * GET /admin/api/wecom/config
     */
    public function getConfig()
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $cfg = Db::name('wecom_config')->find(1);
            $defaults = [
                'id' => 1,
                'corp_id' => '',
                'agent_id' => '',
                'secret' => '',
                'contact_secret' => '',
                'owner_userids' => '',
                // “联系我二维码”固定展示的成员 userid（可留空，留空则回退 owner_userids[0]）
                'contact_way_userid' => '',
                'join_way_scene' => 2,
                'join_way_auto_create_room' => 1,
                'join_way_room_base_name' => '',
                'join_way_room_base_id' => 0,
                'join_way_mark_source' => 1,
                'callback_token' => '',
                'callback_encoding_aes_key' => '',
                'welcome_after_contact_text' => '',
                'welcome_link_title' => '加入同城家教群',
                'welcome_public_base_url' => '',
            ];
            $cfg = is_array($cfg) ? $cfg : [];
            return json(['success' => true, 'data' => array_merge($defaults, $cfg)]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // 表不存在时给明确指引
            if (strpos($msg, 'wecom_config') !== false && (strpos($msg, 'doesn\'t exist') !== false || strpos($msg, 'does not exist') !== false)) {
                return json([
                    'success' => false,
                    'error' => '数据库缺少表 fa_wecom_config，请先执行 new_system/backend/sql/wecom_config.sql 建表后再保存'
                ]);
            }
            return json(['success' => false, 'error' => $msg]);
        }
    }

    /**
     * POST /admin/api/wecom/config
     */
    public function saveConfig()
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $data = $this->request->param();
            $allow = [
                'corp_id',
                'agent_id',
                'secret',
                'contact_secret',
                'owner_userids',
                'contact_way_userid',
                'join_way_scene',
                'join_way_auto_create_room',
                'join_way_room_base_name',
                'join_way_room_base_id',
                'join_way_mark_source',
                'callback_token',
                'callback_encoding_aes_key',
                'welcome_after_contact_text',
                'welcome_link_title',
                'welcome_public_base_url',
            ];
            $update = [];
            foreach ($allow as $f) {
                if (isset($data[$f])) {
                    // owner_userids 允许直接存 JSON 字符串
                    if ($f === 'owner_userids') {
                        $update[$f] = is_string($data[$f]) ? trim((string)$data[$f]) : (is_array($data[$f]) ? json_encode($data[$f], JSON_UNESCAPED_UNICODE) : '');
                        continue;
                    }
                    if ($f === 'join_way_room_base_name') {
                        $update[$f] = trim((string)$data[$f]);
                        continue;
                    }
                    if (in_array($f, ['join_way_scene', 'join_way_auto_create_room', 'join_way_room_base_id', 'join_way_mark_source'], true)) {
                        $update[$f] = (int)$data[$f];
                        continue;
                    }
                    $update[$f] = is_string($data[$f]) ? trim((string)$data[$f]) : '';
                }
            }
            $now = date('Y-m-d H:i:s');
            $update['update_time'] = $now;

            $exists = Db::name('wecom_config')->find(1);
            if ($exists) {
                // 兼容旧表结构：若数据库缺少 join_way_* 字段，不应阻塞保存基础配置
                Db::name('wecom_config')->strict(false)->where('id', 1)->update($update);
            } else {
                $update['id'] = 1;
                $update['create_time'] = $now;
                Db::name('wecom_config')->strict(false)->insert($update);
            }
            return json(['success' => true, 'message' => '保存成功']);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'wecom_config') !== false && (strpos($msg, 'doesn\'t exist') !== false || strpos($msg, 'does not exist') !== false)) {
                return json([
                    'success' => false,
                    'error' => '数据库缺少表 fa_wecom_config，请先执行 new_system/backend/sql/wecom_config.sql 建表后再保存'
                ]);
            }
            return json(['success' => false, 'error' => $msg]);
        }
    }

    /**
     * GET /admin/api/wecom/userid?mobile=xxx
     * 根据手机号查询企业微信成员 userid（用于配置固定二维码成员）
     */
    public function userid()
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $mobile = trim((string)$this->request->param('mobile', ''));
            if ($mobile === '') {
                return json(['success' => false, 'error' => '请提供手机号 mobile']);
            }

            $userid = WecomService::getUseridByMobile($mobile);
            $user = [];
            try {
                $user = WecomService::getUser($userid);
            } catch (\Throwable $e) {
                // 成员详情权限不足不阻塞，仅返回 userid
                $user = [];
            }

            return json([
                'success' => true,
                'data' => [
                    'mobile' => $mobile,
                    'userid' => $userid,
                    'user' => $user,
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /admin/api/wecom/city-groups
     * 创建/初始化城市群配置（用于填写 chat_id_list）
     */
    public function save()
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $data = $this->request->param();
            $cityId = (int)($data['city_id'] ?? 0);
            if ($cityId <= 0) {
                return json(['success' => false, 'error' => '请提供城市ID']);
            }

            $city = Db::name('cities')->where('id', $cityId)->find();
            $cityName = $city ? trim((string)($city['name'] ?? '')) : '';
            if ($cityName === '') {
                return json(['success' => false, 'error' => '城市不存在']);
            }
            $groupName = '【91家教】' . $cityName . '家教群';

            $chatIdListRaw = $data['chat_id_list'] ?? '[]';
            // 允许前端传数组或 JSON 字符串
            if (is_array($chatIdListRaw)) {
                $chatIdList = $chatIdListRaw;
            } else {
                $decoded = json_decode((string)$chatIdListRaw, true);
                $chatIdList = is_array($decoded) ? $decoded : [];
            }
            $chatIdList = array_values(array_filter($chatIdList, function ($v) {
                return is_string($v) && trim($v) !== '';
            }));

            $now = date('Y-m-d H:i:s');
            $exists = Db::name('wecom_city_groups')->where('city_id', $cityId)->find();
            $saveData = [
                'city_id' => $cityId,
                'city_name' => $cityName,
                'group_name' => $groupName,
                // 允许先不填 chat_id_list：用于“先建记录，后续自动匹配/再编辑补充”
                'chat_id_list' => empty($chatIdList) ? '' : json_encode($chatIdList, JSON_UNESCAPED_UNICODE),
                'update_time' => $now,
            ];
            if ($exists) {
                Db::name('wecom_city_groups')->where('id', $exists['id'])->update($saveData);
                $id = (int)$exists['id'];
            } else {
                $saveData['create_time'] = $now;
                $saveData['status'] = 1;
                $saveData['member_count'] = 0;
                $saveData['join_way_config_id'] = '';
                $saveData['qr_code'] = '';
                Db::name('wecom_city_groups')->insert($saveData);
                $id = (int)Db::name('wecom_city_groups')->getLastInsID();
            }

            return json(['success' => true, 'message' => '保存成功', 'data' => ['id' => $id]]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /admin/api/wecom/city-groups
     */
    public function index()
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $page = (int)$this->request->param('page', 1);
            $limit = (int)$this->request->param('limit', 20);

            $query = Db::name('wecom_city_groups')->order('id', 'desc');
            $list = $query->paginate(['list_rows' => $limit, 'page' => $page]);

            return json([
                'success' => true,
                'data' => $list->items(),
                'total' => $list->total()
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * PUT /admin/api/wecom/city-groups/:id
     */
    public function update($id)
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return json(['success' => false, 'error' => '参数错误']);
            }

            $data = $this->request->param();
            $update = [];
            if (isset($data['member_count'])) {
                $update['member_count'] = (int)$data['member_count'];
            }
            if (isset($data['group_send_sender_userid'])) {
                $update['group_send_sender_userid'] = is_string($data['group_send_sender_userid'])
                    ? trim((string)$data['group_send_sender_userid'])
                    : '';
            }
            if (isset($data['chat_id_list'])) {
                $chatIdListRaw = $data['chat_id_list'];
                // 允许前端传数组或 JSON 字符串
                if (is_array($chatIdListRaw)) {
                    $chatIdList = $chatIdListRaw;
                } else {
                    $decoded = json_decode((string)$chatIdListRaw, true);
                    $chatIdList = is_array($decoded) ? $decoded : [];
                }
                $chatIdList = array_values(array_filter($chatIdList, function ($v) {
                    return is_string($v) && trim($v) !== '';
                }));
                if (empty($chatIdList)) {
                    return json(['success' => false, 'error' => 'chat_id_list 不能为空（JSON数组）']);
                }
                $update['chat_id_list'] = json_encode($chatIdList, JSON_UNESCAPED_UNICODE);
                // 只要 chat_id_list 变更，就清空旧的 join_way_config_id/qr_code，避免误用旧二维码
                $update['join_way_config_id'] = '';
                $update['qr_code'] = '';
            }
            if (isset($data['status'])) {
                $update['status'] = (int)$data['status'];
            }
            if (empty($update)) {
                return json(['success' => false, 'error' => '没有要更新的数据']);
            }
            $update['update_time'] = date('Y-m-d H:i:s');

            // 兼容旧表结构：缺字段时不应阻塞保存
            Db::name('wecom_city_groups')->strict(false)->where('id', $id)->update($update);
            return json(['success' => true, 'message' => '保存成功']);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /admin/api/wecom/city-groups/:id/test-group-send
     * 测试：创建一条“客户群群发任务”（需要成员在客户端确认发送）
     */
    public function testGroupSend($id)
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return json(['success' => false, 'error' => '参数错误']);
            }
            $res = WecomGroupSendService::createCityGroupTestSendByCityGroupId($id);
            return json(['success' => true, 'message' => '群发任务已创建（请在企业微信客户端确认发送）', 'data' => $res]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * DELETE /admin/api/wecom/city-groups/:id
     * 删除城市群配置记录，并尽量清理企微侧的入群方式/联系我配置
     *
     * 说明：
     * - 企业微信“客户群本身（chat_id）”无法通过开放 API 直接删除；这里只能删除「入群方式配置」与「联系我配置」以及本地数据库记录。
     */
    public function delete($id)
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return json(['success' => false, 'error' => '参数错误']);
            }

            $row = Db::name('wecom_city_groups')->where('id', $id)->find();
            if (!$row) {
                return json(['success' => true, 'message' => '记录不存在或已删除']);
            }

            $cleanup = [
                'join_way' => null,
                'contact_way' => null,
            ];

            $joinWayConfigId = trim((string)($row['join_way_config_id'] ?? ''));
            if ($joinWayConfigId !== '') {
                try {
                    $cleanup['join_way'] = WecomService::deleteGroupJoinWay($joinWayConfigId);
                } catch (\Throwable $e) {
                    $cleanup['join_way'] = ['err' => $e->getMessage()];
                }
            }

            $contactWayConfigId = trim((string)($row['contact_way_config_id'] ?? ''));
            if ($contactWayConfigId !== '') {
                try {
                    $cleanup['contact_way'] = WecomService::deleteContactWay($contactWayConfigId);
                } catch (\Throwable $e) {
                    $cleanup['contact_way'] = ['err' => $e->getMessage()];
                }
            }

            Db::name('wecom_city_groups')->where('id', $id)->delete();
            return json(['success' => true, 'message' => '已删除', 'data' => ['cleanup' => $cleanup]]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /admin/api/wecom/city-groups/:id/generate-qr
     * 后台主动生成/刷新该城市群二维码（会写入 qr_code/join_way_config_id）
     */
    public function generateQr($id)
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $debugOn = (int)$this->request->param('debug', 0) === 1;
            $dbg = [
                'handler' => 'admin.WecomGroup@generateQr_v20260420_1',
                'request_id' => uniqid('wecom_', true),
                'route_id' => $id,
                'ts' => date('Y-m-d H:i:s'),
                // 仅记录调试需要的字段，避免泄露敏感信息
                'req' => [
                    'city_id' => $this->request->param('city_id', null),
                    'chat_id_list' => $this->request->param('chat_id_list', null),
                ],
            ];

            $ret = function (array $payload) use ($debugOn, &$dbg) {
                if ($debugOn) {
                    $payload['debug'] = $dbg;
                }
                return json($payload);
            };

            $id = (int)$id;
            if ($id <= 0) {
                $dbg['error_at'] = 'validate_id';
                return $ret(['success' => false, 'error' => '参数错误']);
            }

            $row = Db::name('wecom_city_groups')->where('id', $id)->find();
            if (!$row) {
                $dbg['error_at'] = 'load_row';
                return $ret(['success' => false, 'error' => '记录不存在']);
            }
            if ((int)($row['status'] ?? 1) !== 1) {
                $dbg['error_at'] = 'row_disabled';
                return $ret(['success' => false, 'error' => '该城市群已禁用']);
            }

            $cityId = (int)($row['city_id'] ?? 0);
            $cityName = trim((string)($row['city_name'] ?? ''));
            $dbg['row'] = [
                'id' => (int)($row['id'] ?? 0),
                'city_id' => $cityId,
                'city_name' => $cityName,
                'chat_id_list' => (string)($row['chat_id_list'] ?? ''),
                'join_way_config_id' => (string)($row['join_way_config_id'] ?? ''),
                'qr_code' => (string)($row['qr_code'] ?? ''),
            ];
            if ($cityId <= 0 || $cityName === '') {
                $dbg['error_at'] = 'row_city_missing';
                return $ret(['success' => false, 'error' => '城市信息缺失']);
            }

            $groupName = '【91家教】' . $cityName . '家教群';

            // 兼容：优先使用本次请求携带的 chat_id_list，其次使用数据库已保存的 chat_id_list
            // 目的：避免某些旧逻辑/错误请求用空值覆盖已保存的 chat_id_list，导致“生成二维码后 ID 被清空”
            $chatIdList = [];
            $reqChatIdListRaw = $this->request->param('chat_id_list', null);
            if ($reqChatIdListRaw !== null) {
                if (is_array($reqChatIdListRaw)) {
                    $tmp = $reqChatIdListRaw;
                } else {
                    $tmpDecoded = json_decode((string)$reqChatIdListRaw, true);
                    $tmp = is_array($tmpDecoded) ? $tmpDecoded : [];
                }
                $tmp = array_values(array_filter($tmp, function ($v) {
                    return is_string($v) && trim($v) !== '';
                }));
                if (!empty($tmp)) {
                    $chatIdList = $tmp;
                    // 同步写回数据库，确保后续列表展示一致
                    Db::name('wecom_city_groups')->where('id', $id)->update([
                        'chat_id_list' => json_encode($chatIdList, JSON_UNESCAPED_UNICODE),
                        'update_time' => date('Y-m-d H:i:s'),
                    ]);
                    $row['chat_id_list'] = json_encode($chatIdList, JSON_UNESCAPED_UNICODE);
                }
            }
            if (empty($chatIdList) && !empty($row['chat_id_list'])) {
                $decoded = json_decode((string)$row['chat_id_list'], true);
                if (is_array($decoded)) {
                    $chatIdList = array_values(array_filter($decoded, function ($v) {
                        return is_string($v) && trim($v) !== '';
                    }));
                }
            }
            if (empty($chatIdList)) {
                // 若未配置，尝试自动匹配（依赖 wecom_config.owner_userids）
                $chatIdList = WecomService::discoverChatIdListByCity($cityName);
            }
            if (empty($chatIdList)) {
                $dbg['error_at'] = 'chat_id_list_empty';
                $dbg['resolved_chat_id_list'] = [];
                return $ret(['success' => false, 'error' => 'chat_id_list 为空，无法生成二维码。请先配置 chat_id_list 或配置 owner_userids 以便自动匹配。']);
            }
            $dbg['resolved_chat_id_list'] = $chatIdList;

            $cfg = WecomService::getConfig();
            $payload = [
                'scene' => (int)($cfg['join_way_scene'] ?? 2) ?: 2,
                'remark' => $groupName,
                'state' => ('city_' . $cityId),
                'auto_create_room' => (int)($cfg['join_way_auto_create_room'] ?? 1),
                'room_base_name' => (string)($cfg['join_way_room_base_name'] ?? ''),
                'room_base_id' => (int)($cfg['join_way_room_base_id'] ?? 0),
                'chat_id_list' => $chatIdList,
            ];
            if (empty($payload['room_base_name'])) {
                $payload['room_base_name'] = $groupName;
            }
            if (empty($payload['room_base_id'])) {
                $payload['room_base_id'] = $cityId;
            }
            $payload['mark_source'] = (int)($cfg['join_way_mark_source'] ?? 1) ? true : false;

            $configId = WecomService::addGroupJoinWay($payload);
            $detail = WecomService::getGroupJoinWay($configId);
            $joinWay = (array)($detail['join_way'] ?? []);
            $qrCode = (string)($joinWay['qr_code'] ?? '');
            if ($qrCode === '') {
                $dbg['error_at'] = 'wecom_qrcode_empty';
                return $ret(['success' => false, 'error' => '企业微信未返回二维码']);
            }

            $now = date('Y-m-d H:i:s');
            // 兼容旧表结构：若缺少 missing_group 字段，不应阻塞生成二维码
            Db::name('wecom_city_groups')->strict(false)->where('id', $id)->update([
                'group_name' => $groupName,
                'chat_id_list' => json_encode($chatIdList, JSON_UNESCAPED_UNICODE),
                'join_way_config_id' => $configId,
                'qr_code' => $qrCode,
                // 生成入群二维码成功后，清除“缺群待处理”标记
                'missing_group' => 0,
                'update_time' => $now,
            ]);

            $dbg['result'] = [
                'config_id' => $configId,
                'qr_code' => $qrCode,
            ];
            return $ret(['success' => true, 'message' => '二维码已生成', 'data' => ['qr_code' => $qrCode]]);
        } catch (\Throwable $e) {
            // 避免影响线上调用：仅当显式 debug=1 时回传调试字段
            $debugOn = (int)$this->request->param('debug', 0) === 1;
            $payload = ['success' => false, 'error' => $e->getMessage()];
            if ($debugOn) {
                $payload['debug'] = [
                    'handler' => 'admin.WecomGroup@generateQr_v20260420_1',
                    'ts' => date('Y-m-d H:i:s'),
                    'exception' => $e->getMessage(),
                ];
            }
            return json($payload);
        }
    }

    /**
     * POST /admin/api/wecom/city-groups/:id/refresh-stats
     * 刷新群人数（从第一个 chat_id 拉取详情并写入 member_count）
     */
    public function refreshStats($id)
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $id = (int)$id;
            if ($id <= 0) {
                return json(['success' => false, 'error' => '参数错误']);
            }

            $row = Db::name('wecom_city_groups')->where('id', $id)->find();
            if (!$row) {
                return json(['success' => false, 'error' => '记录不存在']);
            }

            $chatIdList = [];
            if (!empty($row['chat_id_list'])) {
                $decoded = json_decode((string)$row['chat_id_list'], true);
                if (is_array($decoded)) {
                    $chatIdList = array_values(array_filter($decoded, function ($v) {
                        return is_string($v) && trim($v) !== '';
                    }));
                }
            }
            if (empty($chatIdList)) {
                return json(['success' => false, 'error' => 'chat_id_list 为空，无法刷新群人数']);
            }

            $detail = WecomService::getGroupChatDetail((string)$chatIdList[0]);
            $gc = (array)($detail['group_chat'] ?? []);
            $members = $gc['member_list'] ?? [];
            $memberCount = is_array($members) ? count($members) : 0;

            $now = date('Y-m-d H:i:s');
            Db::name('wecom_city_groups')->where('id', $id)->update([
                'member_count' => $memberCount,
                'update_time' => $now,
            ]);

            return json(['success' => true, 'message' => '已刷新', 'data' => ['member_count' => $memberCount]]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET /admin/api/wecom/groupchats?keyword=xxx&limit=50
     * 拉取客户群列表（用于管理端选择 chat_id_list）
     */
    public function groupChats()
    {
        if ($r = $this->ensureAdminLogin()) return $r;
        try {
            $keyword = trim((string)$this->request->param('keyword', ''));
            $limit = (int)$this->request->param('limit', 50);
            $limit = max(1, min(200, $limit));

            $cfg = WecomService::getConfig();
            $ownerRaw = (string)($cfg['owner_userids'] ?? '');
            $ownerList = json_decode($ownerRaw, true);
            $ownerUserids = is_array($ownerList) ? array_values(array_filter($ownerList, function ($v) {
                return is_string($v) && trim($v) !== '';
            })) : [];
            if (empty($ownerUserids)) {
                return json(['success' => false, 'error' => '请先在“企业微信配置”填写 owner_userids（用于拉取客户群列表）']);
            }

            $out = [];
            $detailErrors = [];
            $cursor = '';
            $maxPages = 5;
            for ($i = 0; $i < $maxPages; $i++) {
                $res = WecomService::listGroupChats($ownerUserids, $cursor, 100);
                $chatIds = $res['chat_id_list'] ?? [];
                foreach ($chatIds as $cid) {
                    try {
                        $detail = WecomService::getGroupChatDetail((string)$cid);
                        $gc = (array)($detail['group_chat'] ?? []);
                        $name = trim((string)($gc['name'] ?? ''));
                        if ($keyword !== '' && $name !== '' && mb_strpos($name, $keyword) === false) {
                            continue;
                        }
                        $out[] = [
                            'chat_id' => (string)$cid,
                            'name' => $name,
                        ];
                        if (count($out) >= $limit) {
                            break 2;
                        }
                    } catch (\Throwable $e) {
                        // 记录失败原因；若全部失败，返回更明确错误，避免前端只显示“无数据”
                        if (count($detailErrors) < 3) {
                            $detailErrors[] = $e->getMessage();
                        }
                    }
                }
                $cursor = (string)($res['next_cursor'] ?? '');
                if ($cursor === '') {
                    break;
                }
            }

            if (empty($out) && !empty($detailErrors)) {
                return json([
                    'success' => false,
                    'error' => '拉取客户群详情失败，可能是 contact_secret/权限（客户联系-客户群管理）/应用可见范围 或 owner_userids(必须是成员userid非姓名) 配置问题。示例错误：' . $detailErrors[0]
                ]);
            }

            return json(['success' => true, 'data' => $out]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

