<?php
namespace app\model;

use think\Model;

/**
 * 城市模型
 */
class City extends Model
{
    // 设置表名
    protected $name = 'cities';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'province_id' => 'int',
        'code'        => 'string',
        'name'        => 'string',
        'level'       => 'string',
        'is_hot'      => 'int',
        'sort'        => 'int',
        'status'      => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'code', 'create_time'];
    
    /**
     * 关联省份
     */
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
    
    /**
     * 关联区域
     */
    public function districts()
    {
        return $this->hasMany(District::class, 'city_id');
    }
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(TutorOrder::class, 'city_id');
    }
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * is_hot 获取器 - 确保返回整数类型
     */
    public function getIsHotAttr($value)
    {
        return (int)$value;
    }
    
    /**
     * 搜索器：省份ID
     */
    public function searchProvinceIdAttr($query, $value)
    {
        if ($value) {
            $query->where('province_id', $value);
        }
    }
    
    /**
     * 搜索器：城市等级（保留用于过渡）
     */
    public function searchLevelAttr($query, $value)
    {
        if ($value) {
            $query->where('level', $value);
        }
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
     * 搜索器：城市名称
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }
}

