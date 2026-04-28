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
     * 将相对路径二维码补全为可访问 URL
     */
    private function normalizeAssetUrl(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $raw)) {
            return $raw;
        }

        return rtrim((string) $this->request->domain(), '/') . '/' . ltrim($raw, '/');
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string} [qrcodeUrl, phone, displayName]
     */
    private function extractContactFromAdminRow($admin): array
    {
        if (!$admin) {
            return [null, null, null];
        }
        $rawQr = trim((string) ($admin->wechat_qrcode ?? ''));
        $url = $rawQr === '' ? null : $this->normalizeAssetUrl($rawQr);
        $phone = trim((string) ($admin->booking_service_phone ?? ''));
        $phoneOut = $phone === '' ? null : $phone;
        $nick = trim((string) ($admin->nickname ?? ''));
        if ($nick === '') {
            $nick = trim((string) ($admin->username ?? ''));
        }
        $nameOut = $nick === '' ? null : $nick;

        return [$url ?: null, $phoneOut, $nameOut];
    }

    /**
     * 预约成功页：仅当订单已归属到具体管理员且该管理员配置了微信二维码时返回 URL；
     *
     * @return array{contact_qrcode_url: ?string, booking_service_phone: ?string, contact_admin_nickname: ?string}
     */
    private function resolveBookingContactForSuccess(?\app\model\Admin $admin): array
    {
        list($q, $p, $n) = $this->extractContactFromAdminRow($admin);
        if ($q) {
            return [
                'contact_qrcode_url' => $q,
                'booking_service_phone' => $p,
                'contact_admin_nickname' => $n,
            ];
        }

        return [
            'contact_qrcode_url' => null,
            'booking_service_phone' => $p,
            'contact_admin_nickname' => $n,
        ];
    }

    private function normalizeAvailableTimeSlots($slots)
    {
        if (is_string($slots) && $slots !== '') {
            $decoded = json_decode($slots, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $slots = $decoded;
            }
        }

        // 可辅导时间段为选填：空数组直接通过；有数据时仅保留校验通过的项，不阻断下单
        if (!is_array($slots) || empty($slots)) {
            return [true, '', []];
        }

        $normalized = [];
        foreach ($slots as $slot) {
            if (!is_array($slot)) {
                continue;
            }
            $weekDay = intval($slot['week_day'] ?? 0);
            $startTime = trim((string)($slot['start_time'] ?? ''));
            $endTime = trim((string)($slot['end_time'] ?? ''));

            if ($weekDay < 1 || $weekDay > 7) {
                continue;
            }
            // 允许 end_time 为 24:00（跨天的边界），其余必须为 HH:mm 且分钟为 00/30
            if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
                continue;
            }

            // 将时间转换为分钟（0-1440）
            $startTs = strtotime('2000-01-01 ' . $startTime . ':00');
            // end_time=24:00 时 strtotime 会进到下一天，这里手动处理为 1440 分钟
            $endMinutes = null;
            if ($endTime === '24:00') {
                $endMinutes = 24 * 60;
            } else {
                $endTs = strtotime('2000-01-01 ' . $endTime . ':00');
                if ($endTs !== false) {
                    $endMinutes = (int)(($endTs - strtotime('2000-01-01 00:00:00')) / 60);
                }
            }
            if ($startTs === false || $endMinutes === null) {
                continue;
            }
            $startMinutes = (int)(($startTs - strtotime('2000-01-01 00:00:00')) / 60);
            if ($endMinutes <= $startMinutes) {
                continue;
            }

            // duration_minutes：优先取入参；不合法/不一致时按时间差兜底计算
            $durationMinutesRaw = intval($slot['duration_minutes'] ?? 0);
            $computedDuration = (int)($endMinutes - $startMinutes);
            $durationMinutes = $durationMinutesRaw > 0 ? $durationMinutesRaw : $computedDuration;
            if ($durationMinutes <= 0 || $durationMinutes % 30 !== 0) {
                continue;
            }
            if ($computedDuration > 0 && $computedDuration % 30 === 0 && $durationMinutes !== $computedDuration) {
                // 若传入时长与时间差不一致，则以时间差为准（避免前端/历史数据导致被过滤成空）
                $durationMinutes = $computedDuration;
            }

            $normalized[] = [
                'week_day' => $weekDay,
                'start_time' => $startTime,
                'duration_minutes' => $durationMinutes,
                'end_time' => $endTime
            ];
        }

        return [true, '', $normalized];
    }
    /**
     * 创建预约订单
     * @return Response
     */
    public function create()
    {
        $userId = $this->request->post('user_id');
        $adminOpenid = $this->request->post('admin_openid'); // 接收管理员openid（小程序内分享）
        $adminIdParam = $this->request->post('admin_id'); // 接收管理员ID（管理后台复制的链接）
        $bookingData = $this->request->post('booking_data');
        
        // ===== 调试信息开始 =====
        $debugInfo = [
            'user_id' => $userId,
            'admin_openid' => $adminOpenid,
            'admin_id' => $adminIdParam,
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
            
            // 确定归属管理员：
            // 1) 优先使用 admin_id（管理后台链接）
            // 2) 其次使用 admin_openid（小程序分享）
            // 3) 若仍为空，使用用户表 superior_openid（登录时首次分享绑定，一次写死不覆盖）
            $admin = null;
            $adminId = 0;
            $adminNickname = null;
            $resolvedAdminOpenid = '';
            if (!empty($adminIdParam) && intval($adminIdParam) > 0) {
                // 管理后台复制的链接：直接使用 admin_id
                $admin = \app\model\Admin::find(intval($adminIdParam));
                if ($admin) {
                    $adminId = $admin->id;
                    $adminNickname = $admin->nickname ?: $admin->username;
                    $resolvedAdminOpenid = (string) ($admin->openid ?? '');
                    \think\facade\Log::info('通过 admin_id 关联管理员: id=' . $adminId . ', nickname=' . $adminNickname);
                } else {
                    \think\facade\Log::warning('admin_id 无效或管理员不存在: admin_id=' . $adminIdParam);
                }
            } elseif ($adminOpenid) {
                // 小程序内分享：通过 openid 查找管理员
                \think\facade\Log::info('开始查找管理员: openid=' . $adminOpenid);
                $admin = \app\model\Admin::findByOpenidToken($adminOpenid);
                if ($admin) {
                    $adminId = $admin->id;
                    $adminNickname = $admin->nickname ?: $admin->username;
                    $resolvedAdminOpenid = (string) ($admin->openid ?? '');
                    \think\facade\Log::info('通过 openid 找到管理员: id=' . $adminId . ', nickname=' . $adminNickname);
                } else {
                    \think\facade\Log::warning('未找到匹配的管理员: openid=' . $adminOpenid);
                }
            } else {
                $superiorOpenid = (string) ($user->superior_openid ?? '');
                if ($superiorOpenid !== '') {
                    \think\facade\Log::info('尝试使用用户 superior_openid 关联管理员: openid=' . $superiorOpenid);
                    $admin = \app\model\Admin::findByOpenidToken($superiorOpenid);
                    if ($admin) {
                        $adminId = $admin->id;
                        $adminNickname = $admin->nickname ?: $admin->username;
                        $resolvedAdminOpenid = (string) ($admin->openid ?? '');
                        \think\facade\Log::info('通过 superior_openid 找到管理员: id=' . $adminId . ', nickname=' . $adminNickname);
                    } else {
                        \think\facade\Log::warning('superior_openid 未找到匹配管理员: openid=' . $superiorOpenid);
                    }
                } else {
                    \think\facade\Log::warning('admin_openid/admin_id/superior_openid 均为空，订单无法关联管理员');
                }
            }

            // 绑定补偿：若本次已解析到管理员，但用户 superior_openid 为空，则将其写入一次
            // 目的：兼容“后台复制链接带 admin_id”的入口（不一定携带 superior_openid），避免后台归属显示一直是 “-”
            try {
                if ($admin && $user) {
                    $currentSuperior = trim((string)($user->superior_openid ?? ''));
                    if ($currentSuperior === '') {
                        $tokens = \app\model\Admin::splitOpenids($admin->openid ?? '');
                        $first = '';
                        foreach ($tokens as $t) {
                            $t = trim((string)$t);
                            // openid 常见以 o 开头
                            if ($t !== '' && preg_match('/^o[A-Za-z0-9\-_]{19,39}$/', $t)) {
                                $first = $t;
                                break;
                            }
                        }
                        if ($first !== '' && $first !== (string)($user->openid ?? '')) {
                            $user->superior_openid = $first;
                            $user->update_time = date('Y-m-d H:i:s');
                            $user->save();
                            \think\facade\Log::info('[superior_bind] mini-booking.create 补写 superior_openid', [
                                'user_id' => (int)($user->id ?? 0),
                                'user_openid' => (string)($user->openid ?? ''),
                                'superior_openid_written' => $first,
                                'admin_id' => (int)($admin->id ?? 0),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \think\facade\Log::warning('[superior_bind] mini-booking.create 补写失败: ' . $e->getMessage());
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
            
            list(, , $normalizedSlots) = $this->normalizeAvailableTimeSlots($bookingData['available_time_slots'] ?? []);

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
                'teacher_id' => $bookingData['teacher_id'] ?? ($bookingData['selected_teacher_id'] ?? null),  // 预约的教师ID
                'teaching_method' => $bookingData['teaching_method'] ?? '',
                'address' => $bookingData['address'] ?? '',
                'province_id' => $bookingData['province_id'] ?? 0,
                'city_id' => $bookingData['city_id'] ?? 0,
                'district_id' => $bookingData['district_id'] ?? 0,
                'available_time_slots' => json_encode($normalizedSlots, JSON_UNESCAPED_UNICODE),
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

            $contactPayload = $this->resolveBookingContactForSuccess($admin);
            
            return json([
                'code' => 200,
                'message' => '预约成功',
                'data' => [
                    'order_no' => $order->order_no,
                    'order_id' => $order->id,
                    'order' => $order,
                    'contact_qrcode_url' => $contactPayload['contact_qrcode_url'],
                    'booking_service_phone' => $contactPayload['booking_service_phone'],
                    'contact_admin_nickname' => $contactPayload['contact_admin_nickname'],
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
            
            $items = [];
            foreach ($list->items() as $item) {
                $row = $item instanceof \think\Model ? $item->toArray() : $item;
                $row['available_time_slots'] = json_decode((string)($row['available_time_slots'] ?? '[]'), true) ?: [];
                $items[] = $row;
            }

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $items,
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
            
            $row = $order->toArray();
            $row['available_time_slots'] = json_decode((string)($row['available_time_slots'] ?? '[]'), true) ?: [];

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $row
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
}
