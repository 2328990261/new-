<?php
namespace app\model;

use think\Model;

/**
 * 区域模型
 */
class District extends Model
{
    // 设置表名
    protected $name = 'districts';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'city_id'     => 'int',
        'code'        => 'string',
        'name'        => 'string',
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
     * 关联城市
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(TutorOrder::class, 'district_id');
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
     * 搜索器：城市ID
     */
    public function searchCityIdAttr($query, $value)
    {
        if ($value) {
            $query->where('city_id', $value);
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
     * 搜索器：区域名称
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }
}

