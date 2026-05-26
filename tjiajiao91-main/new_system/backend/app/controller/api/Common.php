<?php
namespace app\controller\api;

use app\BaseController;
use app\model\City;
use app\model\District;
use app\model\Subject;

/**
 * 公共数据API控制器
 * 提供城市、区域、科目等基础数据
 */
class Common extends BaseController
{
    /**
     * 获取城市列表
     */
    public function cities()
    {
        try {
            $cities = City::where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select();
            
            return json([
                'success' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取城市列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取区域列表
     */
    public function districts()
    {
        $cityId = $this->request->param('city_id');
        
        if (empty($cityId)) {
            return json([
                'success' => false,
                'error' => '请提供城市ID'
            ]);
        }
        
        try {
            $districts = District::where('city_id', $cityId)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select();
            
            return json([
                'success' => true,
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取区域列表失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取科目列表
     */
    public function subjects()
    {
        try {
            $subjects = Subject::where('status', 1)
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select();
            
            return json([
                'success' => true,
                'data' => $subjects
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取科目列表失败：' . $e->getMessage()
            ]);
        }
    }
}
