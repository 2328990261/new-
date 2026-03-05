<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\PaymentConfig as PaymentConfigModel;
use app\service\WechatPayService;
use think\facade\Validate;

/**
 * 支付配置管理控制器
 */
class PaymentConfig extends BaseController
{
    /**
     * 获取支付配置
     * GET /admin/payment-config/get 或 /admin/payments/config
     */
    public function getConfig()
    {
        try {
            // 获取微信支付配置
            $wechatConfig = PaymentConfigModel::where('payment_method', 'wechat')->find();
            $wechatData = $wechatConfig ? $wechatConfig->toArray() : [
                'payment_method' => 'wechat',
                'app_id' => '',
                'mch_id' => '',
                'api_key' => '',
                'app_secret' => '',
                'cert_path' => '',
                'key_path' => '',
                'notify_url' => '',
                'is_enabled' => 0
            ];
            
            // 获取支付宝配置
            $alipayConfig = PaymentConfigModel::where('payment_method', 'alipay')->find();
            $alipayData = $alipayConfig ? $alipayConfig->toArray() : [
                'payment_method' => 'alipay',
                'app_id' => '',
                'mch_id' => '',
                'api_key' => '',
                'app_secret' => '',
                'cert_path' => '',
                'key_path' => '',
                'notify_url' => '',
                'is_enabled' => 0
            ];
            
            // 转换字段名以匹配前端
            $wechatData['enabled'] = (bool)$wechatData['is_enabled'];
            $alipayData['enabled'] = (bool)$alipayData['is_enabled'];
            
            return json([
                'success' => true,
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'wechat' => $wechatData,
                    'alipay' => $alipayData
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 保存支付配置
     * POST /admin/payment-config/save 或 /admin/payments/config
     */
    public function saveConfig()
    {
        try {
            $data = $this->request->post();
            
            // 前端发送的数据格式: { wechat: {...}, alipay: {...} }
            $saved = [];
            
            // 保存微信支付配置
            if (isset($data['wechat'])) {
                $wechatData = $data['wechat'];
                $wechatData['payment_method'] = 'wechat';
                $wechatData['is_enabled'] = $wechatData['enabled'] ?? 0;
                unset($wechatData['enabled']);
                
                $config = PaymentConfigModel::where('payment_method', 'wechat')->find();
                if ($config) {
                    $config->save($wechatData);
                } else {
                    PaymentConfigModel::create($wechatData);
                }
                $saved[] = 'wechat';
            }
            
            // 保存支付宝配置
            if (isset($data['alipay'])) {
                $alipayData = $data['alipay'];
                $alipayData['payment_method'] = 'alipay';
                $alipayData['is_enabled'] = $alipayData['enabled'] ?? 0;
                unset($alipayData['enabled']);
                
                $config = PaymentConfigModel::where('payment_method', 'alipay')->find();
                if ($config) {
                    $config->save($alipayData);
                } else {
                    PaymentConfigModel::create($alipayData);
                }
                $saved[] = 'alipay';
            }
            
            return json([
                'success' => true,
                'code' => 200,
                'message' => '保存成功'
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'message' => '保存失败：' . $e->getMessage(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * 测试支付配置
     * POST /admin/payment-config/test 或 /admin/payments/config/test
     */
    public function testPaymentConfig()
    {
        try {
            $paymentMethod = $this->request->post('payment_method', 'wechat');
            
            if ($paymentMethod === 'wechat') {
                // 获取微信支付配置
                $config = PaymentConfigModel::where('payment_method', 'wechat')->find();
                
                if (!$config) {
                    return json([
                        'success' => false,
                        'code' => 400,
                        'message' => '未找到微信支付配置',
                        'data' => [
                            'success' => false,
                            'message' => '请先配置微信支付参数'
                        ]
                    ]);
                }
                
                // 验证必填字段
                $errors = [];
                if (empty($config->app_id)) {
                    $errors[] = '应用ID(AppID)不能为空';
                }
                if (empty($config->mch_id)) {
                    $errors[] = '商户号不能为空';
                }
                if (empty($config->api_key)) {
                    $errors[] = 'API密钥不能为空';
                }
                
                if (!empty($errors)) {
                    return json([
                        'success' => false,
                        'code' => 400,
                        'message' => '配置不完整',
                        'data' => [
                            'success' => false,
                            'message' => '配置验证失败',
                            'errors' => $errors
                        ]
                    ]);
                }
                
                // 调用微信支付API进行真实验证
                try {
                    $testResult = $this->testWechatPayApi($config);
                    
                    if ($testResult['success']) {
                        return json([
                            'success' => true,
                            'code' => 200,
                            'message' => '测试成功',
                            'data' => [
                                'success' => true,
                                'message' => '微信支付配置正确，API连接成功',
                                'details' => $testResult['message']
                            ]
                        ]);
                    } else {
                        return json([
                            'success' => false,
                            'code' => 400,
                            'message' => '配置验证失败',
                            'data' => [
                                'success' => false,
                                'message' => $testResult['message'],
                                'errors' => [$testResult['message']]
                            ]
                        ]);
                    }
                } catch (\Exception $e) {
                    return json([
                        'success' => false,
                        'code' => 500,
                        'message' => 'API调用失败',
                        'data' => [
                            'success' => false,
                            'message' => 'API调用失败：' . $e->getMessage(),
                            'errors' => [$e->getMessage()]
                        ]
                    ]);
                }
                
            } elseif ($paymentMethod === 'alipay') {
                return json([
                    'success' => false,
                    'code' => 500,
                    'message' => '支付宝配置测试功能开发中',
                    'data' => [
                        'success' => false,
                        'message' => '支付宝配置测试功能开发中'
                    ]
                ]);
            }
            
            return json([
                'success' => false,
                'code' => 400,
                'message' => '不支持的支付方式',
                'data' => [
                    'success' => false,
                    'message' => '不支持的支付方式'
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'message' => '测试失败：' . $e->getMessage(),
                'data' => [
                    'success' => false,
                    'message' => '测试失败：' . $e->getMessage()
                ]
            ]);
        }
    }
    
    /**
     * 测试微信支付API连接
     * @param object $config 支付配置
     * @return array
     */
    private function testWechatPayApi($config)
    {
        // 准备测试订单数据
        $params = [
            'appid' => $config->app_id,
            'mch_id' => $config->mch_id,
            'nonce_str' => $this->generateNonceStr(),
            'body' => '配置测试',
            'out_trade_no' => 'TEST' . time(),
            'total_fee' => 1, // 1分钱
            'spbill_create_ip' => $this->request->ip(),
            'notify_url' => $config->notify_url ?: 'http://example.com/notify',
            'trade_type' => 'NATIVE'
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params, $config->api_key);
        
        // 转换为XML
        $xml = $this->arrayToXml($params);
        
        // 调用微信统一下单API
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'message' => '网络请求失败：' . $error
                ];
            }
            
            if ($httpCode != 200) {
                return [
                    'success' => false,
                    'message' => 'HTTP请求失败，状态码：' . $httpCode
                ];
            }
            
            // 解析XML响应
            $result = $this->xmlToArray($response);
            
            if (!isset($result['return_code'])) {
                return [
                    'success' => false,
                    'message' => '响应格式错误'
                ];
            }
            
            if ($result['return_code'] == 'SUCCESS') {
                if ($result['result_code'] == 'SUCCESS') {
                    return [
                        'success' => true,
                        'message' => 'API连接成功，配置正确'
                    ];
                } else {
                    // 业务失败，但说明配置基本正确
                    $errMsg = $result['err_code_des'] ?? $result['err_code'] ?? '未知错误';
                    return [
                        'success' => true,
                        'message' => 'API连接成功（业务提示：' . $errMsg . '）'
                    ];
                }
            } else {
                $errMsg = $result['return_msg'] ?? '通信失败';
                return [
                    'success' => false,
                    'message' => '微信支付返回错误：' . $errMsg
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API调用异常：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成随机字符串
     */
    private function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
    
    /**
     * 生成签名
     */
    private function generateSign($params, $key)
    {
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string .= 'key=' . $key;
        return strtoupper(md5($string));
    }
    
    /**
     * 数组转XML
     */
    private function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * XML转数组
     */
    private function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
}
