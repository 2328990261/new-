<?php
namespace app\controller\api;

use app\BaseController;
use app\model\City;
use app\model\District;
use app\model\Subject;

/**
 * 搜索筛选控制器（用户端）
 */
class Search extends BaseController
{
    /**
     * 获取城市列表（平铺显示，热门优先）
     */
    public function cities()
    {
        try {
            // 加载城市及其关联的省份，热门城市优先排序
            $cities = City::with(['province'])
                ->where('status', 1)
                ->order('is_hot', 'desc')  // 热门城市优先
                ->order('sort', 'asc')      // 然后按排序值升序
                ->select();
            
            return json(['success' => true, 'data' => $cities]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取区域列表（按城市）
     */
    public function districts()
    {
        $cityId = $this->request->get('city_id');
        
        if (!$cityId) {
            return json(['success' => false, 'error' => '请提供城市ID']);
        }
        
        try {
            $districts = District::where('city_id', $cityId)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select();
            
            return json(['success' => true, 'data' => $districts]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取科目列表（按分类）
     */
    public function subjects()
    {
        try {
            $subjects = Subject::where('status', 1)
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
     * 综合搜索
     */
    public function search()
    {
        $keyword = $this->request->post('keyword');
        
        if (empty($keyword)) {
            return json(['success' => false, 'error' => '请提供搜索关键词']);
        }
        
        try {
            // 搜索城市
            $cities = City::where('name', 'like', '%' . $keyword . '%')
                ->where('status', 1)
                ->limit(5)
                ->select();
            
            // 搜索区域
            $districts = District::where('name', 'like', '%' . $keyword . '%')
                ->where('status', 1)
                ->with(['city'])
                ->limit(5)
                ->select();
            
            // 搜索科目
            $subjects = Subject::where('name', 'like', '%' . $keyword . '%')
                ->where('status', 1)
                ->limit(5)
                ->select();
            
            return json([
                'success' => true,
                'data' => [
                    'cities' => $cities,
                    'districts' => $districts,
                    'subjects' => $subjects
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

