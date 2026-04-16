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
    /** @var string[] */
    private static $persistFields = [
        'payment_method', 'scene', 'name', 'app_id', 'mch_id', 'api_key', 'app_secret',
        'cert_path', 'key_path', 'notify_url', 'refund_follow_qrcode', 'is_enabled', 'is_default',
    ];

    /**
     * 获取支付配置
     * GET /admin/payment-config/get 或 /admin/payments/config
     */
    public function getConfig()
    {
        try {
            $wechatList = PaymentConfigModel::where('payment_method', 'wechat')
                ->order('scene', 'asc')
                ->order('is_default', 'desc')
                ->order('id', 'asc')
                ->select();

            $alipayList = PaymentConfigModel::where('payment_method', 'alipay')
                ->order('scene', 'asc')
                ->order('is_default', 'desc')
                ->order('id', 'asc')
                ->select();

            $wechatListArr = [];
            foreach ($wechatList as $row) {
                $a = $row->toArray();
                $a['enabled'] = (bool) ($a['is_enabled'] ?? 0);
                $wechatListArr[] = $a;
            }

            $alipayListArr = [];
            foreach ($alipayList as $row) {
                $alipayListArr[] = $this->expandAlipayRow($row->toArray());
            }

            // 列表可为空；兼容旧字段 wechat / alipay 仍给一条占位结构
            $wechatData = $wechatListArr !== [] ? $wechatListArr[0] : $this->emptyWechatTemplate();
            $alipayData = $alipayListArr !== [] ? $alipayListArr[0] : $this->emptyAlipayTemplate();

            return json([
                'success' => true,
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'wechat_list' => $wechatListArr,
                    'alipay_list' => $alipayListArr,
                    'wechat' => $wechatData,
                    'alipay' => $alipayData,
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

    private function emptyWechatTemplate(): array
    {
        return [
            'id' => null,
            'payment_method' => 'wechat',
            'scene' => 'default',
            'name' => '默认微信支付',
            'app_id' => '',
            'mch_id' => '',
            'api_key' => '',
            'app_secret' => '',
            'cert_path' => '',
            'key_path' => '',
            'notify_url' => '',
            'refund_follow_qrcode' => '',
            'is_enabled' => 0,
            'is_default' => 1,
            'enabled' => false,
        ];
    }

    private function emptyAlipayTemplate(): array
    {
        return [
            'id' => null,
            'payment_method' => 'alipay',
            'scene' => 'default',
            'name' => '',
            'app_id' => '',
            'mch_id' => '',
            'api_key' => '',
            'app_secret' => '',
            'cert_path' => '',
            'key_path' => '',
            'notify_url' => '',
            'is_enabled' => 0,
            'is_default' => 0,
            'enabled' => false,
            'alipay_public_key' => '',
            'private_key' => '',
            'sandbox' => false,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function expandAlipayRow(array $row): array
    {
        $row['enabled'] = (bool) ($row['is_enabled'] ?? 0);
        $row['alipay_public_key'] = '';
        $row['private_key'] = '';
        $row['sandbox'] = false;
        $secret = $row['app_secret'] ?? '';
        if ($secret !== '' && isset($secret[0]) && $secret[0] === '{') {
            $j = json_decode($secret, true);
            if (is_array($j)) {
                $row['alipay_public_key'] = (string) ($j['alipay_public_key'] ?? '');
                $row['private_key'] = (string) ($j['private_key'] ?? '');
                $row['sandbox'] = !empty($j['sandbox']);
            }
        }

        return $row;
    }

    /**
     * @param array<string,mixed> $item
     */
    private function mergeAlipayExtrasIntoAppSecret(array &$item): void
    {
        $extra = [];
        foreach (['alipay_public_key', 'private_key', 'sandbox'] as $k) {
            if (array_key_exists($k, $item)) {
                $extra[$k] = $item[$k];
                unset($item[$k]);
            }
        }
        if ($extra === []) {
            return;
        }
        $existing = [];
        $as = $item['app_secret'] ?? '';
        if ($as !== '' && isset($as[0]) && $as[0] === '{') {
            $decoded = json_decode($as, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }
        $item['app_secret'] = json_encode(array_merge($existing, $extra), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    private function filterPersistPayload(array $item): array
    {
        $out = [];
        foreach (self::$persistFields as $f) {
            if (array_key_exists($f, $item)) {
                $out[$f] = $item[$f];
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $item
     */
    private function saveOnePaymentConfigRow(array $item, string $method): void
    {
        $item['payment_method'] = $method;
        $scene = isset($item['scene']) ? (string) $item['scene'] : 'default';
        if ($scene === '') {
            $scene = 'default';
        }
        $item['scene'] = $scene;

        if (array_key_exists('enabled', $item)) {
            $item['is_enabled'] = !empty($item['enabled']) ? 1 : 0;
            unset($item['enabled']);
        }

        if ($method === 'alipay') {
            $this->mergeAlipayExtrasIntoAppSecret($item);
        }

        if ($method === 'wechat' && (($item['name'] ?? '') === '')) {
            $item['name'] = '微信支付-' . date('YmdHis');
        }
        if ($method === 'alipay' && (($item['name'] ?? '') === '')) {
            $item['name'] = '支付宝-' . date('YmdHis');
        }

        $id = isset($item['id']) ? (int) $item['id'] : 0;
        $name = (string) ($item['name'] ?? '');
        if ($id <= 0 && $name !== '') {
            $exist = PaymentConfigModel::where('payment_method', $method)
                ->where('scene', $scene)
                ->where('name', $name)
                ->find();
            if ($exist) {
                $id = (int) $exist->id;
            }
        }

        $isDefault = !empty($item['is_default']);
        if ($isDefault) {
            PaymentConfigModel::where('payment_method', $method)
                ->where('scene', $scene)
                ->update(['is_default' => 0]);
        }
        $item['is_default'] = $isDefault ? 1 : 0;

        $payload = $this->filterPersistPayload($item);
        unset($payload['id']);

        if ($id > 0) {
            $row = PaymentConfigModel::find($id);
            if ($row && $row->payment_method === $method) {
                $row->save($payload);
            }
        } else {
            PaymentConfigModel::create($payload);
        }
    }

    private function normalizeDefaultFlagsPerScene(string $method): void
    {
        $scenes = PaymentConfigModel::where('payment_method', $method)->column('scene');
        $scenes = array_unique($scenes);
        foreach ($scenes as $scene) {
            $rows = PaymentConfigModel::where('payment_method', $method)
                ->where('scene', $scene)
                ->where('is_default', 1)
                ->order('id', 'asc')
                ->select();
            if (count($rows) > 1) {
                $first = true;
                foreach ($rows as $r) {
                    if ($first) {
                        $first = false;
                        continue;
                    }
                    $r->is_default = 0;
                    $r->save();
                }
            }
        }
    }

    /**
     * POST body: { id }
     */
    public function deleteItem()
    {
        try {
            $id = (int) $this->request->post('id', 0);
            if ($id <= 0) {
                return json([
                    'success' => false,
                    'code' => 400,
                    'message' => '参数错误',
                ]);
            }
            $row = PaymentConfigModel::find($id);
            if (!$row) {
                return json([
                    'success' => false,
                    'code' => 404,
                    'message' => '配置不存在',
                ]);
            }
            $row->delete();

            return json([
                'success' => true,
                'code' => 200,
                'message' => '已删除',
            ]);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'code' => 500,
                'message' => '删除失败：' . $e->getMessage(),
                'error' => $e->getMessage(),
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

            if (isset($data['wechat_list']) || isset($data['alipay_list'])) {
                foreach ($data['wechat_list'] ?? [] as $item) {
                    if (is_array($item)) {
                        $this->saveOnePaymentConfigRow($item, 'wechat');
                    }
                }
                foreach ($data['alipay_list'] ?? [] as $item) {
                    if (is_array($item)) {
                        $this->saveOnePaymentConfigRow($item, 'alipay');
                    }
                }
                $this->normalizeDefaultFlagsPerScene('wechat');
                $this->normalizeDefaultFlagsPerScene('alipay');

                return json([
                    'success' => true,
                    'code' => 200,
                    'message' => '保存成功',
                ]);
            }

            if (isset($data['wechat'])) {
                $w = $data['wechat'];
                if (empty($w['id'])) {
                    $this->ensureLegacyRowId($w, 'wechat');
                }
                $this->saveOnePaymentConfigRow($w, 'wechat');
            }
            if (isset($data['alipay'])) {
                $a = $data['alipay'];
                if (empty($a['id'])) {
                    $this->ensureLegacyRowId($a, 'alipay');
                }
                $this->saveOnePaymentConfigRow($a, 'alipay');
            }
            $this->normalizeDefaultFlagsPerScene('wechat');
            $this->normalizeDefaultFlagsPerScene('alipay');

            return json([
                'success' => true,
                'code' => 200,
                'message' => '保存成功',
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
     * @param array<string,mixed> $item
     */
    private function ensureLegacyRowId(array &$item, string $method): void
    {
        if (!empty($item['id'])) {
            return;
        }
        $scene = isset($item['scene']) ? (string) $item['scene'] : 'default';
        $exist = PaymentConfigModel::where('payment_method', $method)
            ->where('scene', $scene)
            ->order('is_default', 'desc')
            ->order('id', 'asc')
            ->find();
        if ($exist) {
            $item['id'] = $exist->id;
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
            $configId = (int) $this->request->post('config_id', 0);
            
            if ($paymentMethod === 'wechat') {
                $config = null;
                if ($configId > 0) {
                    $config = PaymentConfigModel::find($configId);
                    if ($config && $config->payment_method !== 'wechat') {
                        $config = null;
                    }
                }
                if (!$config) {
                    $config = PaymentConfigModel::getConfigRow('wechat', 'default');
                }
                
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
