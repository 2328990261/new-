<?php
namespace app\model;

use think\Model;

/**
 * 优势标签模型
 */
class AdvantageTag extends Model
{
    // 设置表名
    protected $name = 'advantage_tags';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取启用的标签列表
     * @return array
     */
    public static function getEnabledTags()
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->column('name');
    }
    
    /**
     * 获取所有标签（用于管理）
     * @return array
     */
    public static function getAllTags()
    {
        return self::order('sort', 'asc')
            ->select()
            ->toArray();
    }
}
