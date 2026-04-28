<?php

namespace app\model;

use think\Model;

class ExpenseType extends Model
{
    protected $name = 'expense_types';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 类型转换
    protected $type = [
        'status' => 'integer',
        'is_system' => 'integer',
        'sort' => 'integer',
    ];
    
    /**
     * 获取启用的费用类型列表
     */
    public static function getEnabledList()
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取所有费用类型列表（包含禁用的）
     */
    public static function getAllList()
    {
        return self::order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
}
