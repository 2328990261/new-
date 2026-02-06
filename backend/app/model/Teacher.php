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
        'id'                    => 'int',
        'account_id'            => 'int',
        'name'                  => 'string',
        'gender'                => 'string',
        'phone'                 => 'string',
        'wechat_id'             => 'string',
        'wechat_nickname'       => 'string',
        'openid'                => 'string',
        'email'                 => 'string',
        'education'             => 'string',
        'school'                => 'string',
        'major'                 => 'string',
        'teacher_type'          => 'string',
        'grade_level'           => 'string',
        'education_level'       => 'string',
        'hourly_rate'           => 'float',
        'subject_ids'           => 'string',
        'subject_names'         => 'string',
        'district_ids'          => 'string',
        'district_names'        => 'string',
        'experience'            => 'string',
        'self_intro'            => 'string',
        'personal_advantage'    => 'string',
        'advantage_tags'        => 'string',
        'photos'                => 'string',
        'status'                => 'string',
        'real_name_verified'    => 'int',
        'education_verified'    => 'int',
        'teacher_verified'      => 'int',
        'id_card_front'         => 'string',
        'id_card_back'          => 'string',
        'education_certificate' => 'string',
        'teacher_certificate'   => 'string',
        'review_status'         => 'string',
        'review_time'           => 'datetime',
        'reviewer_id'           => 'int',
        'review_note'           => 'string',
        'is_top'                => 'int',
        'last_login_time'       => 'datetime',
        'create_time'           => 'datetime',
        'update_time'           => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * 获取器：教学经历（支持结构化数组）
     * 返回格式：[
     *   {
     *     "start_date": "2023-01",
     *     "end_date": "2023-06",
     *     "subject": "高中数学",
     *     "location": "北京市海淀区",
     *     "description": "辅导高三学生数学"
     *   }
     * ]
     */
    public function getExperienceAttr($value)
    {
        if (empty($value)) {
            return [];
        }
        
        $decoded = json_decode($value, true);
        
        // 如果解码失败或不是数组，返回空数组
        if (!is_array($decoded)) {
            return [];
        }
        
        // 如果是旧格式（字符串），转换为新格式
        if (isset($decoded[0]) && is_string($decoded[0])) {
            return [[
                'start_date' => '',
                'end_date' => '',
                'subject' => '',
                'location' => '',
                'description' => $decoded[0]
            ]];
        }
        
        return $decoded;
    }
    
    /**
     * 修改器：教学经历（支持结构化数组）
     */
    public function setExperienceAttr($value)
    {
        if (empty($value)) {
            return null;
        }
        
        if (is_string($value)) {
            // 如果是字符串，尝试解码
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
            // 如果不是JSON，作为单条经历保存
            return json_encode([[
                'start_date' => '',
                'end_date' => '',
                'subject' => '',
                'location' => '',
                'description' => $value
            ]], JSON_UNESCAPED_UNICODE);
        }
        
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        return null;
    }
    
    /**
     * 获取器：照片列表（区分头像和教学照片）
     * 返回格式：{
     *   "avatar": "https://...",
     *   "teaching_photos": ["https://...", "https://..."]
     * }
     */
    public function getPhotosAttr($value)
    {
        if (empty($value)) {
            return [
                'avatar' => '',
                'teaching_photos' => []
            ];
        }
        
        $decoded = json_decode($value, true);
        
        // 如果解码失败，返回默认结构
        if (!is_array($decoded)) {
            return [
                'avatar' => '',
                'teaching_photos' => []
            ];
        }
        
        // 如果是旧格式（简单数组），第一张作为头像，其余作为教学照片
        if (isset($decoded[0]) && is_string($decoded[0])) {
            return [
                'avatar' => $decoded[0] ?? '',
                'teaching_photos' => array_slice($decoded, 1)
            ];
        }
        
        // 如果已经是新格式，直接返回
        if (isset($decoded['avatar']) || isset($decoded['teaching_photos'])) {
            return [
                'avatar' => $decoded['avatar'] ?? '',
                'teaching_photos' => $decoded['teaching_photos'] ?? []
            ];
        }
        
        return [
            'avatar' => '',
            'teaching_photos' => []
        ];
    }
    
    /**
     * 修改器：照片列表（区分头像和教学照片）
     */
    public function setPhotosAttr($value)
    {
        if (empty($value)) {
            return json_encode([
                'avatar' => '',
                'teaching_photos' => []
            ], JSON_UNESCAPED_UNICODE);
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                // 如果是旧格式数组，转换为新格式
                if (isset($decoded[0]) && is_string($decoded[0])) {
                    return json_encode([
                        'avatar' => $decoded[0] ?? '',
                        'teaching_photos' => array_slice($decoded, 1)
                    ], JSON_UNESCAPED_UNICODE);
                }
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }
        
        if (is_array($value)) {
            // 如果是简单数组，转换为新格式
            if (isset($value[0]) && is_string($value[0]) && !isset($value['avatar'])) {
                return json_encode([
                    'avatar' => $value[0] ?? '',
                    'teaching_photos' => array_slice($value, 1)
                ], JSON_UNESCAPED_UNICODE);
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        return json_encode([
            'avatar' => '',
            'teaching_photos' => []
        ], JSON_UNESCAPED_UNICODE);
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
    
    /**
     * 获取器：优势标签数组
     */
    public function getAdvantageTagsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    /**
     * 修改器：优势标签数组
     */
    public function setAdvantageTagsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
    
    /**
     * 获取认证状态
     * @return string 已认证|未认证|待认证
     */
    public function getCertificationStatus()
    {
        // 如果审核状态是待审核，返回待认证
        if ($this->review_status === 'pending') {
            return '待认证';
        }
        
        // 如果三种认证中至少有一种通过，返回已认证
        if ($this->real_name_verified || $this->education_verified || $this->teacher_verified) {
            return '已认证';
        }
        
        // 否则返回未认证
        return '未认证';
    }
    
    /**
     * 获取认证详情
     * @return array
     */
    public function getCertificationDetails()
    {
        return [
            'status' => $this->getCertificationStatus(),
            'real_name' => $this->real_name_verified ? '已认证' : '未认证',
            'education' => $this->education_verified ? '已认证' : '未认证',
            'teacher' => $this->teacher_verified ? '已认证' : '未认证',
            'review_status' => $this->review_status,
            'review_time' => $this->review_time,
        ];
    }
}

