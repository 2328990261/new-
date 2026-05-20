<?php
namespace app\model;

use think\Model;

/**
 * 人员主表模型（入职登记）
 * 对应表：fa_personnel
 */
class Personnel extends Model
{
    protected $name = 'personnel';

    protected $schema = [
        'id'               => 'int',
        // 基本信息
        'name'             => 'string',
        'phone'            => 'string',
        'gender'           => 'string',
        'birth_date'       => 'date',
        'native_place'     => 'string',
        'ethnicity'        => 'string',
        'political_status' => 'string',
        'id_card'          => 'string',
        'email'            => 'string',
        'current_address'  => 'string',
        'wechat_account'   => 'string',
        // 在职信息
        'dept_name'        => 'string',
        'position_name'    => 'string',
        'position_type'    => 'string',
        'entry_date'       => 'date',
        'employment_status'=> 'string',
        'leave_date'       => 'date',
        'regularize_date'  => 'date',
        // 银行卡
        'bank_name'        => 'string',
        'bank_card_no'     => 'string',
        // 材料附件
        'photo_url'        => 'string',
        'id_card_front'    => 'string',
        'id_card_back'     => 'string',
        'degree_cert'      => 'string',
        'graduation_cert'  => 'string',
        'resignation_cert' => 'string',
        'health_report'    => 'string',
        'xuexin_report'    => 'string',
        // 系统字段
        'create_time'      => 'int',
        'update_time'      => 'int',
        'delete_time'      => 'int',
    ];

    protected $autoWriteTimestamp = true;

    use \think\model\concern\SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    /**
     * 关联：教育经历（一对多）
     */
    public function educations()
    {
        return $this->hasMany(PersonnelEducation::class, 'personnel_id', 'id')->order('sort', 'asc');
    }

    /**
     * 关联：紧急联系人（一对多）
     */
    public function emergencies()
    {
        return $this->hasMany(PersonnelEmergency::class, 'personnel_id', 'id')->order('sort', 'asc');
    }
}
