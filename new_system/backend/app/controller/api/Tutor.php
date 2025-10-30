<?php
namespace app\controller\api;

use app\BaseController;
use app\model\TutorOrder;
use think\facade\Db;

/**
 * 家教信息查询控制器（用户端）
 */
class Tutor extends BaseController
{
    /**
     * 获取家教信息列表
     */
    public function list()
    {
        try {
            $cityId = $this->request->get('city_id');
            $districtId = $this->request->get('district_id');
            $subjectId = $this->request->get('subject_id');
            $grade = $this->request->get('grade');
            $keyword = $this->request->get('keyword');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            $query = TutorOrder::with(['city', 'district', 'subject', 'admin', 'dispatcher'])
                ->where('status', 1)
                ->order([
                    'is_top' => 'desc',
                    'is_urgent' => 'desc',
                    'create_time' => 'desc'
                ]);
            
            // 过滤掉过期的置顶
            $query->where(function($q) {
                $q->where('is_top', 0)
                  ->whereOr(function($q2) {
                      $q2->where('is_top', 1)
                         ->where(function($q3) {
                             $q3->where('top_expire_time', null)
                                ->whereOr('top_expire_time', '>', date('Y-m-d H:i:s'));
                         });
                  });
            });
            
            // 筛选条件
            if ($cityId) $query->where('city_id', $cityId);
            if ($districtId) $query->where('district_id', $districtId);
            if ($subjectId) $query->where('subject_id', $subjectId);
            if ($grade) $query->where('grade', 'like', '%' . $grade . '%');
            if ($keyword) $query->where('content', 'like', '%' . $keyword . '%');
            
            $list = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            // 处理派单信息
            $items = $list->items();
            foreach ($items as &$item) {
                if ($item->dispatcher) {
                    $item->contact_person = $item->dispatcher->nickname;
                    // 确保contact_info字段有值
                    if (empty($item->contact_info) && !empty($item->dispatcher->contact)) {
                        $item->contact_info = $item->dispatcher->contact;
                    }
                }
            }
            
            return json([
                'success' => true,
                'data' => $items,
                'total' => $list->total(),
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取家教信息详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        
        try {
            $order = TutorOrder::with(['city', 'district', 'subject', 'dispatcher'])
                ->where('status', 1)
                ->find($id);
            
            if (!$order) {
                return json(['success' => false, 'error' => '信息不存在']);
            }
            
            // 如果有派单信息，添加联系方式
            if ($order->dispatcher) {
                $order->contact_person = $order->dispatcher->nickname;
                // 确保contact_info字段有值
                if (empty($order->contact_info) && !empty($order->dispatcher->contact)) {
                    $order->contact_info = $order->dispatcher->contact;
                }
            }
            
            return json(['success' => true, 'data' => $order]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门城市
     */
    public function hotCities()
    {
        try {
            $hotCities = Db::name('tutor_orders_new')
                ->alias('o')
                ->join('cities c', 'o.city_id = c.id')
                ->where('o.status', 1)
                ->field('c.id, c.name, c.level, COUNT(*) as count')
                ->group('o.city_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select();
            
            return json(['success' => true, 'data' => $hotCities]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取热门科目
     */
    public function hotSubjects()
    {
        try {
            $hotSubjects = Db::name('tutor_orders_new')
                ->alias('o')
                ->join('subjects s', 'o.subject_id = s.id')
                ->where('o.status', 1)
                ->field('s.id, s.name, s.category, COUNT(*) as count')
                ->group('o.subject_id')
                ->order('count', 'desc')
                ->limit(10)
                ->select();
            
            return json(['success' => true, 'data' => $hotSubjects]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

