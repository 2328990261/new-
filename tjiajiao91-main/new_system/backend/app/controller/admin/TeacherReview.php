<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Teacher;
use app\service\WechatNotificationService;
use app\service\SubscribeMessageService;
use app\service\TeacherMiniOpenidResolver;
use think\facade\Db;
use think\facade\Log;

/**
 * 教师审核控制器
 */
class TeacherReview extends BaseController
{
    /**
     * 获取待审核教师列表
     */
    public function pendingList()
    {
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 20);
        
        $list = Teacher::where('review_status', 'pending')
            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $pageSize,
                'page' => $page
            ]);
        
        return json([
            'success' => true,
            'data' => $list
        ]);
    }
    
    /**
     * 获取教师详情（用于审核）
     */
    public function detail()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['success' => false, 'error' => '缺少教师ID']);
        }
        
        $teacher = Teacher::find($id);
        
        if (!$teacher) {
            return json(['success' => false, 'error' => '教师不存在']);
        }
        
        // 添加认证状态信息
        $data = $teacher->toArray();
        $data['certification'] = $teacher->getCertificationDetails();
        
        return json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * 审核教师认证
     */
    public function review()
    {
        $id = $this->request->param('id');
        $realNameVerified = $this->request->param('real_name_verified', 0);
        $educationVerified = $this->request->param('education_verified', 0);
        $teacherVerified = $this->request->param('teacher_verified', 0);
        $reviewNote = $this->request->param('review_note', '');
        
        if (empty($id)) {
            return json(['success' => false, 'error' => '缺少教师ID']);
        }
        
        $teacher = Teacher::find($id);
        
        if (!$teacher) {
            return json(['success' => false, 'error' => '教师不存在']);
        }
        
        try {
            // 更新认证状态
            $teacher->real_name_verified = $realNameVerified ? 1 : 0;
            $teacher->education_verified = $educationVerified ? 1 : 0;
            $teacher->teacher_verified = $teacherVerified ? 1 : 0;
            
            // 判断审核结果
            // 如果至少有一项认证通过，则审核通过
            if ($realNameVerified || $educationVerified || $teacherVerified) {
                $teacher->review_status = 'approved';
            } else {
                $teacher->review_status = 'rejected';
            }
            
            $teacher->review_time = date('Y-m-d H:i:s');
            $teacher->reviewer_id = session('admin_id');
            $teacher->review_note = $reviewNote;
            
            $teacher->save();
            
            // 简历认证通过：若该教师是被邀请人，则发放邀请双方优惠券
            if ($teacher->review_status === 'approved' && !empty($teacher->openid)) {
                \app\service\InvitationService::grantCouponsForApprovedInvitee($teacher->openid);
            }

            // 发送小程序订阅消息（不影响审核主流程）
            $notifyRes = $this->notifyReviewByMiniSubscribe($teacher);
            
            return json([
                'success' => true,
                'message' => '审核完成',
                'data' => [
                    'review_status' => $teacher->review_status,
                    'certification_status' => $teacher->getCertificationStatus(),
                    'mini_subscribe_notify' => $notifyRes
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '审核失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量审核
     */
    public function batchReview()
    {
        $ids = $this->request->param('ids', []);
        $realNameVerified = $this->request->param('real_name_verified', 0);
        $educationVerified = $this->request->param('education_verified', 0);
        $teacherVerified = $this->request->param('teacher_verified', 0);
        $reviewNote = $this->request->param('review_note', '');
        
        if (empty($ids) || !is_array($ids)) {
            return json(['success' => false, 'error' => '请选择要审核的教师']);
        }
        
        try {
            $reviewStatus = ($realNameVerified || $educationVerified || $teacherVerified) ? 'approved' : 'rejected';
            
            $updateData = [
                'real_name_verified' => $realNameVerified ? 1 : 0,
                'education_verified' => $educationVerified ? 1 : 0,
                'teacher_verified' => $teacherVerified ? 1 : 0,
                'review_status' => $reviewStatus,
                'review_time' => date('Y-m-d H:i:s'),
                'reviewer_id' => session('admin_id'),
                'review_note' => $reviewNote
            ];
            
            $count = Teacher::whereIn('id', $ids)->update($updateData);
            
            // 本次设为「通过」的教师：若为被邀请人则发放邀请优惠券
            if ($reviewStatus === 'approved') {
                $approvedTeachers = Teacher::whereIn('id', $ids)->where('review_status', 'approved')->column('openid');
                foreach ($approvedTeachers as $openid) {
                    if (!empty($openid)) {
                        \app\service\InvitationService::grantCouponsForApprovedInvitee($openid);
                    }
                }
            }

            // 批量审核也发送小程序订阅消息（不影响审核主流程）
            $teachers = Teacher::whereIn('id', $ids)->select();
            foreach ($teachers as $teacher) {
                $this->notifyReviewByMiniSubscribe($teacher);
            }
            
            return json([
                'success' => true,
                'message' => "成功审核 {$count} 位教师"
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '批量审核失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取审核统计
     */
    public function statistics()
    {
        $stats = [
            'pending' => Teacher::where('review_status', 'pending')->count(),
            'approved' => Teacher::where('review_status', 'approved')->count(),
            'rejected' => Teacher::where('review_status', 'rejected')->count(),
            'real_name_verified' => Teacher::where('real_name_verified', 1)->count(),
            'education_verified' => Teacher::where('education_verified', 1)->count(),
            'teacher_verified' => Teacher::where('teacher_verified', 1)->count(),
        ];
        
        return json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 审核结果通知：按 unionid 关联 fa_wechat_users，给已关注公众号的 openid 发送模板消息
     * 仅作为通知，失败不影响审核主流程
     */
    private function notifyReviewByOfficialAccount($teacher): void
    {
        try {
            $miniOpenid = trim((string)($teacher->openid ?? ''));
            if ($miniOpenid === '') {
                Log::warning('教师审核通知跳过: teacher_openid为空');
                return;
            }

            $officialOpenid = $this->resolveOfficialOpenidByUnionid($miniOpenid);
            if ($officialOpenid === null) {
                Log::warning('教师审核通知跳过: 未解析到服务号openid, mini_openid=' . $miniOpenid);
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

            // 依赖后台公众号模板配置：template_code=resume_review_notify
            $sendRes = WechatNotificationService::sendTemplateMessage($officialOpenid, 'resume_review_notify', [
                'result' => $resultText,
                'review_time' => date('Y-m-d H:i:s'),
                'remark' => $remark,
                'teacher_name' => (string)($teacher->name ?? ''),
                'review_status' => (string)($teacher->review_status ?? '')
            ]);

            if (empty($sendRes['success'])) {
                Log::warning('教师审核公众号通知发送失败: ' . ($sendRes['message'] ?? 'unknown'));
            } else {
                Log::info('教师审核公众号通知发送成功, to=' . $officialOpenid);
            }
        } catch (\Throwable $e) {
            Log::error('教师审核公众号通知异常: ' . $e->getMessage());
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
                Log::warning('教师审核订阅消息跳过: 无法解析小程序 openid（教师表无 openid，且未通过 account_id/手机号匹配到 users）');
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

            if (empty($sendRes['success'])) {
                Log::warning('教师审核订阅消息发送失败: ' . ($sendRes['message'] ?? 'unknown') . ', mini_openid=' . $miniOpenid);
                return [
                    'success' => false,
                    'message' => (string)($sendRes['message'] ?? 'unknown'),
                    'mini_openid' => $miniOpenid,
                ];
            } else {
                Log::info('教师审核订阅消息发送成功, mini_openid=' . $miniOpenid);
                return [
                    'success' => true,
                    'message' => '发送成功',
                    'mini_openid' => $miniOpenid,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('教师审核订阅消息异常: ' . $e->getMessage());
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
            Log::warning('教师复核发送前 unionid 回填失败: ' . $e->getMessage());
        }
    }
}
