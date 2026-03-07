<?php
namespace app\model;

use think\Model;

/**
 * 科目模型
 */
class Subject extends Model
{
    // 设置表名
    protected $name = 'subjects';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'parent_id'   => 'int',
        'name'        => 'string',
        'category'    => 'string',
        'sort'        => 'int',
        'status'      => 'int',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'create_time'];
    
    /**
     * 关联订单
     */
    public function orders()
    {
        return $this->hasMany(TutorOrder::class, 'subject_id');
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
     * 搜索器：科目分类
     */
    public function searchCategoryAttr($query, $value)
    {
        if ($value) {
            $query->where('category', $value);
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
     * 搜索器：科目名称
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }
    
    /**
     * 获取子科目
     */
    public function children()
    {
        return $this->hasMany(Subject::class, 'parent_id')->order('sort', 'asc');
    }
    
    /**
     * 获取父科目
     */
    public function parent()
    {
        return $this->belongsTo(Subject::class, 'parent_id');
    }
}

