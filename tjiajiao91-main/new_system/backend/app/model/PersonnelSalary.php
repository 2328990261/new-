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
        'id'                        => 'int',
        'personnel_id'              => 'int',
        'base_salary'               => 'float',
        'performance_salary'        => 'float',
        'post_allowance'            => 'float',
        'housing_allowance'         => 'float',
        'meal_allowance'            => 'float',
        'transport_allowance'       => 'float',
        'other_allowance'           => 'float',
        'provident_fund_company'    => 'float',
        'provident_fund_personal'   => 'float',
        'social_insurance_company'  => 'float',
        'social_insurance_personal' => 'float',
        'total_salary'              => 'float',
        'salary_month'              => 'string',
        'effective_date'            => 'date',
        'end_date'                  => 'date',
        'status'                    => 'int',
        'remark'                    => 'string',
        'create_time'               => 'int',
        'update_time'               => 'int',
        'delete_time'               => 'int',
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

    public function getProvidentFundCompanyAttr($value)
    {
        return number_format((float)$value, 2, '.', '');
    }

    public function getProvidentFundPersonalAttr($value)
    {
        return number_format((float)$value, 2, '.', '');
    }

    public function getSocialInsuranceCompanyAttr($value)
    {
        return number_format((float)$value, 2, '.', '');
    }

    public function getSocialInsurancePersonalAttr($value)
    {
        return number_format((float)$value, 2, '.', '');
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
     * 修改器 - 自动计算总薪酬（实发 = 收入合计 - 个人社保 - 个人公积金）
     */
    public function setTotalSalaryAttr($value, $data)
    {
        $income = 0;
        $income += isset($data['base_salary']) ? floatval($data['base_salary']) : 0;
        $income += isset($data['performance_salary']) ? floatval($data['performance_salary']) : 0;
        $income += isset($data['post_allowance']) ? floatval($data['post_allowance']) : 0;
        $income += isset($data['housing_allowance']) ? floatval($data['housing_allowance']) : 0;
        $income += isset($data['meal_allowance']) ? floatval($data['meal_allowance']) : 0;
        $income += isset($data['transport_allowance']) ? floatval($data['transport_allowance']) : 0;
        $income += isset($data['other_allowance']) ? floatval($data['other_allowance']) : 0;

        $deduction = 0;
        $deduction += isset($data['provident_fund_personal']) ? floatval($data['provident_fund_personal']) : 0;
        $deduction += isset($data['social_insurance_personal']) ? floatval($data['social_insurance_personal']) : 0;

        return $income - $deduction;
    }
}
