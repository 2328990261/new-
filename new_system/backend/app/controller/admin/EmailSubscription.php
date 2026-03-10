<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\EmailSubscription as EmailSubscriptionModel;
use think\facade\Validate;

/**
 * 邮件订阅管理控制器
 */
class EmailSubscription extends BaseController
{
    /**
     * 获取订阅列表
     * GET /admin/email-subscription/list
     */
    public function list()
    {
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            $email = $this->request->get('email', '');
            $status = $this->request->get('status', '');
            $isVerified = $this->request->get('is_verified', '');
            
            // 构建查询
            $query = EmailSubscriptionModel::order('create_time', 'desc');
            
            // 筛选条件
            if ($email) {
                $query->where('email', 'like', '%' . $email . '%');
            }
            if ($status !== '') {
                $query->where('status', $status);
            }
            if ($isVerified !== '') {
                $query->where('is_verified', $isVerified);
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
            trace('获取订阅列表失败: ' . $e->getMessage(), 'error');
            return json([
                'success' => false,
                'message' => '获取订阅列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取订阅详情
     * GET /admin/email-subscription/:id
     */
    public function detail($id)
    {
        try {
            $subscription = EmailSubscriptionModel::find($id);
            
            if (!$subscription) {
                return json(['code' => 404, 'message' => '订阅不存在']);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $subscription
            ]);
            
        } catch (\Exception $e) {
            trace('获取订阅详情失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '获取订阅详情失败']);
        }
    }
    
    /**
     * 创建订阅
     * POST /admin/email-subscription/create
     */
    public function create()
    {
        try {
            $data = $this->request->post();
            
            // 数据验证
            $validate = Validate::rule([
                'email' => 'require|email',
            ])->message([
                'email.require' => '邮箱不能为空',
                'email.email' => '邮箱格式不正确',
            ]);
            
            if (!$validate->check($data)) {
                return json(['code' => 400, 'message' => $validate->getError()]);
            }
            
            // 检查邮箱是否已存在
            $exists = EmailSubscriptionModel::where('email', $data['email'])->find();
            if ($exists) {
                return json(['code' => 400, 'message' => '该邮箱已订阅']);
            }
            
            // 创建订阅
            $subscription = EmailSubscriptionModel::create([
                'email' => $data['email'],
                'city_ids' => $data['city_ids'] ?? null,
                'district_ids' => $data['district_ids'] ?? null,
                'subject_ids' => $data['subject_ids'] ?? null,
                'grade_levels' => $data['grade_levels'] ?? null,
                'status' => $data['status'] ?? 1,
                'is_verified' => $data['is_verified'] ?? 1, // 管理员创建默认已验证
            ]);
            
            return json([
                'code' => 200,
                'message' => '创建成功',
                'data' => $subscription
            ]);
            
        } catch (\Exception $e) {
            trace('创建订阅失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '创建订阅失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新订阅
     * PUT /admin/email-subscription/:id/update
     */
    public function update($id)
    {
        try {
            $data = $this->request->post();
            
            $subscription = EmailSubscriptionModel::find($id);
            if (!$subscription) {
                return json(['code' => 404, 'message' => '订阅不存在']);
            }
            
            // 更新字段
            $allowedFields = ['city_ids', 'district_ids', 'subject_ids', 'grade_levels', 'status', 'is_verified'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $subscription->$field = $data[$field];
                }
            }
            
            $subscription->save();
            
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => $subscription
            ]);
            
        } catch (\Exception $e) {
            trace('更新订阅失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '更新订阅失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除订阅
     * DELETE /admin/email-subscription/:id/delete
     */
    public function delete($id)
    {
        try {
            $subscription = EmailSubscriptionModel::find($id);
            
            if (!$subscription) {
                return json(['code' => 404, 'message' => '订阅不存在']);
            }
            
            $subscription->delete();
            
            return json([
                'code' => 200,
                'message' => '删除成功'
            ]);
            
        } catch (\Exception $e) {
            trace('删除订阅失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '删除订阅失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量启用/禁用
     * POST /admin/email-subscription/batch-status
     */
    public function batchStatus()
    {
        try {
            $ids = $this->request->post('ids');
            $status = $this->request->post('status');
            
            if (empty($ids) || !is_array($ids)) {
                return json(['code' => 400, 'message' => '请选择要操作的订阅']);
            }
            
            if (!in_array($status, [0, 1])) {
                return json(['code' => 400, 'message' => '状态参数错误']);
            }
            
            EmailSubscriptionModel::whereIn('id', $ids)->update(['status' => $status]);
            
            return json([
                'code' => 200,
                'message' => '操作成功'
            ]);
            
        } catch (\Exception $e) {
            trace('批量操作失败: ' . $e->getMessage(), 'error');
            return json(['code' => 500, 'message' => '批量操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取订阅统计
     * GET /admin/email-subscription/stats
     */
    public function stats()
    {
        try {
            $total = EmailSubscriptionModel::count();
            $active = EmailSubscriptionModel::where('status', 1)->count();
            $verified = EmailSubscriptionModel::where('is_verified', 1)->count();
            $unverified = EmailSubscriptionModel::where('is_verified', 0)->count();
            
            return json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'verified' => $verified,
                    'unverified' => $unverified
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('获取订阅统计失败: ' . $e->getMessage(), 'error');
            return json([
                'success' => false,
                'message' => '获取订阅统计失败：' . $e->getMessage()
            ]);
        }
    }
}
