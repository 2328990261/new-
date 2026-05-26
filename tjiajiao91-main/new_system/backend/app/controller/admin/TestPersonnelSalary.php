<?php
namespace app\controller\admin;

use app\BaseController;

class TestPersonnelSalary extends BaseController
{
    public function test()
    {
        try {
            // 测试1: 基本响应
            $result = [
                'test1' => 'OK',
                'timestamp' => time(),
            ];
            
            // 测试2: 检查模型
            try {
                $personnelSalaryClass = '\\app\\model\\PersonnelSalary';
                $result['test2_model_exists'] = class_exists($personnelSalaryClass) ? 'YES' : 'NO';
            } catch (\Exception $e) {
                $result['test2_error'] = $e->getMessage();
            }
            
            // 测试3: 查询人员
            try {
                $personnelModel = new \app\model\Personnel();
                $count = $personnelModel->where('delete_time', 0)->count();
                $result['test3_personnel_count'] = $count;
            } catch (\Exception $e) {
                $result['test3_error'] = $e->getMessage();
            }
            
            // 测试4: 查询薪酬
            try {
                $salaryModel = new \app\model\PersonnelSalary();
                $count = $salaryModel->count();
                $result['test4_salary_count'] = $count;
            } catch (\Exception $e) {
                $result['test4_error'] = $e->getMessage();
            }
            
            return json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
