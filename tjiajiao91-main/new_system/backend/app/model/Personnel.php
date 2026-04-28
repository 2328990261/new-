<?php
namespace app\model;

use think\Model;

class Personnel extends Model
{
    protected $name = 'personnel';
    
    // 设置字段信息
    protected $schema = [
        'id'                => 'int',
        'name'              => 'string',
        'phone'             => 'string',
        'id_card'           => 'string',
        'employment_status' => 'string',
        'employment_type'   => 'string',
        'entry_date'        => 'string',
        'departure_date'    => 'string',
        'department'        => 'string',
        'position'          => 'string',
        'remark'            => 'string',
        'create_time'       => 'int',
        'update_time'       => 'int',
        'delete_time'       => 'int',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 软删除
    use \think\model\concern\SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    
    // 关联薪酬记录
    public function salaries()
    {
        return $this->hasMany(Salary::class, 'personnel_id');
    }
    
    // 获取在职人员数量
    public static function getOnJobCount()
    {
        return self::where('employment_status', '在职')->count();
    }
    
    // 获取离职人员数量
    public static function getOffJobCount()
    {
        return self::where('employment_status', '离职')->count();
    }
}
