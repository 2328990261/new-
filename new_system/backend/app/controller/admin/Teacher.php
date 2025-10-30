<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Teacher as TeacherModel;
use think\facade\Session;

/**
 * 教师管理控制器
 */
class Teacher extends BaseController
{
    /**
     * 获取教师列表
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
            $keyword = $this->request->get('keyword');
            $status = $this->request->get('status');
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 构建查询条件
            $where = [];
            
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            
            if ($status) {
                $where[] = ['status', '=', $status];
            }
            
            // 获取总数
            $total = TeacherModel::where($where)->count();
            
            // 获取数据列表
            $list = TeacherModel::where($where)
                ->order('is_top', 'desc')
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取教师详情
     */
    public function read($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $teacher = TeacherModel::find($id);
            
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            return json([
                'success' => true,
                'data' => $teacher
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 审核教师
     */
    public function review($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $status = $this->request->post('status');
            $reason = $this->request->post('reason', '');
            
            if (!in_array($status, ['approved', 'rejected'])) {
                return json(['success' => false, 'error' => '无效的审核状态']);
            }
            
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            // 更新审核状态
            $teacher->status = $status;
            $teacher->reject_reason = $status === 'rejected' ? $reason : '';
            $teacher->review_time = date('Y-m-d H:i:s');
            $teacher->reviewer_id = $_SESSION['admin_id'];
            $teacher->save();
            
            return json([
                'success' => true,
                'message' => $status === 'approved' ? '审核通过' : '已拒绝'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 设置教师置顶
     */
    public function setTop($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $isTop = $this->request->post('is_top', 0);
            
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $teacher->is_top = $isTop ? 1 : 0;
            $teacher->save();
            
            return json([
                'success' => true,
                'message' => $isTop ? '置顶成功' : '取消置顶成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 删除教师
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
            $teacher = TeacherModel::find($id);
            if (!$teacher) {
                return json(['success' => false, 'error' => '教师不存在']);
            }
            
            $teacher->delete();
            
            return json([
                'success' => true,
                'message' => '删除成功'
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

