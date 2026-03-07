<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Subject as SubjectModel;
use think\facade\Session;

/**
 * 科目管理控制器
 */
class Subject extends BaseController
{
    /**
     * 获取科目树形列表
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
            $status = $this->request->get('status');
            $keyword = $this->request->get('keyword');
            
            // 构建查询条件
            $where = [];
            if ($status !== '' && $status !== null) {
                $where[] = ['status', '=', $status];
            }
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            
            // 获取所有科目（包含一级和二级）
            $allSubjects = SubjectModel::where($where)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            // 构建树形结构
            $tree = $this->buildTree($allSubjects);
            
            return json([
                'success' => true,
                'data' => $tree
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 构建树形结构
     */
    private function buildTree($items, $parentId = 0)
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }
    
    /**
     * 获取所有科目（不分页）
     */
    public function all()
    {
        try {
            $subjects = SubjectModel::where('status', 1)
                ->order('sort', 'asc')
                ->select();
            
            // 按分类分组
            $grouped = [];
            foreach ($subjects as $subject) {
                $category = $subject->category ?: '其他';
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $subject;
            }
            
            return json(['success' => true, 'data' => $grouped]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 创建科目
     */
    public function save()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        try {
            $subject = SubjectModel::create($data);
            return json(['success' => true, 'message' => '创建成功', 'data' => $subject]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新科目
     */
    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $data = $this->request->post();
        
        try {
            $subject = SubjectModel::find($id);
            if (!$subject) {
                return json(['success' => false, 'error' => '科目不存在']);
            }
            
            $subject->save($data);
            return json(['success' => true, 'message' => '更新成功', 'data' => $subject]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除科目
     */
    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            $subject = SubjectModel::find($id);
            if (!$subject) {
                return json(['success' => false, 'error' => '科目不存在']);
            }
            
            $subject->delete();
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 切换状态
     */
    public function toggleStatus()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        $status = $this->request->post('status');
        
        try {
            $subject = SubjectModel::find($id);
            if (!$subject) {
                return json(['success' => false, 'error' => '科目不存在']);
            }
            
            $subject->status = $status;
            $subject->save();
            
            return json(['success' => true, 'message' => '状态更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量更新排序（支持拖拽后的树形结构）
     */
    public function batchUpdateSort()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $tree = $this->request->post('tree');
        
        if (!$tree || !is_array($tree)) {
            return json(['success' => false, 'error' => '参数错误']);
        }
        
        try {
            // 递归更新排序
            $this->updateTreeSort($tree, 0);
            
            return json(['success' => true, 'message' => '排序更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 递归更新树形结构的排序
     */
    private function updateTreeSort($nodes, $parentId = 0, &$sort = 0)
    {
        foreach ($nodes as $node) {
            $sort += 10;
            
            // 更新当前节点
            SubjectModel::where('id', $node['id'])->update([
                'parent_id' => $parentId,
                'sort' => $sort
            ]);
            
            // 递归更新子节点
            if (isset($node['children']) && is_array($node['children'])) {
                $childSort = 0;
                $this->updateTreeSort($node['children'], $node['id'], $childSort);
            }
        }
    }
    
    /**
     * 获取一级科目列表（用于选择父级）
     */
    public function parents()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $parents = SubjectModel::where('parent_id', 0)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select();
            
            return json(['success' => true, 'data' => $parents]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

