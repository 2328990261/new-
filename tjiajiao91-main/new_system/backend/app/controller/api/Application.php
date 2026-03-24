<?php
namespace app\controller\api;

use app\BaseController;
use app\model\ResumeApplication as ResumeApplicationModel;
use app\model\Teacher as TeacherModel;
use app\model\Tutor as TutorModel;
use think\facade\Db;

class Application extends BaseController
{
    private const MAX_PENDING_APPLICATIONS = 5;

    /**
     * 投递简历
     */
    public function apply()
    {
        // 从 Authorization header 中解析 token 获取用户信息
        $authorization = $this->request->header('Authorization');
        
        if (!$authorization || strpos($authorization, 'Bearer ') !== 0) {
            return json(['success' => false, 'error' => '请先登录']);
        }
        
        $token = substr($authorization, 7);
        
        // 解析 token 获取用户信息（这里简化处理，实际应该验证 token）
        // 从 token 中提取手机号（假设 token 格式为 test_token_timestamp）
        // 实际项目中应该使用 JWT 或其他方式存储用户信息
        
        // 临时方案：从 storage 中获取用户信息
        $userInfo = $this->request->post('userInfo'); // 前端传递用户信息
        
        if (!$userInfo || empty($userInfo['phone'])) {
            return json(['success' => false, 'error' => '请先登录']);
        }
        
        // 获取参数
        $tutorId = $this->request->post('tutor_id');
        
        // 记录日志
        trace('投递简历请求参数: tutor_id=' . $tutorId . ', phone=' . $userInfo['phone'], 'info');
        
        if (empty($tutorId)) {
            return json(['success' => false, 'error' => '缺少必要参数：tutor_id']);
        }
        
        try {
            // 通过手机号查找教师
            $teacher = TeacherModel::where('phone', $userInfo['phone'])->find();
            if (!$teacher) {
                return json(['success' => false, 'error' => '未找到对应的教师账号']);
            }
            
            $teacherId = $teacher->id;
            trace('找到教师: teacher_id=' . $teacherId . ', name=' . $teacher->name, 'info');
            
            // 限制：待审核投递最多只能有 5 份
            $pendingCount = ResumeApplicationModel::where('teacher_id', $teacherId)
                ->where('status', 'pending')
                ->count();
            if ($pendingCount >= self::MAX_PENDING_APPLICATIONS) {
                return json([
                    'success' => false,
                    'error' => '待审核的投递最多只能保留' . self::MAX_PENDING_APPLICATIONS . '份，请先等待审核或取消部分待审核投递后再试'
                ]);
            }

            // 查询家教订单信息
            $tutor = Db::name('tutor_orders_new')->where('id', $tutorId)->find();
            
            if (!$tutor) {
                return json(['success' => false, 'error' => '家教订单不存在']);
            }
            
            // 检查是否已经投递过
            $existingApplication = ResumeApplicationModel::where('teacher_id', $teacherId)
                ->where('tutor_id', $tutorId)
                ->find();
            
            if ($existingApplication) {
                return json(['success' => false, 'error' => '您已经投递过该岗位']);
            }
            
            // 创建投递记录
            $application = new ResumeApplicationModel();
            $application->teacher_id = $teacherId;
            $application->tutor_id = $tutorId;
            $application->status = 'pending';
            $application->apply_time = date('Y-m-d H:i:s');
            $application->save();
            
            return json([
                'success' => true,
                'message' => '投递成功',
                'data' => [
                    'id' => $application->id
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('投递失败: ' . $e->getMessage(), 'error');
            return json([
                'success' => false,
                'error' => '投递失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取我的投递记录
     */
    public function myList()
    {
        // 从 Authorization header 中解析 token
        $authorization = $this->request->header('Authorization');
        
        if (!$authorization || strpos($authorization, 'Bearer ') !== 0) {
            return json(['success' => false, 'error' => '请先登录']);
        }
        
        // 从 URL 参数中获取手机号
        $phone = $this->request->get('phone');
        
        if (!$phone) {
            return json(['success' => false, 'error' => '请先登录']);
        }
        
        try {
            // 通过手机号查找教师
            $teacher = TeacherModel::where('phone', $phone)->find();
            if (!$teacher) {
                return json(['success' => false, 'error' => '未找到对应的教师账号']);
            }
            
            $teacherId = $teacher->id;
            
            // 获取筛选参数
            $status = $this->request->get('status', '');
            
            // 构建查询 - 只返回当前教师的投递记录
            $query = ResumeApplicationModel::alias('ra')
                ->leftJoin('fa_tutor_orders_new t', 't.id = ra.tutor_id')
                ->leftJoin('fa_subjects s', 's.id = t.subject_id')
                ->where('ra.teacher_id', $teacherId)
                ->field([
                    'ra.id',
                    'ra.teacher_id',
                    'ra.tutor_id',
                    'ra.status',
                    'ra.apply_time',
                    'ra.review_time',
                    'ra.admin_remark',
                    't.content as tutor_content',
                    's.name as tutor_subject',
                    't.grade as tutor_grade',
                    't.salary as tutor_salary'
                ])
                ->order('ra.apply_time', 'desc');
            
            // 状态筛选
            if (!empty($status)) {
                $query->where('ra.status', $status);
            }
            
            $list = $query->select()->toArray();
            
            // 为每条记录生成标题
            foreach ($list as &$item) {
                // 从 content 中提取信息生成标题
                $content = $item['tutor_content'] ?? '';
                $grade = $item['tutor_grade'] ?? '';
                
                // 简单处理：使用年级作为标题的一部分
                if ($grade) {
                    $item['tutor_title'] = $grade . ' 家教辅导';
                } else {
                    $item['tutor_title'] = '家教辅导';
                }
                
                // 如果 content 中包含科目信息，尝试提取
                if (preg_match('/([语数英物化生政史地][\w]*)/u', $content, $matches)) {
                    $item['tutor_title'] = $grade . ' ' . $matches[1];
                }
            }
            
            // 统计数据 - 只统计当前教师的
            $statistics = [
                'total' => ResumeApplicationModel::where('teacher_id', $teacherId)->count(),
                'pending' => ResumeApplicationModel::where('teacher_id', $teacherId)->where('status', 'pending')->count(),
                'approved' => ResumeApplicationModel::where('teacher_id', $teacherId)->where('status', 'approved')->count(),
                'rejected' => ResumeApplicationModel::where('teacher_id', $teacherId)->where('status', 'rejected')->count()
            ];
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $list,
                    'statistics' => $statistics
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '查询失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取投递详情
     */
    public function detail()
    {
        // 🔴 临时移除所有登录验证
        
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        try {
            // 不关联教师表，只查询投递记录和家教订单信息
            $application = ResumeApplicationModel::alias('ra')
                ->leftJoin('fa_tutor_orders_new t', 't.id = ra.tutor_id')
                ->where('ra.id', $id)
                ->field([
                    'ra.*',
                    't.content as tutor_content',
                    't.grade as tutor_grade',
                    't.salary as tutor_salary'
                ])
                ->find();
            
            if (!$application) {
                return json(['success' => false, 'error' => '投递记录不存在']);
            }
            
            // 生成标题
            $content = $application['tutor_content'] ?? '';
            $grade = $application['tutor_grade'] ?? '';
            
            if ($grade) {
                $application['tutor_title'] = $grade . ' 家教辅导';
            } else {
                $application['tutor_title'] = '家教辅导';
            }
            
            // 如果 content 中包含科目信息，尝试提取
            if (preg_match('/([语数英物化生政史地][\w]*)/u', $content, $matches)) {
                $application['tutor_title'] = $grade . ' ' . $matches[1];
            }
            
            return json([
                'success' => true,
                'data' => $application
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '查询失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 取消投递
     */
    public function cancel()
    {
        // 🔴 临时移除所有登录验证
        
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['success' => false, 'error' => '缺少必要参数']);
        }
        
        try {
            // 查询投递记录
            $application = ResumeApplicationModel::where('id', $id)->find();
            
            if (!$application) {
                return json(['success' => false, 'error' => '投递记录不存在']);
            }
            
            // 只有待审核状态才能取消
            if ($application->status !== 'pending') {
                return json(['success' => false, 'error' => '该投递记录无法取消']);
            }
            
            // 删除投递记录
            $application->delete();
            
            return json([
                'success' => true,
                'message' => '取消成功'
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '取消失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 根据订单ID获取投递列表（小程序预约详情页使用）
     */
    public function listByOrder()
    {
        $orderId = $this->request->get('order_id');
        
        if (empty($orderId)) {
            return json(['success' => false, 'error' => '缺少订单ID']);
        }
        
        try {
            // 这里暂时返回空数组，因为小程序的预约订单(parent_orders)和投递系统(resume_application)
            // 是两个不同的业务模块，预约订单不会有投递记录
            // 如果后续需要关联，需要在数据库设计上做调整
            
            return json([
                'success' => true,
                'data' => []
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '查询失败：' . $e->getMessage()
            ]);
        }
    }
}
