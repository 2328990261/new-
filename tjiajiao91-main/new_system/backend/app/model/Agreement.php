<?php
namespace app\model;

use think\Model;

/**
 * 协议模型
 */
class Agreement extends Model
{
    // 设置表名
    protected $name = 'agreements';
    
    // 设置字段信息
    protected $schema = [
        'id'             => 'int',
        'type'           => 'string',
        'title'          => 'string',
        'content'        => 'text',
        'plain_content'  => 'text',
        'version'        => 'string',
        'status'         => 'int',
        'is_current'     => 'int',
        'effective_time' => 'datetime',
        'creator_id'     => 'int',
        'updater_id'     => 'int',
        'create_time'    => 'datetime',
        'update_time'    => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 协议类型常量
    const TYPE_USER_AGREEMENT = 'user_agreement';
    const TYPE_PRIVACY_POLICY = 'privacy_policy';
    
    /**
     * 获取协议类型列表
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_USER_AGREEMENT => '用户服务协议',
            self::TYPE_PRIVACY_POLICY => '隐私政策'
        ];
    }
    
    /**
     * 获取协议类型名称
     */
    public function getTypeTextAttr($value, $data)
    {
        $typeList = self::getTypeList();
        return $typeList[$data['type']] ?? $data['type'];
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == 1 ? '启用' : '禁用';
    }
    
    /**
     * 获取当前版本文本
     */
    public function getCurrentTextAttr($value, $data)
    {
        return $data['is_current'] == 1 ? '是' : '否';
    }
    
    /**
     * 关联创建者
     */
    public function creator()
    {
        return $this->belongsTo('Admin', 'creator_id', 'id');
    }
    
    /**
     * 关联更新者
     */
    public function updater()
    {
        return $this->belongsTo('Admin', 'updater_id', 'id');
    }
    
    /**
     * 获取当前版本协议
     */
    public static function getCurrentAgreement($type)
    {
        return self::where('type', $type)
            ->where('is_current', 1)
            ->where('status', 1)
            ->find();
    }
    
    /**
     * 设置当前版本
     */
    public function setCurrent()
    {
        // 将同类型的其他协议设为非当前版本
        self::where('type', $this->type)
            ->where('id', '<>', $this->id)
            ->update(['is_current' => 0]);
        
        // 设置当前协议为当前版本
        $this->is_current = 1;
        return $this->save();
    }
}