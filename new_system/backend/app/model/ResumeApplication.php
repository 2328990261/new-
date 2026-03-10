<?php
namespace app\model;

use think\Model;

class ResumeApplication extends Model
{
    protected $name = 'resume_application';
    
    // 设置字段信息
    protected $schema = [
        'id' => 'int',
        'teacher_id' => 'int',
        'tutor_id' => 'int',
        'status' => 'string',
        'apply_time' => 'datetime',
        'review_time' => 'datetime',
        'admin_remark' => 'text',
    ];
    
    // 关闭自动时间戳
    protected $autoWriteTimestamp = false;
    
    // 关联教师表
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id')->bind([
            'teacher_name' => 'name',
            'teacher_phone' => 'phone',
            'teacher_avatar' => 'photos',
            'teacher_education' => 'education',
            'teacher_school' => 'school',
            'teacher_subjects' => 'subject_names',
        ]);
    }
    
    // 关联家教订单表（直接使用fa_tutor_orders_new表）
    public function tutorOrder()
    {
        return $this->hasOne('think\Model', 'id', 'tutor_id')
            ->setTable('fa_tutor_orders_new')
            ->bind([
                'tutor_title' => 'content',
                'tutor_subject' => 'subject',
                'tutor_grade' => 'grade',
                'tutor_city' => 'city',
                'tutor_district' => 'district',
            ]);
    }
    
    // 关联审核人
    public function reviewer()
    {
        return $this->belongsTo('app\model\Admin', 'reviewer_id', 'id')->bind([
            'reviewer_name' => 'username'
        ]);
    }
    
    // 状态文本
    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝'
        ];
        return $statusMap[$data['status']] ?? $data['status'];
    }
}
