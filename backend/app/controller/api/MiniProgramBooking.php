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
        $bookingData = $this->request->post('booking_data');
        
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
                'admin_id' => 0,  // 小程序预约暂无管理员，设为0
                'grade' => $bookingData['grade'] ?? '',
                'student_gender' => $bookingData['gender'] ?? '',
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
                'teaching_method' => $bookingData['teaching_method'] ?? '',
                'address' => $bookingData['address'] ?? '',
                'province_id' => $bookingData['province_id'] ?? 0,
                'city_id' => $bookingData['city_id'] ?? 0,
                'district_id' => $bookingData['district_id'] ?? 0,
                'parent_name' => $bookingData['contact'] ?? '',
                'parent_contact' => $user->phone ?? '',
                'remark' => '小程序预约',
                'status' => 'pending',
                'booking_channel' => '小程序',
                'user_id' => $userId,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
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
