<?php
namespace app\controller\api;

use app\model\EmailSubscription;
use app\service\EmailService;
use think\Request;
use think\Response;

/**
 * API - 邮件订阅控制器
 */
class Email
{
    /**
     * 订阅家教信息推送
     */
    public function subscribe(Request $request): Response
    {
        $email = $request->post('email');
        $cityId = $request->post('city_id');
        $subjectId = $request->post('subject_id');
        $grade = $request->post('grade');
        
        if (empty($email)) {
            return json(['success' => false, 'error' => '邮箱地址不能为空']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => false, 'error' => '邮箱地址格式不正确']);
        }
        
        if (empty($cityId)) {
            return json(['success' => false, 'error' => '请选择订阅城市']);
        }
        
        try {
            // 检查是否已订阅（同一邮箱可以订阅多个城市）
            $cityIds = is_array($cityId) ? $cityId : [$cityId];
            $exists = EmailSubscription::where('email', $email)
                ->where('status', 1)
                ->find();
                
            if ($exists) {
                // 更新现有订阅
                $existingCityIds = $exists->city_ids ? explode(',', $exists->city_ids) : [];
                $newCityIds = array_unique(array_merge($existingCityIds, $cityIds));
                
                $exists->city_ids = implode(',', $newCityIds);
                $exists->subject_ids = is_array($subjectId) ? implode(',', $subjectId) : $subjectId;
                $exists->save();
                
                return json([
                    'success' => true,
                    'message' => '订阅已更新！',
                    'data' => ['email' => $email]
                ]);
            }
            
            // 创建新订阅记录
            $subscription = EmailSubscription::create([
                'email' => $email,
                'city_ids' => is_array($cityId) ? implode(',', $cityId) : $cityId,
                'subject_ids' => is_array($subjectId) ? implode(',', $subjectId) : $subjectId,
                'status' => 1,
                'is_verified' => 1
            ]);
            
            return json([
                'success' => true,
                'message' => '订阅成功！我们会将最新信息发送到您的邮箱',
                'data' => [
                    'email' => $email
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '订阅失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 验证邮箱
     */
    public function verify(Request $request): Response
    {
        $code = $request->get('code');
        
        if (empty($code)) {
            return json(['code' => 400, 'msg' => '验证码不能为空']);
        }
        
        $subscription = EmailSubscription::where('verify_code', $code)->find();
        
        if (!$subscription) {
            return json(['code' => 404, 'msg' => '无效的验证码']);
        }
        
        if ($subscription->status == 1) {
            return json(['code' => 400, 'msg' => '该邮箱已验证']);
        }
        
        // 更新状态
        $subscription->status = 1;
        $subscription->save();
        
        return json([
            'code' => 200,
            'msg' => '邮箱验证成功！您将收到最新的家教信息推送。',
            'data' => ['email' => $subscription->email]
        ]);
    }
    
    /**
     * 取消订阅
     */
    public function unsubscribe(Request $request): Response
    {
        $email = $request->post('email');
        
        if (empty($email)) {
            return json(['success' => false, 'error' => '邮箱地址不能为空']);
        }
        
        try {
            $subscription = EmailSubscription::where('email', $email)
                ->where('status', 1)
                ->find();
            
            if (!$subscription) {
                return json(['success' => false, 'error' => '未找到订阅记录']);
            }
            
            // 软删除或更新状态
            $subscription->status = 0;
            $subscription->save();
            
            return json([
                'success' => true,
                'message' => '取消订阅成功',
                'data' => ['email' => $email]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '取消订阅失败：' . $e->getMessage()]);
        }
    }
}

