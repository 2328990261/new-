<?php
namespace app\model;

use think\Model;

/**
 * 管理员模型
 */
class Admin extends Model
{
    // 设置表名
    protected $name = 'admin';
    
    // 设置字段信息
    protected $schema = [
        'id'       => 'int',
        'username' => 'string',
        'nickname' => 'string',
        'password' => 'string',
        'role'     => 'string', // 角色：super_admin(超级管理员)、team_leader(客服组长)、customer_service(客服)、dispatcher(派单)
        'leader_id' => 'int',   // 组长ID，关联admin表
        'city_id'  => 'string', // 归属城市ID（逗号分隔），仅派单组使用
        'status'   => 'int',    // 状态：0-禁用，1-启用
        'contact'  => 'string', // 联系方式，用于派单组
        'wechat_qrcode' => 'string', // 微信二维码URL，用于派单组成员
        'email'    => 'string', // 邮箱地址，用于接收通知
        'openid'   => 'string', // 绑定的小程序用户openid
        'bind_time' => 'datetime', // 绑定时间
        'last_login_time' => 'datetime', // 最后登录时间
        'create_time' => 'datetime', // 创建时间
        'update_time' => 'datetime', // 更新时间
    ];
    
    // 隐藏字段
    protected $hidden = ['password'];
    
    // 只读字段
    protected $readonly = ['id', 'username'];
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(TutorOrder::class, 'admin_id');
    }
    
    /**
     * 关联组长
     */
    public function leader()
    {
        return $this->belongsTo(Admin::class, 'leader_id');
    }
    
    /**
     * 关联组员（下属客服）
     */
    public function teamMembers()
    {
        return $this->hasMany(Admin::class, 'leader_id');
    }
    
    /**
     * 获取组员ID列表（包含自己）
     */
    public function getTeamMemberIds()
    {
        $memberIds = self::where('leader_id', $this->id)->column('id');
        $memberIds[] = $this->id; // 包含自己
        return $memberIds;
    }
    
    /**
     * 判断是否为组长
     */
    public function isTeamLeader()
    {
        return $this->role === 'team_leader';
    }
    
    /**
     * 判断是否为超级管理员
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }
    
    /**
     * 密码修改器
     */
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
    
    /**
     * 验证密码
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * 将逗号分隔的 openid 字符串标准化为数组（去重、去空白）
     */
    public static function splitOpenids($openidValue): array
    {
        if ($openidValue === null) {
            return [];
        }
        $parts = preg_split('/\s*,\s*/', (string) $openidValue);
        if (!is_array($parts)) {
            return [];
        }
        $parts = array_map(static function ($v) {
            return trim((string) $v);
        }, $parts);
        $parts = array_values(array_unique(array_filter($parts, static function ($v) {
            return $v !== '';
        })));
        return $parts;
    }

    /**
     * 按单个 openid token 查询（兼容 openid 字段为逗号分隔）
     */
    public static function queryByOpenidToken(string $openid)
    {
        $openid = trim($openid);
        return self::where(function ($q) use ($openid) {
            $q->where('openid', $openid)
              ->whereOrRaw("FIND_IN_SET(?, REPLACE(IFNULL(openid, ''), ' ', '')) > 0", [$openid]);
        });
    }

    /**
     * 按多个 openid token 查询（任一命中即匹配）
     */
    public static function queryByOpenidTokens(array $openids)
    {
        $openids = array_values(array_unique(array_filter(array_map('trim', $openids))));
        return self::where(function ($q) use ($openids) {
            foreach ($openids as $idx => $openid) {
                if ($idx === 0) {
                    $q->where('openid', $openid)
                      ->whereOrRaw("FIND_IN_SET(?, REPLACE(IFNULL(openid, ''), ' ', '')) > 0", [$openid]);
                } else {
                    $q->whereOr('openid', $openid)
                      ->whereOrRaw("FIND_IN_SET(?, REPLACE(IFNULL(openid, ''), ' ', '')) > 0", [$openid]);
                }
            }
        });
    }

    /**
     * 查找包含指定 openid 的管理员
     */
    public static function findByOpenidToken(string $openid)
    {
        return self::queryByOpenidToken($openid)->find();
    }

    /**
     * 检查 openid 是否与其他管理员冲突（任一 token 重叠即冲突）
     */
    public static function hasOpenidConflict($openidValue, ?int $excludeId = null): bool
    {
        $tokens = self::splitOpenids($openidValue);
        if (empty($tokens)) {
            return false;
        }

        $query = self::queryByOpenidTokens($tokens);
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        $rows = $query->field('id,openid')->select();
        if (!$rows || count($rows) === 0) {
            return false;
        }

        $tokenSet = array_fill_keys($tokens, true);
        foreach ($rows as $row) {
            $existing = self::splitOpenids($row->openid ?? '');
            foreach ($existing as $item) {
                if (isset($tokenSet[$item])) {
                    return true;
                }
            }
        }
        return false;
    }
}

