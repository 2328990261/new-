<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Teacher as TeacherModel;
use app\service\WechatNotificationService;
use app\service\SubscribeMessageService;
use app\service\TeacherMiniOpenidResolver;
use think\facade\Db;
use think\facade\Log;
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
            $cityId = $this->request->get('city_id'); // 授课城市筛选
            $districtIds = $this->request->get('district_ids'); // 授课区域筛选（多选）
            $subjectIds = $this->request->get('subject_ids'); // 科目筛选（多选）
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 构建查询条件
            $where = [];
            
            if ($keyword) {
                $where[] = function($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                          ->whereOr('phone', 'like', '%' . $keyword . '%')
                          ->whereOr('wechat_id', 'like', '%' . $keyword . '%')
                          ->whereOr('teacher_no', 'like', '%' . $keyword . '%')
                          ->whereOr('school', 'like', '%' . $keyword . '%')
                          ->whereOr('hometown', 'like', '%' . $keyword . '%')
                          ->whereOr('major', 'like', '%' . $keyword . '%')
                          ->whereOr('personal_advantage', 'like', '%' . $keyword . '%')
                          ->whereOr('self_intro', 'like', '%' . $keyword . '%')
                          ->whereOr('experience', 'like', '%' . $keyword . '%');

                    // 支持编号搜索：T1022 / 1022 -> teacher_no 或 id 精确匹配
                    $k = trim((string)$keyword);
                    if (preg_match('/^T?\d+$/i', $k)) {
                        $num = (int)preg_replace('/\D+/', '', $k);
                        if ($num > 0) {
                            $query->whereOr('id', '=', $num)
                                  ->whereOr('teacher_no', '=', $num);
                        }
                    }
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
            
            // 科目筛选（多选）- 使用 JSON_CONTAINS 或 LIKE 查询
            if ($subjectIds) {
                // 如果是字符串，转换为数组
                if (is_string($subjectIds)) {
                    $subjectIds = explode(',', $subjectIds);
                }
                
                if (!empty($subjectIds)) {
                    $where[] = function($query) use ($subjectIds) {
                        foreach ($subjectIds as $subjectId) {
                            $query->whereOr('subject_ids', 'like', '%"' . $subjectId . '"%');
                        }
                    };
                }
            }
            
            // 如果有城市或区域筛选，需要关联 teacher_teaching_info 表
            if ($cityId || $districtIds) {
                // 先从 teacher_teaching_info 表中查询符合条件的 teacher_id
                $teachingInfoQuery = \think\facade\Db::name('teacher_teaching_info');
                
                if ($cityId) {
                    $teachingInfoQuery->where('city_id', $cityId);
                }
                
                if ($districtIds) {
                    // 如果是字符串，转换为数组
                    if (is_string($districtIds)) {
                        $districtIds = explode(',', $districtIds);
                    }
                    
                    if (!empty($districtIds)) {
                        $teachingInfoQuery->where(function($query) use ($districtIds) {
                            foreach ($districtIds as $districtId) {
                                $query->whereOr('districts', 'like', '%"id":' . $districtId . '%');
                            }
                        });
                    }
                }
                
                // 获取符合条件的 teacher_id 列表
                $teacherIds = $teachingInfoQuery->column('teacher_id');

                // 城市筛选：同时支持「所在城市(location_address)」与「授课城市(teacher_teaching_info.city_id)」
                // 说明：当同时筛选了具体区域时，仅按授课信息筛选（所在城市无法精确到区域）
                if ($cityId && !$districtIds) {
                    $cityName = trim((string)Db::name('cities')->where('id', (int)$cityId)->value('name'));
                    if ($cityName !== '') {
                        $locationTeacherIds = TeacherModel::where('location_address', 'like', '%' . $cityName . '%')->column('id');
                        if (!empty($locationTeacherIds)) {
                            $teacherIds = array_values(array_unique(array_merge($teacherIds ?: [], $locationTeacherIds)));
                        }
                    }
                }

                if (empty($teacherIds)) {
                    // 如果没有符合条件的教师，直接返回空结果
                    return json([
                        'success' => true,
                        'data' => [
                            'list' => [],
                            'total' => 0,
                            'page' => (int)$page,
                            'limit' => (int)$limit
                        ]
                    ]);
                }

                // 添加 teacher_id 筛选条件
                $where[] = ['id', 'in', $teacherIds];
            }
            
            // 获取总数
            $total = TeacherModel::where($where)->count();
            
            // 获取数据列表 - 按最新登录时间排序，然后按置顶和创建时间
            $list = TeacherModel::where($where)
                ->order('last_login_time', 'desc')
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
                // 若教师表头像为空，用 fa_users 表头像(openid 关联，微信/登录头像)作为列表头像
                if (empty($itemArray['avatar']) && !empty($item->openid)) {
                    $user = Db::name('users')->where('openid', $item->openid)->field('avatar')->find();
                    if ($user && !empty($user['avatar'])) {
                        $itemArray['avatar'] = $user['avatar'];
                    }
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
     * 获取上一老师/下一老师 ID（优先按 teacher_no 顺序，无该字段或异常时按 id）
     */
    public function prevNext($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        $currentId = (int) $id;
        if ($currentId <= 0) {
            return json(['success' => true, 'data' => ['prev_id' => null, 'next_id' => null]]);
        }
        try {
            $current = TeacherModel::find($currentId);
            if (!$current) {
                return json(['success' => true, 'data' => ['prev_id' => null, 'next_id' => null]]);
            }
            $prev = null;
            $next = null;
            // 优先按 teacher_no 判断（表有该字段且当前有值时才用）
            $useTeacherNo = isset($current->teacher_no) && $current->teacher_no !== '' && $current->teacher_no !== null;
            if ($useTeacherNo) {
                try {
                    $currentNo = (int) $current->teacher_no;
                    $prev = TeacherModel::whereRaw('COALESCE(teacher_no, 0) < ?', [$currentNo])
                        ->orderRaw('COALESCE(teacher_no, 0) DESC')
                        ->value('id');
                    $next = TeacherModel::whereRaw('COALESCE(teacher_no, 0) > ?', [$currentNo])
                        ->orderRaw('COALESCE(teacher_no, 0) ASC')
                        ->value('id');
                } catch (\Throwable $e) {
                    $useTeacherNo = false;
                }
            }
            if (!$useTeacherNo) {
                $prev = TeacherModel::where('id', '<', $currentId)->order('id', 'desc')->value('id');
                $next = TeacherModel::where('id', '>', $currentId)->order('id', 'asc')->value('id');
            }
            return json([
                'success' => true,
                'data' => [
                    'prev_id' => $prev ? (int) $prev : null,
                    'next_id' => $next ? (int) $next : null
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
            
            // 确保所有字段都存在，即使为空（避免前端/序列化因空值报错）
            $data['self_intro'] = $data['self_intro'] ?? '';
            $data['wechat_nickname'] = $data['wechat_nickname'] ?? '';
            $data['openid'] = $data['openid'] ?? '';
            $data['last_login_time'] = $data['last_login_time'] ?? null;
            $data['birth_date'] = isset($data['birth_date']) && $data['birth_date'] !== '' ? $data['birth_date'] : null;
            
            // 获取教师授课信息
            $teachingInfo = \app\model\TeacherTeachingInfo::getByTeacher($id, $data['openid'] ?? null, $data['phone'] ?? null);
            if ($teachingInfo) {
                $data['teaching_info'] = [
                    'city_name' => $teachingInfo->city_name ?? '',
                    'districts' => $teachingInfo->districts ?? [],
                    'grades' => $teachingInfo->grades ?? [],
                    'subjects' => $teachingInfo->subjects ?? []
                ];
            } else {
                $data['teaching_info'] = null;
            }
            
            // 微信昵称旁的头像使用用户表 fa_users 的 avatar，优先 account_id 再 openid 关联
            $data['user_avatar'] = '';
            if (!empty($teacher->account_id)) {
                $user = Db::name('users')->where('id', $teacher->account_id)->field('avatar')->find();
                if ($user && !empty($user['avatar'])) {
                    $data['user_avatar'] = $user['avatar'];
                }
            }
            if (empty($data['user_avatar']) && !empty($teacher->openid)) {
                $user = Db::name('users')->where('openid', $teacher->openid)->field('avatar')->find();
                if ($user && !empty($user['avatar'])) {
                    $data['user_avatar'] = $user['avatar'];
                }
            }
            // 若教师表头像(photos.avatar)为空，用用户表头像(微信/登录头像)作为主头像，保证后台能显示真实头像
            if (empty($data['avatar']) && !empty($data['user_avatar'])) {
                $data['avatar'] = $data['user_avatar'];
            }

            // 分享者：1) 邀请活动 user_invitations.inviter_openid  2) 小程序分享链路 users.superior_openid（与登录注册绑定一致）
            $data['sharer_nickname'] = '';
            $data['sharer_avatar'] = '';
            if (!empty($teacher->openid)) {
                $invitation = Db::name('user_invitations')
                    ->where('invitee_openid', $teacher->openid)
                    ->order('is_rewarded', 'desc')
                    ->order('reward_time', 'desc')
                    ->order('verify_time', 'desc')
                    ->order('create_time', 'desc')
                    ->find();
                if ($invitation) {
                    $inviter = null;
                    if (!empty($invitation['inviter_openid'])) {
                        $inviter = Db::name('users')
                            ->where('openid', $invitation['inviter_openid'])
                            ->field('nickname,avatar')
                            ->find();
                    }
                    if (!$inviter && !empty($invitation['inviter_user_id'])) {
                        $inviter = Db::name('users')
                            ->where('id', (int) $invitation['inviter_user_id'])
                            ->field('nickname,avatar')
                            ->find();
                    }
                    if ($inviter) {
                        $data['sharer_nickname'] = (string)($inviter['nickname'] ?? '');
                        $data['sharer_avatar'] = (string)($inviter['avatar'] ?? '');
                    }
                }
            }
            // 邀请表未建链或邀请人资料为空时，用该教师对应用户行的 superior_openid 反查分享者（优师精选/分享注册常见）
            $teacherUser = null;
            if (!empty($teacher->account_id)) {
                $teacherUser = Db::name('users')
                    ->where('id', $teacher->account_id)
                    ->field('superior_openid')
                    ->find();
            }
            if (!$teacherUser && !empty($teacher->openid)) {
                $teacherUser = Db::name('users')
                    ->where('openid', $teacher->openid)
                    ->field('superior_openid')
                    ->find();
            }
            $superiorOpenid = trim((string)($teacherUser['superior_openid'] ?? ''));
            $teacherOpenidTrim = trim((string)($teacher->openid ?? ''));
            if ($superiorOpenid !== '' && $superiorOpenid !== $teacherOpenidTrim) {
                $superiorUser = Db::name('users')
                    ->where('openid', $superiorOpenid)
                    ->field('nickname,avatar')
                    ->find();
                if ($superiorUser) {
                    if ($data['sharer_nickname'] === '') {
                        $data['sharer_nickname'] = (string)($superiorUser['nickname'] ?? '');
                    }
                    if ($data['sharer_avatar'] === '') {
                        $data['sharer_avatar'] = (string)($superiorUser['avatar'] ?? '');
                    }
                }
            }
            
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
            // 各项认证开关（0/1）
            $realNameVerified = (int)$this->request->post('real_name_verified', 0);
            $educationVerified = (int)$this->request->post('education_verified', 0);
            $teacherVerified = (int)$this->request->post('teacher_verified', 0);
            $reason = $this->request->post('reason', '');
            
            $hasAnyCertification = ($realNameVerified || $educationVerified || $teacherVerified);
            
            // 整体审核状态以请求参数为准（与后台单选一致）；未传时兼容旧逻辑：按三项认证推导
            $status = $this->request->post('status');
            if ($status === null || $status === '') {
                $status = $hasAnyCertification ? 'approved' : 'rejected';
            } else {
                $status = (string) $status;
            }
            if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
                return json(['success' => false, 'error' => '审核状态参数错误']);
            }
            // 审核通过必须至少一项材料认证通过
            if ($status === 'approved' && !$hasAnyCertification) {
                return json(['success' => false, 'error' => '审核通过需至少一项认证材料通过']);
            }
            
            // 拒绝且未填备注时使用默认文案；待审核、通过不要求默认备注
            if ($status === 'rejected' && $reason === '') {
                $reason = '您的提交认证资料不齐全，请重新上传完整且有效的证件信息重新审核。';
            }
            
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            // 更新审核状态与备注
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
            
            // 简历认证通过：若该教师是被邀请人，则发放邀请双方优惠券
            if ($status === 'approved' && !empty($teacher->openid)) {
                \app\service\InvitationService::grantCouponsForApprovedInvitee($teacher->openid);
            }
            // 教师管理页审核：改为小程序订阅消息通知（失败不影响审核主流程）
            $miniNotify = $this->notifyReviewByMiniSubscribe($teacher);
            
            $statusText = [
                'pending' => '待审核',
                'approved' => '审核通过',
                'rejected' => '审核拒绝'
            ];
            
            return json([
                'success' => true,
                'message' => '审核状态已更新为：' . $statusText[$status],
                'data' => [
                    'mini_subscribe_notify' => $miniNotify
                ]
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
                'hometown', 'teaching_years', 'birth_date',
                'location_province', 'location_city', 'location_district', 'location_address',
                'education', 'school', 'major', 'teacher_type', 'grade_level', 'education_level',
                'hourly_rate', 'subject_ids', 'subject_names', 'district_ids', 'district_names',
                'experience', 'experiences', 'self_intro', 'personal_advantage',
                'advantage_tags', 'photos', 'avatar', 'teaching_photos',
                'status', 'is_top',
                'real_name_verified', 'education_verified', 'teacher_verified',
                'id_card_front', 'id_card_back', 'education_certificate', 'teacher_certificate'
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

            // 认证字段类型修正
            if (array_key_exists('real_name_verified', $updateData)) {
                $updateData['real_name_verified'] = (int)$updateData['real_name_verified'];
            }
            if (array_key_exists('education_verified', $updateData)) {
                $updateData['education_verified'] = (int)$updateData['education_verified'];
            }
            if (array_key_exists('teacher_verified', $updateData)) {
                $updateData['teacher_verified'] = (int)$updateData['teacher_verified'];
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

    /**
     * 获取各城市老师数量统计（所在城市 + 授课城市合并去重）
     */
    public function cityStats()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }

        try {
            // 说明：
            // - 所在城市：从 teachers.location_address 字段中提取城市信息，模糊匹配 cities.name
            // - 授课城市：teacher_teaching_info.city_id 精确匹配
            // - 合并后按城市维度对 teacher_id 去重计数
            $prefix = (string) (config('database.connections.mysql.prefix') ?? '');
            $tTeachers = $prefix . 'teachers';
            $tCities = $prefix . 'cities';
            $tTeaching = $prefix . 'teacher_teaching_info';
            $sql = <<<SQL
SELECT x.city_id, x.city_name, COUNT(DISTINCT x.teacher_id) AS count
FROM (
    SELECT c.id AS city_id, c.name AS city_name, t.id AS teacher_id
    FROM {$tTeachers} t
    INNER JOIN {$tCities} c ON t.location_address LIKE CONCAT('%', c.name, '%')
    WHERE t.location_address IS NOT NULL AND t.location_address != ''
    UNION
    SELECT c.id AS city_id, c.name AS city_name, ti.teacher_id AS teacher_id
    FROM {$tTeaching} ti
    INNER JOIN {$tCities} c ON ti.city_id = c.id
) x
GROUP BY x.city_id, x.city_name
ORDER BY count DESC
SQL;

            $rows = Db::query($sql);

            return json([
                'success' => true,
                'data' => $rows
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取城市统计失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 生成教师小程序海报
     */
    public function generatePoster()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $teacherId = $this->request->post('teacher_id');
            
            if (empty($teacherId)) {
                return json(['success' => false, 'error' => '缺少教师ID']);
            }
            
            // 获取教师信息
            $teacher = TeacherModel::find($teacherId);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            // 调用小程序服务
            $wechatService = new \app\service\WechatMiniProgramService();
            
            // 生成二维码
            $qrcodeResult = $wechatService->generateQRCode(
                'pages/teacher-detail/index',
                'id=' . $teacherId,
                [
                    'width' => 430,
                    'env_version' => 'release',
                    'check_path' => false,
                    'is_hyaline' => true
                ]
            );
            
            // 生成短链接
            // 尝试生成多种小程序链接格式
            $links = [];
            
            error_log("=== 开始生成教师海报链接 ===");
            error_log("教师ID: " . $teacherId);
            error_log("教师姓名: " . $teacher->name);
            
            // 1. 尝试生成 URL Scheme（适用于从外部打开小程序）
            error_log("\n--- 尝试生成 URL Scheme ---");
            $urlSchemeResult = $wechatService->generateUrlScheme(
                'pages/teacher-detail/index',
                'id=' . $teacherId,
                [
                    'is_expire' => false,
                    'expire_type' => 0
                ]
            );
            
            if ($urlSchemeResult['success'] && !empty($urlSchemeResult['data'])) {
                $links['url_scheme'] = [
                    'type' => 'URL Scheme',
                    'link' => $urlSchemeResult['data'],
                    'description' => '适用于从外部浏览器、短信等打开小程序'
                ];
                error_log("URL Scheme 生成成功");
            } else {
                $links['url_scheme_error'] = $urlSchemeResult['error'] ?? '未知错误';
                error_log("URL Scheme 生成失败: " . ($urlSchemeResult['error'] ?? '未知错误'));
            }
            
            // 2. 尝试生成 Short Link（适用于微信内分享）
            error_log("\n--- 尝试生成 Short Link ---");
            $shortLinkResult = $wechatService->generateShortLink(
                'pages/teacher-detail/index',
                'id=' . $teacherId,
                [
                    'is_permanent' => true
                ]
            );
            
            if ($shortLinkResult['success'] && !empty($shortLinkResult['data'])) {
                $links['short_link'] = [
                    'type' => 'Short Link',
                    'link' => $shortLinkResult['data'],
                    'description' => '适用于微信内分享'
                ];
                error_log("Short Link 生成成功");
            } else {
                $links['short_link_error'] = $shortLinkResult['error'] ?? '未知错误';
                error_log("Short Link 生成失败: " . ($shortLinkResult['error'] ?? '未知错误'));
            }
            
            // 3. 生成页面路径（备用方案）
            $links['page_path'] = [
                'type' => '页面路径',
                'link' => 'pages/teacher-detail/index?id=' . $teacherId,
                'description' => '在小程序内使用'
            ];
            error_log("页面路径已生成");
            
            error_log("=== 教师海报链接生成完成 ===\n");
            
            if ($qrcodeResult['success']) {
                $responseData = [
                    'qrcode' => $qrcodeResult['data'],
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'avatar' => $teacher->photos['avatar'] ?? '',
                        'school' => $teacher->school,
                        'major' => $teacher->major,
                        'hourly_rate' => $teacher->hourly_rate
                    ],
                    'links' => $links  // 返回所有链接
                ];
                
                return json([
                    'success' => true,
                    'data' => $responseData
                ]);
            } else {
                return json(['success' => false, 'error' => $qrcodeResult['error']]);
            }
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 审核结果通知：按 unionid 关联已关注公众号账号发送模板消息
     * 仅作为通知，失败不影响审核主流程
     */
    private function notifyReviewByOfficialAccount($teacher): void
    {
        try {
            $miniOpenid = trim((string)($teacher->openid ?? ''));
            if ($miniOpenid === '') {
                Log::warning('教师管理审核通知跳过: teacher_openid为空');
                return;
            }

            $officialOpenid = $this->resolveOfficialOpenidByUnionid($miniOpenid);
            if ($officialOpenid === null) {
                Log::warning('教师管理审核通知跳过: 未解析到服务号openid, mini_openid=' . $miniOpenid);
                try {
                    Db::name('notification_logs')->insert([
                        'channel' => 'wechat',
                        'receiver' => $miniOpenid,
                        'template_code' => 'resume_review_notify',
                        'send_data' => json_encode(['mini_openid' => $miniOpenid], JSON_UNESCAPED_UNICODE),
                        'status' => 0,
                        'error_msg' => '未解析到服务号openid（请检查 wechat_openid_bindings 绑定）',
                        'send_time' => date('Y-m-d H:i:s')
                    ]);
                } catch (\Throwable $e) {
                    // ignore logging failure
                }
                return;
            }

            $resultText = ((string)$teacher->review_status === 'approved') ? '审核通过' : '审核驳回';
            $remark = trim((string)($teacher->review_note ?? ''));
            if ($remark === '') {
                $remark = $resultText === '审核通过'
                    ? '您的简历已通过审核，请尽快完善资料并接单。'
                    : '您的简历未通过审核，请根据提示修改后重新提交。';
            }

            $sendRes = WechatNotificationService::sendTemplateMessage($officialOpenid, 'resume_review_notify', [
                'result' => $resultText,
                'review_time' => date('Y-m-d H:i:s'),
                'remark' => $remark,
                'teacher_name' => (string)($teacher->name ?? ''),
                'review_status' => (string)($teacher->review_status ?? '')
            ]);

            if (empty($sendRes['success'])) {
                Log::warning('教师管理审核公众号通知发送失败: ' . ($sendRes['message'] ?? 'unknown'));
            } else {
                Log::info('教师管理审核公众号通知发送成功, to=' . $officialOpenid);
            }
        } catch (\Throwable $e) {
            Log::error('教师管理审核公众号通知异常: ' . $e->getMessage());
        }
    }

    /**
     * 审核结果通知：小程序订阅消息
     * 仅作为通知，失败不影响审核主流程
     */
    private function notifyReviewByMiniSubscribe($teacher): array
    {
        try {
            $miniOpenid = TeacherMiniOpenidResolver::resolve($teacher);
            if ($miniOpenid === '') {
                return ['success' => false, 'message' => 'teacher_openid为空（教师表无 openid，且未通过 account_id/手机号匹配到 users）'];
            }

            $resultText = ((string)($teacher->review_status ?? '') === 'approved') ? '审核通过' : '审核驳回';
            $remark = trim((string)($teacher->review_note ?? ''));
            if ($remark === '') {
                $remark = $resultText === '审核通过'
                    ? '您的简历已通过审核，请尽快完善资料并接单。'
                    : '您的简历未通过审核，请根据提示修改后重新提交。';
            }
            $reviewTime = (string)($teacher->review_time ?? '') ?: date('Y-m-d H:i:s');
            // 必须带 teacher_id，否则小程序预览页 onLoad 不会拉取后端数据（从订阅消息直达时空白）
            $tid = (int)($teacher->id ?? 0);
            $rv = (string)($teacher->review_status ?? '');
            $page = 'pages/teacher-resume-preview/index?readonly=true'
                . ($tid > 0 ? '&teacher_id=' . $tid : '')
                . ($rv !== '' ? '&status=' . rawurlencode($rv) : '');

            $sendRes = SubscribeMessageService::sendResumeReviewMessage($miniOpenid, [
                'result' => $resultText,
                'remark' => $remark,
                'review_time' => $reviewTime,
                'page' => $page,
            ]);

            return [
                'success' => !empty($sendRes['success']),
                'message' => (string)($sendRes['message'] ?? ''),
                'mini_openid' => $miniOpenid,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 通过 mini_openid/unionid 解析公众号 openid（优先绑定表）
     */
    private function resolveOfficialOpenidByUnionid(string $miniOpenid): ?string
    {
        $this->ensureMiniUnionidBinding($miniOpenid);

        $bind = Db::name('wechat_openid_bindings')
            ->where('mini_openid', $miniOpenid)
            ->order('update_time', 'desc')
            ->field('mp_openid,unionid,is_subscribed')
            ->find();
        if ($bind && !empty($bind['mp_openid'])) {
            $mpOpenid = trim((string)$bind['mp_openid']);
            if ((int)($bind['is_subscribed'] ?? 0) === 1) {
                return $mpOpenid;
            }
            // 兜底：实时查一次关注状态，避免绑定后未刷新导致不发送
            $live = WechatNotificationService::getUserInfo($mpOpenid);
            if (!empty($live['success']) && !empty($live['data']) && is_array($live['data'])) {
                $sub = (int)($live['data']['subscribe'] ?? 0);
                if ($sub === 1) {
                    $now = date('Y-m-d H:i:s');
                    Db::name('wechat_openid_bindings')
                        ->where('mini_openid', $miniOpenid)
                        ->update([
                            'is_subscribed' => 1,
                            'subscribe_time' => (int)($live['data']['subscribe_time'] ?? time()),
                            'unionid' => trim((string)($live['data']['unionid'] ?? ($bind['unionid'] ?? ''))) ?: null,
                            'update_time' => $now
                        ]);
                    Db::name('wechat_users')
                        ->where('openid', $mpOpenid)
                        ->update([
                            'subscribe' => 1,
                            'subscribe_time' => (int)($live['data']['subscribe_time'] ?? time()),
                            'unionid' => trim((string)($live['data']['unionid'] ?? '')) ?: null,
                            'update_time' => $now
                        ]);
                    return $mpOpenid;
                }
            }
        }

        $base = Db::name('wechat_users')
            ->where('openid', $miniOpenid)
            ->field('openid,unionid,subscribe')
            ->find();
        if (!$base) {
            return null;
        }

        $unionid = trim((string)($base['unionid'] ?? ''));
        if ($unionid !== '') {
            $bindByUnion = Db::name('wechat_openid_bindings')
                ->where('unionid', $unionid)
                ->where('mp_openid', '<>', '')
                ->order('update_time', 'desc')
                ->field('mp_openid,is_subscribed')
                ->find();
            if ($bindByUnion && !empty($bindByUnion['mp_openid']) && (int)($bindByUnion['is_subscribed'] ?? 1) === 1) {
                return trim((string)$bindByUnion['mp_openid']);
            }

            $official = Db::name('wechat_users')
                ->where('unionid', $unionid)
                ->where('subscribe', 1)
                ->where('openid', '<>', '')
                ->order('update_time', 'desc')
                ->order('create_time', 'desc')
                ->field('openid')
                ->find();
            if (!empty($official['openid'])) {
                return trim((string)$official['openid']);
            }
        }

        return null;
    }

    /**
     * 发送前兜底：将小程序侧 unionid 回填到绑定表
     */
    private function ensureMiniUnionidBinding(string $miniOpenid): void
    {
        try {
            $miniOpenid = trim((string)$miniOpenid);
            if ($miniOpenid === '') {
                return;
            }
            $unionid = trim((string)(Db::name('wechat_users')
                ->where('openid', $miniOpenid)
                ->value('unionid') ?? ''));
            if ($unionid === '') {
                return;
            }
            $now = date('Y-m-d H:i:s');
            $bind = Db::name('wechat_openid_bindings')->where('mini_openid', $miniOpenid)->find();
            if ($bind) {
                if (trim((string)($bind['unionid'] ?? '')) === '') {
                    Db::name('wechat_openid_bindings')->where('id', (int)$bind['id'])->update([
                        'unionid' => $unionid,
                        'update_time' => $now
                    ]);
                }
            } else {
                Db::name('wechat_openid_bindings')->insert([
                    'mini_openid' => $miniOpenid,
                    'unionid' => $unionid,
                    'scene_key' => 'send_before_' . $miniOpenid,
                    'is_subscribed' => 0,
                    'create_time' => $now,
                    'update_time' => $now
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('教师审核发送前 unionid 回填失败: ' . $e->getMessage());
        }
    }
}

