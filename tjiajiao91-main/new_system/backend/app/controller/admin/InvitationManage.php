<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\facade\Session;

/**
 * 邀请管理控制器
 */
class InvitationManage extends BaseController
{
    /**
     * 获取当前登录管理员信息（与 Auth 中间件一致）
     * @return array{0:int|null,1:string}
     */
    private function getLoginAdmin()
    {
        // Auth 中间件会注入到 request
        $adminId = $this->request->adminId ?? null;
        $adminNickname = $this->request->adminNickname ?? '';

        // Auth 中间件使用原生 $_SESSION
        if (!$adminId) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $adminId = $_SESSION['admin_id'] ?? null;
            $adminNickname = $_SESSION['admin_nickname'] ?? '';
        }

        // 兼容其它地方可能写入的 session key
        if (!$adminId) {
            $adminId = session('admin_id') ?: \think\facade\Session::get('admin_id');
        }
        if (!$adminNickname) {
            $adminNickname = (string) (session('admin_nickname') ?: \think\facade\Session::get('admin_name') ?: \think\facade\Session::get('admin_nickname'));
        }

        return [$adminId ? (int) $adminId : null, (string) $adminNickname];
    }

    /**
     * 获取邀请统计概览
     * GET /admin/invitation/overview
     */
    public function overview()
    {
        $data = [
            'totalParticipants' => 0,
            'totalInvitations' => 0,
            'verifiedInvitations' => 0,
            'pendingInvitations' => 0,
            'totalCoupons' => 0,
            'receivedCoupons' => 0,
            'redeemedCoupons' => 0,
            'pendingCoupons' => 0,
            'totalCouponAmount' => 0,
            'redeemedAmount' => 0
        ];
        try {
            $data['totalParticipants'] = (int) Db::name('invitation_ranking')->count();
            $data['totalInvitations'] = (int) Db::name('user_invitations')->count();
            $data['verifiedInvitations'] = (int) Db::name('user_invitations')->where('status', 1)->count();
            $data['pendingInvitations'] = $data['totalInvitations'] - $data['verifiedInvitations'];
            $data['totalCoupons'] = (int) Db::name('user_coupons')->count();
            $data['receivedCoupons'] = (int) Db::name('user_coupons')->whereIn('status', [1, 2])->count();
            $data['redeemedCoupons'] = (int) Db::name('user_coupons')->where('status', 2)->count();
            $data['pendingCoupons'] = (int) Db::name('user_coupons')->where('status', 0)->count();
            $data['totalCouponAmount'] = (float) Db::name('user_coupons')->sum('coupon_amount');
            $data['redeemedAmount'] = (float) Db::name('user_coupons')->where('status', 2)->sum('coupon_amount');
        } catch (\Throwable $e) {
            trace('获取邀请概览失败: ' . $e->getMessage(), 'error');
            // 表不存在或 SQL 错误时仍返回 200，数据为 0，避免前端报错
        }
        return json(['code' => 200, 'message' => '获取成功', 'data' => $data]);
    }
    
    /**
     * 获取邀请记录列表
     * GET /admin/invitation/list
     */
    public function invitationList()
    {
        $page = (int) $this->request->get('page', 1) ?: 1;
        $pageSize = (int) $this->request->get('page_size', 20) ?: 20;
        $status = $this->request->get('status', '');
        $keyword = trim((string) $this->request->get('keyword', ''));
        $prefix = $this->getTablePrefix();
        $usersTable = $prefix . 'users';
        try {
            $query = Db::name('user_invitations')
                ->alias('i')
                ->leftJoin($usersTable . ' inviter', 'i.inviter_openid = inviter.openid')
                ->leftJoin($usersTable . ' invitee', 'i.invitee_openid = invitee.openid')
                ->field('i.*, inviter.nickname as inviter_name, inviter.phone as inviter_phone, 
                        invitee.nickname as invitee_name, invitee.phone as invitee_phone');
            if ($status !== '') {
                $query->where('i.status', (int)$status);
            }
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereOr('inviter.nickname', 'like', "%{$keyword}%")
                      ->whereOr('inviter.phone', 'like', "%{$keyword}%")
                      ->whereOr('invitee.nickname', 'like', "%{$keyword}%")
                      ->whereOr('invitee.phone', 'like', "%{$keyword}%")
                      ->whereOr('i.invitation_code', 'like', "%{$keyword}%");
                });
            }
            $total = $query->count();
            $list = $query->order('i.create_time', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]
            ]);
        } catch (\Throwable $e) {
            trace('获取邀请列表失败: ' . $e->getMessage(), 'error');
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize]
            ]);
        }
    }
    
    /**
     * 获取优惠券列表
     * GET /admin/invitation/coupon-list
     */
    public function couponList()
    {
        $page = (int) $this->request->get('page', 1) ?: 1;
        $pageSize = (int) $this->request->get('page_size', 20) ?: 20;
        $status = $this->request->get('status', '');
        $keyword = trim((string) $this->request->get('keyword', ''));
        $prefix = $this->getTablePrefix();
        $usersTable = $prefix . 'users';
        try {
            $query = Db::name('user_coupons')
                ->alias('c')
                ->leftJoin($usersTable . ' u', 'c.openid = u.openid')
                ->field('c.*, u.nickname, u.phone, u.avatar as avatar_url');
            if ($status !== '') {
                $query->where('c.status', (int)$status);
            }
            if ($keyword !== '') {
                $query->where(function ($q) use ($keyword) {
                    $q->whereOr('u.nickname', 'like', "%{$keyword}%")
                      ->whereOr('u.phone', 'like', "%{$keyword}%")
                      ->whereOr('c.openid', 'like', "%{$keyword}%")
                      ->whereOr('c.coupon_code', 'like', "%{$keyword}%");
                });
            }
            $total = $query->count();
            // 按状态排序：待审核(4) > 已领取(1) > 已兑换(2) > 已过期(3) > 待领取(0)，同状态按创建时间倒序
            $list = $query->orderRaw('FIELD(c.status, 4, 1, 2, 3, 0)')
                ->order('c.create_time', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]
            ]);
        } catch (\Throwable $e) {
            trace('获取优惠券列表失败: ' . $e->getMessage(), 'error');
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize]
            ]);
        }
    }
    
    /**
     * 人工兑换优惠券
     * POST /admin/invitation/redeem-coupon
     */
    public function redeemCoupon()
    {
        try {
            $couponId = $this->request->post('coupon_id', 0);
            $note = $this->request->post('note', '');
            
            if (!$couponId) {
                return json(['code' => 400, 'message' => '优惠券ID不能为空']);
            }
            
            // 获取管理员信息（与 Auth 中间件一致）
            [$adminId, $adminName] = $this->getLoginAdmin();
            if (!$adminId) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 查找优惠券
            $coupon = Db::name('user_coupons')->where('id', $couponId)->find();
            
            if (!$coupon) {
                return json(['code' => 404, 'message' => '优惠券不存在']);
            }
            
            if ($coupon['status'] == 2) {
                return json(['code' => 400, 'message' => '优惠券已兑换']);
            }
            if ($coupon['status'] == 3) {
                return json(['code' => 400, 'message' => '优惠券已过期']);
            }
            // 仅允许 1-已领取 或 4-待审核 进行人工兑换
            if (!in_array((int)$coupon['status'], [1, 4], true)) {
                return json(['code' => 400, 'message' => '该优惠券状态不可兑换']);
            }
            
            // 开启事务
            Db::startTrans();
            try {
                // 更新优惠券状态
                Db::name('user_coupons')->where('id', $couponId)->update([
                    'status' => 2,
                    'redeem_time' => date('Y-m-d H:i:s'),
                    'redeem_admin_id' => $adminId,
                    'redeem_note' => $note
                ]);
                
                // 记录兑换日志
                Db::name('coupon_redeem_log')->insert([
                    'coupon_id' => $couponId,
                    'user_id' => $coupon['user_id'],
                    'openid' => $coupon['openid'],
                    'admin_id' => $adminId,
                    'admin_name' => $adminName,
                    'coupon_amount' => $coupon['coupon_amount'],
                    'redeem_note' => $note,
                    'create_time' => date('Y-m-d H:i:s')
                ]);
                
                // 更新排行榜数据
                $this->updateRankingAfterRedeem($coupon['openid']);
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => '兑换成功'
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            trace('兑换优惠券失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量兑换优惠券
     * POST /admin/invitation/batch-redeem
     */
    public function batchRedeem()
    {
        try {
            $couponIds = $this->request->post('coupon_ids', []);
            $note = $this->request->post('note', '');
            
            if (empty($couponIds)) {
                return json(['code' => 400, 'message' => '请选择要兑换的优惠券']);
            }
            
            // 获取管理员信息（与 Auth 中间件一致）
            [$adminId, $adminName] = $this->getLoginAdmin();
            if (!$adminId) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $successCount = 0;
            $failCount = 0;
            
            Db::startTrans();
            try {
                foreach ($couponIds as $couponId) {
                    $coupon = Db::name('user_coupons')->where('id', $couponId)->find();
                    
                    if (!$coupon || !in_array((int)$coupon['status'], [1, 4], true)) {
                        $failCount++;
                        continue;
                    }
                    
                    // 更新优惠券状态
                    Db::name('user_coupons')->where('id', $couponId)->update([
                        'status' => 2,
                        'redeem_time' => date('Y-m-d H:i:s'),
                        'redeem_admin_id' => $adminId,
                        'redeem_note' => $note
                    ]);
                    
                    // 记录兑换日志
                    Db::name('coupon_redeem_log')->insert([
                        'coupon_id' => $couponId,
                        'user_id' => $coupon['user_id'],
                        'openid' => $coupon['openid'],
                        'admin_id' => $adminId,
                        'admin_name' => $adminName,
                        'coupon_amount' => $coupon['coupon_amount'],
                        'redeem_note' => $note,
                        'create_time' => date('Y-m-d H:i:s')
                    ]);
                    
                    // 更新排行榜数据
                    $this->updateRankingAfterRedeem($coupon['openid']);
                    
                    $successCount++;
                }
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => "批量兑换完成，成功{$successCount}个，失败{$failCount}个"
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            trace('批量兑换失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 搜索用户的优惠券
     * GET /admin/invitation/search-user-coupons
     */
    public function searchUserCoupons()
    {
        try {
            $keyword = $this->request->get('keyword', '');
            
            if (empty($keyword)) {
                return json(['code' => 400, 'message' => '请输入搜索关键词']);
            }
            
            // 先查找用户（fa_users 表）
            $users = Db::name('users')
                ->where(function($q) use ($keyword) {
                    $q->where('nickname', 'like', "%{$keyword}%")
                      ->whereOr('phone', 'like', "%{$keyword}%");
                })
                ->limit(10)
                ->select()
                ->toArray();
            foreach ($users as &$u) {
                $u['avatar_url'] = $u['avatar'] ?? '';
            }
            unset($u);
            
            $result = [];
            foreach ($users as $user) {
                // 查询该用户的优惠券
                $coupons = Db::name('user_coupons')
                    ->where('openid', $user['openid'])
                    ->select()
                    ->toArray();
                
                $result[] = [
                    'user' => $user,
                    'coupons' => $coupons,
                    'total_coupons' => count($coupons),
                    'received_coupons' => count(array_filter($coupons, function($c) { return in_array($c['status'], [1, 2]); })),
                    'redeemed_coupons' => count(array_filter($coupons, function($c) { return $c['status'] == 2; }))
                ];
            }
            
            return json([
                'code' => 200,
                'message' => '搜索成功',
                'data' => $result
            ]);
            
        } catch (\Throwable $e) {
            trace('搜索用户优惠券失败: ' . $e->getMessage(), 'error');
            return json(['code' => 200, 'message' => '搜索成功', 'data' => []]);
        }
    }
    
    /**
     * 获取排行榜
     * GET /admin/invitation/ranking
     */
    public function ranking()
    {
        $page = (int) $this->request->get('page', 1) ?: 1;
        $pageSize = (int) $this->request->get('page_size', 20) ?: 20;
        try {
            $total = Db::name('invitation_ranking')->count();
            $list = Db::name('invitation_ranking')
                ->order('ranking_score', 'desc')
                ->order('last_invite_time', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['list' => $list, 'total' => $total, 'page' => $page, 'page_size' => $pageSize]
            ]);
        } catch (\Throwable $e) {
            trace('获取排行榜失败: ' . $e->getMessage(), 'error');
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => ['list' => [], 'total' => 0, 'page' => $page, 'page_size' => $pageSize]
            ]);
        }
    }
    
    /**
     * 刷新排行榜数据（统计所有邀请人的数据）
     * POST /admin/invitation/refresh-ranking
     */
    public function refreshRanking()
    {
        try {
            // 获取所有邀请人的统计数据
            $inviterStats = Db::name('user_invitations')
                ->alias('i')
                ->leftJoin('users u', 'i.inviter_openid = u.openid')
                ->field([
                    'i.inviter_user_id as user_id',
                    'i.inviter_openid as openid',
                    'u.nickname',
                    'u.avatar as avatar_url',
                    'COUNT(*) as total_invitations',
                    'SUM(CASE WHEN i.status = 1 THEN 1 ELSE 0 END) as verified_invitations',
                    'SUM(CASE WHEN i.status = 0 THEN 1 ELSE 0 END) as pending_invitations',
                    'MAX(i.create_time) as last_invite_time'
                ])
                ->group('i.inviter_openid')
                ->select()
                ->toArray();
            
            $successCount = 0;
            $errorCount = 0;
            
            Db::startTrans();
            try {
                foreach ($inviterStats as $stat) {
                    if (empty($stat['openid'])) {
                        $errorCount++;
                        continue;
                    }
                    
                    // 统计该用户的优惠券数据
                    $couponStats = Db::name('user_coupons')
                        ->where('openid', $stat['openid'])
                        ->field([
                            'COUNT(*) as total_coupons',
                            'SUM(CASE WHEN status IN (1, 2) THEN 1 ELSE 0 END) as received_coupons',
                            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as redeemed_coupons',
                            'SUM(coupon_amount) as total_amount'
                        ])
                        ->find();
                    
                    // 检查是否已存在排行榜记录
                    $existing = Db::name('invitation_ranking')
                        ->where('openid', $stat['openid'])
                        ->find();
                    
                    $rankingData = [
                        'user_id' => $stat['user_id'],
                        'openid' => $stat['openid'],
                        'nickname' => $stat['nickname'] ?: '未设置昵称',
                        'avatar_url' => $stat['avatar_url'] ?: '',
                        'total_invitations' => (int)$stat['total_invitations'],
                        'verified_invitations' => (int)$stat['verified_invitations'],
                        'pending_invitations' => (int)$stat['pending_invitations'],
                        'total_coupons_received' => (int)($couponStats['received_coupons'] ?? 0),
                        'total_coupons_redeemed' => (int)($couponStats['redeemed_coupons'] ?? 0),
                        'total_coupon_amount' => (float)($couponStats['total_amount'] ?? 0),
                        'ranking_score' => (int)$stat['verified_invitations'], // 排行分数 = 已认证人数
                        'last_invite_time' => $stat['last_invite_time'],
                        'update_time' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($existing) {
                        // 更新现有记录
                        Db::name('invitation_ranking')
                            ->where('openid', $stat['openid'])
                            ->update($rankingData);
                    } else {
                        // 插入新记录
                        $rankingData['create_time'] = date('Y-m-d H:i:s');
                        Db::name('invitation_ranking')->insert($rankingData);
                    }
                    
                    $successCount++;
                }
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => "排行榜数据刷新成功，处理{$successCount}条记录" . ($errorCount > 0 ? "，跳过{$errorCount}条无效记录" : '')
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Throwable $e) {
            trace('刷新排行榜失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '刷新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新排行榜数据（兑换后）
     */
    private function updateRankingAfterRedeem($openid)
    {
        try {
            $ranking = Db::name('invitation_ranking')->where('openid', $openid)->find();
            if ($ranking) {
                $redeemedCount = Db::name('user_coupons')
                    ->where('openid', $openid)
                    ->where('status', 2)
                    ->count();
                Db::name('invitation_ranking')
                    ->where('openid', $openid)
                    ->update(['total_coupons_redeemed' => $redeemedCount]);
            }
        } catch (\Throwable $e) {
            trace('更新排行榜失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 获取当前连接的表前缀（与 config/database.php 一致，用于 leftJoin 表名）
     */
    private function getTablePrefix()
    {
        $prefix = config('database.connections.mysql.prefix');
        return is_string($prefix) ? $prefix : 'fa_';
    }
}
