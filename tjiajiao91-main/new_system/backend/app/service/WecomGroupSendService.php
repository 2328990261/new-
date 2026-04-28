<?php
namespace app\service;

use app\model\TutorOrder;
use think\facade\Db;
use think\facade\Log;

/**
 * 企业微信客户群群发（创建群发任务，需要成员在客户端确认发送）
 *
 * 文档：客户联系 -> 消息推送 -> 创建企业群发（chat_type=group）
 */
class WecomGroupSendService
{
    public static function createGroupSend(string $senderUserid, array $chatIdList, string $text, array $attachments = []): array
    {
        $senderUserid = trim($senderUserid);
        if ($senderUserid === '') {
            throw new \Exception('缺少 sender userid（发送者成员userid）');
        }
        $chatIdList = array_values(array_filter($chatIdList, function ($v) {
            return is_string($v) && trim($v) !== '';
        }));
        if (empty($chatIdList)) {
            throw new \Exception('缺少 chat_id_list（客户群ID列表）');
        }
        $text = trim($text);
        if ($text === '' && empty($attachments)) {
            throw new \Exception('text 和 attachments 不能同时为空');
        }

        $token = WecomService::getAccessTokenForExternalContact(false);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_msg_template?access_token=' . urlencode($token);

        $payload = [
            'chat_type' => 'group',
            'sender' => $senderUserid,
            'chat_id_list' => $chatIdList,
        ];
        if ($text !== '') {
            $payload['text'] = ['content' => $text];
        }
        if (!empty($attachments)) {
            $payload['attachments'] = $attachments;
        }

        $raw = self::httpPostJson($url, $payload);
        $data = json_decode($raw, true);
        if (!is_array($data) || (int)($data['errcode'] ?? -1) !== 0) {
            $errcode = is_array($data) ? (int)($data['errcode'] ?? 0) : 0;
            $errmsg = is_array($data) ? (string)($data['errmsg'] ?? 'unknown') : 'invalid response';
            throw new \Exception('创建客户群群发任务失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        return $data;
    }

    private static function buildSenderCandidates(array $cfg, array $cityGroup, array $chatIds): array
    {
        $candidates = [];
        $push = function ($v) use (&$candidates) {
            $v = trim((string)$v);
            if ($v === '') return;
            if (!in_array($v, $candidates, true)) {
                $candidates[] = $v;
            }
        };

        // 0) 若城市群单独配置了 sender，优先用它
        $push($cityGroup['group_send_sender_userid'] ?? '');

        // 1) 其次用配置的固定成员
        $push($cfg['contact_way_userid'] ?? '');

        // 2) 尝试用客户群群主 owner（最稳）
        if (!empty($chatIds)) {
            try {
                $detail = WecomService::getGroupChatDetail((string)$chatIds[0]);
                $gc = (array)($detail['group_chat'] ?? []);
                $push($gc['owner'] ?? '');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 3) 回退 owner_userids 列表
        $ownerRaw = (string)($cfg['owner_userids'] ?? '');
        $ownerList = json_decode($ownerRaw, true);
        if (is_array($ownerList)) {
            foreach ($ownerList as $u) {
                $push($u);
            }
        }

        return $candidates;
    }

    private static function shouldRetryWithOtherSender(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        return (strpos($msg, '(41048)') !== false) || (strpos($msg, '41048') !== false) || (stripos($msg, 'no customer to send') !== false);
    }

    public static function createCityGroupTutorOrderSend($tutorOrderOrId): void
    {
        try {
            $id = is_object($tutorOrderOrId) ? (string)($tutorOrderOrId->id ?? '') : (string)$tutorOrderOrId;
            if ($id === '') return;

            $row = TutorOrder::with(['city', 'district', 'subject'])->where('id', $id)->find();
            if (!$row) return;

            $cityId = (int)($row->city_id ?? 0);
            if ($cityId <= 0) return;

            $cfg = Db::name('wecom_config')->find(1);
            $cfg = is_array($cfg) ? $cfg : [];

            $cg = Db::name('wecom_city_groups')->where('city_id', $cityId)->find();
            if (!is_array($cg)) return;

            $chatIds = [];
            $rawChat = (string)($cg['chat_id_list'] ?? '');
            if ($rawChat !== '') {
                $decoded = json_decode($rawChat, true);
                if (is_array($decoded)) {
                    $chatIds = $decoded;
                }
            }
            if (empty($chatIds)) return;

            // 按你的需求：群发内容直接使用家教单正文 content，不发送链接
            $content = trim((string)($row->content ?? ''));
            if ($content === '') {
                return;
            }
            // 企业微信文本上限 4000 字节；这里按 UTF-8 粗略截断，避免超限导致报错
            if (strlen($content) > 3800) {
                $content = substr($content, 0, 3800) . '…';
            }
            $text = $content;
            $attachments = [];

            $senders = self::buildSenderCandidates($cfg, $cg, $chatIds);
            if (empty($senders)) return;

            $lastErr = null;
            $tried = [];
            foreach ($senders as $sender) {
                try {
                    self::createGroupSend($sender, $chatIds, $text, $attachments);
                    $lastErr = null;
                    break;
                } catch (\Throwable $e) {
                    $tried[] = $sender;
                    $lastErr = $e;
                    if (!self::shouldRetryWithOtherSender($e)) {
                        break;
                    }
                }
            }
            if ($lastErr) {
                throw new \Exception($lastErr->getMessage() . '；已尝试sender=' . json_encode($tried, JSON_UNESCAPED_UNICODE));
            }
        } catch (\Throwable $e) {
            Log::warning('wecom group send create failed: ' . $e->getMessage());
        }
    }

    /**
     * 测试：按城市群配置创建一条客户群群发任务（用于管理端按钮）
     * - 会走与正式推送相同的 sender 回退逻辑
     * - 返回企微接口原始响应（含 msgid / fail_list）
     */
    public static function createCityGroupTestSendByCityGroupId(int $cityGroupId): array
    {
        $cityGroupId = (int)$cityGroupId;
        if ($cityGroupId <= 0) {
            throw new \Exception('参数错误');
        }

        $cg = Db::name('wecom_city_groups')->where('id', $cityGroupId)->find();
        if (!is_array($cg)) {
            throw new \Exception('记录不存在');
        }

        $chatIds = [];
        $rawChat = (string)($cg['chat_id_list'] ?? '');
        if ($rawChat !== '') {
            $decoded = json_decode($rawChat, true);
            if (is_array($decoded)) {
                $chatIds = $decoded;
            }
        }
        if (empty($chatIds)) {
            throw new \Exception('chat_id_list 为空，请先为该城市配置客户群');
        }

        $cfg = Db::name('wecom_config')->find(1);
        $cfg = is_array($cfg) ? $cfg : [];

        $cityName = trim((string)($cg['city_name'] ?? ''));
        $text = "【群发测试】城市：" . ($cityName !== '' ? $cityName : '未知') . "\n时间：" . date('Y-m-d H:i:s')
            . "\n\n说明：该测试会创建群发任务，需要发送者在企业微信里确认发送。";

        $senders = self::buildSenderCandidates($cfg, $cg, $chatIds);
        if (empty($senders)) {
            throw new \Exception('缺少 sender（请配置该城市群 sender 或全局 contact_way_userid / owner_userids）');
        }

        $lastErr = null;
        $tried = [];
        foreach ($senders as $sender) {
            try {
                $res = self::createGroupSend($sender, $chatIds, $text, []);
                return array_merge($res, [
                    '_sender_used' => $sender,
                    '_sender_candidates' => $senders,
                ]);
            } catch (\Throwable $e) {
                $tried[] = $sender;
                $lastErr = $e;
                if (!self::shouldRetryWithOtherSender($e)) {
                    break;
                }
            }
        }
        if ($lastErr) {
            throw new \Exception($lastErr->getMessage() . '；已尝试sender=' . json_encode($tried, JSON_UNESCAPED_UNICODE));
        }
        throw new \Exception('创建失败');
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

