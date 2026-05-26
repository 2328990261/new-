<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\ExpenseType as ExpenseTypeModel;
use think\facade\Db;

class ExpenseType extends BaseController
{
    /**
     * 获取费用类型列表
     */
    public function index()
    {
        try {
            $list = ExpenseTypeModel::getAllList();
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => count($list)
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取启用的费用类型列表（用于下拉选择）
     */
    public function getEnabled()
    {
        try {
            $list = ExpenseTypeModel::getEnabledList();
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 创建费用类型
     */
    public function create()
    {
        try {
            $data = $this->request->post();
            
            // 验证必填字段
            if (empty($data['name'])) {
                return json(['code' => 1, 'msg' => '费用类型名称不能为空']);
            }
            
            // 检查名称是否已存在
            $exists = ExpenseTypeModel::where('name', $data['name'])->find();
            if ($exists) {
                return json(['code' => 1, 'msg' => '该费用类型名称已存在']);
            }
            
            // 设置默认值
            $insertData = [
                'name' => $data['name'],
                'sort' => $data['sort'] ?? 0,
                'status' => $data['status'] ?? 1,
                'is_system' => 0, // 用户创建的都不是系统内置
            ];
            
            $model = ExpenseTypeModel::create($insertData);
            
            return json([
                'code' => 0,
                'msg' => '创建成功',
                'data' => $model
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '创建失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 更新费用类型
     */
    public function update()
    {
        try {
            $id = $this->request->param('id');
            $data = $this->request->post();
            
            if (empty($id)) {
                return json(['code' => 1, 'msg' => 'ID不能为空']);
            }
            
            $model = ExpenseTypeModel::find($id);
            if (!$model) {
                return json(['code' => 1, 'msg' => '费用类型不存在']);
            }
            
            // 验证必填字段
            if (isset($data['name']) && empty($data['name'])) {
                return json(['code' => 1, 'msg' => '费用类型名称不能为空']);
            }
            
            // 检查名称是否已被其他记录使用
            if (isset($data['name'])) {
                $exists = ExpenseTypeModel::where('name', $data['name'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    return json(['code' => 1, 'msg' => '该费用类型名称已存在']);
                }
            }
            
            // 更新数据
            $updateData = [];
            if (isset($data['name'])) $updateData['name'] = $data['name'];
            if (isset($data['sort'])) $updateData['sort'] = $data['sort'];
            if (isset($data['status'])) $updateData['status'] = $data['status'];
            
            $model->save($updateData);
            
            return json([
                'code' => 0,
                'msg' => '更新成功',
                'data' => $model
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '更新失败: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 删除费用类型
     */
    public function delete()
    {
        try {
            $id = $this->request->param('id');
            
            if (empty($id)) {
                return json(['code' => 1, 'msg' => 'ID不能为空']);
            }
            
            $model = ExpenseTypeModel::find($id);
            if (!$model) {
                return json(['code' => 1, 'msg' => '费用类型不存在']);
            }
            
            // 检查是否为系统内置类型
            if ($model->is_system == 1) {
                return json(['code' => 1, 'msg' => '系统内置类型不能删除']);
            }
            
            // 检查是否有关联的费用记录
            $usedCount = Db::name('salary')
                ->where('expense_type', $model->name)
                ->count();
            
            if ($usedCount > 0) {
                return json(['code' => 1, 'msg' => '该费用类型已被使用，不能删除']);
            }
            
            $model->delete();
            
            return json([
                'code' => 0,
                'msg' => '删除成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '删除失败: ' . $e->getMessage()
            ]);
        }
    }
}
