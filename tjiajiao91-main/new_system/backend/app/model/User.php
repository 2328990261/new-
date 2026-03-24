<?php
namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    // 不设置 $table，让 ThinkPHP 使用默认规则
    // 模型名 User 会自动对应表名 fa_user（加前缀）
    // 但我们的表名是 fa_users（复数），所以需要设置 $name
    protected $name = 'users';  // 这样会变成 fa_users
    
    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'openid' => 'string',
        'superior_openid' => 'string',
        'phone' => 'string',
        'nickname' => 'string',
        'avatar' => 'string',
        'platform' => 'string',
        'status' => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime'
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
