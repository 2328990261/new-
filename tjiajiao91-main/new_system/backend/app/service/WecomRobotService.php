<?php
namespace app\service;

use app\model\TutorOrder;
use think\facade\Db;
use think\facade\Log;

/**
 * 企业微信群机器人推送（用于客户群内通知）
 */
class WecomRobotService
{
    public static function sendMarkdown(string $webhookUrl, string $content): array
    {
        $webhookUrl = trim($webhookUrl);
        if ($webhookUrl === '') {
            throw new \Exception('缺少 webhook 地址');
        }

        $payload = [
            'msgtype' => 'markdown',
            'markdown' => [
                'content' => $content,
            ],
        ];

        $raw = self::httpPostJson($webhookUrl, $payload);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \Exception('机器人响应无效');
        }
        $errcode = (int)($data['errcode'] ?? -1);
        if ($errcode !== 0) {
            $errmsg = (string)($data['errmsg'] ?? 'unknown');
            throw new \Exception('机器人发送失败：' . $errmsg . ($errcode ? ('(' . $errcode . ')') : ''));
        }
        return $data;
    }

    public static function buildTutorOrderMarkdown(array $order): string
    {
        $id = (string)($order['id'] ?? '');
        $city = (string)($order['city_name'] ?? '');
        $district = (string)($order['district_name'] ?? '');
        $grade = (string)($order['grade'] ?? '');
        $subject = (string)($order['subject_name'] ?? '');
        $salary = (string)($order['salary'] ?? '');
        $teacherType = (string)($order['teacher_type'] ?? '');
        $content = trim((string)($order['content'] ?? ''));
        $time = (string)($order['create_time'] ?? '');

        $location = trim($city . ($district ? (' ' . $district) : ''));
        $titleParts = array_values(array_filter([$location, $grade, $subject], function ($v) {
            return trim((string)$v) !== '';
        }));
        $title = implode(' ', $titleParts);

        $teacherTypeText = $teacherType;
        if ($teacherType === 'student') $teacherTypeText = '大学生';
        if ($teacherType === 'professional') $teacherTypeText = '专职老师';
        if ($teacherType === 'online') $teacherTypeText = '线上';

        $contentShort = $content;
        if (mb_strlen($contentShort, 'UTF-8') > 180) {
            $contentShort = mb_substr($contentShort, 0, 180, 'UTF-8') . '…';
        }
        $contentShort = str_replace(["\r\n", "\r", "\n"], "\n", $contentShort);

        $detailUrl = '';
        if ($id !== '') {
            $detailUrl = 'https://t.jiajiao91.com/detail/' . urlencode($id);
        }

        $lines = [];
        $lines[] = '### 新家教单';
        if ($title !== '') $lines[] = '**' . $title . '**';
        $metaParts = array_values(array_filter([
            $salary ? ('时薪：' . $salary) : '',
            $teacherTypeText ? ('老师类型：' . $teacherTypeText) : '',
            $time ? ('时间：' . $time) : '',
        ], function ($v) {
            return $v !== '';
        }));
        if (!empty($metaParts)) {
            $lines[] = implode('  ', $metaParts);
        }
        if ($contentShort !== '') {
            $lines[] = '';
            $lines[] = $contentShort;
        }
        if ($detailUrl !== '') {
            $lines[] = '';
            $lines[] = '[查看详情](' . $detailUrl . ')';
        }
        return implode("\n", $lines);
    }

    /**
     * 推送家教单到对应城市企微群（机器人 webhook）
     */
    public static function pushTutorOrderToCityGroup($tutorOrderOrId): void
    {
        try {
            $id = is_object($tutorOrderOrId) ? (string)($tutorOrderOrId->id ?? '') : (string)$tutorOrderOrId;
            if ($id === '') return;

            // 补齐展示字段（城市/区县/科目名）
            $row = TutorOrder::with(['city', 'district', 'subject'])->where('id', $id)->find();
            if (!$row) return;

            $cityId = (int)($row->city_id ?? 0);
            if ($cityId <= 0) return;

            // 旧实现：内部群机器人 webhook。外部客户群不支持 webhook，这里保留文件但不再使用。
            return;

            $order = [
                'id' => (string)$row->id,
                'city_name' => (string)($row->city->name ?? ''),
                'district_name' => (string)($row->district->name ?? ''),
                'grade' => (string)($row->grade ?? ''),
                'subject_name' => (string)($row->subject->name ?? ''),
                'salary' => (string)($row->salary ?? ''),
                'teacher_type' => (string)($row->teacher_type ?? ''),
                'content' => (string)($row->content ?? ''),
                'create_time' => (string)($row->create_time ?? ''),
            ];

            // noop
        } catch (\Throwable $e) {
            // 不影响主流程
            Log::warning('wecom robot push failed: ' . $e->getMessage());
        }
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

