<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\PersonnelSalary as PersonnelSalaryModel;
use app\model\Personnel as PersonnelModel;
use think\facade\Db;

/**
 * 人员薪酬管理
 * 路由前缀：/admin/api/personnel-salary
 */
class PersonnelSalary extends BaseController
{
    /**
     * 列表 GET /personnel-salary
     */
    public function index()
    {
        try {
            $page          = (int)input('page', 1);
            $pageSize      = (int)input('pageSize', 20);
            $keyword       = trim((string)input('keyword', ''));
            $personnelId   = (int)input('personnel_id', 0);
            $status        = input('status', '');

            $query = PersonnelSalaryModel::order('id', 'desc');

            // 关键词搜索
            if ($keyword !== '') {
                $query->where(function($q) use ($keyword) {
                    $like = '%' . $keyword . '%';
                    // 先查询符合条件的人员ID
                    $personnelIds = PersonnelModel::where('delete_time', 0)
                        ->where(function($pq) use ($like) {
                            $pq->where('name', 'like', $like)
                               ->whereOr('phone', 'like', $like);
                        })
                        ->column('id');
                    
                    if (!empty($personnelIds)) {
                        $q->whereIn('personnel_id', $personnelIds);
                    } else {
                        // 如果没有找到匹配的人员，返回空结果
                        $q->where('id', 0);
                    }
                });
            }
            
            if ($personnelId > 0) {
                $query->where('personnel_id', $personnelId);
            }
            if ($status !== '') {
                $query->where('status', $status);
            }

            $total = $query->count();
            $list  = $query->page($page, $pageSize)->select();
            
            // 手动加载人员信息
            $listArray = [];
            foreach ($list as $item) {
                $itemArray = $item->toArray();
                
                // 确保status字段是整数类型
                $itemArray['status'] = (int)$itemArray['status'];
                
                // 获取人员信息
                $personnel = PersonnelModel::where('id', $item->personnel_id)
                    ->where('delete_time', 0)
                    ->field('id,name,phone,position_name,dept_name')
                    ->find();
                
                if ($personnel) {
                    $itemArray['personnel'] = $personnel->toArray();
                } else {
                    $itemArray['personnel'] = [
                        'id' => $item->personnel_id,
                        'name' => '未知',
                        'phone' => '',
                        'position_name' => '',
                        'dept_name' => ''
                    ];
                }
                
                $listArray[] = $itemArray;
            }

            return json([
                'success' => true,
                'data'    => [
                    'list'  => $listArray,
                    'total' => $total,
                ],
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '获取列表失败：' . $e->getMessage(),
                'data'    => [
                    'list'  => [],
                    'total' => 0,
                ],
            ]);
        }
    }

    /**
     * 详情 GET /personnel-salary/:id
     */
    public function read($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $salary = PersonnelSalaryModel::with(['personnel'])->find($id);
        if (!$salary) {
            return json(['success' => false, 'message' => '薪酬记录不存在']);
        }
        return json([
            'success' => true,
            'data'    => $salary->toArray(),
        ]);
    }

    /**
     * 根据人员ID获取当前有效薪酬 GET /personnel-salary/current/:personnelId
     */
    public function getCurrentByPersonnel($personnelId)
    {
        $personnelId = (int)$personnelId;
        if ($personnelId <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }

        $salary = PersonnelSalaryModel::where('personnel_id', $personnelId)
            ->where('status', 1)
            ->where(function($query) {
                $query->whereNull('end_date')
                      ->whereOr('end_date', '>=', date('Y-m-d'));
            })
            ->order('effective_date', 'desc')
            ->find();

        if (!$salary) {
            return json(['success' => false, 'message' => '未找到有效薪酬记录']);
        }

        return json([
            'success' => true,
            'data'    => $salary->toArray(),
        ]);
    }

