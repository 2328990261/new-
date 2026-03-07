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
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'create_time'];
    
    /**
     * 生成订单ID
     * 格式: YYMMDD + 三位随机不重复数字
     * 例如: 2025年10月3日 -> 251003001
     */
    public static function generateOrderId()
    {
        // 获取日期前缀（年后两位+月+日）
        $datePrefix = date('ymd'); // 例如: 251003
        
        // 尝试生成不重复的ID，最多尝试1000次
        for ($i = 0; $i < 1000; $i++) {
            // 生成三位随机数（000-999）
            $randomSuffix = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            
            // 组合成完整ID
            $orderId = $datePrefix . $randomSuffix;
            
            // 检查是否已存在
            $exists = self::where('id', $orderId)->find();
            
            if (!$exists) {
                return $orderId;
            }
        }
        
        // 如果1000次都重复了，抛出异常
        throw new \Exception('无法生成唯一订单ID，请稍后重试');
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
}

