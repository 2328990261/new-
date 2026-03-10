<?php
namespace app\model;

use think\Model;

/**
 * 线索跟进记录模型
 */
class LeadFollowLog extends Model
{
    protected $name = 'lead_follow_logs';
    protected $pk = 'id';
    
    // 设置字段信息
    protected $schema = [
        'id'                 => 'int',
        'lead_id'            => 'int',
        'old_status'         => 'string',
        'new_status'         => 'string',
        'remark'             => 'string',
        'operator_admin_id'  => 'int',
        'proof_images'       => 'json',        // 不需要凭证截图数组（JSON）
        'invalid_images'     => 'json',        // 无效截图数组（JSON）
        'create_time'        => 'datetime',
    ];
    
    // 不使用自动JSON转换，手动处理
    // protected $json = ['proof_images', 'invalid_images'];
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 关联线索
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
    
    // 关联操作人
    public function operatorAdmin()
    {
        return $this->belongsTo(Admin::class, 'operator_admin_id');
    }
}
