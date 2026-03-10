<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 通用数据接口
 */
class Common extends BaseController
{
    /**
     * 获取所有城市列表
     */
    public function cities()
    {
        try {
            $cities = Db::name('cities')
                ->where('status', 1)
                ->order('sort_order asc, id asc')
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => $cities
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取城市的区域列表
     */
    public function districts()
    {
        try {
            $cityId = $this->request->param('city_id');
            
            if (empty($cityId)) {
                return json([
                    'success' => false,
                    'error' => '缺少城市ID'
                ]);
            }
            
            $districts = Db::name('districts')
                ->where('city_id', $cityId)
                ->where('status', 1)
                ->order('sort_order asc, id asc')
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => $districts
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取所有年级列表
     */
    public function grades()
    {
        try {
            $grades = Db::name('grades')
                ->where('status', 1)
                ->order('sort_order asc, id asc')
                ->select()
                ->toArray();
            
            return json([
                'success' => true,
                'data' => $grades
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取所有科目列表（分类）
     */
    public function subjects()
    {
        try {
            // 获取所有科目
            $subjects = Db::name('subjects')
                ->where('status', 1)
                ->order('sort_order asc, id asc')
                ->select()
                ->toArray();
            
            // 按分类组织
            $categories = [];
            $categoryMap = [];
            
            foreach ($subjects as $subject) {
                $category = $subject['category'] ?? '其他';
                
                if (!isset($categoryMap[$category])) {
                    $categoryMap[$category] = [
                        'name' => $category,
                        'children' => []
                    ];
                }
                
                $categoryMap[$category]['children'][] = [
                    'id' => $subject['id'],
                    'name' => $subject['name']
                ];
            }
            
            // 转换为数组
            $categories = array_values($categoryMap);
            
            return json([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
