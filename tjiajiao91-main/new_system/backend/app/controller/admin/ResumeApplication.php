<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\ResumeApplication as ApplicationModel;
use app\service\SubscribeMessageService;
use think\facade\Db;
use think\facade\Log;

/**
 * 简历投递管理控制器
 */
class ResumeApplication extends BaseController
{
    /**
     * 应用「我的投递」筛选条件（与家教信息“我的订单”逻辑一致）
     * 只按录入者筛选：o.admin_id = 当前登录管理员
     */
    private function applyMineFilter($query)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $adminId = $_SESSION['admin_id'] ?? null;
        if (!$adminId) {
            return $query;
        }
        $query->where('o.admin_id', $adminId);
        return $query;
    }

    /**
     * 获取投递列表
     * view_scope: mine=我的投递（按录入者过滤）, all=全部投递
     */
    public function index()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $page = $this->request->param('page', 1);
            $pageSize = $this->request->param('page_size', 20);
            $viewScope = $this->request->param('view_scope', 'mine');
            $status = $this->request->param('status', '');
            $teacherName = $this->request->param('teacher_name', '');
            $tutorTitle = $this->request->param('tutor_title', '');
            $startTime = $this->request->param('start_time', '');
            $endTime = $this->request->param('end_time', '');
            
            // 构建查询
            $query = Db::name('resume_application')->alias('ra')
                ->leftJoin('teachers t', 'ra.teacher_id = t.id')
                ->leftJoin('tutor_orders_new o', 'ra.tutor_id = o.id')
                ->leftJoin('admin a', 'ra.reviewer_id = a.id')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->leftJoin('districts d', 'o.district_id = d.id')
                ->leftJoin('subjects s', 'o.subject_id = s.id')
                ->field('ra.*, t.name as teacher_name, t.phone as teacher_phone, 
                        t.education as teacher_education, t.school as teacher_school,
                        t.subject_names as teacher_subjects,
                        o.content as tutor_title, o.grade as tutor_grade, 
                        o.salary as tutor_salary, s.name as tutor_subject,
                        c.name as tutor_city, d.name as tutor_district,
                        a.nickname as reviewer_name');

            // 我的投递 / 全部投递
            if ($viewScope === 'mine') {
                $this->applyMineFilter($query);
            }
            
            // 状态筛选
            if ($status) {
                $query->where('ra.status', $status);
            }
            
            // 教师姓名筛选
            if ($teacherName) {
                $query->where('t.name', 'like', "%{$teacherName}%");
            }
            
            // 家教标题筛选
            if ($tutorTitle) {
                $query->where('o.content', 'like', "%{$tutorTitle}%");
            }
            
            // 时间范围筛选
            if ($startTime && $endTime) {
                $query->whereBetweenTime('ra.apply_time', $startTime, $endTime);
            }
            
            // 按状态优先级排序：pending(待审核) > approved(已通过) > rejected(已拒绝)
            // 使用 FIELD 函数自定义排序顺序，然后按投递时间降序
            $list = $query->orderRaw("FIELD(ra.status, 'pending', 'approved', 'rejected')")
                ->order('ra.apply_time', 'desc')
                ->paginate([
                    'list_rows' => $pageSize,
                    'page' => $page
                ]);
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $list->items(),
                    'total' => $list->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取投递列表失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取投递详情
     */
    public function read($id)
    {
        try {
            $application = Db::name('resume_application')->alias('ra')
                ->leftJoin('teachers t', 'ra.teacher_id = t.id')
                ->leftJoin('tutor_orders_new o', 'ra.tutor_id = o.id')
                ->leftJoin('admin a', 'ra.reviewer_id = a.id')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->leftJoin('districts d', 'o.district_id = d.id')
                ->leftJoin('subjects s', 'o.subject_id = s.id')
                ->field('ra.*, t.name as teacher_name, t.phone as teacher_phone, 
                        t.education as teacher_education, t.school as teacher_school,
                        t.subject_names as teacher_subjects,
                        o.content as tutor_title, o.grade as tutor_grade, 
                        o.salary as tutor_salary, s.name as tutor_subject,
                        c.name as tutor_city, d.name as tutor_district,
                        a.nickname as reviewer_name')
                ->where('ra.id', $id)
                ->find();
            
            if (!$application) {
                return json([
                    'code' => 404,
                    'message' => '投递记录不存在'
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $application
            ]);
        } catch (\Exception $e) {
            Log::error('获取投递详情失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 审核投递（通过/拒绝）
     */
    public function review()
    {
        // 添加测试日志
        Log::info('========== 审核投递方法被调用 ==========');
        Log::info('请求参数: ' . json_encode($this->request->param()));
        
        try {
            $id = $this->request->param('id');
            $status = $this->request->param('status'); // approved 或 rejected
            $remark = $this->request->param('remark', '');
            
            Log::info('审核参数: id=' . $id . ', status=' . $status . ', remark=' . $remark);
            
            if (!in_array($status, ['approved', 'rejected'])) {
                return json([
                    'code' => 400,
                    'message' => '状态参数错误'
                ]);
            }
            
            $application = ApplicationModel::find($id);
            if (!$application) {
                return json([
                    'code' => 404,
                    'message' => '投递记录不存在'
                ]);
            }
            
            // 获取当前登录管理员ID
            $adminId = $this->request->adminId ?? null;
            
            // 更新状态
            $application->status = $status;
            $application->admin_remark = $remark;
            $application->review_time = date('Y-m-d H:i:s');
            $application->reviewer_id = $adminId;
            $application->save();
            
            // 发送订阅消息通知
            try {
                Log::info('=== 准备调用 sendApplicationNotification ===');
                $this->sendApplicationNotification($application, $status, $remark);
                Log::info('=== sendApplicationNotification 调用完成 ===');
            } catch (\Exception $e) {
                Log::error('调用 sendApplicationNotification 异常: ' . $e->getMessage());
            }
            
            // 发送邮件通知给客服、客服组长和家长组
            try {
                Log::info('=== 准备发送邮件通知 ===');
                \app\service\EmailService::sendApplicationReviewNotification($application, $status, $remark);
                Log::info('=== 邮件通知发送完成 ===');
            } catch (\Exception $e) {
                Log::error('发送邮件通知异常: ' . $e->getMessage());
            }
            
            $statusText = $status === 'approved' ? '通过' : '拒绝';
            
            return json([
                'code' => 200,
                'message' => "审核{$statusText}成功"
            ]);
        } catch (\Exception $e) {
            Log::error('审核投递失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '审核失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 批量审核
     */
    public function batchReview()
    {
        try {
            $ids = $this->request->param('ids', []);
            $status = $this->request->param('status');
            $remark = $this->request->param('remark', '');
            
            if (empty($ids) || !is_array($ids)) {
                return json([
                    'code' => 400,
                    'message' => '请选择要审核的记录'
                ]);
            }
            
            if (!in_array($status, ['approved', 'rejected'])) {
                return json([
                    'code' => 400,
                    'message' => '状态参数错误'
                ]);
            }
            
            // 获取当前登录管理员ID
            $adminId = $this->request->adminId ?? null;
            
            // 获取所有要审核的记录，用于发送通知
            $applications = ApplicationModel::whereIn('id', $ids)->select();
            
            ApplicationModel::whereIn('id', $ids)->update([
                'status' => $status,
                'admin_remark' => $remark,
                'review_time' => date('Y-m-d H:i:s'),
                'reviewer_id' => $adminId
            ]);
            
            // 批量发送订阅消息通知
            foreach ($applications as $application) {
                $this->sendApplicationNotification($application, $status, $remark);
            }
            
            // 批量发送邮件通知给客服、客服组长和家长组
            foreach ($applications as $application) {
                try {
                    \app\service\EmailService::sendApplicationReviewNotification($application, $status, $remark);
                } catch (\Exception $e) {
                    Log::error('批量发送邮件通知异常: application_id=' . $application->id . ', error=' . $e->getMessage());
                }
            }
            
            $statusText = $status === 'approved' ? '通过' : '拒绝';
            
            return json([
                'code' => 200,
                'message' => "批量审核{$statusText}成功"
            ]);
        } catch (\Exception $e) {
            Log::error('批量审核失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '批量审核失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取统计数据
     */
    public function statistics()
    {
        try {
            $total = ApplicationModel::count();
            $pending = ApplicationModel::where('status', 'pending')->count();
            $approved = ApplicationModel::where('status', 'approved')->count();
            $rejected = ApplicationModel::where('status', 'rejected')->count();

            // 我的投递（按录入者过滤，和家教信息“我的订单”一致）
            $mineQuery = Db::name('resume_application')->alias('ra')
                ->leftJoin('tutor_orders_new o', 'ra.tutor_id = o.id');
            $this->applyMineFilter($mineQuery);
            $mine = (clone $mineQuery)->count();
            $minePending = (clone $mineQuery)->where('ra.status', 'pending')->count();
            $mineApproved = (clone $mineQuery)->where('ra.status', 'approved')->count();
            $mineRejected = (clone $mineQuery)->where('ra.status', 'rejected')->count();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'mine' => (int) $mine,
                    'total' => $total,
                    'pending' => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    // 我的投递：分状态统计（前端“我的投递”视图使用）
                    'mine_pending' => (int) $minePending,
                    'mine_approved' => (int) $mineApproved,
                    'mine_rejected' => (int) $mineRejected
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取统计数据失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 删除投递记录（改为标记为"未通过"，不真删除）
     */
    public function delete($id)
    {
        try {
            $application = ApplicationModel::find($id);
            if (!$application) {
                return json([
                    'code' => 404,
                    'message' => '投递记录不存在'
                ]);
            }
            
            // 改为标记为"未通过"，而不是真删除
            $application->status = 'rejected';
            $application->admin_remark = '管理员已删除此投递';
            $application->review_time = date('Y-m-d H:i:s');
            // 获取当前登录管理员ID
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $adminId = $_SESSION['admin_id'] ?? null;
            $application->reviewer_id = $adminId;
            $application->save();
            
            return json([
                'code' => 200,
                'message' => '删除成功'
            ]);
        } catch (\Exception $e) {
            Log::error('删除投递记录失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 发送简历投递审核通知
     */
    private function sendApplicationNotification($application, $status, $remark)
    {
        try {
            Log::info('开始发送简历投递审核通知: application_id=' . $application->id . ', status=' . $status);
            
            // 获取教师的openid
            $teacher = Db::name('teachers')->where('id', $application->teacher_id)->find();
            Log::info('教师信息: ' . json_encode($teacher));
            
            if (!$teacher || empty($teacher['openid'])) {
                Log::warning('教师openid为空，无法发送订阅消息: teacher_id=' . $application->teacher_id);
                return;
            }
            
            // 获取家教订单信息
            $tutor = Db::name('tutor_orders_new')->alias('o')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->leftJoin('districts d', 'o.district_id = d.id')
                ->leftJoin('subjects s', 'o.subject_id = s.id')
                ->field('o.*, c.name as city_name, d.name as district_name, s.name as subject_name')
                ->where('o.id', $application->tutor_id)
                ->find();
            
            if (!$tutor) {
                Log::warning('家教订单不存在，无法发送订阅消息: tutor_id=' . $application->tutor_id);
                return;
            }
            
            // 构建家教信息摘要
            $tutorInfo = ($tutor['grade'] ?? '') . ' ' . ($tutor['subject_name'] ?? '') . ' ' . 
                        ($tutor['city_name'] ?? '') . ($tutor['district_name'] ? ' ' . $tutor['district_name'] : '');
            
            Log::info('家教信息: ' . $tutorInfo);
            
            if ($status === 'approved') {
                // 审核通过：发送通过通知
                // 获取派单员信息
                $dispatcher = Db::name('admin')->where('id', $tutor['admin_id'] ?? 0)->find();
                $dispatcherName = $dispatcher ? ($dispatcher['nickname'] ?? $dispatcher['username'] ?? '派单员') : '派单员';
                $dispatcherContact = $dispatcher ? ($dispatcher['phone'] ?? $dispatcher['contact'] ?? '') : '';
                
                Log::info('准备发送通过通知: openid=' . $teacher['openid'] . ', contact=' . $dispatcherContact);
                
                $result = SubscribeMessageService::sendApplicationAuditMessage($teacher['openid'], [
                    'tutor_info' => $tutorInfo,
                    'audit_result' => '审核通过',
                    'recommender' => $dispatcherName,
                    'contact_phone' => $dispatcherContact, // 可以为空，服务层会处理
                    'audit_time' => $application->review_time ?? date('Y-m-d H:i:s'),
                    'application_id' => $application->id
                ]);
                
                Log::info('发送通过通知结果: ' . json_encode($result));
            } else {
                // 审核驳回：发送驳回通知
                // 获取派单员信息
                $dispatcher = Db::name('admin')->where('id', $tutor['admin_id'] ?? 0)->find();
                $dispatcherName = $dispatcher ? ($dispatcher['nickname'] ?? $dispatcher['username'] ?? '派单员') : '派单员';
                $dispatcherContact = $dispatcher ? ($dispatcher['phone'] ?? $dispatcher['contact'] ?? '') : '';
                
                Log::info('准备发送驳回通知: openid=' . $teacher['openid'] . ', contact=' . $dispatcherContact);
                
                $result = SubscribeMessageService::sendApplicationAuditMessage($teacher['openid'], [
                    'tutor_info' => $tutorInfo,
                    'audit_result' => '审核驳回',
                    'recommender' => $dispatcherName,
                    'contact_phone' => $dispatcherContact, // 可以为空，服务层会处理
                    'audit_time' => $application->review_time ?? date('Y-m-d H:i:s'),
                    'application_id' => $application->id
                ]);
                
                Log::info('发送驳回通知结果: ' . json_encode($result));
            }
        } catch (\Exception $e) {
            Log::error('发送简历投递审核通知失败: ' . $e->getMessage());
            Log::error('异常堆栈: ' . $e->getTraceAsString());
            // 不抛出异常，避免影响主流程
        }
    }
}
