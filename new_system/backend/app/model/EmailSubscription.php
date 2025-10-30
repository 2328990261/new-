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
        
        return true;
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

