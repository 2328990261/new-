<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;
use app\service\WecomService;

/**
 * 企业微信同城家教群（小程序端）
 */
class WecomGroup extends BaseController
{
    /**
     * GET /api/wecom/city-entry?city_id=xxx
     * 智能返回入群二维码或联系我二维码：
     * - 优先尝试返回“入群二维码”（客户群加入群聊）
     * - 若缺少 chat_id / 无法匹配客户群，则回退返回“联系我二维码”
     */
    public function cityEntry()
    {
        try {
            $cityId = (int)$this->request->param('city_id', 0);
            if ($cityId <= 0) {
                return json(['success' => false, 'message' => '请提供城市ID']);
            }

            // 先尝试：入群二维码
            $groupRes = $this->cityGroup();
            $arr = $groupRes instanceof \think\Response ? $groupRes->getData() : null;
            if (is_array($arr) && ($arr['success'] ?? false) === true) {
                $data = (array)($arr['data'] ?? []);
                return json([
                    'success' => true,
                    'data' => array_merge($data, [
                        'type' => 'group',
                    ]),
                ]);
            }

            $msg = '';
            if (is_array($arr)) {
                $msg = (string)($arr['message'] ?? $arr['error'] ?? '');
            }

            // 仅当明确是“没群/缺 chat_id”这类问题，才回退联系我；否则原样返回错误，便于排查配置
            $fallbackKeywords = ['chat_id', '客户群', 'owner_userids', '未找到'];
            $shouldFallback = $msg === '';
            foreach ($fallbackKeywords as $kw) {
                if ($msg !== '' && mb_strpos($msg, $kw) !== false) {
                    $shouldFallback = true;
                    break;
                }
            }
            if (!$shouldFallback) {
                return $groupRes;
            }

            // 回退：联系我二维码
            $cwRes = $this->cityContactWay();
            $cwArr = $cwRes instanceof \think\Response ? $cwRes->getData() : null;
            if (is_array($cwArr) && ($cwArr['success'] ?? false) === true) {
                $data = (array)($cwArr['data'] ?? []);
                return json([
                    'success' => true,
                    'data' => array_merge($data, [
                        'type' => 'contact_way',
                    ]),
                ]);
            }

            return $cwRes;
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/wecom/city-group?city_id=xxx
     * 按城市获取/生成群二维码（复用）
     */
    public function cityGroup()
    {
        try {
            $cityId = (int)$this->request->param('city_id', 0);
            if ($cityId <= 0) {
                return json(['success' => false, 'message' => '请提供城市ID']);
            }

            // 查城市名称
            $city = Db::name('cities')->where('id', $cityId)->find();
            $cityName = $city ? trim((string)($city['name'] ?? '')) : '';
            if ($cityName === '') {
                return json(['success' => false, 'message' => '城市不存在']);
            }

            $groupName = '【91家教】' . $cityName . '家教群';

            // 先复用已存在记录
            $row = Db::name('wecom_city_groups')->where('city_id', $cityId)->find();
            if ($row && !empty($row['qr_code'])) {
                return json([
                    'success' => true,
                    'data' => [
                        'city_id' => $cityId,
                        'city_name' => $cityName,
                        'group_name' => (string)($row['group_name'] ?? $groupName),
                        'qr_code' => (string)$row['qr_code'],
                        'member_count' => (int)($row['member_count'] ?? 0),
                        'create_time' => (string)($row['create_time'] ?? ''),
                    ]
                ]);
            }

            // 99546：使用“客户联系-可调用接口的应用”对应的自建应用 secret 获取 token
            // 兼容配置：contact_secret 为空则回退用 secret
            $cfg = WecomService::getConfig();
            $corpId = trim((string)($cfg['corp_id'] ?? ''));
            $secret = trim((string)($cfg['contact_secret'] ?? ''));
            if ($secret === '') {
                $secret = trim((string)($cfg['secret'] ?? ''));
            }
            if ($corpId === '' || $secret === '') {
                return json([
                    'success' => false,
                    'message' => '企业微信未配置CORP_ID/SECRET，请在管理端“企业微信配置”中填写后重试'
                ]);
            }

            // 创建入群方式（客户群活码）
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
                // 自动尝试匹配客户群 chat_id（按群名包含城市名与“家教”）
                $chatIdList = WecomService::discoverChatIdListByCity($cityName);
                if (empty($chatIdList)) {
                    return json([
                        'success' => false,
                        'message' => '未找到可用的企业微信客户群chat_id。请在管理端“企业微信配置”填写 owner_userids（用于拉取客户群列表），或手动为该城市配置 chat_id_list'
                    ]);
                }
            }

            $payload = [
                // 99546：scene 必填（1小程序插件/2二维码插件）
                'scene' => (int)($cfg['join_way_scene'] ?? 2) ?: 2,
                // remark 非必填但利于后台识别
                'remark' => $groupName,
                // state 用于区分城市（最长 32）
                'state' => ('city_' . $cityId),
                // 当群满后自动建群（默认 1）
                'auto_create_room' => (int)($cfg['join_way_auto_create_room'] ?? 1),
                // 自动建群前缀/起始序号（可选）
                'room_base_name' => (string)($cfg['join_way_room_base_name'] ?? ''),
                'room_base_id' => (int)($cfg['join_way_room_base_id'] ?? 0),
                // chat_id_list: 由管理端配置（客户群ID列表）
                'chat_id_list' => $chatIdList,
            ];
            // room_base_name 没配时，默认使用城市群名作为前缀（会自动拼序号）
            if (empty($payload['room_base_name'])) {
                $payload['room_base_name'] = $groupName;
            }
            // room_base_id 没配时，用城市ID做起始序号，避免不同城市撞号（可按需覆盖）
            if (empty($payload['room_base_id'])) {
                $payload['room_base_id'] = $cityId;
            }
            // mark_source 仅营销获客应用生效；这里按配置透传（默认 1）
            $payload['mark_source'] = (int)($cfg['join_way_mark_source'] ?? 1) ? true : false;

            $configId = WecomService::addGroupJoinWay($payload);
            $detail = WecomService::getGroupJoinWay($configId);
            $joinWay = (array)($detail['join_way'] ?? []);
            $qrCode = (string)($joinWay['qr_code'] ?? '');
            if ($qrCode === '') {
                return json(['success' => false, 'message' => '企业微信未返回群二维码']);
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

            return json([
                'success' => true,
                'data' => [
                    'city_id' => $cityId,
                    'city_name' => $cityName,
                    'group_name' => $groupName,
                    'qr_code' => $qrCode,
                    'member_count' => (int)($data['member_count'] ?? 0),
                    'create_time' => (string)($row['create_time'] ?? $now),
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/wecom/city-contact-way?city_id=xxx
     * 按城市获取/生成“联系我”二维码（复用）
     *
     * 说明：
     * - 该二维码用于“先加企业员工”，不依赖客户群 chat_id_list
     * - 需要配置 wecom_config.owner_userids（至少 1 个可用的成员 userid）
     */
    public function cityContactWay()
    {
        try {
            $cityId = (int)$this->request->param('city_id', 0);
            if ($cityId <= 0) {
                return json(['success' => false, 'message' => '请提供城市ID']);
            }

            $city = Db::name('cities')->where('id', $cityId)->find();
            $cityName = $city ? trim((string)($city['name'] ?? '')) : '';
            if ($cityName === '') {
                return json(['success' => false, 'message' => '城市不存在']);
            }

            $remark = '【91家教】' . $cityName . '加微信';

            $row = Db::name('wecom_city_groups')->where('city_id', $cityId)->find();
            if ($row && !empty($row['contact_way_qr_code'])) {
                return json([
                    'success' => true,
                    'data' => [
                        'city_id' => $cityId,
                        'city_name' => $cityName,
                        'remark' => $remark,
                        'qr_code' => (string)$row['contact_way_qr_code'],
                        'create_time' => (string)($row['create_time'] ?? ''),
                    ]
                ]);
            }

            $cfg = WecomService::getConfig();
            $ownerRaw = (string)($cfg['owner_userids'] ?? '');
            $ownerList = json_decode($ownerRaw, true);
            $ownerUserids = is_array($ownerList) ? array_values(array_filter($ownerList, function ($v) {
                return is_string($v) && trim($v) !== '';
            })) : [];
            if (empty($ownerUserids)) {
                return json([
                    'success' => false,
                    'message' => '企业微信未配置 owner_userids（至少填写一个可用成员userid，用于生成“联系我”二维码）'
                ]);
            }

            // 优先使用“固定展示成员”（若配置且在可见成员列表内），否则回退用第一个成员
            $preferred = trim((string)($cfg['contact_way_userid'] ?? ''));
            $chosen = '';
            if ($preferred !== '' && in_array($preferred, $ownerUserids, true)) {
                $chosen = $preferred;
            } else {
                $chosen = (string)$ownerUserids[0];
            }

            $payload = [
                'type' => 1,
                'scene' => 2,
                'remark' => $remark,
                'skip_verify' => true,
                'state' => ('city_' . $cityId),
                'user' => [ $chosen ],
            ];

            $configId = WecomService::addContactWay($payload);
            $detail = WecomService::getContactWay($configId);
            $cw = (array)($detail['contact_way'] ?? []);
            $qrCode = (string)($cw['qr_code'] ?? '');
            if ($qrCode === '') {
                return json(['success' => false, 'message' => '企业微信未返回联系我二维码']);
            }

            $now = date('Y-m-d H:i:s');
            $data = [
                'city_id' => $cityId,
                'city_name' => $cityName,
                'group_name' => (string)($row['group_name'] ?? ('【91家教】' . $cityName . '家教群')),
                'contact_way_config_id' => $configId,
                'contact_way_qr_code' => $qrCode,
                // 标记：该城市尚未配置/生成入群二维码（用于后台提示客服建群/回填 chat_id）
                'missing_group' => 1,
                'last_request_time' => $now,
                'request_count' => Db::raw('IFNULL(request_count,0)+1'),
                'update_time' => $now,
            ];
            if ($row) {
                // 兼容旧表结构：缺少 missing_group/request_count 等字段时，不阻塞返回二维码
                Db::name('wecom_city_groups')->strict(false)->where('id', $row['id'])->update($data);
            } else {
                $data['create_time'] = $now;
                $data['status'] = 1;
                $data['member_count'] = 0;
                // 首次创建时，request_count 用 1
                $data['request_count'] = 1;
                Db::name('wecom_city_groups')->strict(false)->insert($data);
            }

            return json([
                'success' => true,
                'data' => [
                    'city_id' => $cityId,
                    'city_name' => $cityName,
                    'remark' => $remark,
                    'qr_code' => $qrCode,
                    'create_time' => (string)($row['create_time'] ?? $now),
                ]
            ]);
        } catch (\Throwable $e) {
            return json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

