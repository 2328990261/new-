<?php

namespace app\model;

use think\Model;

class MiniProgramConfig extends Model
{
    protected $name = 'mini_program_config';

    protected $schema = [
        'id'             => 'int',
        'platform'       => 'string',
        'mini_program_name' => 'string',
        'app_id'         => 'string',
        'app_secret_enc' => 'string',
        'phone_aes_key_enc' => 'string',
        'env_version'    => 'string',
        'is_enabled'     => 'int',
        'is_default'     => 'int',
        'remark'         => 'string',
        'create_time'    => 'datetime',
        'update_time'    => 'datetime',
    ];

    protected $autoWriteTimestamp = true;
}
