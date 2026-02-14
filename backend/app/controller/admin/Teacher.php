<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Teacher as TeacherModel;
use think\facade\Session;

/**
 * 教师管理控制器
 */
class Teacher extends BaseController
{
    /**
     * 获取教师列表
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $keyword = $this->request->get('keyword');
            $status = $this->request->get('status');
            $reviewStatus = $this->request->get('review_status'); // 审核状态筛选
            $teacherType = $this->request->get('teacher_type'); // 教师类型筛选
            $school = $this->request->get('school'); // 学校筛选
            $isTop = $this->request->get('is_top'); // 置顶筛选
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 构建查询条件
            $where = [];
            
            if ($keyword) {
                $where[] = function($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                          ->whereOr('phone', 'like', '%' . $keyword . '%')
                          ->whereOr('wechat_id', 'like', '%' . $keyword . '%');
                };
            }
            
            if ($status) {
                $where[] = ['status', '=', $status];
            }
            
            if ($reviewStatus) {
                $where[] = ['review_status', '=', $reviewStatus];
            }
            
            if ($teacherType) {
                $where[] = ['teacher_type', '=', $teacherType];
            }
            
            if ($school) {
                $where[] = ['school', 'like', '%' . $school . '%'];
            }
            
            if ($isTop !== '' && $isTop !== null) {
                $where[] = ['is_top', '=', intval($isTop)];
            }
            
            // 获取总数
            $total = TeacherModel::where($where)->count();
            
            // 获取数据列表
            $list = TeacherModel::where($where)
                ->order('is_top', 'desc')
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            // 处理数据，确保字段格式正确
            $data = [];
            foreach ($list as $item) {
                $itemArray = $item->toArray();
                
                // 确保 photos 字段返回正确的结构
                if (isset($itemArray['photos'])) {
                    $photos = $item->photos; // 使用获取器
                    $itemArray['avatar'] = $photos['avatar'] ?? '';
                    $itemArray['teaching_photos'] = $photos['teaching_photos'] ?? [];
                }
                
                // 使用获取器返回解析后的experience数组
                $itemArray['experience'] = $item->experience ?? [];
                
                // 确保认证字段为整数类型
                $itemArray['real_name_verified'] = (int)($itemArray['real_name_verified'] ?? 0);
                $itemArray['education_verified'] = (int)($itemArray['education_verified'] ?? 0);
                $itemArray['teacher_verified'] = (int)($itemArray['teacher_verified'] ?? 0);
                $itemArray['is_top'] = (int)($itemArray['is_top'] ?? 0);
                
                // 确保所有字段都存在，即使为空
                $itemArray['self_intro'] = $itemArray['self_intro'] ?? '';
                $itemArray['wechat_nickname'] = $itemArray['wechat_nickname'] ?? '';
                $itemArray['openid'] = $itemArray['openid'] ?? '';
                $itemArray['last_login_time'] = $itemArray['last_login_time'] ?? null;
                
                $data[] = $itemArray;
            }
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $data,
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取教师详情
     */
    public function read($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $teacher = TeacherModel::find($id);
            
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $data = $teacher->toArray();
            
            // 确保 photos 字段返回正确的结构
            if (isset($data['photos'])) {
                $photos = $teacher->photos; // 使用获取器
                $data['avatar'] = $photos['avatar'] ?? '';
                $data['teaching_photos'] = $photos['teaching_photos'] ?? [];
            }
            
            // 使用获取器返回解析后的experience数组
            $data['experience'] = $teacher->experience ?? [];
            
            // 确保认证字段为整数类型
            $data['real_name_verified'] = (int)($data['real_name_verified'] ?? 0);
            $data['education_verified'] = (int)($data['education_verified'] ?? 0);
            $data['teacher_verified'] = (int)($data['teacher_verified'] ?? 0);
            $data['is_top'] = (int)($data['is_top'] ?? 0);
            
            // 确保所有字段都存在，即使为空
            $data['self_intro'] = $data['self_intro'] ?? '';
            $data['wechat_nickname'] = $data['wechat_nickname'] ?? '';
            $data['openid'] = $data['openid'] ?? '';
            $data['last_login_time'] = $data['last_login_time'] ?? null;
            
            return json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新教师状态
     */
    public function updateStatus($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $status = $this->request->post('status');
            
            if (!in_array($status, ['active', 'inactive', 'disabled'])) {
                return json(['success' => false, 'error' => '状态参数错误']);
            }
            
            $teacher = TeacherModel::find($id);
            
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $teacher->status = $status;
            $teacher->save();
            
            return json([
                'success' => true,
                'message' => '状态更新成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 审核教师
     */
    public function review($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $status = $this->request->post('status');
            $reason = $this->request->post('reason', '');
            $realNameVerified = $this->request->post('real_name_verified');
            $educationVerified = $this->request->post('education_verified');
            $teacherVerified = $this->request->post('teacher_verified');
            
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                return json(['success' => false, 'error' => '无效的审核状态']);
            }
            
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            // 更新审核状态
            $teacher->review_status = $status;
            $teacher->review_note = $reason;
            $teacher->review_time = date('Y-m-d H:i:s');
            $teacher->reviewer_id = $_SESSION['admin_id'];
            
            // 更新各项认证状态
            if ($realNameVerified !== null) {
                $teacher->real_name_verified = intval($realNameVerified);
            }
            if ($educationVerified !== null) {
                $teacher->education_verified = intval($educationVerified);
            }
            if ($teacherVerified !== null) {
                $teacher->teacher_verified = intval($teacherVerified);
            }
            
            $teacher->save();
            
            $statusText = [
                'pending' => '待审核',
                'approved' => '审核通过',
                'rejected' => '审核拒绝'
            ];
            
            return json([
                'success' => true,
                'message' => '审核状态已更新为：' . $statusText[$status]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 设置教师置顶
     */
    public function setTop($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $isTop = $this->request->post('is_top', 0);
            
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $teacher->is_top = $isTop ? 1 : 0;
            $teacher->save();
            
            return json([
                'success' => true,
                'message' => $isTop ? '置顶成功' : '取消置顶成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 删除教师
     */
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $teacher->delete();
            
            return json([
                'success' => true,
                'message' => '删除成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 批量删除教师
     */
    public function batchDelete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $ids = $this->request->post('ids');
            
            if (empty($ids) || !is_array($ids)) {
                return json(['success' => false, 'error' => '请选择要删除的教师']);
            }
            
            $count = TeacherModel::whereIn('id', $ids)->delete();
            
            return json([
                'success' => true,
                'message' => "成功删除 {$count} 条记录"
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 批量更新状态
     */
    public function batchUpdateStatus()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $ids = $this->request->post('ids');
            $status = $this->request->post('status');
            
            if (empty($ids) || !is_array($ids)) {
                return json(['success' => false, 'error' => '请选择要更新的教师']);
            }
            
            if (!in_array($status, ['active', 'inactive', 'disabled'])) {
                return json(['success' => false, 'error' => '状态参数错误']);
            }
            
            $count = TeacherModel::whereIn('id', $ids)->update(['status' => $status]);
            
            return json([
                'success' => true,
                'message' => "成功更新 {$count} 条记录"
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新教师信息
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $data = $this->request->post();
            
            // 允许更新的字段
            $allowedFields = [
                'name', 'gender', 'phone', 'wechat_id', 'wechat_nickname', 'openid', 'email',
                'hometown', 'teaching_years', 'birth_year',
                'location_province', 'location_city', 'location_district', 'location_address',
                'education', 'school', 'major', 'teacher_type', 'grade_level', 'education_level',
                'hourly_rate', 'subject_ids', 'subject_names', 'district_ids', 'district_names',
                'experience', 'experiences', 'self_intro', 'personal_advantage',
                'advantage_tags', 'photos', 'avatar', 'teaching_photos',
                'status', 'is_top'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // 处理教学经历
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
                $updateData['experience'] = $validExperiences;
                unset($updateData['experiences']);
            }
            
            // 处理照片
            if (isset($data['avatar']) || isset($data['teaching_photos'])) {
                $photosData = [
                    'avatar' => $data['avatar'] ?? $teacher->photos['avatar'] ?? '',
                    'teaching_photos' => $data['teaching_photos'] ?? $teacher->photos['teaching_photos'] ?? []
                ];
                $updateData['photos'] = $photosData;
                unset($updateData['avatar']);
                unset($updateData['teaching_photos']);
            }
            
            $teacher->save($updateData);
            
            return json([
                'success' => true,
                'message' => '更新成功',
                'data' => $teacher
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取教师统计信息
     */
    public function statistics()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $total = TeacherModel::count();
            $active = TeacherModel::where('status', 'active')->count();
            $inactive = TeacherModel::where('status', 'inactive')->count();
            $disabled = TeacherModel::where('status', 'disabled')->count();
            $pending = TeacherModel::where('review_status', 'pending')->count();
            $approved = TeacherModel::where('review_status', 'approved')->count();
            $rejected = TeacherModel::where('review_status', 'rejected')->count();
            
            // 按教师类型统计
            $typeStats = TeacherModel::field('teacher_type, COUNT(*) as count')
                ->group('teacher_type')
                ->select()
                ->toArray();
            
            // 按学校统计（前10）
            $schoolStats = TeacherModel::field('school, COUNT(*) as count')
                ->where('school', '<>', '')
                ->group('school')
                ->order('count', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                    'disabled' => $disabled,
                    'pending' => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'type_stats' => $typeStats,
                    'school_stats' => $schoolStats
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

