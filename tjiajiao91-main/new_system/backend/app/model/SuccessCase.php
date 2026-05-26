<?php
namespace app\model;

use think\Model;

/**
 * 成功案例（小程序家长端展示）
 */
class SuccessCase extends Model
{
    protected $name = 'success_cases';

    protected $schema = [
        'id'                  => 'int',
        'grade'               => 'string',
        'subject'             => 'string',
        'theme_tag'           => 'string',
        'cover_images'        => 'string',
        'title'               => 'string',
        'course_intro'        => 'string',
        'student_background'  => 'string',
        'tutoring_results'    => 'string',
        'parent_comment'      => 'string',
        'sort_order'          => 'int',
        'status'              => 'int',
        'create_time'         => 'datetime',
        'update_time'         => 'datetime',
    ];

    protected $autoWriteTimestamp = true;
}
