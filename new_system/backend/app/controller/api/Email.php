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
        
        if (empty($email)) {
            return json(['code' => 400, 'msg' => '邮箱地址不能为空']);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['code' => 400, 'msg' => '邮箱地址格式不正确']);
        }
        
        // 检查是否已订阅
        $exists = EmailSubscription::where('email', $email)->find();
        if ($exists) {
            if ($exists->status == 1) {
                return json(['code' => 400, 'msg' => '该邮箱已订阅']);
            } else {
                // 重新激活订阅
                $exists->status = 1;
                $exists->save();
                return json(['code' => 200, 'msg' => '订阅成功']);
            }
        }
        
        // 生成验证码
        $verifyCode = md5($email . time() . rand(1000, 9999));
        
        // 创建订阅记录
        $subscription = EmailSubscription::create([
            'email' => $email,
            'verify_code' => $verifyCode,
            'status' => 0, // 待验证
        ]);
        
        // 发送验证邮件
        try {
            $emailService = new EmailService();
            $verifyUrl = $request->domain() . '/api/subscribe/verify?code=' . $verifyCode;
            
            $content = "
                <h2>欢迎订阅家教信息推送服务</h2>
                <p>请点击下方链接完成邮箱验证：</p>
                <p><a href='{$verifyUrl}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>验证邮箱</a></p>
                <p>或复制以下链接到浏览器打开：</p>
                <p>{$verifyUrl}</p>
                <p>如果这不是您的操作，请忽略此邮件。</p>
            ";
            
            $emailService->sendEmail($email, '验证您的订阅', $content);
            
            return json([
                'code' => 200,
                'msg' => '订阅成功，请查收验证邮件',
                'data' => ['email' => $email]
            ]);
        } catch (\Exception $e) {
            // 发送失败，删除订阅记录
            $subscription->delete();
            return json(['code' => 500, 'msg' => '发送验证邮件失败：' . $e->getMessage()]);
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
        $code = $request->post('code', '');
        
        if (empty($email)) {
            return json(['code' => 400, 'msg' => '邮箱地址不能为空']);
        }
        
        $query = EmailSubscription::where('email', $email);
        
        // 如果提供了验证码，使用验证码验证
        if (!empty($code)) {
            $query->where('verify_code', $code);
        }
        
        $subscription = $query->find();
        
        if (!$subscription) {
            return json(['code' => 404, 'msg' => '未找到订阅记录']);
        }
        
        // 软删除或更新状态
        $subscription->status = 0;
        $subscription->save();
        
        return json([
            'code' => 200,
            'msg' => '取消订阅成功',
            'data' => ['email' => $email]
        ]);
    }
}

