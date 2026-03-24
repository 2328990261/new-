<?php
namespace app\service;

use think\facade\Db;

/**
 * 邀请相关逻辑：简历认证通过后发放优惠券
 */
class InvitationService
{
    /**
     * 被邀请人（老师）简历认证通过后，为邀请人、被邀请人各发放 ￥20 优惠券
     * 有效期：自发放当日起 30 日
     * @param string $inviteeOpenid 被邀请人 openid（即审核通过的老师 openid）
     */
    public static function grantCouponsForApprovedInvitee($inviteeOpenid)
    {
        if (empty($inviteeOpenid)) {
            return;
        }
        try {
            $pending = Db::name('user_invitations')
                ->where('invitee_openid', $inviteeOpenid)
                ->where('is_rewarded', 0)
                ->select()
                ->toArray();
            if (empty($pending)) {
                return;
            }
            $now = date('Y-m-d H:i:s');
            $expire = date('Y-m-d H:i:s', strtotime('+30 days'));
            foreach ($pending as $inv) {
                Db::startTrans();
                try {
                    $inviter = Db::name('users')->where('openid', $inv['inviter_openid'])->find();
                    if (!$inviter) {
                        Db::rollback();
                        continue;
                    }
                    $inviterCouponId = Db::name('user_coupons')->insertGetId([
                        'user_id' => $inviter['id'],
                        'openid' => $inv['inviter_openid'],
                        'coupon_type' => 'invitation',
                        'coupon_amount' => 20.00,
                        'source' => 'inviter',
                        'related_invitation_id' => $inv['id'],
                        'coupon_code' => self::generateUniqueCouponCode(),
                        'status' => 1,
                        'expire_time' => $expire,
                        'create_time' => $now,
                        'receive_time' => $now
                    ]);
                    $inviteeCouponId = Db::name('user_coupons')->insertGetId([
                        'user_id' => $inv['invitee_user_id'],
                        'openid' => $inviteeOpenid,
                        'coupon_type' => 'invitation',
                        'coupon_amount' => 20.00,
                        'source' => 'invitee',
                        'related_invitation_id' => $inv['id'],
                        'coupon_code' => self::generateUniqueCouponCode(),
                        'status' => 1,
                        'expire_time' => $expire,
                        'create_time' => $now,
                        'receive_time' => $now
                    ]);
                    Db::name('user_invitations')->where('id', $inv['id'])->update([
                        'status' => 1,
                        'is_rewarded' => 1,
                        'verify_time' => $now,
                        'reward_time' => $now,
                        'inviter_coupon_id' => $inviterCouponId,
                        'invitee_coupon_id' => $inviteeCouponId
                    ]);
                    Db::commit();
                    \think\facade\Log::info('邀请奖励(审核通过)发放: invitation_id=' . $inv['id'] . ', inviter=' . $inv['inviter_openid'] . ', invitee=' . $inviteeOpenid);
                } catch (\Throwable $e) {
                    Db::rollback();
                    \think\facade\Log::error('邀请奖励发放失败: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error('InvitationService::grantCouponsForApprovedInvitee: ' . $e->getMessage());
        }
    }

    /**
     * 生成唯一优惠券码（Service 内部使用）
     */
    private static function generateUniqueCouponCode()
    {
        $attempts = 0;
        $maxAttempts = 100;

        while ($attempts < $maxAttempts) {
            $code = 'CP' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

            $exists = Db::name('user_coupons')
                ->where('coupon_code', $code)
                ->find();

            if (!$exists) {
                return $code;
            }

            $attempts++;
        }

        throw new \RuntimeException('无法生成唯一优惠券码');
    }
}
