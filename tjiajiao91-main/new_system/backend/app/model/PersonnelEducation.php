<?php
namespace app\model;

use think\Model;

/**
 * 人员教育经历（一对多）
 * 对应表：fa_personnel_education
 */
class PersonnelEducation extends Model
{
    protected $name = 'personnel_education';

    protected $schema = [
        'id'              => 'int',
        'personnel_id'    => 'int',
        'degree'          => 'string',
        'school'          => 'string',
        'enroll_date'     => 'date',
        'graduate_date'   => 'date',
        'major'           => 'string',
        'academic_degree' => 'string',
        'sort'            => 'int',
        'create_time'     => 'int',
        'update_time'     => 'int',
    ];

    protected $autoWriteTimestamp = true;

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'personnel_id', 'id');
    }
}
