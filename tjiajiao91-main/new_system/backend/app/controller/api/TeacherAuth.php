<?php
namespace app\controller\api;

use app\BaseController;
use app\model\TeacherAccount;

class TeacherAuth extends BaseController
{
    /**
     * 教师注册（无需邮箱验证，注册即可登录）
     */
    public function register()
    {
        $email    = $this->request->param('email');
        $password = $this->request->param('password');

        if (empty($email) || empty($password)) {
            return json(['success' => false, 'error' => '邮箱和密码不能为空']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return json(['success' => false, 'error' => '邮箱格式不正确']);
        }

        if (TeacherAccount::where('email', $email)->find()) {
            return json(['success' => false, 'error' => '该邮箱已注册']);
        }

        try {
            $account = TeacherAccount::create([
                'email'       => $email,
                'password'    => password_hash($password, PASSWORD_DEFAULT),
                'status'      => 1, // 直接激活，无需验证
                'create_time' => time(),
            ]);

            return json([
                'success' => true,
                'message' => '注册成功，请登录',
                'data'    => ['id' => $account->id, 'email' => $account->email],
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
        $email    = $this->request->param('email');
        $password = $this->request->param('password');

        if (empty($email) || empty($password)) {
            return json(['success' => false, 'error' => '邮箱和密码不能为空']);
        }

        try {
            $account = TeacherAccount::where('email', $email)->find();

            if (!$account || !password_verify($password, $account->password)) {
                return json(['success' => false, 'error' => '邮箱或密码错误']);
            }

            if ($account->status === 2) {
                return json(['success' => false, 'error' => '账号已被禁用']);
            }

            $token = md5($account->id . time() . rand(1000, 9999));

            $account->last_login_time = time();
            $account->save();

            return json([
                'success' => true,
                'message' => '登录成功',
                'data'    => [
                    'token'      => $token,
                    'id'         => $account->id,
                    'email'      => $account->email,
                    'teacher_id' => $account->teacher_id,
                ],
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '登录失败：' . $e->getMessage()]);
        }
    }

    /**
     * 登出
     */
    public function logout()
    {
        return json(['success' => true, 'message' => '登出成功']);
    }
}
