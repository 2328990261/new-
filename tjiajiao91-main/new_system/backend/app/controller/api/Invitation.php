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

            // 顶部展示用的“邀请人头像/昵称”
            // 规则：取 user_invitations 最新一条记录的 inviter_user_id（已发放优先），再去 users 表查头像昵称
            $latestInvitation = Db::name('user_invitations')
                ->order('is_rewarded', 'desc')
                ->order('reward_time', 'desc')
                ->order('verify_time', 'desc')
                ->order('create_time', 'desc')
                ->find();
            $displayUser = null;
            if ($latestInvitation && !empty($latestInvitation['inviter_user_id'])) {
                $displayUser = Db::name('users')->where('id', (int)$latestInvitation['inviter_user_id'])->find();
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
                        'nickname' => $displayUser['nickname'] ?? ($user['nickname'] ?? ''),
                        'avatarUrl' => $displayUser['avatar'] ?? ($user['avatar'] ?? '')
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
     * 获取“我的邀请人”资料（受邀人视角）
     * 规则：优先取已发放(is_rewarded=1)的最新一条，否则取最新一条邀请记录
     * GET /api/invitation/inviter-profile
     */
    public function inviterProfile()
    {
        try {
            $openid = $this->getUserOpenid();
            if (!$openid) {
                return json(['code' => 401, 'message' => '请先登录']);
            }

            $invitation = Db::name('user_invitations')
                ->where('invitee_openid', $openid)
                ->order('is_rewarded', 'desc')
                ->order('reward_time', 'desc')
                ->order('verify_time', 'desc')
                ->order('create_time', 'desc')
                ->find();

            if (!$invitation) {
                return json([
                    'code' => 200,
                    'message' => '无邀请记录',
                    'data' => null
                ]);
            }

            $inviter = Db::name('users')->where('openid', $invitation['inviter_openid'])->find();

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'invitation_id' => $invitation['id'] ?? null,
                    'inviter_openid' => $invitation['inviter_openid'] ?? '',
                    'status' => (int)($invitation['status'] ?? 0),
                    'is_rewarded' => (int)($invitation['is_rewarded'] ?? 0),
                    'inviter' => [
                        'nickname' => $inviter['nickname'] ?? '',
                        'avatarUrl' => $inviter['avatar'] ?? ''
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            trace('获取邀请人资料失败: ' . $e->getMessage(), 'error');
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
     * 使用/兑换优惠券（小程序端点击「使用」后标记为已使用，实际支付未开放时仅做记录）
     * POST /api/invitation/use-coupon
     */
    public function useCoupon()
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
            
            $coupon = Db::name('user_coupons')
                ->where('id', $couponId)
                ->where('openid', $openid)
                ->find();
            
            if (!$coupon) {
                return json(['code' => 404, 'message' => '优惠券不存在']);
            }
            
            if ($coupon['status'] != 1) {
                return json(['code' => 400, 'message' => '该优惠券不可使用']);
            }
            
            if ($coupon['expire_time'] && strtotime($coupon['expire_time']) < time()) {
                Db::name('user_coupons')->where('id', $couponId)->update(['status' => 3]);
                return json(['code' => 400, 'message' => '优惠券已过期']);
            }
            
            // 仅简历认证通过成为老师后才可使用优惠券
            $teacher = Db::name('teachers')
                ->where('openid', $openid)
                ->where('review_status', 'approved')
                ->find();
            if (!$teacher) {
                return json(['code' => 400, 'message' => '仅简历认证通过成为老师后可使用该优惠券']);
            }
            
            // 用户申请使用 → 置为待审核，由后台邀请管理人工兑换后变为已使用
            $update = [
                'status' => 4, // 4-待审核（人工兑换后由后台改为 2-已使用）
                'update_time' => date('Y-m-d H:i:s')
            ];
            Db::name('user_coupons')->where('id', $couponId)->update($update);
            
            return json([
                'code' => 200,
                'message' => '已提交，等待客服人工审核兑换'
            ]);
            
        } catch (\Exception $e) {
            trace('使用优惠券失败: ' . $e->getMessage(), 'error');
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
            
            // 是否已通过简历认证成为老师（仅老师可使用优惠券）
            $teacherCertified = (bool) Db::name('teachers')
                ->where('openid', $openid)
                ->where('review_status', 'approved')
                ->find();
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $list,
                'teacher_certified' => $teacherCertified
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
            
            // 获取被邀请人信息
            $invitee = Db::name('users')->where('openid', $openid)->find();
            if (!$invitee) {
                return json(['code' => 404, 'message' => '用户信息不存在']);
            }
            
            // 创建邀请记录
            Db::name('user_invitations')->insert([
                'inviter_user_id' => $inviter['id'],
                'inviter_openid' => $inviter['openid'],
                'invitee_user_id' => $invitee['id'],
                'invitee_openid' => $openid,
                'invitation_code' => $inviteCode,
                'status' => 0,
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
                    'coupon_code' => $this->generateUniqueCouponCode(),
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
                    'coupon_code' => $this->generateUniqueCouponCode(),
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
     * 生成唯一优惠券码
     * 券码用于后台查询和小程序展示，保持全局唯一
     */
    private function generateUniqueCouponCode()
    {
        $attempts = 0;
        $maxAttempts = 100;

        while ($attempts < $maxAttempts) {
            // 生成 10 位字母数字券码，例如：CP8F3A9K2L
            $code = 'CP' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

            $exists = Db::name('user_coupons')
                ->where('coupon_code', $code)
                ->find();

            if (!$exists) {
                return $code;
            }

            $attempts++;
        }

        throw new \Exception('无法生成唯一优惠券码');
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
            // 尝试解析token（假设token是base64编码的JSON）
            try {
                $tokenData = json_decode(base64_decode($token), true);
                if ($tokenData && isset($tokenData['openid'])) {
                    return $tokenData['openid'];
                }
            } catch (\Exception $e) {
                // 解析失败，可能token就是openid
            }
            
            // 如果解析失败，假设token就是openid
            return $token;
        }
        
        // 从session中获取
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['openid'] ?? null;
    }
}
