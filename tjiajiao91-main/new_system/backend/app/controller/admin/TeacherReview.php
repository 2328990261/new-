<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Teacher;
use think\facade\Db;

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
            
            return json([
                'success' => true,
                'message' => '审核完成',
                'data' => [
                    'review_status' => $teacher->review_status,
                    'certification_status' => $teacher->getCertificationStatus()
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
}
