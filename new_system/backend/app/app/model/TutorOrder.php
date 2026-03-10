<?php
namespace app\model;

use think\Model;

/**
 * 家教订单模型
 */
class TutorOrder extends Model
{
    // 设置表名
    protected $name = 'tutor_orders_new';
    
    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'content'         => 'string',
        'city_id'         => 'int',
        'district_id'     => 'int',
        'grade'           => 'string',
        'subject_id'      => 'int',
        'salary'          => 'string',
        'is_urgent'       => 'int',
        'is_top'          => 'int',
        'top_expire_time' => 'datetime',
        'admin_id'        => 'int',
        'dispatcher_id'   => 'int',    // 派单管理员ID
        'contact_info'    => 'string', // 派单联系方式
        'assigned_time'   => 'datetime', // 派单时间
        'status'          => 'int',
        'is_channel'      => 'int',    // 是否是渠道单
        'channel_code'    => 'string', // 渠道代号
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'create_time'];
    
    /**
     * 生成订单ID
     * 格式: YYMMDD + 三位序号（基于订单创建时间）
     * 例如: 2025年1月10日创建的订单 -> 250110001, 250110002, 250110003...
     * 
     * @param string $createTime 订单创建时间，格式：Y-m-d H:i:s 或 Y-m-d
     * @return string
     */
    public static function generateOrderId($createTime = null)
    {
        // 如果没有指定创建时间，使用当前时间
        if (!$createTime) {
            $createTime = date('Y-m-d H:i:s');
        }
        
        // 解析创建时间，获取日期前缀（年后两位+月+日）
        $timestamp = strtotime($createTime);
        if ($timestamp === false) {
            // 如果时间格式错误，使用当前时间
            $timestamp = time();
        }
        
        $datePrefix = date('ymd', $timestamp); // 例如: 250110
        
        // 查询该日期已有的记录数量
        $dayCount = self::where('id', 'like', $datePrefix . '%')->count();
        
        // 生成序号（从001开始）
        $sequence = str_pad($dayCount + 1, 3, '0', STR_PAD_LEFT);
        
        // 组合成完整ID
        $orderId = $datePrefix . $sequence;
        
        // 检查是否已存在（双重保险）
        $exists = self::where('id', $orderId)->find();
        
        if ($exists) {
            // 如果存在，使用时间戳+随机数作为备选
            $timestamp = substr(time(), -3); // 取时间戳后3位
            $random = rand(0, 9); // 0-9随机数
            $orderId = $datePrefix . $timestamp . $random;
            
            // 再次检查
            $exists2 = self::where('id', $orderId)->find();
            if ($exists2) {
                // 最后备选：使用微秒时间戳
                $microtime = substr(microtime(true) * 1000000, -6); // 取微秒后6位
                $orderId = $datePrefix . substr($microtime, -3); // 取后3位
            }
        }
        
        return $orderId;
    }
    
    /**
     * 关联城市
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
    
    /**
     * 关联区域
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
    
    /**
     * 关联科目
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
    
    /**
     * 关联创建管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
    
    /**
     * 关联派单管理员
     */
    public function dispatcher()
    {
        return $this->belongsTo(Admin::class, 'dispatcher_id');
    }
    
    /**
     * 是否加急获取器
     */
    public function getIsUrgentTextAttr($value, $data)
    {
        return $data['is_urgent'] ? '是' : '否';
    }
    
    /**
     * 是否置顶获取器
     */
    public function getIsTopTextAttr($value, $data)
    {
        return $data['is_top'] ? '是' : '否';
    }
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '已删除', 1 => '正常'];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 是否渠道单获取器
     */
    public function getIsChannelTextAttr($value, $data)
    {
        return $data['is_channel'] ? '是' : '否';
    }
    
    /**
     * 检查是否置顶有效
     */
    public function getIsTopValidAttr($value, $data)
    {
        if (!$data['is_top']) {
            return false;
        }
        if (!$data['top_expire_time']) {
            return true;
        }
        return strtotime($data['top_expire_time']) > time();
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
     * 搜索器：区域ID
     */
    public function searchDistrictIdAttr($query, $value)
    {
        if ($value) {
            $query->where('district_id', $value);
        }
    }
    
    /**
     * 搜索器：科目ID
     */
    public function searchSubjectIdAttr($query, $value)
    {
        if ($value) {
            $query->where('subject_id', $value);
        }
    }
    
    /**
     * 搜索器：年级
     */
    public function searchGradeAttr($query, $value)
    {
        if ($value) {
            $query->where('grade', 'like', '%' . $value . '%');
        }
    }
    
    /**
     * 搜索器：是否加急
     */
    public function searchIsUrgentAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_urgent', $value);
        }
    }
    
    /**
     * 搜索器：是否置顶
     */
    public function searchIsTopAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_top', $value);
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
     * 搜索器：关键词
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value) {
            $query->where('content', 'like', '%' . $value . '%');
        }
    }
    
    /**
     * 搜索器：创建时间范围
     */
    public function searchCreateTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('create_time', $value[0], $value[1]);
        }
    }
    
    /**
     * 搜索器：管理员ID
     */
    public function searchAdminIdAttr($query, $value)
    {
        if ($value) {
            $query->where('admin_id', $value);
        }
    }
    
    /**
     * 搜索器：是否渠道单
     */
    public function searchIsChannelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_channel', $value);
        }
    }
    
    /**
     * 搜索器：渠道代号
     */
    public function searchChannelCodeAttr($query, $value)
    {
        if ($value) {
            $query->where('channel_code', $value);
        }
    }
}

