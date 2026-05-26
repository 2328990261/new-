<?php

namespace app\service;

use think\facade\Db;

/**
 * 教师行上可能未同步 openid，但 users 表（account_id / 手机号）有小程序 openid，发送订阅消息前统一解析。
 */
class TeacherMiniOpenidResolver
{
    /**
     * @param \think\Model|object|array $teacher Teacher 模型或数组
     */
    public static function resolve($teacher): string
    {
        if ($teacher instanceof \think\Model) {
            $row = $teacher->toArray();
        } elseif (is_array($teacher)) {
            $row = $teacher;
        } else {
            $row = (array)$teacher;
        }

        $oid = trim((string)($row['openid'] ?? ''));
        if ($oid !== '') {
            return $oid;
        }

        $accountId = (int)($row['account_id'] ?? 0);
        if ($accountId > 0) {
            $fromUser = self::openidByUserId($accountId);
            if ($fromUser !== '') {
                return $fromUser;
            }
        }

        $phone = trim((string)($row['phone'] ?? ''));
        if ($phone !== '') {
            $fromPhone = self::openidByPhone($phone);
            if ($fromPhone !== '') {
                return $fromPhone;
            }
        }

        return '';
    }

    private static function openidByUserId(int $userId): string
    {
        foreach (['users', 'fa_users'] as $name) {
            try {
                $o = Db::name($name)->where('id', $userId)->value('openid');
                if ($o) {
                    return trim((string)$o);
                }
            } catch (\Throwable $e) {
                // 表名/前缀不一致时跳过
            }
        }

        return '';
    }

    private static function openidByPhone(string $phone): string
    {
        foreach (['users', 'fa_users'] as $name) {
            try {
                $o = Db::name($name)->where('phone', $phone)->order('id', 'desc')->value('openid');
                if ($o) {
                    return trim((string)$o);
                }
            } catch (\Throwable $e) {
            }
        }

        return '';
    }
}
