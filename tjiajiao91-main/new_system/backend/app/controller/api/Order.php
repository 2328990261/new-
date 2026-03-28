<?php
namespace app\controller\api;

use app\BaseController;
use app\model\ParentOrder;
use app\model\TutorOrder;
use app\model\Admin;
use app\model\User;
use app\service\EmailService;
use app\service\DispatcherAutoAssignService;
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
                    $emailSent = EmailService::sendBookingNotification($admin, $order);
                    if ($emailSent) {
                        trace('预约通知邮件发送成功: ' . $admin->email, 'info');
                    } else {
                        trace('预约通知邮件发送失败，但订单已创建: ' . $order->order_no, 'warning');
                    }
                } else {
                    trace('管理员未设置邮箱，跳过邮件通知: admin_id=' . $admin->id, 'warning');
                }
            } catch (\Throwable $e) {
                // 捕获所有错误和异常，确保不影响订单创建
                trace('邮件发送异常: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine(), 'error');
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
            // 尝试获取管理员信息，如果没有登录则返回空数据而不是错误
            $admin = $this->getAdminInfo();
            
            $status = $this->request->get('status', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            $isChannel = $this->request->get('is_channel', '');
            $adminId = $this->request->get('admin_id', ''); // 前端传递的admin_id参数
            $keyword = trim((string) $this->request->get('keyword', ''));
            
            // 如果没有登录，返回空数据
            if (!$admin) {
                return json([
                    'success' => true,
                    'data' => [
                        'list' => [],
                        'total' => 0,
                        'page' => $page,
                        'limit' => $limit
                    ],
                    'message' => '请先登录查看订单'
                ]);
            }
            
            // 构建查询（不预加载 admin 关联，避免 ThinkPHP 在 admin_id=0 时错误返回第一条记录）
            $query = ParentOrder::with(['teacher' => function($query) {
                    $query->field('id,name,phone');
                }])
                ->order('create_time', 'desc');
            
            // 处理admin_id筛选
            if ($adminId) {
                // 前端明确指定了admin_id，按指定的admin_id筛选
                $query->where('admin_id', $adminId);
            } elseif (!$this->canViewAllOrders()) {
                // 非超级管理员/客服组长且没有指定admin_id，只能查看归属于自己的订单
                $query->where('admin_id', $admin->id);
            }
            // 超级管理员或客服组长且没有指定admin_id，查看所有订单
            
            // 筛选状态
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }
            
            // 关键词：家长电话、订单号、称呼、订单ID、归属管理员（昵称/登录名）
            if ($keyword !== '') {
                $like = '%' . addcslashes($keyword, '%_\\') . '%';
                // 1) 匹配管理员（admin 表 nickname/username）
                $adminIds = Admin::where(function ($aq) use ($like) {
                    $aq->where('nickname', 'like', $like)->whereOr('username', 'like', $like);
                })->column('id');
                $adminOpenids = [];
                if (!empty($adminIds)) {
                    $adminOpenidValues = Admin::where('id', 'in', $adminIds)->column('openid');
                    foreach ($adminOpenidValues as $openidValue) {
                        $adminOpenids = array_merge($adminOpenids, Admin::splitOpenids($openidValue));
                    }
                    $adminOpenids = array_values(array_unique($adminOpenids));
                }

                // 2) 匹配分享者为普通用户（users 表 nickname/phone）
                $shareUserOpenids = User::where(function ($uq) use ($like) {
                    $uq->where('nickname', 'like', $like)->whereOr('phone', 'like', $like);
                })->column('openid');

                // 3) 通过 superior_openid 找到被该 openid 绑定的下级用户，再反查订单 user_id
                $superiorOpenids = array_values(array_unique(array_filter(array_merge($adminOpenids, $shareUserOpenids))));
                $userIdsBySuperior = [];
                if (!empty($superiorOpenids)) {
                    $userIdsBySuperior = User::where('superior_openid', 'in', $superiorOpenids)->column('id');
                }

                $query->where(function ($q) use ($like, $keyword, $adminIds, $userIdsBySuperior) {
                    $q->where('parent_contact', 'like', $like)
                        ->whereOr('order_no', 'like', $like)
                        ->whereOr('parent_name', 'like', $like);
                    if (preg_match('/^\d+$/', $keyword)) {
                        $q->whereOr('id', '=', (int) $keyword);
                    }
                    if (!empty($adminIds)) {
                        $q->whereOr('admin_id', 'in', $adminIds);
                    }
                    if (!empty($userIdsBySuperior)) {
                        $q->whereOr('user_id', 'in', $userIdsBySuperior);
                    }
                });
            }
            
            // 分页查询
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ]);
            
            // 手动构建 admin，完全避免 ThinkPHP 关联在 admin_id=0 时错误返回第一条记录
            $items = $result->items();
            // 批量解析：通过下单用户 user_id → superior_openid → admin/user 显示归属
            $userIds = [];
            foreach ($items as $o) {
                $uid = (int) ($o->getData('user_id') ?? 0);
                if ($uid > 0) $userIds[] = $uid;
            }
            $userIds = array_values(array_unique($userIds));

            $usersById = [];
            $superiorOpenids = [];
            if (!empty($userIds)) {
                $uRows = User::field('id,openid,superior_openid,phone,nickname')->where('id', 'in', $userIds)->select();
                foreach ($uRows as $ur) {
                    $ua = $ur->toArray();
                    $usersById[(int) $ua['id']] = $ua;
                    $so = trim((string) ($ua['superior_openid'] ?? ''));
                    if ($so !== '') $superiorOpenids[] = $so;
                }
            }
            $superiorOpenids = array_values(array_unique(array_filter($superiorOpenids)));

            $adminsByOpenid = [];
            $shareUsersByOpenid = [];
            if (!empty($superiorOpenids)) {
                $aRows = Admin::queryByOpenidTokens($superiorOpenids)
                    ->field('id,username,nickname,openid')
                    ->select();
                foreach ($aRows as $ar) {
                    $aa = $ar->toArray();
                    $openidTokens = Admin::splitOpenids($aa['openid'] ?? '');
                    foreach ($openidTokens as $ok) {
                        if (!isset($adminsByOpenid[$ok])) {
                            $adminsByOpenid[$ok] = $aa;
                        }
                    }
                }
                $suRows = User::field('id,openid,phone,nickname')->where('openid', 'in', $superiorOpenids)->select();
                foreach ($suRows as $sr) {
                    $sa = $sr->toArray();
                    $ok = trim((string) ($sa['openid'] ?? ''));
                    if ($ok !== '') $shareUsersByOpenid[$ok] = $sa;
                }
            }

            $list = [];
            foreach ($items as $order) {
                $arr = $order->toArray();
                $rawAdminId = $order->getData('admin_id');
                if ($rawAdminId > 0) {
                    $admin = Admin::field('id,username,nickname')->find($rawAdminId);
                    $arr['admin'] = $admin ? $admin->toArray() : null;
                    $arr['admin_id'] = (int) $rawAdminId;
                    $arr['owner_display'] = ($arr['admin'] && ($arr['admin']['nickname'] ?? '' || $arr['admin']['username'] ?? ''))
                        ? (($arr['admin']['nickname'] ?? '') ?: ($arr['admin']['username'] ?? ''))
                        : '-';
                } else {
                    $arr['admin'] = null;
                    $arr['admin_id'] = 0;
                    $arr['owner_display'] = '-';

                    $uid = (int) ($order->getData('user_id') ?? 0);
                    if ($uid > 0 && isset($usersById[$uid])) {
                        $so = trim((string) ($usersById[$uid]['superior_openid'] ?? ''));
                        if ($so !== '') {
                            if (isset($adminsByOpenid[$so])) {
                                $a = $adminsByOpenid[$so];
                                $arr['owner_display'] = (string) (($a['nickname'] ?? '') ?: ($a['username'] ?? ''));
                            } elseif (isset($shareUsersByOpenid[$so])) {
                                $u = $shareUsersByOpenid[$so];
                                $arr['owner_display'] = (string) (($u['nickname'] ?? '') ?: ($u['phone'] ?? '') ?: ('用户#' . ($u['id'] ?? '')));
                            }
                        }
                    }
                }
                $list[] = $arr;
            }
            
            // 禁用缓存
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $list,
                    'total' => $result->total(),
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取订单列表失败: ' . $e->getMessage(), 'error');
            return json([
                'success' => false,
                'message' => '获取订单列表失败：' . $e->getMessage()
            ]);
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
            $adminId = $this->request->get('admin_id', ''); // 前端传递的admin_id参数
            
            // 如果没有登录，返回空统计
            if (!$admin) {
                return json([
                    'success' => true,
                    'data' => [
                        'mine' => 0,
                        'all' => 0,
                        'total' => 0,
                        'pending' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                        'cancelled' => 0,
                        'channel' => 0
                    ],
                    'message' => '请先登录查看统计'
                ]);
            }
            
            // 我的订单统计（当前登录管理员的订单）
            $mine = ParentOrder::where('admin_id', $admin->id)->count();
            
            // 根据admin_id参数决定统计范围
            if ($adminId) {
                // 前端指定了admin_id，统计该管理员的订单
                $total = ParentOrder::where('admin_id', $adminId)->count();
                $pending = ParentOrder::where('admin_id', $adminId)->where('status', 'pending')->count();
                $approved = ParentOrder::where('admin_id', $adminId)->where('status', 'approved')->count();
                $rejected = ParentOrder::where('admin_id', $adminId)->where('status', 'rejected')->count();
                $cancelled = ParentOrder::where('admin_id', $adminId)->where('status', 'cancelled')->count();
            } elseif ($this->canViewAllOrders()) {
                // 超级管理员或客服组长且没有指定admin_id，统计所有订单
                $total = ParentOrder::count();
                $pending = ParentOrder::where('status', 'pending')->count();
                $approved = ParentOrder::where('status', 'approved')->count();
                $rejected = ParentOrder::where('status', 'rejected')->count();
                $cancelled = ParentOrder::where('status', 'cancelled')->count();
            } else {
                // 非超级管理员且没有指定admin_id，只统计自己的订单
                $total = ParentOrder::where('admin_id', $admin->id)->count();
                $pending = ParentOrder::where('admin_id', $admin->id)->where('status', 'pending')->count();
                $approved = ParentOrder::where('admin_id', $admin->id)->where('status', 'approved')->count();
                $rejected = ParentOrder::where('admin_id', $admin->id)->where('status', 'rejected')->count();
                $cancelled = ParentOrder::where('admin_id', $admin->id)->where('status', 'cancelled')->count();
            }
            
            // 所有订单统计（超级管理员或客服组长）
            $allCount = 0;
            if ($this->canViewAllOrders()) {
                $allCount = ParentOrder::count();
            }
            
            // 禁用缓存
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            return json([
                'success' => true,
                'data' => [
                    'mine' => $mine,        // 我的订单数量
                    'all' => $allCount,     // 所有订单数量（仅超级管理员）
                    'total' => $total,      // 当前Tab的总数
                    'pending' => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'cancelled' => $cancelled,
                    'channel' => 0          // 暂时返回0，等添加字段后再启用
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取订单统计失败: ' . $e->getMessage(), 'error');
            return json([
                'success' => false,
                'message' => '获取订单统计失败：' . $e->getMessage()
            ]);
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
            
            // 超级管理员、客服组长可查看所有订单，其他管理员只能查看自己的订单
            $query = ParentOrder::with([
                    'teacher' => function($query) {
                        $query->field('id,name,phone');
                    }
                ])
                ->where('id', $id);
            
            if (!$this->canViewAllOrders()) {
                $query->where('admin_id', $admin->id);
            }
            
            $order = $query->find();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在或无权访问']);
            }
            
            $data = $order->toArray();
            $rawAdminId = $order->getData('admin_id');
            if ($rawAdminId > 0) {
                $admin = Admin::field('id,username,nickname')->find($rawAdminId);
                $data['admin'] = $admin ? $admin->toArray() : null;
                $data['admin_id'] = (int) $rawAdminId;
                $data['owner_display'] = ($data['admin'] && (($data['admin']['nickname'] ?? '') || ($data['admin']['username'] ?? '')))
                    ? (($data['admin']['nickname'] ?? '') ?: ($data['admin']['username'] ?? ''))
                    : '-';
            } else {
                $data['admin'] = null;
                $data['admin_id'] = 0;
                $data['owner_display'] = '-';

                $uid = (int) ($order->getData('user_id') ?? 0);
                if ($uid > 0) {
                    $u = User::field('id,openid,superior_openid,phone,nickname')->find($uid);
                    $so = trim((string) ($u->superior_openid ?? ''));
                    if ($so !== '') {
                        $a = Admin::queryByOpenidToken($so)
                            ->field('id,username,nickname,openid')
                            ->find();
                        if ($a) {
                            $data['owner_display'] = (string) (($a->nickname ?: '') ?: ($a->username ?: ''));
                        } else {
                            $su = User::field('id,openid,phone,nickname')->where('openid', $so)->find();
                            if ($su) {
                                $data['owner_display'] = (string) (($su->nickname ?: '') ?: ($su->phone ?: '') ?: ('用户#' . $su->id));
                            }
                        }
                    }
                }
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $data
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
            
            // 查找订单（超级管理员、客服组长可以操作所有订单）
            $query = ParentOrder::where('id', $id);
            
            if (!$this->canViewAllOrders()) {
                $query->where('admin_id', $admin->id);
            }
            
            $order = $query->find();
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在或无权访问']);
            }
            
            if ($order->status !== 'pending') {
                return json(['code' => 400, 'message' => '订单状态不正确']);
            }

            // 是否在审核通过时转换为家教单
            // 兼容旧前端：未传参数时默认转换（保持原行为）
            $convertToTutorRaw = $this->request->post('convert_to_tutor', 1);
            $convertToTutor = in_array(
                strtolower(trim((string) $convertToTutorRaw)),
                ['1', 'true', 'yes', 'on'],
                true
            );
            
            // 开启事务
            Db::startTrans();
            try {
                // 不转换：仅更新预约状态
                if (!$convertToTutor) {
                    $order->status = 'approved';
                    $order->audit_time = date('Y-m-d H:i:s');
                    $order->save();

                    Db::commit();

                    return json([
                        'code' => 200,
                        'message' => '审核通过，未生成家教单',
                        'data' => [
                            'tutor_id' => null
                        ]
                    ]);
                }

                // 获取下一个可用的ID（使用简单自增方式）
                $maxId = TutorOrder::max('id') ?: 0;
                $tutorId = $maxId + 1;
                
                // 构建家教单内容（使用原始格式）
                $tutorContent = $this->buildTutorContentFromOrder($order);
                
                // 解析薪酬范围 - 直接使用预约单的时薪范围字段
                $salaryValue = null;
                $salaryStr = $order->salary ?: '';
                if (empty($salaryStr) && $order->budget_min && $order->budget_max) {
                    $salaryStr = $order->budget_min . '-' . $order->budget_max . '元/小时';
                }
                // 提取最低薪酬数字用于排序
                if (preg_match('/(\d+)/', $salaryStr, $matches)) {
                    $salaryValue = intval($matches[1]);
                }
                
                // 解析科目ID
                $subjectId = $this->getSubjectIdByName($order->subject);
                
                // 解析老师类型
                $teacherType = $this->parseTeacherType($order->teacher_type);
                
                // 处理城市区域 - 线上授课识别为"全国 线上"
                $cityId = $order->city_id;
                $districtId = $order->district_id;
                $isOnline = ($order->teaching_method === '线上授课' || strpos($order->address, '线上') !== false);
                if ($isOnline) {
                    // 线上授课，查找或使用"全国"城市和"线上"区域
                    // 先查找名为"全国"的城市
                    $onlineCity = \app\model\City::where('name', '全国')->find();
                    if ($onlineCity) {
                        $cityId = $onlineCity->id;
                        // 查找该城市下名为"线上"的区域
                        $onlineDistrict = \app\model\District::where('city_id', $onlineCity->id)
                            ->where('name', '线上')
                            ->find();
                        $districtId = $onlineDistrict ? $onlineDistrict->id : null;
                    } else {
                        // 如果没有"全国"城市，设为null
                        $cityId = null;
                        $districtId = null;
                    }
                }
                // 外键约束：city_id 必须存在于 fa_cities 或为 NULL，否则插入 fa_tutor_orders_new 会报错
                if ($cityId !== null && $cityId !== '') {
                    $cityId = (int) $cityId;
                    if ($cityId <= 0 || !\app\model\City::where('id', $cityId)->find()) {
                        $cityId = null;
                        $districtId = null;
                    }
                } else {
                    $cityId = null;
                }
                
                // 创建家教信息（手动设置ID）
                $tutorData = [
                    'id' => $tutorId,
                    'content' => $tutorContent,
                    'grade' => $order->grade,
                    'city_id' => $cityId,
                    'district_id' => $districtId,
                    'subject_id' => $subjectId,
                    'salary' => $salaryStr,  // 直接存储时薪范围字符串（如：130-150元/小时）
                    'teacher_type' => $teacherType,
                    'admin_id' => $order->admin_id ?: 0,
                    'admin_openid' => null,  // 先设为null，后面根据admin_id填充
                    'is_urgent' => 0,
                    'status' => 1,
                    'booking_channel' => $order->booking_channel ?: '小程序'  // 预约渠道，用于标识预约单
                ];
                
                // 如果预约订单有关联的管理员，填充 admin_openid
                if ($order->admin_id > 0) {
                    $orderAdmin = \app\model\Admin::find($order->admin_id);
                    if ($orderAdmin && !empty($orderAdmin->openid)) {
                        $openidTokens = \app\model\Admin::splitOpenids($orderAdmin->openid);
                        $tutorData['admin_openid'] = $openidTokens[0] ?? null;
                    }
                }
                
                $tutor = TutorOrder::create($tutorData);
                
                // 自动轮派给发单组
                $this->autoAssignToDispatcher($tutor);
                
                // 更新订单状态
                $order->status = 'approved';
                $order->tutor_id = $tutor->id;
                $order->audit_time = date('Y-m-d H:i:s');
                $order->save();
                
                Db::commit();
                
                // 发送邮件通知给匹配的订阅者（异步，不影响主流程）
                try {
                    $this->sendOrderNotificationToSubscribers($tutor);
                } catch (\Exception $e) {
                    trace('发送订单通知邮件失败（不影响主流程）: ' . $e->getMessage(), 'info');
                }
                
                return json([
                    'code' => 200,
                    'message' => '审核通过，家教信息已发布并自动派单',
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
     * 根据科目名称获取科目ID
     */
    private function getSubjectIdByName($subjectName)
    {
        if (empty($subjectName)) return null;
        
        // 科目映射表
        $subjectMap = [
            '语文' => 1, '数学' => 2, '英语' => 3, '物理' => 4, '化学' => 5,
            '生物' => 6, '历史' => 7, '地理' => 8, '政治' => 9,
            '幼儿英语' => 3, '幼儿拼音' => 1, '幼儿数学' => 2,
            '科学' => 10, '艺术' => 11, '体育' => 12
        ];
        
        return $subjectMap[$subjectName] ?? null;
    }
    
    /**
     * 解析老师类型
     */
    private function parseTeacherType($teacherType)
    {
        if (empty($teacherType)) return null;
        
        // 老师类型映射
        $typeMap = [
            '大学生' => '大学生',
            '专职老师' => '专职老师',
            '留学生' => '留学生',
            '不限' => null
        ];
        
        return $typeMap[$teacherType] ?? $teacherType;
    }
    
    /**
     * 自动派单给派单组（按订单城市匹配归属城市，同工作量随机）
     */
    private function autoAssignToDispatcher($order)
    {
        DispatcherAutoAssignService::assignToDispatcher($order);
    }
    
    /**
     * 构建家教单内容（使用原始格式）
     */
    private function buildTutorContentFromOrder($order)
    {
        // 判断是否线上授课
        $isOnline = ($order->teaching_method === '线上授课' || strpos($order->address, '线上') !== false);
        
        // 获取城市区域名称（用于内容显示）
        $cityArea = '';
        if ($isOnline) {
            // 线上授课显示为"全国 线上"
            $cityArea = '全国 线上';
        } else {
            if ($order->city_id) {
                $city = \app\model\City::find($order->city_id);
                if ($city) $cityArea .= $city->name;
            }
            if ($order->district_id) {
                $district = \app\model\District::find($order->district_id);
                if ($district) $cityArea .= ' ' . $district->name;
            }
            if (empty($cityArea) && $order->address) {
                // 从地址中提取城市区域
                $cityArea = $order->address;
            }
        }
        
        // 地址显示（线上授课不显示地址）
        $addressDisplay = $isOnline ? '' : ($order->address ?: '');
        
        // 学生情况
        $studentGender = $order->student_gender ?: '';
        $studentInfo = $order->student_info ?: '';
        
        // 时薪范围 - 直接使用预约单的时薪范围字段
        $salary = $order->salary ?: '';
        if (empty($salary) && $order->budget_min && $order->budget_max) {
            $salary = $order->budget_min . '-' . $order->budget_max . '元/小时';
        }
        
        // 老师要求 - 正确映射老师类型
        $teacherType = $this->parseTeacherType($order->teacher_type) ?: '';
        $teacherGender = $order->teacher_gender ?: '';
        $teacherReq = trim($teacherType . ' ' . $teacherGender);
        
        // 构建内容标题（城市区域 + 地址 + 年级 + 科目）
        $titleParts = array_filter([$cityArea, $addressDisplay, $order->grade, $order->subject]);
        $title = implode(' ', $titleParts);
        
        // 构建内容（不包含来源信息，来源通过卡片标签显示）
        $content = "【{$title}】\n";
        $content .= "【学生情况】{$studentGender}" . ($studentGender && $studentInfo ? '，' : '') . "{$studentInfo}\n";
        $content .= "【时间频率】{$order->frequency}" . ($order->frequency && $order->duration ? '，' : '') . "{$order->duration}\n";
        $content .= "【时薪范围】{$salary}\n";
        $content .= "【老师要求】{$teacherReq}";
        
        return $content;
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
            
            // 查找订单（超级管理员、客服组长可以操作所有订单）
            $query = ParentOrder::where('id', $id);
            
            if (!$this->canViewAllOrders()) {
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
     * 删除订单（仅超级管理员和客服组长）
     * DELETE /api/order/:id/delete
     */
    public function delete($id)
    {
        try {
            $admin = $this->getAdminInfo();
            if (!$admin) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 检查权限：只有超级管理员和客服组长可以删除
            if (!$this->canDeleteOrder()) {
                return json(['code' => 403, 'message' => '无权限删除订单']);
            }
            
            // 查找订单
            $order = ParentOrder::find($id);
            
            if (!$order) {
                return json(['code' => 404, 'message' => '订单不存在']);
            }
            
            // 软删除：不物理删除记录，只标记为已取消（status=cancelled），与已拒绝区分
            $order->status = 'cancelled';
            $order->reject_reason = '已由管理员取消';
            $order->audit_time = date('Y-m-d H:i:s');
            $order->save();
            
            trace('订单已标记为已取消（软删除），ID: ' . $id . '，操作员: ' . $admin->nickname, 'info');
            
            return json([
                'code' => 200,
                'message' => '订单已取消'
            ]);
            
        } catch (\Exception $e) {
            trace('删除订单失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '删除订单失败：' . $e->getMessage()]);
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
     * 检查是否可以查看全部预约（超级管理员或客服组长，与前端 canViewAllOrders 一致）
     */
    private function canViewAllOrders()
    {
        $admin = $this->getAdminInfo();
        return $admin && in_array($admin->role, ['super_admin', 'team_leader']);
    }
    
    /**
     * 检查是否可以删除订单（超级管理员或客服组长）
     */
    private function canDeleteOrder()
    {
        $admin = $this->getAdminInfo();
        return $admin && ($admin->role === 'super_admin' || $admin->role === 'team_leader');
    }
    
    /**
     * 发送订单通知给匹配的订阅者（异步方式）
     */
    private function sendOrderNotificationToSubscribers($order)
    {
        try {
            // 查找匹配的订阅者
            $subscribers = $this->getMatchedSubscribers($order);
            
            if (empty($subscribers)) {
                trace('没有匹配的订阅者，订单ID: ' . $order->id, 'info');
                return;
            }
            
            trace('找到 ' . count($subscribers) . ' 个匹配的订阅者，订单ID: ' . $order->id, 'info');
            
            // 将邮件添加到队列（异步发送）
            foreach ($subscribers as $subscriber) {
                $this->addEmailToQueue($subscriber['email'], $order);
            }
            
        } catch (\Exception $e) {
            trace('添加订单通知邮件到队列失败: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 获取匹配订单的订阅者
     */
    private function getMatchedSubscribers($order)
    {
        // 查询所有启用且已验证的订阅者
        $query = \app\model\EmailSubscription::where('status', 1)
            ->where('is_verified', 1);
        
        $subscribers = $query->select()->toArray();
        
        // 过滤匹配的订阅者
        $matched = [];
        foreach ($subscribers as $subscriber) {
            $model = new \app\model\EmailSubscription();
            $model->data($subscriber);
            
            // 将订单数据转换为数组格式
            $orderData = [
                'city_id' => $order->city_id,
                'district_id' => $order->district_id,
                'subject_id' => $order->subject_id,
                'grade' => $order->grade
            ];
            
            if ($model->matchesOrder($orderData)) {
                $matched[] = $subscriber;
            }
        }
        
        return $matched;
    }
    
    /**
     * 添加邮件到队列
     */
    private function addEmailToQueue($email, $order)
    {
        try {
            // 获取邮件配置
            $config = Db::name('notification_config')->find(1);
            
            if (!$config || !$config['smtp_host'] || !$config['email_enabled']) {
                trace('邮件配置未设置或未启用，跳过发送', 'info');
                return;
            }
            
            // 构建邮件内容
            $subject = '新家教信息通知 - ' . ($order->grade ?: '') . ' ' . ($order->subject ? $order->subject->name : '');
            $body = $this->renderOrderEmailTemplate($order, $config);
            
            // 添加到队列
            \app\model\EmailQueue::create([
                'email_type' => \app\model\EmailQueue::TYPE_ORDER,
                'recipient_email' => $email,
                'subject' => $subject,
                'body' => $body,
                'related_id' => $order->id,
                'status' => \app\model\EmailQueue::STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            trace('订单通知邮件已加入队列: email=' . $email . ', order_id=' . $order->id, 'info');
            
        } catch (\Exception $e) {
            trace('添加邮件到队列失败: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 渲染订单邮件模板
     */
    private function renderOrderEmailTemplate($order, $config)
    {
        // 获取城市区域名称
        $cityName = $order->city ? $order->city->name : '';
        $districtName = $order->district ? $order->district->name : '';
        $subjectName = $order->subject ? $order->subject->name : '';
        
        // 使用配置的模板或默认模板
        if (!empty($config['email_template'])) {
            // 使用自定义模板
            $replacements = [
                '{{city}}' => $cityName,
                '{{district}}' => $districtName,
                '{{grade}}' => $order->grade ?: '',
                '{{subject}}' => $subjectName,
                '{{salary}}' => $order->salary ?: '',
                '{{content}}' => nl2br(htmlspecialchars($order->content)),
            ];
            
            return str_replace(
                array_keys($replacements),
                array_values($replacements),
                $config['email_template']
            );
        }
        
        // 默认模板
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<div style="background: #667eea; color: white; padding: 20px; text-align: center;">';
        $html .= '<h2 style="margin: 0;">新家教信息通知</h2>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 20px; background: #f9f9f9;">';
        $html .= '<div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px;">';
        $html .= '<p style="margin: 10px 0;"><strong>订单编号：</strong>' . htmlspecialchars($order->id) . '</p>';
        
        if ($cityName || $districtName) {
            $html .= '<p style="margin: 10px 0;"><strong>城市区域：</strong>' . htmlspecialchars($cityName . ' ' . $districtName) . '</p>';
        }
        
        if ($order->grade) {
            $html .= '<p style="margin: 10px 0;"><strong>年级：</strong>' . htmlspecialchars($order->grade) . '</p>';
        }
        
        if ($subjectName) {
            $html .= '<p style="margin: 10px 0;"><strong>科目：</strong>' . htmlspecialchars($subjectName) . '</p>';
        }
        
        if ($order->salary) {
            $html .= '<p style="margin: 10px 0;"><strong>薪资：</strong>' . htmlspecialchars($order->salary) . '</p>';
        }
        
        if ($order->teacher_type) {
            $html .= '<p style="margin: 10px 0;"><strong>老师类型：</strong>' . htmlspecialchars($order->teacher_type) . '</p>';
        }
        
        $html .= '</div>';
        
        $html .= '<div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px;">';
        $html .= '<p style="margin: 0 0 10px 0;"><strong>详细信息：</strong></p>';
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; line-height: 1.6;">';
        $html .= nl2br(htmlspecialchars($order->content));
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 3px; margin-bottom: 15px;">';
        $html .= '<p style="margin: 0; color: #856404; font-size: 14px;">';
        $html .= '💡 如果您对此家教信息感兴趣，请联系平台获取详细联系方式。';
        $html .= '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '<div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">';
        $html .= '<p style="margin: 0;">此邮件由系统自动发送，请勿直接回复。</p>';
        $html .= '<p style="margin: 5px 0 0 0;">如需取消订阅，请登录平台管理您的订阅设置。</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
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
            if (!is_array($data) || $data === []) {
                $json = json_decode((string) $this->request->getContent(), true);
                if (is_array($json)) {
                    $data = $json;
                }
            }
            if (!is_array($data)) {
                $data = [];
            }
            
            // 查找订单（超级管理员、客服组长可编辑全部；普通客服仅能编辑归属自己的订单）
            $query = ParentOrder::where('id', $id);
            
            if (!$this->canViewAllOrders()) {
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
                    if (array_key_exists($field, $data)) {
                        $order->$field = $data[$field];
                    }
                }
                
                // 归属管理员：仅超级管理员、客服组长可修改
                if ($this->canViewAllOrders() && array_key_exists('admin_id', $data)) {
                    $newAid = (int) $data['admin_id'];
                    if ($newAid <= 0) {
                        $order->admin_id = 0;
                    } else {
                        $targetAdmin = Admin::where('id', $newAid)->where('status', 1)->find();
                        if (!$targetAdmin) {
                            Db::rollback();
                            return json(['code' => 400, 'message' => '所选管理员不存在或已禁用']);
                        }
                        if ($admin->role === 'team_leader') {
                            $allowedIds = Admin::where('leader_id', $admin->id)->where('status', 1)->column('id');
                            $allowedIds[] = (int) $admin->id;
                            if (!in_array($newAid, array_map('intval', $allowedIds), true)) {
                                Db::rollback();
                                return json(['code' => 403, 'message' => '仅能将订单归属给本组客服']);
                            }
                        }
                        $order->admin_id = $newAid;
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
    
    /**
     * 测试获取订单列表（无需认证）
     * GET /api/order/test-list
     */
    public function testList()
    {
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            $isChannel = $this->request->get('is_channel', '');
            
            // 构建查询
            $query = ParentOrder::order('create_time', 'desc');
            
            // 如果有is_channel参数，可以根据需要过滤
            if ($isChannel !== '') {
                // 这里可以根据需要添加过滤逻辑
                // 例如：$query->where('booking_channel', $isChannel == 1 ? '小程序' : 'H5');
            }
            
            // 分页查询
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page,
            ]);
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $result->items(),
                    'total' => $result->total(),
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取订单列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 测试获取订单统计（无需认证）
     * GET /api/order/test-stats
     */
    public function testStats()
    {
        try {
            $total = ParentOrder::count();
            $pending = ParentOrder::where('status', 'pending')->count();
            $approved = ParentOrder::where('status', 'approved')->count();
            $rejected = ParentOrder::where('status', 'rejected')->count();
            
            return json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'pending' => $pending,
                    'approved' => $approved,
                    'rejected' => $rejected
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取订单统计失败：' . $e->getMessage()
            ]);
        }
    }
}




