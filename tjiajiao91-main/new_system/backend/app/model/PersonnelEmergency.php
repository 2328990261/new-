<?php
namespace app\model;

use think\Model;

/**
 * 人员紧急联系人（一对多）
 * 对应表：fa_personnel_emergency
 */
class PersonnelEmergency extends Model
{
    protected $name = 'personnel_emergency';

    protected $schema = [
        'id'           => 'int',
        'personnel_id' => 'int',
        'name'         => 'string',
        'relation'     => 'string',
        'phone'        => 'string',
        'address'      => 'string',
        'sort'         => 'int',
        'create_time'  => 'int',
        'update_time'  => 'int',
    ];

    protected $autoWriteTimestamp = true;

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'personnel_id', 'id');
    }
}
