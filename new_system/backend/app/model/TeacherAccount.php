<?php
namespace app\model;

use think\Model;

/**
 * 教师账号模型
 */
class TeacherAccount extends Model
{
    // 设置表名
    protected $name = 'teacher_accounts';
    
    // 设置字段信息
    protected $schema = [
        'id'                => 'int',
        'teacher_id'        => 'int',
        'email'             => 'string',
        'password'          => 'string',
        'status'            => 'int',
        'last_login_time'   => 'int',
        'create_time'       => 'int',
        'update_time'       => 'int',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'create_time';
    
    // 更新时间字段
    protected $updateTime = 'update_time';
    
    // 关联教师表
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
