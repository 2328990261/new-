<?php
namespace app\model;

use think\Model;

/**
 * 省份模型
 */
class Province extends Model
{
    // 设置表名
    protected $name = 'provinces';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'code'        => 'string',
        'name'        => 'string',
        'short_name'  => 'string',
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
    public function cities()
    {
        return $this->hasMany(City::class, 'province_id');
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
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：省份名称
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }
}

