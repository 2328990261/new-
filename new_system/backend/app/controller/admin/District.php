<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\District as DistrictModel;
use think\facade\Session;

/**
 * 区域管理控制器
 */
class District extends BaseController
{
    /**
     * 获取区域列表
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
            $cityId = $this->request->get('city_id');
            $status = $this->request->get('status');
            $keyword = $this->request->get('keyword');
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 构建查询条件
            $where = [];
            if ($cityId) {
                $where[] = ['city_id', '=', $cityId];
            }
            if ($status !== '' && $status !== null) {
                $where[] = ['status', '=', $status];
            }
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            
            // 获取总数
            $total = DistrictModel::where($where)->count();
            
            // 获取数据列表
            $list = DistrictModel::where($where)
                ->with(['city'])
                ->order('sort', 'asc')
                ->page($page, $limit)
                ->select();
            
            return json([
                'success' => true,
                'data' => $list,
                'total' => $total,
                'page' => (int)$page,
                'limit' => (int)$limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取指定城市的所有区域
     */
    public function byCity()
    {
        $cityId = $this->request->get('city_id');
        
        if (!$cityId) {
            return json(['success' => false, 'error' => '请提供城市ID']);
        }
        
        try {
            $districts = DistrictModel::where('city_id', $cityId)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select();
            
            return json(['success' => true, 'data' => $districts]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 创建区域
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
        
        if (empty($data['city_id'])) {
            return json(['success' => false, 'error' => '请选择所属城市']);
        }
        
        try {
            $district = DistrictModel::create($data);
            return json(['success' => true, 'message' => '创建成功', 'data' => $district]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新区域
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
            $district = DistrictModel::find($id);
            if (!$district) {
                return json(['success' => false, 'error' => '区域不存在']);
            }
            
            $district->save($data);
            return json(['success' => true, 'message' => '更新成功', 'data' => $district]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除区域
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
            $district = DistrictModel::find($id);
            if (!$district) {
                return json(['success' => false, 'error' => '区域不存在']);
            }
            
            $district->delete();
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
            $district = DistrictModel::find($id);
            if (!$district) {
                return json(['success' => false, 'error' => '区域不存在']);
            }
            
            $district->status = $status;
            $district->save();
            
            return json(['success' => true, 'message' => '状态更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
}

