<?php
namespace app\model;

use think\Model;

/**
 * 人员薪酬模型
 * 对应表：fa_personnel_salary
 */
class PersonnelSalary extends Model
{
    protected $name = 'personnel_salary';

    protected $schema = [
        'id'                  => 'int',
        'personnel_id'        => 'int',
        'base_salary'         => 'float',
        'performance_salary'  => 'float',
        'post_allowance'      => 'float',
        'housing_allowance'   => 'float',
        'meal_allowance'      => 'float',
        'transport_allowance' => 'float',
        'other_allowance'     => 'float',
        'total_salary'        => 'float',
        'effective_date'      => 'date',
        'end_date'            => 'date',
        'status'              => 'int',
        'remark'              => 'string',
        'create_time'         => 'int',
        'update_time'         => 'int',
        'delete_time'         => 'int',
    ];

    protected $autoWriteTimestamp = true;

    use \think\model\concern\SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;

    /**
     * 关联：人员信息
     */
    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'personnel_id', 'id');
    }

    /**
     * 获取器 - 格式化金额
     */
    public function getBaseSalaryAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getPerformanceSalaryAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getPostAllowanceAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getHousingAllowanceAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getMealAllowanceAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getTransportAllowanceAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getOtherAllowanceAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    public function getTotalSalaryAttr($value)
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * 获取器 - 确保状态为整数
     */
    public function getStatusAttr($value)
    {
        return (int)$value;
    }

    /**
     * 修改器 - 自动计算总薪酬
     */
    public function setTotalSalaryAttr($value, $data)
    {
        $total = 0;
        $total += isset($data['base_salary']) ? floatval($data['base_salary']) : 0;
        $total += isset($data['performance_salary']) ? floatval($data['performance_salary']) : 0;
        $total += isset($data['post_allowance']) ? floatval($data['post_allowance']) : 0;
        $total += isset($data['housing_allowance']) ? floatval($data['housing_allowance']) : 0;
        $total += isset($data['meal_allowance']) ? floatval($data['meal_allowance']) : 0;
        $total += isset($data['transport_allowance']) ? floatval($data['transport_allowance']) : 0;
        $total += isset($data['other_allowance']) ? floatval($data['other_allowance']) : 0;
        return $total;
    }
}
