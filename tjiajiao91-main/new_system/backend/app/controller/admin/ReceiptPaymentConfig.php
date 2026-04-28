<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\ReceiptMethodConfig;
use app\model\PaymentMethodConfig;
use app\model\Salary as SalaryModel;

class ReceiptPaymentConfig extends BaseController
{
    // ========== 收款单位管理 ==========
    
    // 获取收款单位配置列表
    public function getReceiptMethods()
    {
        try {
            $list = ReceiptMethodConfig::order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => ['list' => $list]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 创建收款单位
    public function createReceiptMethod()
    {
        try {
            $data = request()->post();
            
            if (empty($data['name'])) {
                return json(['code' => 1, 'msg' => '请输入收款单位名称']);
            }
            
            $config = ReceiptMethodConfig::create($data);
            return json(['code' => 0, 'msg' => '创建成功', 'data' => $config]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 更新收款单位
    public function updateReceiptMethod()
    {
        try {
            $id = input('id');
            $data = request()->param();
            unset($data['id']);
            
            $config = ReceiptMethodConfig::find($id);
            if (!$config) {
                return json(['code' => 1, 'msg' => '记录不存在']);
            }
            
            $config->save($data);
            return json(['code' => 0, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 删除收款单位
    public function deleteReceiptMethod()
    {
        try {
            $id = request()->param('id');
            
            $config = ReceiptMethodConfig::find($id);
            if (!$config) {
                return json(['code' => 1, 'msg' => '记录不存在']);
            }
            
            $config->delete();
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 获取收款单位下拉列表（仅从配置表获取）
    public function getReceiptMethodOptions()
    {
        try {
            // 只从配置表获取
            $configList = ReceiptMethodConfig::order('sort', 'asc')
                ->order('id', 'asc')
                ->column('name');
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $configList
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 自动添加收款单位到配置表（如果不存在）
    public function autoAddReceiptMethod()
    {
        try {
            $name = request()->param('name');
            
            if (empty($name)) {
                return json(['code' => 1, 'msg' => '收款单位名称不能为空']);
            }
            
            // 检查是否已存在
            $exists = ReceiptMethodConfig::where('name', $name)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => '已存在', 'data' => $exists]);
            }
            
            // 获取当前最大排序值
            $maxSort = ReceiptMethodConfig::max('sort') ?: 0;
            
            // 创建新配置
            $config = ReceiptMethodConfig::create([
                'name' => $name,
                'sort' => $maxSort + 1
            ]);
            
            return json(['code' => 0, 'msg' => '添加成功', 'data' => $config]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // ========== 支付方式管理 ==========
    
    // 获取支付方式配置列表
    public function getPaymentMethods()
    {
        try {
            $list = PaymentMethodConfig::order('sort', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => ['list' => $list]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 创建支付方式
    public function createPaymentMethod()
    {
        try {
            $data = request()->post();
            
            if (empty($data['name'])) {
                return json(['code' => 1, 'msg' => '请输入支付方式名称']);
            }
            
            $config = PaymentMethodConfig::create($data);
            return json(['code' => 0, 'msg' => '创建成功', 'data' => $config]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 更新支付方式
    public function updatePaymentMethod()
    {
        try {
            $id = input('id');
            $data = request()->param();
            unset($data['id']);
            
            $config = PaymentMethodConfig::find($id);
            if (!$config) {
                return json(['code' => 1, 'msg' => '记录不存在']);
            }
            
            $config->save($data);
            return json(['code' => 0, 'msg' => '更新成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 删除支付方式
    public function deletePaymentMethod()
    {
        try {
            $id = request()->param('id');
            
            $config = PaymentMethodConfig::find($id);
            if (!$config) {
                return json(['code' => 1, 'msg' => '记录不存在']);
            }
            
            $config->delete();
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 获取支付方式下拉列表（仅从配置表获取）
    public function getPaymentMethodOptions()
    {
        try {
            // 只从配置表获取
            $configList = PaymentMethodConfig::order('sort', 'asc')
                ->order('id', 'asc')
                ->column('name');
            
            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $configList
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    // 自动添加支付方式到配置表（如果不存在）
    public function autoAddPaymentMethod()
    {
        try {
            $name = request()->param('name');
            
            if (empty($name)) {
                return json(['code' => 1, 'msg' => '支付方式名称不能为空']);
            }
            
            // 检查是否已存在
            $exists = PaymentMethodConfig::where('name', $name)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => '已存在', 'data' => $exists]);
            }
            
            // 获取当前最大排序值
            $maxSort = PaymentMethodConfig::max('sort') ?: 0;
            
            // 创建新配置
            $config = PaymentMethodConfig::create([
                'name' => $name,
                'sort' => $maxSort + 1
            ]);
            
            return json(['code' => 0, 'msg' => '添加成功', 'data' => $config]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}
