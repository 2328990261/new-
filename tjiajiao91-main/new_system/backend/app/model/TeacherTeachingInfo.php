<?php
namespace app\model;

use think\Model;

class TeacherTeachingInfo extends Model
{
    // 不需要指定表名，ThinkPHP 会自动使用 fa_teacher_teaching_info
    
    // 设置JSON字段
    protected $json = ['districts', 'grades', 'subjects'];
    
    // 设置JSON字段的数据类型
    protected $jsonAssoc = true;

    /**
     * 允许写入的字段白名单（避免前端多传字段导致更新异常/丢字段）
     */
    private static function normalizePayload(array $data): array
    {
        $allowed = [
            'teacher_id',
            'openid',
            'phone',
            'city_id',
            'city_name',
            'districts',
            'grades',
            'subjects',
            'subscribe_push',
            'wechat_notify',
            'email_notify',
            'email',
        ];
        $out = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $out[$k] = $data[$k];
            }
        }

        // 类型归一化：避免前端传 true/false 导致 DB 写入不稳定
        foreach (['teacher_id', 'city_id'] as $ik) {
            if (isset($out[$ik]) && $out[$ik] !== '' && $out[$ik] !== null) {
                $out[$ik] = (int)$out[$ik];
            }
        }
        foreach (['subscribe_push', 'wechat_notify', 'email_notify'] as $bk) {
            if (isset($out[$bk])) {
                $out[$bk] = (int)(!!$out[$bk]);
            }
        }
        if (isset($out['openid'])) $out['openid'] = trim((string)$out['openid']);
        if (isset($out['phone'])) $out['phone'] = trim((string)$out['phone']);
        if (isset($out['city_name'])) $out['city_name'] = trim((string)$out['city_name']);
        if (isset($out['email'])) $out['email'] = trim((string)$out['email']);

        return $out;
    }
    
    /**
     * 获取教师授课信息
     */
    public static function getByTeacher($teacherId = null, $openid = null, $phone = null)
    {
        // 优先使用 teacher_id 查询
        if ($teacherId) {
            $info = self::where('teacher_id', $teacherId)->find();
            if ($info) {
                return $info;
            }
        }
        
        // 其次使用 openid 查询
        if ($openid) {
            $info = self::where('openid', $openid)->find();
            if ($info) {
                return $info;
            }
        }
        
        // 最后使用 phone 查询
        if ($phone) {
            $info = self::where('phone', $phone)->find();
            if ($info) {
                return $info;
            }
        }
        
        return null;
    }
    
    /**
     * 保存或更新授课信息
     */
    public static function saveInfo($data)
    {
        $data = self::normalizePayload((array)$data);

        $teacherId = $data['teacher_id'] ?? null;
        $openid = $data['openid'] ?? null;
        $phone = $data['phone'] ?? null;
        
        // 查找现有记录
        $info = self::getByTeacher($teacherId, $openid, $phone);
        
        if ($info) {
            // 更新 - 如果有 teacher_id，也要更新到记录中
            if ($teacherId && !$info->teacher_id) {
                $data['teacher_id'] = $teacherId;
            }
            $info->save($data);
            return $info;
        } else {
            // 新增
            return self::create($data);
        }
    }
    
    /**
     * 检查是否订阅推送
     */
    public static function isSubscribed($teacherId = null, $openid = null, $phone = null)
    {
        $info = self::getByTeacher($teacherId, $openid, $phone);
        return $info && $info->subscribe_push == 1;
    }
}
