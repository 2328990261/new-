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
            $teacherGender = $this->request->get('teacher_gender');
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

            if ($teacherGender) {
                $unlimitedKeywords = ['男女不限', '男女老师', '男女大学生'];
                if ($teacherGender === 'male') {
                    $query->where(function($q) {
                        $q->where('content', 'like', '%男老师%')
                          ->whereOr('content', 'like', '%男大学生%');
                    })->where(function($q) {
                        $q->where('content', 'not like', '%女老师%')
                          ->where('content', 'not like', '%女大学生%');
                    })->where(function($q) use ($unlimitedKeywords) {
                        foreach ($unlimitedKeywords as $keyword) {
                            $q->where('content', 'not like', '%' . $keyword . '%');
                        }
                    });
                } elseif ($teacherGender === 'female') {
                    $query->where(function($q) {
                        $q->where('content', 'like', '%女老师%')
                          ->whereOr('content', 'like', '%女大学生%');
                    })->where(function($q) {
                        $q->where('content', 'not like', '%男老师%')
                          ->where('content', 'not like', '%男大学生%');
                    })->where(function($q) use ($unlimitedKeywords) {
                        foreach ($unlimitedKeywords as $keyword) {
                            $q->where('content', 'not like', '%' . $keyword . '%');
                        }
                    });
                }
            }
            
            // 关键字搜索：支持多关键词搜索（AND逻辑）
            if ($keyword) {
                // 分割关键词（支持空格分隔）
                $keywords = array_filter(explode(' ', trim($keyword)));
                
                if (!empty($keywords)) {
                    // 每个关键词都必须匹配（AND逻辑）
                    foreach ($keywords as $kw) {
                        $query->where(function($q) use ($kw) {
                            $q->where('content', 'like', '%' . $kw . '%')
                              ->whereOr('id', 'like', '%' . $kw . '%')
                              ->whereOr('city_id', 'in', function($query) use ($kw) {
                                  $query->name('cities')->where('name', 'like', '%' . $kw . '%')->field('id');
                              })
                              ->whereOr('district_id', 'in', function($query) use ($kw) {
                                  $query->name('districts')->where('name', 'like', '%' . $kw . '%')->field('id');
                              })
                              ->whereOr('subject_id', 'in', function($query) use ($kw) {
                                  $query->name('subjects')->where('name', 'like', '%' . $kw . '%')->field('id');
                              });
                        });
                    }
                }
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

