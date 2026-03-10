<?php
namespace app\controller\api;

use app\BaseController;
use app\model\ParentOrder;
use app\model\User;
use think\Response;

/**
 * 小程序预约控制器
 */
class MiniProgramBooking extends BaseController
{
    /**
     * 创建预约订单
     * @return Response
     */
    public function create()
    {
        $userId = $this->request->post('user_id');
        $adminOpenid = $this->request->post('admin_openid'); // 接收管理员openid
        $bookingData = $this->request->post('booking_data');
        
        // ===== 调试信息开始 =====
        $debugInfo = [
            'user_id' => $userId,
            'admin_openid' => $adminOpenid,
            'admin_openid_length' => $adminOpenid ? strlen($adminOpenid) : 0,
            'admin_openid_empty' => empty($adminOpenid) ? 'YES' : 'NO',
            'booking_data_keys' => $bookingData ? array_keys($bookingData) : [],
            'all_post_data' => $this->request->post(),
            'request_time' => date('Y-m-d H:i:s')
        ];
        
        \think\facade\Log::info('=== 订单创建调试信息 ===');
        \think\facade\Log::info('接收到的参数: ' . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        // ===== 调试信息结束 =====
        
        if (empty($userId)) {
            return json([
                'code' => 400,
                'message' => '缺少用户ID'
            ]);
        }
        
        if (empty($bookingData)) {
            return json([
                'code' => 400,
                'message' => '缺少预约数据'
            ]);
        }
        
        try {
            // 验证用户是否存在
            $user = User::find($userId);
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            // 通过openid查找管理员
            $admin = null;
            $adminId = 0;
            $adminNickname = null;
            if ($adminOpenid) {
                \think\facade\Log::info('开始查找管理员: openid=' . $adminOpenid);
                $admin = \app\model\Admin::where('openid', $adminOpenid)->find();
                if ($admin) {
                    $adminId = $admin->id;
                    $adminNickname = $admin->nickname ?: $admin->username;
                    \think\facade\Log::info('找到管理员: id=' . $adminId . ', nickname=' . $adminNickname);
                } else {
                    \think\facade\Log::warning('未找到匹配的管理员: openid=' . $adminOpenid);
                }
            } else {
                \think\facade\Log::warning('admin_openid 为空，无法关联管理员');
            }
            
            // 生成订单号
            $orderNo = ParentOrder::generateOrderNo();
            
            // 构建时薪范围字符串
            $budgetMin = $bookingData['budget_min'] ?? 0;
            $budgetMax = $bookingData['budget_max'] ?? 0;
            $salary = '';
            if ($budgetMin > 0 && $budgetMax > 0) {
                $salary = $budgetMin . '-' . $budgetMax . '元/小时';
            } elseif (!empty($bookingData['budget'])) {
                $salary = $bookingData['budget'];
            }
            
            // 构建学生情况描述
            $studentInfo = $bookingData['child_description'] ?? '';
            
            // 构建教师要求描述
            $teacherType = $bookingData['teacher_type'] ?? '';
            $teacherGender = $bookingData['teacher_gender'] ?? '';
            $teacherRequirement = trim($teacherType . ' ' . $teacherGender);
            
            // 创建订单 - 所有字段独立保存
            $order = ParentOrder::create([
                'order_no' => $orderNo,
                'admin_id' => $adminId ?: 0,  // 保存管理员ID
                'grade' => $bookingData['grade'] ?? '',
                'student_gender' => $bookingData['gender'] ?? '',
                'student_name' => $bookingData['student_name'] ?? '',  // 学生昵称
                'subject' => $bookingData['subject'] ?? '',
                'student_info' => $studentInfo,  // 学生情况描述
                'frequency' => $bookingData['frequency'] ?? '',
                'duration' => $bookingData['duration'] ?? '',
                'salary' => $salary,  // 时薪范围字符串
                'budget_min' => $budgetMin,
                'budget_max' => $budgetMax,
                'teacher_requirement' => $teacherRequirement,  // 教师要求
                'teacher_type' => $bookingData['teacher_type'] ?? '',
                'teacher_gender' => $bookingData['teacher_gender'] ?? '',
                'teacher_id' => $bookingData['teacher_id'] ?? null,  // 预约的教师ID
                'teaching_method' => $bookingData['teaching_method'] ?? '',
                'address' => $bookingData['address'] ?? '',
                'province_id' => $bookingData['province_id'] ?? 0,
                'city_id' => $bookingData['city_id'] ?? 0,
                'district_id' => $bookingData['district_id'] ?? 0,
                'parent_name' => $bookingData['contact'] ?? '',
                'parent_contact' => $user->phone ?? '',
                'remark' => $adminNickname ? "小程序预约（{$adminNickname}）" : '小程序预约',  // 在备注中显示管理员昵称
                'status' => 'pending',
                'booking_channel' => '小程序',
                'user_id' => $userId,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            \think\facade\Log::info('订单创建成功: order_id=' . $order->id . ', order_no=' . $order->order_no . ', admin_id=' . $adminId . ', remark=' . $order->remark);
            
            // 发送邮件通知给管理员
            try {
                if ($admin) {
                    if ($admin->email) {
                        \think\facade\Log::info('准备发送邮件通知: admin_id=' . $admin->id . ', email=' . $admin->email);
                        $emailSent = \app\service\EmailService::sendBookingNotification($admin, $order);
                        if ($emailSent) {
                            \think\facade\Log::info('邮件通知发送成功: admin_id=' . $admin->id);
                        } else {
                            \think\facade\Log::warning('邮件通知发送失败，但订单已创建: order_no=' . $order->order_no);
                        }
                    } else {
                        \think\facade\Log::warning('管理员没有设置邮箱，无法发送邮件通知: admin_id=' . $admin->id);
                    }
                } else {
                    \think\facade\Log::warning('未找到管理员，无法发送邮件通知');
                }
            } catch (\Throwable $e) {
                // 捕获所有错误和异常，确保不影响订单创建
                \think\facade\Log::error('邮件发送异常: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
                trace('邮件发送异常: ' . $e->getMessage(), 'error');
            }
            
            return json([
                'code' => 200,
                'message' => '预约成功',
                'data' => [
                    'order_no' => $order->order_no,
                    'order_id' => $order->id,
                    'order' => $order
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '预约失败：' . $e->getMessage()
            ]);
        }
    }
    

    
    /**
     * 获取用户的预约列表
     * @return Response
     */
    public function myOrders()
    {
        $userId = $this->request->param('user_id');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('pageSize', 10);
        
        if (empty($userId)) {
            return json([
                'code' => 400,
                'message' => '缺少用户ID'
            ]);
        }
        
        try {
            // 通过用户手机号查询订单
            $user = User::find($userId);
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            $list = ParentOrder::where('user_id', $userId)
                ->where('booking_channel', '小程序')
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $pageSize,
                    'page' => $page
                ]);
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $list->items(),
                    'total' => $list->total(),
                    'page' => $page,
                    'pageSize' => $pageSize
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取订单详情
     * @return Response
     */
    public function detail()
    {
        $orderId = $this->request->param('order_id');
        $userId = $this->request->param('user_id');
        
        if (empty($orderId) || empty($userId)) {
            return json([
                'code' => 400,
                'message' => '缺少参数'
            ]);
        }
        
        try {
            // 验证用户
            $user = User::find($userId);
            if (!$user) {
                return json([
                    'code' => 404,
                    'message' => '用户不存在'
                ]);
            }
            
            // 查询订单
            $order = ParentOrder::where('id', $orderId)
                ->where('user_id', $userId)
                ->find();
            
            if (!$order) {
                return json([
                    'code' => 404,
                    'message' => '订单不存在'
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
}
