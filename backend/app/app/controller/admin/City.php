<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\City as CityModel;
use think\facade\Session;

/**
 * 城市管理控制器
 */
class City extends BaseController
{
    /**
     * 获取城市列表
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
            $province_id = $this->request->get('province_id');
            $level = $this->request->get('level');
            $status = $this->request->get('status');
            $keyword = $this->request->get('keyword');
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 20);
            
            // 构建查询条件
            $where = [];
            if ($province_id) {
                $where[] = ['province_id', '=', $province_id];
            }
            if ($level) {
                $where[] = ['level', '=', $level];
            }
            if ($status !== '' && $status !== null) {
                $where[] = ['status', '=', $status];
            }
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }
            
            // 获取总数
            $total = CityModel::where($where)->count();
            
            // 获取数据列表，加载省份关联
            // 热门城市优先排序
            $list = CityModel::with(['province'])
                ->where($where)
                ->order('is_hot', 'desc')
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
     * 获取所有城市（不分页）
     * 热门城市优先排序
     */
    public function all()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $cities = CityModel::with(['province'])
                ->where('status', 1)
                ->order('is_hot', 'desc')  // 热门城市优先（is_hot=1排在前面）
                ->order('sort', 'asc')      // 然后按排序值升序
                ->select();
            
            // 直接返回城市数组，不分组
            return json(['success' => true, 'data' => $cities]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 创建城市
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
            $city = CityModel::create($data);
            return json(['success' => true, 'message' => '创建成功', 'data' => $city]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '创建失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新城市
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
            $city = CityModel::find($id);
            if (!$city) {
                return json(['success' => false, 'error' => '城市不存在']);
            }
            
            $city->save($data);
            return json(['success' => true, 'message' => '更新成功', 'data' => $city]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除城市
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
            $city = CityModel::find($id);
            if (!$city) {
                return json(['success' => false, 'error' => '城市不存在']);
            }
            
            $city->delete();
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
            $city = CityModel::find($id);
            if (!$city) {
                return json(['success' => false, 'error' => '城市不存在']);
            }
            
            $city->status = $status;
            $city->save();
            
            return json(['success' => true, 'message' => '状态更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '操作失败：' . $e->getMessage()]);
        }
    }
}

