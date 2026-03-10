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
     * 获取邀请统计概览
     * GET /admin/invitation/overview
     */
    public function overview()
    {
        try {
            // 总参与人数
            $totalParticipants = Db::name('invitation_ranking')->count();
            
            // 总邀请人数
            $totalInvitations = Db::name('user_invitations')->count();
            
            // 已认证人数
            $verifiedInvitations = Db::name('user_invitations')->where('status', 1)->count();
            
            // 待认证人数
            $pendingInvitations = $totalInvitations - $verifiedInvitations;
            
            // 优惠券统计
            $totalCoupons = Db::name('user_coupons')->count();
            $receivedCoupons = Db::name('user_coupons')->whereIn('status', [1, 2])->count();
            $redeemedCoupons = Db::name('user_coupons')->where('status', 2)->count();
            $pendingCoupons = Db::name('user_coupons')->where('status', 0)->count();
            
            // 优惠券总金额
            $totalCouponAmount = Db::name('user_coupons')->sum('coupon_amount');
            $redeemedAmount = Db::name('user_coupons')->where('status', 2)->sum('coupon_amount');
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'totalParticipants' => $totalParticipants,
                    'totalInvitations' => $totalInvitations,
                    'verifiedInvitations' => $verifiedInvitations,
                    'pendingInvitations' => $pendingInvitations,
                    'totalCoupons' => $totalCoupons,
                    'receivedCoupons' => $receivedCoupons,
                    'redeemedCoupons' => $redeemedCoupons,
                    'pendingCoupons' => $pendingCoupons,
                    'totalCouponAmount' => $totalCouponAmount,
                    'redeemedAmount' => $redeemedAmount
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取邀请概览失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取邀请记录列表
     * GET /admin/invitation/list
     */
    public function invitationList()
    {
        try {
            $page = $this->request->get('page', 1);
            $pageSize = $this->request->get('page_size', 20);
            $status = $this->request->get('status', '');
            $keyword = $this->request->get('keyword', '');
            
            $query = Db::name('user_invitations')
                ->alias('i')
                ->leftJoin('wechat_mini_program_users inviter', 'i.inviter_openid = inviter.openid')
                ->leftJoin('wechat_mini_program_users invitee', 'i.invitee_openid = invitee.openid')
                ->field('i.*, inviter.nickname as inviter_name, inviter.phone as inviter_phone, 
                        invitee.nickname as invitee_name, invitee.phone as invitee_phone');
            
            if ($status !== '') {
                $query->where('i.status', $status);
            }
            
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
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
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取邀请列表失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取优惠券列表
     * GET /admin/invitation/coupon-list
     */
    public function couponList()
    {
        try {
            $page = $this->request->get('page', 1);
            $pageSize = $this->request->get('page_size', 20);
            $status = $this->request->get('status', '');
            $keyword = $this->request->get('keyword', '');
            
            $query = Db::name('user_coupons')
                ->alias('c')
                ->leftJoin('wechat_mini_program_users u', 'c.openid = u.openid')
                ->field('c.*, u.nickname, u.phone, u.avatar_url');
            
            if ($status !== '') {
                $query->where('c.status', $status);
            }
            
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr('u.nickname', 'like', "%{$keyword}%")
                      ->whereOr('u.phone', 'like', "%{$keyword}%")
                      ->whereOr('c.openid', 'like', "%{$keyword}%");
                });
            }
            
            $total = $query->count();
            $list = $query->order('c.create_time', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取优惠券列表失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
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
            
            // 获取管理员信息
            $adminId = Session::get('admin_id');
            $adminName = Session::get('admin_name');
            
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
            
            // 获取管理员信息
            $adminId = Session::get('admin_id');
            $adminName = Session::get('admin_name');
            
            if (!$adminId) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $successCount = 0;
            $failCount = 0;
            
            Db::startTrans();
            try {
                foreach ($couponIds as $couponId) {
                    $coupon = Db::name('user_coupons')->where('id', $couponId)->find();
                    
                    if (!$coupon || $coupon['status'] != 1) {
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
            
            // 先查找用户
            $users = Db::name('wechat_mini_program_users')
                ->where('nickname', 'like', "%{$keyword}%")
                ->whereOr('phone', 'like', "%{$keyword}%")
                ->limit(10)
                ->select()
                ->toArray();
            
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
            
        } catch (\Exception $e) {
            trace('搜索用户优惠券失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '搜索失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取排行榜
     * GET /admin/invitation/ranking
     */
    public function ranking()
    {
        try {
            $page = $this->request->get('page', 1);
            $pageSize = $this->request->get('page_size', 20);
            
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
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取排行榜失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新排行榜数据（兑换后）
     */
    private function updateRankingAfterRedeem($openid)
    {
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
    }
}
