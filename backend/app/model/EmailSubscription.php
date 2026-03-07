<?php
namespace app\model;

use think\Model;

/**
 * 邮件订阅模型
 */
class EmailSubscription extends Model
{
    // 设置表名
    protected $name = 'email_subscriptions';
    
    // 设置字段信息
    protected $schema = [
        'id'           => 'int',
        'email'        => 'string',
        'city_ids'     => 'string',
        'district_ids' => 'string',
        'subject_ids'  => 'string',
        'grade_levels' => 'string',
        'status'       => 'int',
        'verify_token' => 'string',
        'is_verified'  => 'int',
        'create_time'  => 'datetime',
        'update_time'  => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'email', 'create_time'];
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 验证状态获取器
     */
    public function getIsVerifiedTextAttr($value, $data)
    {
        return $data['is_verified'] ? '已验证' : '未验证';
    }
    
    /**
     * 获取订阅城市ID数组
     */
    public function getCityIdsArrayAttr($value, $data)
    {
        return $data['city_ids'] ? explode(',', $data['city_ids']) : [];
    }
    
    /**
     * 获取订阅区域ID数组
     */
    public function getDistrictIdsArrayAttr($value, $data)
    {
        return $data['district_ids'] ? explode(',', $data['district_ids']) : [];
    }
    
    /**
     * 获取订阅科目ID数组
     */
    public function getSubjectIdsArrayAttr($value, $data)
    {
        return $data['subject_ids'] ? explode(',', $data['subject_ids']) : [];
    }
    
    /**
     * 获取订阅年级段数组
     */
    public function getGradeLevelsArrayAttr($value, $data)
    {
        return $data['grade_levels'] ? explode(',', $data['grade_levels']) : [];
    }
    
    /**
     * 设置城市ID修改器
     */
    public function setCityIdsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    
    /**
     * 设置区域ID修改器
     */
    public function setDistrictIdsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    
    /**
     * 设置科目ID修改器
     */
    public function setSubjectIdsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    
    /**
     * 设置年级段修改器
     */
    public function setGradeLevelsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    
    /**
     * 检查订单是否匹配订阅条件
     */
    public function matchesOrder($order)
    {
        // 检查城市
        if ($this->city_ids) {
            $cityIds = $this->getCityIdsArrayAttr(null, $this->getData());
            if (!in_array($order['city_id'], $cityIds)) {
                return false;
            }
        }
        
        // 检查区域
        if ($this->district_ids) {
            $districtIds = $this->getDistrictIdsArrayAttr(null, $this->getData());
            if (!in_array($order['district_id'], $districtIds)) {
                return false;
            }
        }
        
        // 检查科目
        if ($this->subject_ids) {
            $subjectIds = $this->getSubjectIdsArrayAttr(null, $this->getData());
            if (!in_array($order['subject_id'], $subjectIds)) {
                return false;
            }
        }
        
        // 检查年级段
        if ($this->grade_levels) {
            $gradeLevels = $this->getGradeLevelsArrayAttr(null, $this->getData());
            $orderGradeLevel = $this->getGradeLevelFromGrade($order['grade']);
            if ($orderGradeLevel && !in_array($orderGradeLevel, $gradeLevels)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 从年级字符串中提取年级段
     * 例如：小学一年级 -> 小学，初中二年级 -> 初中，高三 -> 高中
     */
    private function getGradeLevelFromGrade($grade)
    {
        if (empty($grade)) {
            return null;
        }
        
        // 年级段映射
        if (strpos($grade, '小学') !== false || strpos($grade, '幼儿') !== false) {
            return '小学';
        } elseif (strpos($grade, '初') !== false) {
            return '初中';
        } elseif (strpos($grade, '高') !== false) {
            return '高中';
        }
        
        return null;
    }
    
    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：验证状态
     */
    public function searchIsVerifiedAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_verified', $value);
        }
    }
    
    /**
     * 搜索器：邮箱
     */
    public function searchEmailAttr($query, $value)
    {
        if ($value) {
            $query->where('email', 'like', '%' . $value . '%');
        }
    }
}

