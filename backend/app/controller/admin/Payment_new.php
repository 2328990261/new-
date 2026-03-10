<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\Payment as PaymentModel;
use app\model\PaymentConfig;
use think\facade\Db;

class Payment extends BaseController
{
    /**
     * 获取支付列表
     */
    public function list()
    {
        try {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 10);
            
            $query = PaymentModel::with(['dispatcher'])->order('paid_time', 'desc');
            
            // 筛选条件
            $tutorName = $this->request->param('tutor_name');
            if ($tutorName) {
                $query->where('tutor_name', 'like', "%{$tutorName}%");
            }
            
            $status = $this->request->param('status');
            if ($status) {
                $query->where('status', $status);
            }
            
            $list = $query->paginate(['list_rows' => $limit, 'page' => $page]);
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $list->items(),
                    'total' => $list->total()
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取支付详情
     */
    public function read()
    {
        try {
            $id = $this->request->param('id');
            $payment = PaymentModel::with(['dispatcher'])->find($id);
            
            if (!$payment) {
                return json(['code' => 404, 'message' => '支付记录不存在']);
            }
            
            return json(['code' => 200, 'message' => '获取成功', 'data' => $payment]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '获取失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 测试支付配置
     * POST /admin/api/payments/config/test
     */
    public function testConfig()
    {
        try {
            $method = $this->request->post('payment_method', 'wechat');
            
            if ($method === 'wechat') {
                $wechatService = new \app\service\WechatPayService();
                $result = $wechatService->testConfig();
                
                return json([
                    'code' => $result['success'] ? 200 : 500,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } elseif ($method === 'alipay') {
                return json([
                    'code' => 500,
                    'message' => '支付宝配置测试功能开发中'
                ]);
            }
            
            return json([
                'code' => 400,
                'message' => '不支持的支付方式'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '测试失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取配置
     */
    public function getConfig()
    {
        try {
            $wechat = PaymentConfig::where('payment_method', 'wechat')->find();
            $alipay = PaymentConfig::where('payment_method', 'alipay')->find();
            
            return json([
                'success' => true,
                'data' => [
                    'wechat' => $wechat ?: [],
                    'alipay' => $alipay ?: []
                ]
            ]);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新配置
     */
    public function updateConfig()
    {
        try {
            $data = $this->request->post();
            
            if (isset($data['wechat'])) {
                $wechat = PaymentConfig::where('payment_method', 'wechat')->find();
                if ($wechat) {
                    $wechat->save($data['wechat']);
                } else {
                    $data['wechat']['payment_method'] = 'wechat';
                    PaymentConfig::create($data['wechat']);
                }
            }
            
            if (isset($data['alipay'])) {
                $alipay = PaymentConfig::where('payment_method', 'alipay')->find();
                if ($alipay) {
                    $alipay->save($data['alipay']);
                } else {
                    $data['alipay']['payment_method'] = 'alipay';
                    PaymentConfig::create($data['alipay']);
                }
            }
            
            return json(['success' => true, 'message' => '保存成功']);
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
