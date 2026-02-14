<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Teacher;
use app\model\TeacherRegistrationProgress;
use think\facade\Db;
use think\facade\Validate;

/**
 * 教师注册控制器
 */
class TeacherRegister extends BaseController
{
    /**
     * 保存注册进度
     */
    public function saveProgress()
    {
        $openid = $this->request->param('openid', '');
        $phone = $this->request->param('phone', '');
        $currentStep = (int)$this->request->param('current_step', 1);
        $formData = $this->request->param('form_data', []);
        
        if (empty($openid) && empty($phone)) {
            return json(['success' => false, 'error' => '缺少用户标识']);
        }
        
        try {
            $identifier = $openid ?: $phone;
            $identifierType = $openid ? 'openid' : 'phone';
            
            $progress = TeacherRegistrationProgress::saveProgress(
                $identifier,
                $identifierType,
                $currentStep,
                $formData
            );
            
            return json([
                'success' => true,
                'message' => '保存成功',
                'data' => [
                    'id' => $progress->id,
                    'current_step' => $progress->current_step
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取注册进度
     */
    public function getProgress()
    {
        $openid = $this->request->param('openid', '');
        $phone = $this->request->param('phone', '');
        
        if (empty($openid) && empty($phone)) {
            return json(['success' => false, 'error' => '缺少用户标识']);
        }
        
        try {
            $identifier = $openid ?: $phone;
            $identifierType = $openid ? 'openid' : 'phone';
            
            $progress = TeacherRegistrationProgress::getProgress($identifier, $identifierType);
            
            if (!$progress) {
                return json([
                    'success' => true,
                    'data' => [
                        'current_step' => 1,
                        'form_data' => []
                    ]
                ]);
            }
            
            return json([
                'success' => true,
                'data' => [
                    'current_step' => $progress->current_step,
                    'form_data' => $progress->form_data
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 提交注册
     */
    public function submit()
    {
        $data = $this->request->post();
        
        // 记录接收到的数据用于调试
        \think\facade\Log::info('Teacher Register Submit Data:', $data);
        
        // 验证必填字段
        $validate = Validate::rule([
            'name' => 'require|max:50',
            'gender' => 'require|in:男,女',
            'phone' => 'require|mobile',
            'wechat_id' => 'require|max:100',
            'email' => 'email',
            'avatar' => 'require',
            'school' => 'require|max:100',
            'major' => 'require|max:100',
            'teacher_type' => 'require',
            'personal_advantage' => 'require|min:10|max:300',
        ])->message([
            'name.require' => '请输入真实姓名',
            'gender.require' => '请选择性别',
            'phone.require' => '请输入手机号',
            'phone.mobile' => '手机号格式不正确',
            'wechat_id.require' => '请输入微信号',
            'email.email' => '邮箱格式不正确',
            'avatar.require' => '请上传头像',
            'school.require' => '请输入学校名称',
            'major.require' => '请输入专业',
            'teacher_type.require' => '请选择教师类型',
            'personal_advantage.require' => '请填写个人优势',
            'personal_advantage.min' => '个人优势至少需要10个字',
        ]);
        
        if (!$validate->check($data)) {
            return json(['success' => false, 'error' => $validate->getError()]);
        }
        
        try {
            // 检查手机号是否已注册
            $exists = Teacher::where('phone', $data['phone'])->find();
            if ($exists) {
                return json(['success' => false, 'error' => '该手机号已注册']);
            }
            
            // 处理教学经历（结构化JSON数组）
            if (isset($data['experiences']) && is_array($data['experiences'])) {
                // 验证每条经历的数据结构
                $validExperiences = [];
                foreach ($data['experiences'] as $exp) {
                    if (is_array($exp)) {
                        $validExperiences[] = [
                            'start_date' => $exp['start_date'] ?? '',
                            'end_date' => $exp['end_date'] ?? '',
                            'subject' => $exp['subject'] ?? '',
                            'location' => $exp['location'] ?? '',
                            'description' => $exp['description'] ?? ''
                        ];
                    }
                }
                $data['experience'] = json_encode($validExperiences, JSON_UNESCAPED_UNICODE);
                unset($data['experiences']);
            }
            
            // 处理教学照片（区分头像和教学照片）
            $photosData = [
                'avatar' => $data['avatar'] ?? '',
                'teaching_photos' => []
            ];
            
            if (isset($data['teaching_photos']) && is_array($data['teaching_photos'])) {
                $photosData['teaching_photos'] = $data['teaching_photos'];
                unset($data['teaching_photos']);
            }
            
            $data['photos'] = json_encode($photosData, JSON_UNESCAPED_UNICODE);
            
            // 处理优势标签
            if (isset($data['advantage_tags']) && is_array($data['advantage_tags'])) {
                $data['advantage_tags'] = json_encode($data['advantage_tags'], JSON_UNESCAPED_UNICODE);
            }
            
            // 只保留数据库中存在的字段
            $allowedFields = [
                'name', 'gender', 'phone', 'wechat_id', 'wechat_nickname', 'openid', 'email',
                'hometown', 'teaching_years', 'birth_year',
                'location_province', 'location_city', 'location_district', 'location_address',
                'location_longitude', 'location_latitude',
                'education', 'school', 'major', 'teacher_type', 'grade_level', 'education_level',
                'hourly_rate', 'subject_ids', 'subject_names', 'district_ids', 'district_names',
                'experience', 'self_intro', 'personal_advantage', 'advantage_tags', 'photos',
                'id_card_front', 'id_card_back', 'education_certificate', 'teacher_certificate'
            ];
            
            $insertData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }
            
            // 设置默认状态
            $insertData['status'] = 'active';  // 账号激活
            $insertData['review_status'] = 'pending';  // 待审核
            $insertData['real_name_verified'] = 0;
            $insertData['education_verified'] = 0;
            $insertData['teacher_verified'] = 0;
            $insertData['is_top'] = 0;
            
            // 创建教师记录
            $teacher = Teacher::create($insertData);
            
            // 清除注册进度
            if (!empty($data['openid'])) {
                TeacherRegistrationProgress::where('openid', $data['openid'])->delete();
            } elseif (!empty($data['phone'])) {
                TeacherRegistrationProgress::where('phone', $data['phone'])->delete();
            }
            
            return json([
                'success' => true,
                'message' => '注册成功',
                'data' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'status' => $teacher->status
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '注册失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 上传图片
     */
    public function uploadImage()
    {
        $file = $this->request->file('file');
        
        if (!$file) {
            return json(['success' => false, 'error' => '请选择文件']);
        }
        
        try {
            // 验证文件大小和类型
            $fileSize = $file->getSize();
            $extension = strtolower($file->extension());
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            
            // 检查文件大小 (10MB)
            if ($fileSize > 10 * 1024 * 1024) {
                return json(['success' => false, 'error' => '文件大小不能超过10MB']);
            }
            
            // 检查文件类型
            if (!in_array($extension, $allowedExt)) {
                return json(['success' => false, 'error' => '只支持 jpg, jpeg, png, gif 格式的图片']);
            }
            
            // 创建上传目录
            $uploadPath = app()->getRootPath() . 'public/uploads/teacher/';
            $dateDir = date('Ymd');
            $fullPath = $uploadPath . $dateDir . '/';
            
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // 生成文件名
            $filename = uniqid() . '.' . $extension;
            $savePath = $fullPath . $filename;
            
            // 移动文件
            $file->move($fullPath, $filename);
            
            // 返回文件路径
            $url = '/uploads/teacher/' . $dateDir . '/' . $filename;
            $relativePath = 'teacher/' . $dateDir . '/' . $filename;
            
            return json([
                'success' => true,
                'message' => '上传成功',
                'data' => [
                    'url' => $url,
                    'path' => $relativePath,
                    'compressed' => false
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '上传失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 压缩图片
     * @param \think\file\UploadedFile $file
     * @return string|false 返回压缩后的临时文件路径
     */
    private function compressImage($file)
    {
        try {
            $filePath = $file->getRealPath();
            $imageInfo = getimagesize($filePath);
            
            if (!$imageInfo) {
                return false;
            }
            
            $mimeType = $imageInfo['mime'];
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // 创建图像资源
            switch ($mimeType) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($filePath);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($filePath);
                    break;
                default:
                    return false;
            }
            
            if (!$source) {
                return false;
            }
            
            // 计算新尺寸（如果图片太大，按比例缩小）
            $maxWidth = 1920;
            $maxHeight = 1920;
            
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = intval($width * $ratio);
                $newHeight = intval($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }
            
            // 创建新图像
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // 保持PNG透明度
            if ($mimeType == 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // 复制并调整大小
            imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // 保存到临时文件
            $tempPath = sys_get_temp_dir() . '/' . uniqid('compress_') . '.jpg';
            
            // 压缩质量：75%
            imagejpeg($newImage, $tempPath, 75);
            
            // 释放资源
            imagedestroy($source);
            imagedestroy($newImage);
            
            return $tempPath;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取教师类型选项
     */
    public function getTeacherTypes()
    {
        $types = [
            [
                'value' => 'undergraduate',
                'label' => '在读本科生',
                'grades' => [
                    ['value' => 'pre_freshman', 'label' => '准大一'],
                    ['value' => 'freshman', 'label' => '大一'],
                    ['value' => 'sophomore', 'label' => '大二'],
                    ['value' => 'junior', 'label' => '大三'],
                    ['value' => 'senior', 'label' => '大四'],
                    ['value' => 'fifth_year', 'label' => '大五'],
                ]
            ],
            [
                'value' => 'graduate_student',
                'label' => '在读研究生',
                'grades' => [
                    ['value' => 'graduate_first', 'label' => '研一'],
                    ['value' => 'graduate_second', 'label' => '研二'],
                    ['value' => 'graduate_third', 'label' => '研三'],
                ]
            ],
            [
                'value' => 'doctoral_student',
                'label' => '在读博士生',
                'grades' => [
                    ['value' => 'doctoral_first', 'label' => '博一'],
                    ['value' => 'doctoral_second', 'label' => '博二'],
                    ['value' => 'doctoral_third', 'label' => '博三'],
                    ['value' => 'doctoral_fourth', 'label' => '博四'],
                    ['value' => 'doctoral_fifth', 'label' => '博五'],
                ]
            ],
            [
                'value' => 'graduated',
                'label' => '毕业生',
                'education_levels' => [
                    ['value' => 'associate', 'label' => '大专'],
                    ['value' => 'bachelor', 'label' => '本科'],
                    ['value' => 'master', 'label' => '研究生'],
                    ['value' => 'doctorate', 'label' => '博士'],
                ]
            ],
            [
                'value' => 'professional',
                'label' => '专职老师',
                'education_levels' => [
                    ['value' => 'associate', 'label' => '大专'],
                    ['value' => 'bachelor', 'label' => '本科'],
                    ['value' => 'master', 'label' => '研究生'],
                    ['value' => 'doctorate', 'label' => '博士'],
                ]
            ],
        ];
        
        return json([
            'success' => true,
            'data' => $types
        ]);
    }
    
    /**
     * 获取优势标签选项
     */
    public function getAdvantageTags()
    {
        // 使用默认标签（硬编码）- 确保始终返回数据
        $defaultTags = [
            '耐心细致',
            '经验丰富',
            '因材施教',
            '提分快',
            '善于沟通',
            '责任心强',
            '方法独特',
            '亲和力强',
            '严格要求',
            '激发兴趣',
            '重点突出',
            '举一反三',
            '循序渐进',
            '查漏补缺',
            '培养习惯'
        ];
        
        try {
            // 尝试从数据库读取
            $tags = Db::name('advantage_tags')
                ->where('status', 1)
                ->order('sort', 'asc')
                ->column('name');
            
            if (!empty($tags)) {
                return json([
                    'success' => true,
                    'data' => $tags,
                    'source' => 'database'
                ]);
            }
        } catch (\Exception $e) {
            // 数据库读取失败，使用默认标签
            // 不抛出错误，静默处理
        }
        
        // 返回默认标签
        return json([
            'success' => true,
            'data' => $defaultTags,
            'source' => 'default'
        ]);
    }
    
    /**
     * 验证手机号是否已注册
     */
    public function checkPhone()
    {
        $phone = $this->request->param('phone', '');
        
        if (empty($phone)) {
            return json(['success' => false, 'error' => '请输入手机号']);
        }
        
        $exists = Teacher::where('phone', $phone)->find();
        
        return json([
            'success' => true,
            'data' => [
                'exists' => !empty($exists),
                'message' => $exists ? '该手机号已注册' : '手机号可用'
            ]
        ]);
    }
}