    /**
     * 新增 POST /personnel-salary
     */
    public function save()
    {
        $data = $this->request->post();
        $check = $this->validateSalaryData($data);
        if ($check !== true) {
            return json(['success' => false, 'message' => $check]);
        }

        try {
            // salary_month 兼容：若未传 effective_date，用 salary_month 第一天补全
            if (empty($data['effective_date']) && !empty($data['salary_month'])) {
                $data['effective_date'] = $data['salary_month'] . '-01';
            }

            // 空字符串的 end_date 转为 null，避免 MySQL 日期格式报错
            if (isset($data['end_date']) && $data['end_date'] === '') {
                $data['end_date'] = null;
            }

            // 自动计算总薪酬
            $data['total_salary'] = $this->calculateTotalSalary($data);

            // 如果设置为当前有效，需要将该人员的其他有效薪酬设为失效
            if (isset($data['status']) && $data['status'] == 1) {
                PersonnelSalaryModel::where('personnel_id', $data['personnel_id'])
                    ->where('status', 1)
                    ->update(['status' => 0, 'end_date' => date('Y-m-d', strtotime('-1 day', strtotime($data['effective_date'])))]);
            }

            $salary = PersonnelSalaryModel::create($data);
            return json(['success' => true, 'message' => '创建成功', 'data' => ['id' => $salary->id]]);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '创建失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新 PUT /personnel-salary/:id
     */
    public function update($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $salary = PersonnelSalaryModel::find($id);
        if (!$salary) {
            return json(['success' => false, 'message' => '薪酬记录不存在']);
        }

        $data = $this->request->put();
        if (empty($data)) {
            $data = $this->request->post();
        }
        $check = $this->validateSalaryData($data);
        if ($check !== true) {
            return json(['success' => false, 'message' => $check]);
        }

        try {
            // salary_month 兼容：若未传 effective_date，用 salary_month 第一天补全
            if (empty($data['effective_date']) && !empty($data['salary_month'])) {
                $data['effective_date'] = $data['salary_month'] . '-01';
            }

            // 空字符串的 end_date 转为 null，避免 MySQL 日期格式报错
            if (isset($data['end_date']) && $data['end_date'] === '') {
                $data['end_date'] = null;
            }

            // 自动计算总薪酬
            $data['total_salary'] = $this->calculateTotalSalary($data);

            // 如果设置为有效，需要将该人员的其他有效薪酬设为失效
            if (isset($data['status']) && $data['status'] == 1) {
                PersonnelSalaryModel::where('personnel_id', $salary->personnel_id)
                    ->where('id', '<>', $id)
                    ->where('status', 1)
                    ->update(['status' => 0, 'end_date' => date('Y-m-d', strtotime('-1 day', strtotime($data['effective_date'])))]);
            }

            // 保存更新
            $salary->save($data);
            
            // 重新查询以获取最新数据
            $salary->refresh();
            
            return json(['success' => true, 'message' => '更新成功', 'data' => $salary->toArray()]);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
        }
    }

    /**
     * 删除 DELETE /personnel-salary/:id
     */
    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return json(['success' => false, 'message' => '参数错误']);
        }
        $salary = PersonnelSalaryModel::find($id);
        if (!$salary) {
            return json(['success' => false, 'message' => '薪酬记录不存在']);
        }
        try {
            $salary->delete();
            return json(['success' => true, 'message' => '删除成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'message' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取人员列表（用于下拉选择）
     */
    public function getPersonnelOptions()
    {
        try {
            // 记录日志
            error_log('PersonnelSalary::getPersonnelOptions - 开始执行');
            
            $list = PersonnelModel::field('id,name,phone,position_name,dept_name')
                ->where('delete_time', 0)
                ->order('id', 'desc')
                ->select();
            
            error_log('PersonnelSalary::getPersonnelOptions - 查询到 ' . count($list) . ' 条记录');
            
            // 转换为数组
            $listArray = [];
            foreach ($list as $item) {
                $listArray[] = $item->toArray();
            }
            
            error_log('PersonnelSalary::getPersonnelOptions - 转换完成');

            return json([
                'success' => true,
                'data'    => $listArray,
            ]);
        } catch (\Exception $e) {
            error_log('PersonnelSalary::getPersonnelOptions - 错误: ' . $e->getMessage());
            error_log('PersonnelSalary::getPersonnelOptions - 堆栈: ' . $e->getTraceAsString());
            
            return json([
                'success' => false, 
                'message' => '获取人员列表失败：' . $e->getMessage(),
                'data'    => [],
            ]);
        }
    }

    /**
     * 薪酬统计
     */
    public function statistics()
    {
        try {
            // 在职人员总数（从人员管理表获取）
            $activeCount = PersonnelModel::where('delete_time', 0)->count();

            // 平均薪酬
            $avgSalary = PersonnelSalaryModel::where('status', 1)->where('delete_time', 0)->avg('total_salary');

            // 最高薪酬
            $maxSalary = PersonnelSalaryModel::where('status', 1)->where('delete_time', 0)->max('total_salary');

            // 最低薪酬
            $minSalary = PersonnelSalaryModel::where('status', 1)->where('delete_time', 0)->min('total_salary');

            // 薪酬总额
            $totalSalary = PersonnelSalaryModel::where('status', 1)->where('delete_time', 0)->sum('total_salary');

            return json([
                'success' => true,
                'data'    => [
                    'activeCount' => (int)$activeCount,
                    'avgSalary'   => $avgSalary ? number_format($avgSalary, 2, '.', '') : '0.00',
                    'maxSalary'   => $maxSalary ? number_format($maxSalary, 2, '.', '') : '0.00',
                    'minSalary'   => $minSalary ? number_format($minSalary, 2, '.', '') : '0.00',
                    'totalSalary' => $totalSalary ? number_format($totalSalary, 2, '.', '') : '0.00',
                ],
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false, 
                'message' => '获取统计数据失败：' . $e->getMessage(),
                'data'    => [
                    'activeCount' => 0,
                    'avgSalary'   => '0.00',
                    'maxSalary'   => '0.00',
                    'minSalary'   => '0.00',
                    'totalSalary' => '0.00',
                ],
            ]);
        }
    }

    // ===========================================================
    // 内部方法
    // ===========================================================

    /**
     * 字段校验
     */
    private function validateSalaryData(array $data)
    {
        $required = [
            'personnel_id'   => '人员',
            'base_salary'    => '基本工资',
        ];
        // salary_month 优先；兼容旧数据的 effective_date
        if (empty($data['salary_month']) && empty($data['effective_date'])) {
            return '归属月份不能为空';
        }
        foreach ($required as $key => $label) {
            if (!isset($data[$key]) || $data[$key] === '') {
                return $label . '不能为空';
            }
        }

        // 验证人员是否存在
        $personnel = PersonnelModel::find($data['personnel_id']);
        if (!$personnel) {
            return '人员不存在';
        }

        // 验证日期格式
        if (!empty($data['effective_date']) && !strtotime($data['effective_date'])) {
            return '生效日期格式不正确';
        }
        if (isset($data['end_date']) && $data['end_date'] !== '' && !strtotime($data['end_date'])) {
            return '结束日期格式不正确';
        }

        // 验证金额
        $salaryFields = ['base_salary', 'performance_salary', 'post_allowance', 'housing_allowance', 
                        'meal_allowance', 'transport_allowance', 'other_allowance',
                        'provident_fund_company', 'provident_fund_personal',
                        'social_insurance_company', 'social_insurance_personal'];
        foreach ($salaryFields as $field) {
            if (isset($data[$field]) && $data[$field] < 0) {
                return '薪酬金额不能为负数';
            }
        }

        return true;
    }

    /**
     * 计算总薪酬（实发 = 收入合计 - 个人社保 - 个人公积金）
     */
    private function calculateTotalSalary(array $data)
    {
        $income = 0;
        $income += isset($data['base_salary']) ? floatval($data['base_salary']) : 0;
        $income += isset($data['performance_salary']) ? floatval($data['performance_salary']) : 0;
        $income += isset($data['post_allowance']) ? floatval($data['post_allowance']) : 0;
        $income += isset($data['housing_allowance']) ? floatval($data['housing_allowance']) : 0;
        $income += isset($data['meal_allowance']) ? floatval($data['meal_allowance']) : 0;
        $income += isset($data['transport_allowance']) ? floatval($data['transport_allowance']) : 0;
        $income += isset($data['other_allowance']) ? floatval($data['other_allowance']) : 0;

        $deduction = 0;
        $deduction += isset($data['provident_fund_personal']) ? floatval($data['provident_fund_personal']) : 0;
        $deduction += isset($data['social_insurance_personal']) ? floatval($data['social_insurance_personal']) : 0;

        return $income - $deduction;
    }
}
