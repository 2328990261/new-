<?php
namespace app\model;

use think\Model;

/**
 * 家长预约订单模型
 */
class ParentOrder extends Model
{
    // 设置表名
    protected $name = 'parent_orders';
    
    // 设置字段信息
    protected $schema = [
        'id'                  => 'int',
        'order_no'            => 'string',
        'admin_id'            => 'int',
        'grade'               => 'string',
        'subject'             => 'string',
        'student_info'        => 'string',
        'frequency'           => 'string',
        'teacher_requirement' => 'string',
        'address'             => 'string',
        'salary'              => 'string',  // 课费薪资
        'parent_name'         => 'string',
        'parent_contact'      => 'string',
        'remark'              => 'string',
        'status'              => 'string',
        'reject_reason'       => 'string',
        'tutor_id'            => 'int',
        'audit_time'          => 'datetime',
        'create_time'         => 'datetime',
        'update_time'         => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 只读字段
    protected $readonly = ['id', 'order_no', 'create_time'];
    
    /**
     * 生成订单号
     * 格式: ORD + YYYYMMDD + 三位随机数
     * 例如: ORD20250103001
     */
    public static function generateOrderNo()
    {
        // 获取日期前缀
        $datePrefix = 'ORD' . date('Ymd'); // 例如: ORD20250103
        
        // 尝试生成不重复的订单号，最多尝试1000次
        for ($i = 0; $i < 1000; $i++) {
            // 生成三位随机数（001-999）
            $randomSuffix = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // 组合成完整订单号
            $orderNo = $datePrefix . $randomSuffix;
            
            // 检查是否已存在
            $exists = self::where('order_no', $orderNo)->find();
            
            if (!$exists) {
                return $orderNo;
            }
        }
        
        // 如果1000次都重复了，抛出异常
        throw new \Exception('无法生成唯一订单号，请稍后重试');
    }
    
    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
    
    /**
     * 关联家教信息（审核通过后）
     */
    public function tutor()
    {
        return $this->belongsTo(TutorOrder::class, 'tutor_id');
    }
    
    /**
     * 状态获取器
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝'
        ];
        return $status[$data['status']] ?? '未知';
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
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value) {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：关键词（订单号、年级、科目）
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value) {
            $query->where(function ($query) use ($value) {
                $query->whereOr([
                    ['order_no', 'like', '%' . $value . '%'],
                    ['grade', 'like', '%' . $value . '%'],
                    ['subject', 'like', '%' . $value . '%'],
                ]);
            });
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
}




