<?php
namespace app\model;

use think\Model;

/**
 * 教师模型
 */
class Teacher extends Model
{
    // 设置表名
    protected $name = 'teachers';
    
    // 设置字段信息
    protected $schema = [
        'id'              => 'int',
        'account_id'      => 'int',
        'name'            => 'string',
        'gender'          => 'string',
        'phone'           => 'string',
        'email'           => 'string',
        'education'       => 'string',
        'school'          => 'string',
        'major'           => 'string',
        'hourly_rate'     => 'float',
        'subject_ids'     => 'string',
        'subject_names'   => 'string',
        'district_ids'    => 'string',
        'district_names'  => 'string',
        'experience'      => 'string',
        'self_intro'      => 'string',
        'photos'          => 'string',
        'status'          => 'string',
        'reject_reason'   => 'string',
        'is_top'          => 'int',
        'review_time'     => 'datetime',
        'reviewer_id'     => 'int',
        'create_time'     => 'datetime',
        'update_time'     => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取器：照片列表
     */
    public function getPhotosAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 修改器：照片列表
     */
    public function setPhotosAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }
    
    /**
     * 获取器：科目名称数组
     */
    public function getSubjectNamesAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }
    
    /**
     * 获取器：区域名称数组
     */
    public function getDistrictNamesAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }
}

