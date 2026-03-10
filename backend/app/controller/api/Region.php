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
                'success' => true,
                'code' => 200,
                'msg' => '获取成功',
                'data' => $provinces
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
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
                'success' => true,
                'code' => 200,
                'msg' => '获取成功',
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
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
                'success' => true,
                'code' => 200,
                'msg' => '获取成功',
                'data' => $districts
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'msg' => '获取区域失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 获取年级列表
     */
    public function grades()
    {
        try {
            $grades = [
                ['id' => 'infant', 'name' => '幼儿'],
                ['id' => 'primary', 'name' => '小学'],
                ['id' => 'junior', 'name' => '初中'],
                ['id' => 'senior', 'name' => '高中'],
                ['id' => 'adult', 'name' => '成人']
            ];
            
            return json([
                'success' => true,
                'code' => 200,
                'msg' => '获取成功',
                'data' => $grades
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'msg' => '获取年级失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 获取科目列表（二级结构）
     */
    public function subjects()
    {
        try {
            // 获取所有启用的科目
            $allSubjects = \app\model\Subject::where('status', 1)
                ->field('id,parent_id,name,category,sort')
                ->order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            // 分组为父子结构
            $parentSubjects = [];
            $childSubjects = [];
            
            foreach ($allSubjects as $subject) {
                if ($subject['parent_id'] == 0) {
                    $parentSubjects[] = $subject;
                } else {
                    if (!isset($childSubjects[$subject['parent_id']])) {
                        $childSubjects[$subject['parent_id']] = [];
                    }
                    $childSubjects[$subject['parent_id']][] = $subject;
                }
            }
            
            // 组装结果
            $result = [];
            foreach ($parentSubjects as $parent) {
                $result[] = [
                    'id' => $parent['id'],
                    'name' => $parent['name'],
                    'category' => $parent['category'],
                    'children' => $childSubjects[$parent['id']] ?? []
                ];
            }
            
            return json([
                'success' => true,
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'msg' => '获取科目失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
