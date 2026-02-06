<?php
namespace app\model;

use think\Model;

/**
 * 用户模型
 */
class User extends Model
{
    protected $name = 'wechat_users';
    
    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'openid' => 'string',
        'unionid' => 'string',
        'phone' => 'string',
        'nickname' => 'string',
        'headimgurl' => 'string',
        'subscribe' => 'int',
        'subscribe_time' => 'int',
        'user_type' => 'string',
        'user_id' => 'int',
        'remark' => 'string',
        'create_time' => 'datetime',
        'update_time' => 'datetime'
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
