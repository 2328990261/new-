<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Admin;

/**
 * 管理员管理控制器
 */
class AdminManage extends BaseController
{
    /**
     * 获取管理员列表
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $keyword = $this->request->get('keyword', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            $query = Admin::order('create_time', 'desc');
            
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr([
                        ['username', 'like', '%' . $keyword . '%'],
                        ['nickname', 'like', '%' . $keyword . '%'],
                        ['email', 'like', '%' . $keyword . '%']
                    ]);
                });
            }
            
            $result = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            return json([
                'success' => true,
                'data' => $result->items(),
                'total' => $result->total()
            ]);
            
        } catch (\Exception $e) {
            trace('获取管理员列表失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '获取管理员列表失败']);
        }
    }
    
    /**
     * 获取派单组管理员列表
     */
    public function getDispatchers()
    {
        try {
            $dispatchers = Admin::where('role', 'dispatcher')->select();
            return json([
                'success' => true,
                'data' => $dispatchers
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '获取派单组失败']);
        }
    }
    
    /**
     * 添加管理员
     */
    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $data = $this->request->post();
            
            // 验证必填字段
            if (empty($data['username']) || empty($data['password']) || empty($data['nickname'])) {
                return json(['success' => false, 'error' => '请填写完整信息']);
            }
            
            // 检查用户名是否已存在
            if (Admin::where('username', $data['username'])->find()) {
                return json(['success' => false, 'error' => '用户名已存在']);
            }
            
            // 密码加密
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            Admin::create($data);
            
            return json(['success' => true, 'message' => '添加成功']);
            
        } catch (\Exception $e) {
            trace('添加管理员失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '添加管理员失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新管理员
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            $data = $this->request->put();
            
            // 如果有密码，则加密
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }
            
            // 如果修改了用户名，检查是否重复
            if (isset($data['username']) && $data['username'] != $admin->username) {
                if (Admin::where('username', $data['username'])->find()) {
                    return json(['success' => false, 'error' => '用户名已存在']);
                }
            }
            
            $admin->save($data);
            
            return json(['success' => true, 'message' => '更新成功']);
            
        } catch (\Exception $e) {
            trace('更新管理员失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '更新管理员失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除管理员
     */
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            // 不能删除自己
            if ($id == $_SESSION['admin_id']) {
                return json(['success' => false, 'error' => '不能删除当前登录的管理员']);
            }
            
            $admin = Admin::find($id);
            if (!$admin) {
                return json(['success' => false, 'error' => '管理员不存在']);
            }
            
            $admin->delete();
            
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            trace('删除管理员失败: ' . $e->getMessage(), 'error');
            return json(['success' => false, 'error' => '删除管理员失败：' . $e->getMessage()]);
        }
    }
}






