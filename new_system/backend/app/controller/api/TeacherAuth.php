<?php
namespace app\controller\api;

use app\BaseController;
use app\model\TeacherAccount;
use think\facade\Db;

class TeacherAuth extends BaseController
{
    /**
     * 教师注册
     */
    public function register()
    {
        $email = $this->request->param('email');
        $password = $this->request->param('password');
        
        if (empty($email) || empty($password)) {
            return json(['success' => false, 'error' => '邮箱和密码不能为空']);
        }
        
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => false, 'error' => '邮箱格式不正确']);
        }
        
        // 检查邮箱是否已注册
        $exists = TeacherAccount::where('email', $email)->find();
        if ($exists) {
            return json(['success' => false, 'error' => '该邮箱已注册']);
        }
        
        try {
            // 创建账号
            $account = TeacherAccount::create([
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'status' => 0, // 未验证
                'create_time' => time()
            ]);
            
            return json([
                'success' => true,
                'message' => '注册成功',
                'data' => [
                    'id' => $account->id,
                    'email' => $account->email
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '注册失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 教师登录
     */
    public function login()
    {
        $email = $this->request->param('email');
        $password = $this->request->param('password');
        
        if (empty($email) || empty($password)) {
            return json(['success' => false, 'error' => '邮箱和密码不能为空']);
        }
        
        try {
            $account = TeacherAccount::where('email', $email)->find();
            
            if (!$account) {
                return json(['success' => false, 'error' => '邮箱或密码错误']);
            }
            
            if (!password_verify($password, $account->password)) {
                return json(['success' => false, 'error' => '邮箱或密码错误']);
            }
            
            if ($account->status === 0) {
                return json(['success' => false, 'error' => '账号未验证，请先验证邮箱']);
            }
            
            if ($account->status === 2) {
                return json(['success' => false, 'error' => '账号已被禁用']);
            }
            
            // 生成Token（简单实现，实际应使用JWT）
            $token = md5($account->id . time() . rand(1000, 9999));
            
            // 更新最后登录时间
            $account->last_login_time = time();
            $account->save();
            
            // 记录登录日志
            Db::name('teacher_verification_codes')->insert([
                'teacher_id' => $account->id,
                'code' => 'login',
                'type' => 'login',
                'create_time' => time(),
                'expire_time' => time() + 86400
            ]);
            
            return json([
                'success' => true,
                'message' => '登录成功',
                'data' => [
                    'token' => $token,
                    'id' => $account->id,
                    'email' => $account->email,
                    'teacher_id' => $account->teacher_id
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '登录失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 发送验证码
     */
    public function sendVerificationCode()
    {
        $email = $this->request->param('email');
        $type = $this->request->param('type', 'register'); // register, reset
        
        if (empty($email)) {
            return json(['success' => false, 'error' => '邮箱不能为空']);
        }
        
        try {
            // 生成6位数验证码
            $code = sprintf('%06d', rand(0, 999999));
            
            // 如果是注册，检查邮箱是否已存在
            if ($type === 'register') {
                $exists = TeacherAccount::where('email', $email)->find();
                if ($exists) {
                    return json(['success' => false, 'error' => '该邮箱已注册']);
                }
            }
            
            // 如果是重置密码，检查邮箱是否存在
            if ($type === 'reset') {
                $account = TeacherAccount::where('email', $email)->find();
                if (!$account) {
                    return json(['success' => false, 'error' => '该邮箱未注册']);
                }
            }
            
            // 保存验证码到数据库
            Db::name('teacher_verification_codes')->insert([
                'email' => $email,
                'code' => $code,
                'type' => $type,
                'create_time' => time(),
                'expire_time' => time() + 300, // 5分钟有效期
                'is_used' => 0
            ]);
            
            // 实际发送邮件功能待实现
            // 这里应该调用邮件发送服务
            
            return json([
                'success' => true,
                'message' => '验证码已发送',
                'data' => ['code' => $code] // 开发环境返回验证码，生产环境应删除
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '发送失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 验证邮箱
     */
    public function verifyEmail()
    {
        $email = $this->request->param('email');
        $code = $this->request->param('code');
        
        if (empty($email) || empty($code)) {
            return json(['success' => false, 'error' => '邮箱和验证码不能为空']);
        }
        
        try {
            // 查找验证码
            $verification = Db::name('teacher_verification_codes')
                ->where('email', $email)
                ->where('code', $code)
                ->where('type', 'register')
                ->where('is_used', 0)
                ->where('expire_time', '>', time())
                ->order('id', 'desc')
                ->find();
            
            if (!$verification) {
                return json(['success' => false, 'error' => '验证码无效或已过期']);
            }
            
            // 更新账号状态
            $account = TeacherAccount::where('email', $email)->find();
            if ($account) {
                $account->status = 1; // 已验证
                $account->save();
            }
            
            // 标记验证码已使用
            Db::name('teacher_verification_codes')
                ->where('id', $verification['id'])
                ->update(['is_used' => 1]);
            
            return json([
                'success' => true,
                'message' => '验证成功'
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '验证失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 忘记密码
     */
    public function forgotPassword()
    {
        $email = $this->request->param('email');
        
        if (empty($email)) {
            return json(['success' => false, 'error' => '邮箱不能为空']);
        }
        
        try {
            $account = TeacherAccount::where('email', $email)->find();
            if (!$account) {
                return json(['success' => false, 'error' => '该邮箱未注册']);
            }
            
            // 生成重置码
            $resetCode = sprintf('%06d', rand(0, 999999));
            
            // 保存重置码
            Db::name('teacher_verification_codes')->insert([
                'email' => $email,
                'code' => $resetCode,
                'type' => 'reset',
                'create_time' => time(),
                'expire_time' => time() + 1800, // 30分钟有效期
                'is_used' => 0
            ]);
            
            // 发送邮件功能待实现
            
            return json([
                'success' => true,
                'message' => '重置码已发送',
                'data' => ['code' => $resetCode] // 开发环境返回，生产环境删除
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 重置密码
     */
    public function resetPassword()
    {
        $email = $this->request->param('email');
        $code = $this->request->param('code');
        $newPassword = $this->request->param('new_password');
        
        if (empty($email) || empty($code) || empty($newPassword)) {
            return json(['success' => false, 'error' => '参数不完整']);
        }
        
        try {
            // 验证重置码
            $verification = Db::name('teacher_verification_codes')
                ->where('email', $email)
                ->where('code', $code)
                ->where('type', 'reset')
                ->where('is_used', 0)
                ->where('expire_time', '>', time())
                ->order('id', 'desc')
                ->find();
            
            if (!$verification) {
                return json(['success' => false, 'error' => '重置码无效或已过期']);
            }
            
            // 更新密码
            $account = TeacherAccount::where('email', $email)->find();
            if ($account) {
                $account->password = password_hash($newPassword, PASSWORD_DEFAULT);
                $account->save();
            }
            
            // 标记重置码已使用
            Db::name('teacher_verification_codes')
                ->where('id', $verification['id'])
                ->update(['is_used' => 1]);
            
            return json([
                'success' => true,
                'message' => '密码重置成功'
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '重置失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 登出
     */
    public function logout()
    {
        // 清除Token（实际应该是从数据库或缓存中删除Token）
        return json([
            'success' => true,
            'message' => '登出成功'
        ]);
    }
}


