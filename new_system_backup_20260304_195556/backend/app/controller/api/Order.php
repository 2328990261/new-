<?php
namespace app\controller\api;

use app\BaseController;
use app\model\ParentOrder;
use app\model\TutorOrder;
use app\model\Admin;
use app\service\EmailService;
use think\facade\Db;
use think\facade\Validate;

/**
 * 家长预约订单控制器（API）
 */
class Order extends BaseController
{
    /**
     * 提交家长预约（公开接口）
     * POST /api/order/booking
     */
    public function booking()
    {
        try {
            $data = $this->request->post();
            
            // 数据验证
            $validate = Validate::rule([
                'admin_id'            => 'require|number',
                'grade'               => 'require',
                'subject'             => 'require',
                'student_info'        => 'require',
                'frequency'           => 'require',
                'teacher_requirement' => 'require',
                'address'             => 'require',
                'parent_name'         => 'require',
                'parent_contact'      => 'require',
            ])->message([
                'admin_id.require'            => '管理员ID不能为空',
                'grade.require'               => '学员年级不能为空',
                'subject.require'             => '辅导科目不能为空',
                'student_info.require'        => '学生情况不能为空',
                'frequency.require'           => '辅导频率不能为空',
                'teacher_requirement.require' => '老师要求不能为空',
                'address.require'             => '授课地址不能为空',
                'parent_name.require'         => '家长称呼不能为空',
                'parent_contact.require'      => '联系方式不能为空',
            ]);
            
            if (!$validate->check($data)) {
                return json(['code' => 400, 'message' => $validate->getError()]);
            }
            
            // 验证管理员是否存在
            $admin = Admin::find($data['admin_id']);
            if (!$admin) {
                return json(['code' => 400, 'message' => '管理员不存在']);
            }
            
            // 生成订单号
            $data['order_no'] = ParentOrder::generateOrderNo();
            $data['status'] = 'pending';
            
            // 创建订单
            $order = ParentOrder::create($data);
            
            // 发送邮件通知给管理员
            try {
                if ($admin->email) {
                    EmailService::sendBookingNotification($admin, $order);
                }
            } catch (\Exception $e) {
                // 邮件发送失败不影响订单创建
                trace('邮件发送失败: ' . $e->getMessage(), 'error');
            }
            
            return json([
                'code' => 200,
                'message' => '预约提交成功',
                'data' => [
                    'order_no' => $order->order_no
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('预约提交失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '预约提交失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取订单列表（管理员）
     * GET /api/order/list
     */
    public function list()
    {
        try {
            // 验证管理员登录
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $status = $this->request->get('status', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            // 超级管理员可以查看所有订单，其他管理员只能查看自己的订单
            $query = ParentOrder::with(['admin' => function($query) {
                    $query->field('id,username,nickname');
                }])
                ->order('create_time', 'desc');
            
            if (!$this->isSuperAdmin()) {
                // 非超级管理员只能查看归属于自己的订单
                $query->where('admin_id', $admin->id);
            }
            
            // 筛选状态
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }
            
            // 分页查询
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ]);
            
            // 禁用缓存
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $result->items(),
                'total' => $result->total()
            ]);
            
        } catch (\Exception $e) {
            trace('获取订单列表失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取订单列表失败']);
        }
    }
    
    /**
     * 获取订单统计（管理员）
     * GET /api/order/stats
     */
    public function stats()
    {
        try {
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 超级管理员查看所有订单统计，其他管理员只能查看自己的
            if ($this->isSuperAdmin()) {
                $all = ParentOrder::count();
                $pending = ParentOrder::where('status', 'pending')->count();
                $rejected = ParentOrder::where('status', 'rejected')->count();
            } else {
                $all = ParentOrder::where('admin_id', $admin->id)->count();
                $pending = ParentOrder::where('admin_id', $admin->id)->where('status', 'pending')->count();
                $rejected = ParentOrder::where('admin_id', $admin->id)->where('status', 'rejected')->count();
            }
            
            // 禁用缓存
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'all' => $all,
                    'pending' => $pending,
                    'rejected' => $rejected
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取订单统计失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取订单统计失败']);
        }
    }
    
    /**
     * 获取订单详情
     * GET /api/order/:id
     */
    public function detail($id)
    {
        try {
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 超级管理员可以查看所有订单，其他管理员只能查看自己的订单
            $query = ParentOrder::where('id', $id);
            
            if (!$this->isSuperAdmin()) {
                $query->where('admin_id', $admin->id);
            }
            
            $order = $query->find();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在或无权访问']);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            trace('获取订单详情失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取订单详情失败']);
        }
    }
    
    /**
     * 审核通过订单
     * POST /api/order/:id/approve
     */
    public function approve($id)
    {
        try {
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 查找订单（超级管理员可以操作所有订单）
            $query = ParentOrder::where('id', $id);
            
            if (!$this->isSuperAdmin()) {
                $query->where('admin_id', $admin->id);
            }
            
            $order = $query->find();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在或无权访问']);
            }
            
            if ($order->status !== 'pending') {
                return json(['code' => 400, 'message' => '订单状态不正确']);
            }
            
            // 开启事务
            Db::startTrans();
            try {
                // 生成家教信息 ID
                $tutorId = TutorOrder::generateOrderId();
                
                // 创建家教信息（不包含家长隐私信息）
                // 注意：city_id、district_id、subject_id 暂时设置为 NULL
                // 后续管理员可以在家教信息管理中补充完善这些信息
                $tutorData = [
                    'id' => $tutorId,
                    'content' => $this->buildTutorContent($order),
                    'grade' => $order->grade,
                    'city_id' => null,
                    'district_id' => null,
                    'subject_id' => null,
                    'salary' => null,
                    'admin_id' => $order->admin_id, // 使用订单原有的 admin_id
                    'is_urgent' => 0,
                    'status' => 1
                ];
                
                $tutor = TutorOrder::create($tutorData);
                
                // 更新订单状态
                $order->status = 'approved';
                $order->tutor_id = $tutor->id;
                $order->audit_time = date('Y-m-d H:i:s');
                $order->save();
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => '审核通过，家教信息已发布',
                    'data' => [
                        'tutor_id' => $tutor->id
                    ]
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            trace('审核通过失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '审核通过失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 拒绝订单
     * POST /api/order/:id/reject
     */
    public function reject($id)
    {
        try {
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $reason = $this->request->post('reason', '');
            if (empty($reason)) {
                return json(['code' => 400, 'message' => '请输入拒绝原因']);
            }
            
            // 查找订单（超级管理员可以操作所有订单）
            $query = ParentOrder::where('id', $id);
            
            if (!$this->isSuperAdmin()) {
                $query->where('admin_id', $admin->id);
            }
            
            $order = $query->find();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在或无权访问']);
            }
            
            if ($order->status !== 'pending') {
                return json(['code' => 400, 'message' => '订单状态不正确']);
            }
            
            // 更新订单状态
            $order->status = 'rejected';
            $order->reject_reason = $reason;
            $order->audit_time = date('Y-m-d H:i:s');
            $order->save();
            
            return json([
                'code' => 200,
                'message' => '已拒绝该订单'
            ]);
            
        } catch (\Exception $e) {
            trace('拒绝订单失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '拒绝订单失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 构建家教信息内容（不包含隐私信息）
     */
    private function buildTutorContent($order)
    {
        $content = "【学员年级】{$order->grade}\n";
        $content .= "【辅导科目】{$order->subject}\n";
        $content .= "【学生情况】{$order->student_info}\n";
        $content .= "【辅导频率】{$order->frequency}\n";
        $content .= "【老师要求】{$order->teacher_requirement}\n";
        $content .= "【授课地址】{$order->address}\n";
        
        if ($order->salary) {
            $content .= "【课费薪资】{$order->salary}\n";
        }
        
        if ($order->remark) {
            $content .= "【备注】{$order->remark}\n";
        }
        
        return $content;
    }
    
    /**
     * 从请求中获取管理员ID
     * 从 session 中获取已登录的管理员ID
     */
    /**
     * 获取当前登录的管理员信息
     */
    private function getAdminInfo()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 从 session 中获取管理员ID
        $adminId = $_SESSION['admin_id'] ?? null;
        if (!$adminId) {
            return null;
        }
        
        // 查询管理员信息
        $admin = Admin::find($adminId);
        return $admin;
    }
    
    /**
     * 检查是否为超级管理员
     */
    private function isSuperAdmin()
    {
        $admin = $this->getAdminInfo();
        return $admin && $admin->role === 'super_admin';
    }
    
    /**
     * 更新订单信息
     * PUT /api/order/:id/update
     */
    public function update($id)
    {
        try {
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $data = $this->request->post();
            
            // 查找订单（超级管理员可以操作所有订单）
            $query = ParentOrder::where('id', $id);
            
            if (!$this->isSuperAdmin()) {
                $query->where('admin_id', $admin->id);
            }
            
            $order = $query->find();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在或无权访问']);
            }
            
            // 开启事务
            Db::startTrans();
            try {
                // 更新订单字段
                $allowedFields = [
                    'grade', 'subject', 'student_info', 'frequency',
                    'teacher_requirement', 'address', 'parent_name',
                    'parent_contact', 'salary', 'remark'
                ];
                
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        $order->$field = $data[$field];
                    }
                }
                
                $order->save();
                
                // 如果订单已审核通过且有关联的家教信息，同步更新家教信息
                if ($order->status === 'approved' && $order->tutor_id) {
                    $tutor = TutorOrder::find($order->tutor_id);
                    if ($tutor) {
                        // 重新生成家教信息内容
                        $tutor->content = $this->buildTutorContent($order);
                        $tutor->grade = $order->grade;
                        
                        // 如果提供了薪资，更新家教信息的薪资
                        if (isset($data['salary'])) {
                            $tutor->salary = $data['salary'];
                        }
                        
                        $tutor->save();
                    }
                }
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => '订单更新成功' . ($order->tutor_id ? '，家教信息已同步更新' : ''),
                    'data' => $order
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            trace('更新订单失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '更新订单失败：' . $e->getMessage()]);
        }
    }
}




