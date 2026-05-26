<?php
namespace app\model;

use think\Model;

class WecomConfig extends Model
{
    protected $name = 'wecom_config';
    
    protected $schema = [
        'id' => 'int',
        'corp_id' => 'string',
        'secret' => 'string',
        'contact_secret' => 'string',
        'agent_id' => 'string',
        'agent_secret' => 'string',
        'create_time' => 'int',
        'update_time' => 'int',
    ];
    
    protected $autoWriteTimestamp = true;
}
