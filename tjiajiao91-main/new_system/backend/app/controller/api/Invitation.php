<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 邀请功能控制器
 */
class Invitation extends BaseController
{
    /**
     * 获取邀请统计数据
     * GET /api/invitation/stats
     */
    public function stats()
    {
        try {
            // 获取当前用户信息（从session或token）
            $openid = $this->getUserOpenid();
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 获取用户信息
            $user = Db::name('users')->where('openid', $openid)->find();
            if (!$user) {
                return json(['code' => 404, 'message' => '用户不存在']);
            }
            
            // 生成邀请码（如果没有）
            $invitationCode = $this->getOrCreateInvitationCode($openid);
            
            // 统计邀请数据
            $invitedCount = Db::name('user_invitations')
                ->where('inviter_openid', $openid)
                ->count();
            
            $verifiedCount = Db::name('user_invitations')
                ->where('inviter_openid', $openid)
                ->where('status', 1)
                ->count();
            
            $pendingCount = $invitedCount - $verifiedCount;
            
            // 统计优惠券数据
            $totalCoupons = Db::name('user_coupons')
                ->where('openid', $openid)
                ->count();
            
            $receivedCoupons = Db::name('user_coupons')
                ->where('openid', $openid)
                ->whereIn('status', [1, 2])
                ->count();
            
            $redeemedCoupons = Db::name('user_coupons')
                ->where('openid', $openid)
                ->where('status', 2)
                ->count();
            
            $pendingCoupons = Db::name('user_coupons')
                ->where('openid', $openid)
                ->where('status', 0)
                ->count();
            
            // 获取邀请列表
            $inviteList = Db::name('user_invitations')
                ->alias('i')
                ->leftJoin('users u', 'i.invitee_openid = u.openid')
                ->where('i.inviter_openid', $openid)
                ->field('i.id, i.status, i.create_time, i.verify_time, u.nickname as invitee_name, u.avatar as invitee_avatar')
                ->order('i.create_time', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            // 获取排行榜（前10名）
            $rankingList = Db::name('invitation_ranking')
                ->field('nickname, avatar_url, verified_invitations, total_coupon_amount')
                ->order('ranking_score', 'desc')
                ->order('last_invite_time', 'desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            // 统计总参与人数
            $totalParticipants = Db::name('invitation_ranking')->count();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'invitationCode' => $invitationCode,
                    'userInfo' => [
                        'nickname' => $user['nickname'] ?? '',
                        'avatarUrl' => $user['avatar'] ?? ''
                    ],
                    'stats' => [
                        'invitedCount' => $invitedCount,
                        'verifiedCount' => $verifiedCount,
                        'pendingCount' => $pendingCount,
                        'totalCoupons' => $totalCoupons,
                        'receivedCoupons' => $receivedCoupons,
                        'redeemedCoupons' => $redeemedCoupons,
                        'pendingCoupons' => $pendingCoupons,
                        'totalParticipants' => $totalParticipants
                    ],
                    'inviteList' => $inviteList,
                    'rankingList' => $rankingList
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取邀请统计失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 领取优惠券到卡包
     * POST /api/invitation/receive-coupon
     */
    public function receiveCoupon()
    {
        try {
            $openid = $this->getUserOpenid();
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $couponId = $this->request->post('coupon_id', 0);
            if (!$couponId) {
                return json(['code' => 400, 'message' => '优惠券ID不能为空']);
            }
            
            // 查找优惠券
            $coupon = Db::name('user_coupons')
                ->where('id', $couponId)
                ->where('openid', $openid)
                ->find();
            
            if (!$coupon) {
                return json(['code' => 404, 'message' => '优惠券不存在']);
            }
            
            if ($coupon['status'] != 0) {
                return json(['code' => 400, 'message' => '优惠券已领取或已过期']);
            }
            
            // 检查是否过期
            if ($coupon['expire_time'] && strtotime($coupon['expire_time']) < time()) {
                Db::name('user_coupons')->where('id', $couponId)->update(['status' => 3]);
                return json(['code' => 400, 'message' => '优惠券已过期']);
            }
            
            // 更新为已领取
            Db::name('user_coupons')->where('id', $couponId)->update([
                'status' => 1,
                'receive_time' => date('Y-m-d H:i:s')
            ]);
            
            return json([
                'code' => 200,
                'message' => '领取成功'
            ]);
            
        } catch (\Exception $e) {
            trace('领取优惠券失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取我的优惠券列表
     * GET /api/invitation/my-coupons
     */
    public function myCoupons()
    {
        try {
            $openid = $this->getUserOpenid();
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            $status = $this->request->get('status', ''); // 0-待领取，1-已领取，2-已兑换，3-已过期
            
            $query = Db::name('user_coupons')->where('openid', $openid);
            
            if ($status !== '') {
                $query->where('status', $status);
            }
            
            $list = $query->order('create_time', 'desc')->select()->toArray();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $list
            ]);
            
        } catch (\Exception $e) {
            trace('获取优惠券列表失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 检查用户邀请状态
     * GET /api/invitation/check-status
     */
    public function checkStatus()
    {
        try {
            $openid = $this->getUserOpenid();
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 检查是否已经使用过邀请码
            $invitation = Db::name('user_invitations')
                ->where('invitee_openid', $openid)
                ->find();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'hasUsedInvite' => !empty($invitation),
                    'invitationInfo' => $invitation
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('检查邀请状态失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 使用邀请码注册
     * POST /api/invitation/register
     */
    public function register()
    {
        try {
            $inviteCode = $this->request->post('invite_code', '');
            $openid = $this->getUserOpenid();
            
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            if (empty($inviteCode)) {
                return json(['code' => 400, 'message' => '邀请码不能为空']);
            }
            
            // 检查是否已经被邀请过
            $exists = Db::name('user_invitations')
                ->where('invitee_openid', $openid)
                ->find();
            
            if ($exists) {
                return json(['code' => 400, 'message' => '您已经使用过邀请码']);
            }
            
            // 查找邀请人
            $inviter = Db::name('users')
                ->where('invitation_code', $inviteCode)
                ->find();
            
            if (!$inviter) {
                return json(['code' => 400, 'message' => '邀请码不存在']);
            }
            
            if ($inviter['openid'] === $openid) {
                return json(['code' => 400, 'message' => '不能使用自己的邀请码']);
            }
            
            // 创建邀请记录
            Db::name('user_invitations')->insert([
                'inviter_user_id' => $inviter['id'],
                'inviter_openid' => $inviter['openid'],
                'invitee_openid' => $openid,
                'invitation_code' => $inviteCode,
                'status' => 0,
                'reward_points' => 100,
                'is_rewarded' => 0,
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            return json([
                'code' => 200,
                'message' => '邀请码使用成功'
            ]);
            
        } catch (\Exception $e) {
            trace('使用邀请码失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 用户完成认证后的回调
     * POST /api/invitation/verify-callback
     */
    public function verifyCallback()
    {
        try {
            $openid = $this->getUserOpenid();
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }
            
            // 查找该用户的邀请记录
            $invitation = Db::name('user_invitations')
                ->where('invitee_openid', $openid)
                ->where('status', 0)
                ->find();
            
            if (!$invitation) {
                return json(['code' => 200, 'message' => '无待处理的邀请记录']);
            }
            
            // 开启事务
            Db::startTrans();
            try {
                // 获取被邀请人信息
                $invitee = Db::name('users')->where('openid', $openid)->find();
                
                // 1. 给邀请人发放优惠券
                $inviterCouponId = Db::name('user_coupons')->insertGetId([
                    'user_id' => $invitation['inviter_user_id'],
                    'openid' => $invitation['inviter_openid'],
                    'coupon_type' => 'invitation',
                    'coupon_amount' => 20.00,
                    'source' => 'inviter',
                    'related_invitation_id' => $invitation['id'],
                    'status' => 0, // 待领取
                    'expire_time' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'create_time' => date('Y-m-d H:i:s')
                ]);
                
                // 2. 给被邀请人发放优惠券
                $inviteeCouponId = Db::name('user_coupons')->insertGetId([
                    'user_id' => $invitee['id'],
                    'openid' => $openid,
                    'coupon_type' => 'invitation',
                    'coupon_amount' => 20.00,
                    'source' => 'invitee',
                    'related_invitation_id' => $invitation['id'],
                    'status' => 0, // 待领取
                    'expire_time' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'create_time' => date('Y-m-d H:i:s')
                ]);
                
                // 3. 更新邀请记录状态
                Db::name('user_invitations')
                    ->where('id', $invitation['id'])
                    ->update([
                        'status' => 1,
                        'verify_time' => date('Y-m-d H:i:s'),
                        'is_rewarded' => 1,
                        'reward_time' => date('Y-m-d H:i:s'),
                        'inviter_coupon_id' => $inviterCouponId,
                        'invitee_coupon_id' => $inviteeCouponId
                    ]);
                
                // 4. 更新邀请人的排行榜数据
                $this->updateRanking($invitation['inviter_openid'], $invitation['inviter_user_id']);
                
                Db::commit();
                
                return json([
                    'code' => 200,
                    'message' => '认证成功，您和邀请人各获得￥20优惠券'
                ]);
                
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            trace('认证回调失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新用户排行榜数据
     */
    private function updateRanking($openid, $userId)
    {
        // 获取用户信息
        $user = Db::name('users')->where('openid', $openid)->find();
        
        // 统计邀请数据
        $totalInvitations = Db::name('user_invitations')
            ->where('inviter_openid', $openid)
            ->count();
        
        $verifiedInvitations = Db::name('user_invitations')
            ->where('inviter_openid', $openid)
            ->where('status', 1)
            ->count();
        
        $pendingInvitations = $totalInvitations - $verifiedInvitations;
        
        // 统计优惠券数据
        $totalCouponsReceived = Db::name('user_coupons')
            ->where('openid', $openid)
            ->whereIn('status', [1, 2])
            ->count();
        
        $totalCouponsRedeemed = Db::name('user_coupons')
            ->where('openid', $openid)
            ->where('status', 2)
            ->count();
        
        $totalCouponAmount = Db::name('user_coupons')
            ->where('openid', $openid)
            ->sum('coupon_amount');
        
        // 获取最后邀请时间
        $lastInvite = Db::name('user_invitations')
            ->where('inviter_openid', $openid)
            ->order('create_time', 'desc')
            ->find();
        
        // 检查是否已存在排行榜记录
        $ranking = Db::name('invitation_ranking')->where('openid', $openid)->find();
        
        $data = [
            'user_id' => $userId,
            'openid' => $openid,
            'nickname' => $user['nickname'] ?? null,
            'avatar_url' => $user['avatar'] ?? null,
            'total_invitations' => $totalInvitations,
            'verified_invitations' => $verifiedInvitations,
            'pending_invitations' => $pendingInvitations,
            'total_coupons_received' => $totalCouponsReceived,
            'total_coupons_redeemed' => $totalCouponsRedeemed,
            'total_coupon_amount' => $totalCouponAmount,
            'ranking_score' => $verifiedInvitations, // 排行分数=已认证人数
            'last_invite_time' => $lastInvite ? $lastInvite['create_time'] : null
        ];
        
        if ($ranking) {
            // 更新
            Db::name('invitation_ranking')->where('openid', $openid)->update($data);
        } else {
            // 插入
            $data['create_time'] = date('Y-m-d H:i:s');
            Db::name('invitation_ranking')->insert($data);
        }
    }
    
    /**
     * 获取或创建邀请码
     */
    private function getOrCreateInvitationCode($openid)
    {
        $user = Db::name('users')->where('openid', $openid)->find();
        
        if ($user && !empty($user['invitation_code'])) {
            return $user['invitation_code'];
        }
        
        // 生成唯一邀请码
        $code = $this->generateUniqueCode();
        
        // 更新用户表
        Db::name('users')
            ->where('openid', $openid)
            ->update(['invitation_code' => $code]);
        
        return $code;
    }
    
    /**
     * 生成唯一邀请码
     */
    private function generateUniqueCode()
    {
        $attempts = 0;
        $maxAttempts = 100;
        
        while ($attempts < $maxAttempts) {
            // 生成6位数字+字母的邀请码
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            
            // 检查是否已存在
            $exists = Db::name('users')
                ->where('invitation_code', $code)
                ->find();
            
            if (!$exists) {
                return $code;
            }
            
            $attempts++;
        }
        
        throw new \Exception('无法生成唯一邀请码');
    }
    
    /**
     * 获取当前用户openid
     */
    private function getUserOpenid()
    {
        // 优先从请求参数中获取
        $openid = $this->request->param('openid', '');
        if ($openid) {
            return $openid;
        }
        
        // 从请求头中获取token，然后解析openid
        $token = $this->request->header('token', '');
        if ($token) {
            // 从token中解析openid（假设token就是openid或包含openid）
            return $token;
        }
        
        // 从session中获取
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['openid'] ?? null;
    }
}
