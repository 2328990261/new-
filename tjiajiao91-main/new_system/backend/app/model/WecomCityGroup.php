<?php
namespace app\model;

use think\Model;

/**
 * 企业微信同城家教群（按城市）
 */
class WecomCityGroup extends Model
{
    // 表名
    protected $name = 'wecom_city_groups';

    // 字段信息
    protected $schema = [
        'id'               => 'int',
        'city_id'          => 'int',
        'city_name'        => 'string',
        'group_name'       => 'string',
        'chat_id_list'     => 'string',
        'join_way_config_id' => 'string',
        'qr_code'          => 'string',
        'member_count'     => 'int',
        'status'           => 'int',
        'create_time'      => 'datetime',
        'update_time'      => 'datetime',
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
}

