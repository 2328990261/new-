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
            $teacherType = $this->request->get('teacher_type');
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
            
            // 年级筛选：支持单个或多个（逗号分隔）
            if ($grade) {
                // 检查是否包含多个年级（逗号分隔）
                if (strpos($grade, ',') !== false) {
                    $grades = explode(',', $grade);
                    $query->where(function($q) use ($grades) {
                        foreach ($grades as $g) {
                            $g = trim($g);
                            if (!empty($g)) {
                                $q->whereOr('grade', 'like', '%' . $g . '%');
                            }
                        }
                    });
                } else {
                    $query->where('grade', 'like', '%' . $grade . '%');
                }
            }
            
            if ($teacherType) $query->where('teacher_type', $teacherType);
            
            // 关键字搜索：支持搜索ID、内容、城市、区域、科目
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('content', 'like', '%' . $keyword . '%')
                      ->whereOr('id', 'like', '%' . $keyword . '%')
                      ->whereOr('city_id', 'in', function($query) use ($keyword) {
                          $query->name('cities')->where('name', 'like', '%' . $keyword . '%')->field('id');
                      })
                      ->whereOr('district_id', 'in', function($query) use ($keyword) {
                          $query->name('districts')->where('name', 'like', '%' . $keyword . '%')->field('id');
                      })
                      ->whereOr('subject_id', 'in', function($query) use ($keyword) {
                          $query->name('subjects')->where('name', 'like', '%' . $keyword . '%')->field('id');
                      });
                });
            }
            
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
    
    /**
     * 获取城市统计数据
     */
    public function cityStats()
    {
        try {
            // 查询各城市的订单数量（只统计启用状态的订单）
            $cityStats = Db::name('tutor_orders_new')
                ->alias('o')
                ->leftJoin('cities c', 'o.city_id = c.id')
                ->where('o.status', 1)
                ->field('o.city_id, c.name as city_name, COUNT(*) as count')
                ->group('o.city_id')
                ->order('count', 'desc')
                ->select();
            
            return json([
                'success' => true,
                'data' => $cityStats
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取城市统计失败：' . $e->getMessage()
            ]);
        }
    }
}

