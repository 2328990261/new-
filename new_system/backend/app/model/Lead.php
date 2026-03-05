<?php
namespace app\model;

use think\Model;

/**
 * 线索模型
 */
class Lead extends Model
{
    protected $name = 'leads';
    protected $pk = 'id';
    
    // 设置字段信息
    protected $schema = [
        'id'                => 'int',
        'lead_no'           => 'string',
        'raw_content'       => 'string',
        'city_id'           => 'int',
        'district_id'       => 'int',
        'grade'             => 'string',
        'subject'           => 'string',
        'phone'             => 'string',
        'contact_name'      => 'string',
        'assigned_admin_id' => 'int',
        'status'            => 'string',
        'tutor_content'     => 'string',
        'tutor_title'       => 'string',
        'info_fee'          => 'float',
        'create_time'       => 'datetime',
        'update_time'       => 'datetime',
        'creator_admin_id'  => 'int',
        'channel'           => 'string',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 关联城市
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
    
    // 关联区域
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
    
    // 关联负责客服
    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }
    
    // 关联创建人
    public function creatorAdmin()
    {
        return $this->belongsTo(Admin::class, 'creator_admin_id');
    }
    
    // 关联跟进记录
    public function followLogs()
    {
        return $this->hasMany(LeadFollowLog::class, 'lead_id');
    }
    
    /**
     * 生成线索编号
     */
    public static function generateLeadNo()
    {
        $prefix = 'L';
        $date = date('Ymd');
        
        // 查询今天最后一个线索编号
        $lastLead = self::where('lead_no', 'like', $prefix . $date . '%')
            ->order('lead_no', 'desc')
            ->value('lead_no');
        
        if ($lastLead) {
            // 提取序号并加1
            $lastNumber = intval(substr($lastLead, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * 状态文本映射
     */
    public static function getStatusText($status)
    {
        $statusMap = [
            '待跟进' => '待跟进',
            '跟进中' => '跟进中',
            '已发单' => '已发单',
            '不需要' => '不需要',
            '无效'   => '无效',
        ];
        return $statusMap[$status] ?? $status;
    }
    
    /**
     * 渠道文本映射
     */
    public static function getChannelText($channel)
    {
        $channelMap = [
            '美团'     => '美团',
            '58同城'   => '58同城',
            '表单'     => '表单',
            '渠道生源' => '渠道生源',
            '其他'     => '其他',
        ];
        return $channelMap[$channel] ?? $channel;
    }
}
