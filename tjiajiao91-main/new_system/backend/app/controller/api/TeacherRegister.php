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
        $data = $this->getRequestData();
        
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
            
            // 规范化出生年月：统一为 YYYY-MM 写入 birth_date
            $this->normalizeBirthDate($data);
            
            // 只保留数据库中存在的字段
            $allowedFields = [
                'name', 'gender', 'phone', 'wechat_id', 'wechat_nickname', 'openid', 'email',
                'hometown', 'teaching_years', 'birth_date',
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
            
            // 初次提交一律进入人工「待审核」状态，不再因三项材料都为空而自动拒绝
            $insertData['review_status'] = 'pending';
            $insertData['review_note'] = null;
            
            // 设置默认状态
            $insertData['status'] = 'active';  // 账号激活
            $insertData['real_name_verified'] = 0;
            $insertData['education_verified'] = 0;
            $insertData['teacher_verified'] = 0;
            $insertData['is_top'] = 0;
            
            // 自动生成 teacher_no（对外展示编号，从 1000 起自增，如 T1000）
            $maxNo = (int) Teacher::max('teacher_no');
            $insertData['teacher_no'] = ($maxNo >= 1000) ? ($maxNo + 1) : 1000;
            
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
                    'status' => $teacher->status,
                    'review_status' => $teacher->review_status,
                    'review_note' => $teacher->review_note ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '注册失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新教师信息
     */
    public function update()
    {
        $data = $this->getRequestData();
        
        // 验证必填字段
        if (empty($data['id'])) {
            return json(['success' => false, 'error' => '缺少教师ID']);
        }
        
        $teacherId = $data['id'];
        unset($data['id']);

        // 小程序端「正式提交简历/重新提交审核」时传 true；已通过教师即使数据与库中一致也要回到待审核
        $submitForReview = false;
        if (isset($data['submit_for_review'])) {
            $v = $data['submit_for_review'];
            $submitForReview = ($v === true || $v === 1 || $v === '1' || $v === 'true');
        }
        unset($data['submit_for_review']);
        
        try {
            // 查找教师记录
            $teacher = Teacher::find($teacherId);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }

            // 如果请求中包含手机号，检查是否被其它教师占用（排除自己）
            if (!empty($data['phone'])) {
                $exists = Teacher::where('phone', $data['phone'])
                    ->where('id', '<>', $teacherId)
                    ->find();
                if ($exists) {
                    return json(['success' => false, 'error' => '该手机号已被其他教师使用']);
                }
            }
            
            // 处理教学经历（结构化JSON数组）
            if (isset($data['experiences']) && is_array($data['experiences'])) {
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
            if (isset($data['avatar']) || isset($data['teaching_photos'])) {
                $photosData = [
                    'avatar' => $data['avatar'] ?? '',
                    'teaching_photos' => []
                ];
                
                if (isset($data['teaching_photos']) && is_array($data['teaching_photos'])) {
                    $photosData['teaching_photos'] = $data['teaching_photos'];
                    unset($data['teaching_photos']);
                }
                
                $data['photos'] = json_encode($photosData, JSON_UNESCAPED_UNICODE);
            }
            
            // 处理优势标签
            if (isset($data['advantage_tags']) && is_array($data['advantage_tags'])) {
                $data['advantage_tags'] = json_encode($data['advantage_tags'], JSON_UNESCAPED_UNICODE);
            }
            
            // 规范化出生年月并写入 birth_date
            $this->normalizeBirthDate($data);
            
            // 只保留数据库中存在的字段
            $allowedFields = [
                'name', 'gender', 'phone', 'wechat_id', 'wechat_nickname', 'openid', 'email',
                'hometown', 'teaching_years', 'birth_date',
                'location_province', 'location_city', 'location_district', 'location_address',
                'location_longitude', 'location_latitude',
                'education', 'school', 'major', 'teacher_type', 'grade_level', 'education_level',
                'hourly_rate', 'subject_ids', 'subject_names', 'district_ids', 'district_names',
                'experience', 'self_intro', 'personal_advantage', 'advantage_tags', 'photos',
                'id_card_front', 'id_card_back', 'education_certificate', 'teacher_certificate'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // 更新时不再根据认证材料自动改为「已拒绝」，
            // 审核结果主要由后台人工审核接口决定。
            // 若当前为「已拒绝」，用户任意更新后应回到「待审核」
            if ($teacher->review_status === 'rejected') {
                $updateData['review_status'] = 'pending';
                $updateData['review_note'] = null;
                $updateData['review_time'] = null;
                $updateData['reviewer_id'] = null;
            }

            // 若当前为「已通过」：用户正式提交(submit_for_review) 或审核相关内容有变动 → 回到待审核
            if ($teacher->review_status === 'approved') {
                $contentChanged = $this->approvedTeacherAuditContentChanged($teacher, $updateData, $allowedFields);
                if ($submitForReview || $contentChanged) {
                    $updateData['review_status'] = 'pending';
                    $updateData['review_note'] = null;
                    $updateData['review_time'] = null;
                    $updateData['reviewer_id'] = null;
                    $updateData['real_name_verified'] = 0;
                    $updateData['education_verified'] = 0;
                    $updateData['teacher_verified'] = 0;
                }
            }
            
            // 更新教师记录
            $teacher->save($updateData);
            
            return json([
                'success' => true,
                'message' => '更新成功',
                'data' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'review_status' => $teacher->review_status,
                    'review_note' => $teacher->review_note ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
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
            
            // 添加水印
            $imagePath = $fullPath . $filename;
            \app\service\WatermarkService::addWatermark($imagePath, '91家教中心', 'right-bottom');
            
            // 获取当前域名
            $request = request();
            $domain = $request->domain();
            
            // 返回完整URL
            $url = $domain . '/uploads/teacher/' . $dateDir . '/' . $filename;
            $relativePath = '/uploads/teacher/' . $dateDir . '/' . $filename;
            
            return json([
                'success' => true,
                'message' => '上传成功',
                'data' => [
                    'url' => $url,  // 完整URL，用于显示
                    'path' => $relativePath,  // 相对路径，用于保存到数据库
                    'compressed' => false
                ]
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('图片上传失败: ' . $e->getMessage());
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
        $excludeId = (int)$this->request->param('exclude_teacher_id', 0);
        
        if (empty($phone)) {
            return json(['success' => false, 'error' => '请输入手机号']);
        }
        
        $query = Teacher::where('phone', $phone);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        $exists = $query->find();
        
        return json([
            'success' => true,
            'data' => [
                'exists' => !empty($exists),
                'message' => $exists ? '该手机号已注册' : '手机号可用'
            ]
        ]);
    }
    
    /**
     * 获取当前用户的教师注册状态
     */
    public function getRegistrationStatus()
    {
        $openid = $this->request->param('openid', '');
        $phone = $this->request->param('phone', '');
        
        if (empty($openid) && empty($phone)) {
            return json(['success' => false, 'error' => '缺少用户标识']);
        }
        
        try {
            // 查找教师记录
            $query = Teacher::where(function($query) use ($openid, $phone) {
                if (!empty($openid)) {
                    $query->where('openid', $openid);
                }
                if (!empty($phone)) {
                    $query->whereOr('phone', $phone);
                }
            });
            
            $teacher = $query->find();
            
            // 如果没有找到教师记录，说明未注册
            if (!$teacher) {
                return json([
                    'success' => true,
                    'data' => [
                        'registered' => false,
                        'status' => null,
                        'review_status' => null,
                        'reject_reason' => null
                    ]
                ]);
            }
            
            // === 超时自动拒绝逻辑 ===
            // 需求：提交后先进入待审核，如果 5 分钟内仍未上传任何认证材料（三项都为空），再自动标记为已拒绝
            if ($teacher->review_status === 'pending') {
                // 使用最近一次提交时间作为计时基准：
                // 首次提交：update_time≈create_time；
                // 驳回后重新提交：update_time 会刷新，避免刚提交就被立即自动拒绝。
                $pendingBaseAt = $teacher->update_time ?: $teacher->getData('update_time');
                if (empty($pendingBaseAt)) {
                    $pendingBaseAt = $teacher->create_time ?: $teacher->getData('create_time');
                }

                if (!empty($pendingBaseAt)) {
                    $createdTs = strtotime($pendingBaseAt);
                    $nowTs = time();
                    // 超过 5 分钟
                    if ($createdTs > 0 && ($nowTs - $createdTs) >= 300) {
                        // 使用 getData 读取原始数据库值，避免模型获取器或类型转换导致误判为“未上传”
                        $idCardFront = trim((string)($teacher->getData('id_card_front') ?? ''));
                        $idCardBack  = trim((string)($teacher->getData('id_card_back') ?? ''));
                        $eduCert     = trim((string)($teacher->getData('education_certificate') ?? ''));
                        $teacherCert = trim((string)($teacher->getData('teacher_certificate') ?? ''));
                        $hasIdCard = $idCardFront !== '' || $idCardBack !== '';
                        $hasEduCert = $eduCert !== '';
                        $hasTeacherCert = $teacherCert !== '';

                        if (!$hasIdCard && !$hasEduCert && !$hasTeacherCert) {
                            $certRejectNote = '请在认证材料中身份证，学历证明，教师资格证三项中至少上传一项';
                            $teacher->review_status = 'rejected';
                            $teacher->review_note = $certRejectNote;
                            $teacher->review_time = date('Y-m-d H:i:s');
                            // reviewer_id 为空，表示系统自动拒绝
                            $teacher->reviewer_id = null;
                            $teacher->save();
                        }
                    }
                }
            }
            
            // 返回教师注册状态
            return json([
                'success' => true,
                'data' => [
                    'registered' => true,
                    'teacher_id' => $teacher->id,
                    'status' => $teacher->status,
                    'review_status' => $teacher->review_status,
                    'reject_reason' => $teacher->review_note,
                    'review_remark' => $teacher->review_note,  // 审核备注（通过或驳回都可能有）
                    'review_time' => $teacher->review_time
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取状态失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取当前登录教师的完整资料（用于本人编辑简历）
     * 仅返回与教师本人相关的完整字段，包含联系方式等敏感信息。
     * 公开场景仍应使用 Teacher 控制器中的 detail 接口（脱敏版）。
     */
    public function myProfile()
    {
        // 这里通过 openid 或手机号来识别当前登录的教师；
        // 与前端保持一致，优先使用 openid，退化到 phone。
        $openid = $this->request->param('openid', '');
        $phone  = $this->request->param('phone', '');
        $teacherId = (int)$this->request->param('teacher_id', 0);

        if (empty($openid) && empty($phone) && $teacherId <= 0) {
            return json(['success' => false, 'error' => '缺少用户标识']);
        }

        try {
            /** @var Teacher|null $teacher */
            if ($teacherId > 0) {
                // 编辑场景优先按 teacher_id 精确读取，避免 OR 条件命中到错误记录
                $teacher = Teacher::where('id', $teacherId)->find();
            } elseif (!empty($openid)) {
                $teacher = Teacher::where('openid', $openid)->find();
            } else {
                $teacher = Teacher::where('phone', $phone)->find();
            }
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在或未注册']);
            }

            // 只返回与表单相关的字段，避免泄露不必要的信息
            $data = $teacher->toArray();

            // 还原照片结构（与前端期望保持一致）
            // 注意：Teacher 模型有 getPhotosAttr 获取器，toArray 后 photos 可能已经是数组
            if (isset($data['photos']) && is_array($data['photos'])) {
                $data['avatar'] = $data['photos']['avatar'] ?? ($data['avatar'] ?? '');
                $data['teaching_photos'] = is_array($data['photos']['teaching_photos'] ?? null)
                    ? $data['photos']['teaching_photos']
                    : [];
            } elseif (!empty($data['photos']) && is_string($data['photos'])) {
                $photos = json_decode($data['photos'], true);
                if (is_array($photos)) {
                    $data['avatar'] = $photos['avatar'] ?? '';
                    $data['teaching_photos'] = is_array($photos['teaching_photos'] ?? null)
                        ? $photos['teaching_photos']
                        : [];
                } else {
                    $photoArray = array_filter(array_map('trim', explode(',', $data['photos'])));
                    $data['avatar'] = $photoArray[0] ?? '';
                    $data['teaching_photos'] = array_slice($photoArray, 1);
                }
            } else {
                $data['avatar'] = $data['avatar'] ?? '';
                $data['teaching_photos'] = [];
            }

            // 还原优势标签为数组（模型获取器可能已返回数组）
            if (isset($data['advantage_tags']) && is_array($data['advantage_tags'])) {
                // 已是数组，直接使用
            } elseif (!empty($data['advantage_tags']) && is_string($data['advantage_tags'])) {
                $tags = json_decode($data['advantage_tags'], true);
                $data['advantage_tags'] = is_array($tags) ? $tags : [];
            } else {
                $data['advantage_tags'] = [];
            }

            // 还原教学经历为数组（模型获取器可能已返回数组）
            if (isset($data['experience']) && is_array($data['experience'])) {
                $data['experiences'] = $data['experience'];
            } elseif (!empty($data['experience']) && is_string($data['experience'])) {
                $exp = json_decode($data['experience'], true);
                $data['experiences'] = is_array($exp) ? $exp : [];
            } else {
                $data['experiences'] = [];
            }

            return json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取资料失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取请求数据（兼容 application/json 与 form）
     * 小程序端通常发 JSON，$_POST 为空，需从 raw body 解析
     */
    private function getRequestData(): array
    {
        $data = $this->request->param();
        if (empty($data) || (!isset($data['name']) && !isset($data['phone']))) {
            $raw = $this->request->getContent();
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
        return is_array($data) ? $data : [];
    }
    
    /**
     * 规范化出生年月：统一为 YYYY-MM 写入 birth_date（不存 birth_year）
     * 空值时移除键，避免覆盖已有值
     */
    private function normalizeBirthDate(array &$data): void
    {
        $value = $data['birth_date'] ?? $data['birthDate'] ?? '';
        if ($value === '' || $value === null) {
            unset($data['birth_date']);
            return;
        }
        $value = trim((string) $value);
        // 兼容 "2020年03月" 或 "2020-03" 或 "2020-03-01"
        if (preg_match('/^(\d{4})[年\-](\d{1,2})/', $value, $m)) {
            $data['birth_date'] = $m[1] . '-' . str_pad((string)(int)$m[2], 2, '0', STR_PAD_LEFT);
        } elseif (preg_match('/^\d{4}\-\d{2}(?:\-\d{2})?$/', $value)) {
            $data['birth_date'] = substr($value, 0, 7);
        } else {
            $data['birth_date'] = $value;
        }
    }

    /**
     * 已通过审核的教师：本次更新是否改动了任意审核相关内容（与 allowedFields 对齐）
     * 排除 openid、wechat_nickname（账号/微信同步字段，非用户编辑的简历与认证材料）
     */
    private function approvedTeacherAuditContentChanged(Teacher $teacher, array $updateData, array $allowedFields): bool
    {
        $auditFieldKeys = array_values(array_diff($allowedFields, ['openid', 'wechat_nickname']));
        foreach ($auditFieldKeys as $field) {
            if (!array_key_exists($field, $updateData)) {
                continue;
            }
            $oldVal = $teacher->getData($field);
            $newVal = $updateData[$field];
            if (!$this->auditFieldValuesEqual($field, $oldVal, $newVal)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 比较单字段是否与库中一致（JSON 字段做规范化后再比）
     */
    private function auditFieldValuesEqual(string $field, $oldVal, $newVal): bool
    {
        $jsonLike = ['photos', 'experience', 'advantage_tags'];
        if (in_array($field, $jsonLike, true)) {
            return $this->normalizeJsonForAuditCompare($oldVal) === $this->normalizeJsonForAuditCompare($newVal);
        }
        if (in_array($field, ['location_longitude', 'location_latitude', 'hourly_rate'], true)) {
            $o = ($oldVal === null || $oldVal === '') ? null : (float) $oldVal;
            $n = ($newVal === null || $newVal === '') ? null : (float) $newVal;
            if ($o === null && $n === null) {
                return true;
            }
            if ($o === null || $n === null) {
                return false;
            }
            return abs($o - $n) < 0.0000001;
        }
        return trim((string) $oldVal) === trim((string) $newVal);
    }

    /**
     * JSON 存储字段转可比较字符串（空与 null 视为一致）
     */
    private function normalizeJsonForAuditCompare($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $str = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        $decoded = json_decode($str, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return trim($str);
        }
        if (!is_array($decoded)) {
            return trim((string) $value);
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 上传简历并调用 Coze 工作流解析（多格式：PDF/文档/图片）
     * POST teacher-register/parse-resume
     * 入参：file 或 resume（文件上传）二选一，或 resume_url（已可访问的简历文件 URL）
     * 返回：Coze 工作流返回的解析结果（结构化字段 + 专业文字简历等）
     */
    public function parseResume()
    {
        $token = \think\facade\Config::get('coze.resume_token');
        $runUrl = \think\facade\Config::get('coze.resume_run_url');
        if (empty($token) || empty($runUrl)) {
            return json(['success' => false, 'error' => '未配置 Coze 简历解析（COZE_RESUME_TOKEN / COZE_RESUME_RUN_URL）']);
        }

        $resumeUrl = $this->request->param('resume_url', '');
        $fileType = $this->request->param('file_type', '');

        // 若未传 resume_url，则必须上传文件
        if (empty($resumeUrl)) {
            $file = $this->request->file('file') ?: $this->request->file('resume');
            if (!$file) {
                return json(['success' => false, 'error' => '请上传简历文件或传入 resume_url']);
            }
            $allowedExt = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            $ext = strtolower($file->extension());
            if (!in_array($ext, $allowedExt)) {
                return json(['success' => false, 'error' => '仅支持 pdf、doc、docx、jpg、jpeg、png']);
            }
            if ($file->getSize() > 15 * 1024 * 1024) {
                return json(['success' => false, 'error' => '文件大小不能超过 15MB']);
            }
            $uploadPath = app()->getRootPath() . 'public/uploads/resume/';
            $dateDir = date('Ymd');
            $fullPath = $uploadPath . $dateDir . '/';
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            $filename = uniqid() . '.' . $ext;
            $file->move($fullPath, $filename);
            $domain = $this->request->domain();
            $resumeUrl = $domain . '/uploads/resume/' . $dateDir . '/' . $filename;
            if (empty($fileType)) {
                $fileType = $ext === 'doc' || $ext === 'docx' ? 'doc' : ($ext === 'pdf' ? 'pdf' : 'image');
            }
        }

        if (empty($fileType)) {
            $path = parse_url($resumeUrl, PHP_URL_PATH);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $fileType = $ext === 'doc' || $ext === 'docx' ? 'doc' : ($ext === 'pdf' ? 'pdf' : 'image');
        }

        $body = [
            'resume_file' => [
                'url'       => $resumeUrl,
                'file_type' => $fileType,
            ],
        ];

        $ch = curl_init($runUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            \think\facade\Log::error('Coze 简历解析请求失败: ' . $curlError);
            return json(['success' => false, 'error' => '调用解析服务失败：' . $curlError]);
        }
        if ($httpCode !== 200) {
            \think\facade\Log::error('Coze 简历解析 HTTP ' . $httpCode . ': ' . $response);
            return json(['success' => false, 'error' => '解析服务返回错误（' . $httpCode . '）', 'raw' => $response]);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            return json(['success' => false, 'error' => '解析结果格式异常', 'raw' => $response]);
        }

        return json([
            'success' => true,
            'message' => '解析成功',
            'data'    => $data,
        ]);
    }

    /**
     * 简历审核通过待弹窗通知（教师端小程序轮询）
     * 仅用已有字段 review_status + review_time，客户端用本地存储记录已弹过的 review_time，无需建表
     * GET /api/teacher-register/approval-notice?openid=xxx
     */
    public function approvalNotice()
    {
        $openid = $this->request->param('openid', '');
        if ($openid === '') {
            return json(['success' => false, 'error' => '缺少用户标识']);
        }

        try {
            $teacher = Teacher::where('openid', $openid)->find();
            if (!$teacher) {
                return json([
                    'success' => true,
                    'data'    => [
                        'should_prompt' => false,
                        'review_status' => null,
                        'review_time'   => null,
                        'teacher_id'    => null,
                    ],
                ]);
            }

            $status = (string) ($teacher->review_status ?? '');
            $reviewTime = $teacher->review_time;
            $rt = $reviewTime ? (is_string($reviewTime) ? $reviewTime : (string) $reviewTime) : '';

            $should = ($status === 'approved' && $rt !== '');

            return json([
                'success' => true,
                'data'    => [
                    'should_prompt' => $should,
                    'review_status' => $status !== '' ? $status : null,
                    'review_time'   => $rt !== '' ? $rt : null,
                    'teacher_id'    => (int) $teacher->id,
                    'title'         => '简历审核通过',
                    'message'       => '简历通过，请开始您的家教之旅',
                ],
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
