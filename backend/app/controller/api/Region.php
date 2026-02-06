<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Province;
use app\model\City;
use app\model\District;

class Region extends BaseController
{
    /**
     * 获取所有省份
     */
    public function provinces()
    {
        try {
            $provinces = Province::where('status', 1)
                ->field('id,name,code')
                ->order('id', 'asc')
                ->select();
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $provinces
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '获取省份失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 获取城市列表
     */
    public function cities()
    {
        try {
            $provinceId = $this->request->param('province_id', 0);
            
            $query = City::where('status', 1);
            
            if ($provinceId > 0) {
                $query->where('province_id', $provinceId);
            }
            
            $cities = $query->field('id,name,code,province_id')
                ->order('id', 'asc')
                ->select();
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '获取城市失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 获取区域列表
     */
    public function districts($city_id)
    {
        try {
            $districts = District::where('city_id', $city_id)
                ->where('status', 1)
                ->field('id,name,code,city_id')
                ->order('id', 'asc')
                ->select();
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '获取区域失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
