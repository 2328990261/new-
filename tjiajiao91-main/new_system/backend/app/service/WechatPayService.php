<?php
namespace app\service;

use app\model\Payment;
use app\model\PaymentConfig;
use think\facade\Log;

/**
 * 微信支付服务类
 */
class WechatPayService
{
    /**
     * 配置信息
     */
    private $config;
    
    /**
     * 微信支付API地址
     */
    private $apiUrl = 'https://api.mch.weixin.qq.com';
    
    /**
     * @param PaymentConfig|string|null $configOrScene 具体配置行，或场景名 default/h5，null 则 default
     */
    public function __construct($configOrScene = null)
    {
        try {
            if ($configOrScene instanceof PaymentConfig) {
                $this->config = $configOrScene;
            } elseif ($configOrScene === null || $configOrScene === '') {
                $this->config = PaymentConfig::getConfigRow('wechat', 'default');
            } else {
                $this->config = PaymentConfig::getConfigRow('wechat', (string) $configOrScene);
            }
        } catch (\Exception $e) {
            $this->config = null;
            trace('获取微信支付配置失败: ' . $e->getMessage(), 'warning');
        }
    }

    /**
     * 按支付记录选用下单时的商户配置（查单、退款、验签）
     */
    public static function forPayment(Payment $payment): self
    {
        $id = (int) ($payment->wechat_payment_config_id ?? 0);
        if ($id > 0) {
            $cfg = PaymentConfig::find($id);
            if ($cfg) {
                return new self($cfg);
            }
        }

        return new self('default');
    }

    /**
     * 解析统一下单使用的 notify_url（微信对格式很严格：公网 HTTPS、完整路径）
     * 优先级：payment_config.notify_url > 环境变量 PAYMENT_NOTIFY_URL > 当前请求域名 + /api/payment/notify
     */
    private function resolveNotifyUrl(): string
    {
        $candidates = [];
        $envUrl = env('PAYMENT_NOTIFY_URL', '');
        if ($envUrl !== '') {
            $candidates[] = trim((string) $envUrl);
        }
        if ($this->config && !empty($this->config->notify_url)) {
            $candidates[] = trim((string) $this->config->notify_url);
        }
        try {
            if (function_exists('request') && request()) {
                $candidates[] = rtrim(request()->domain(), '/') . '/api/payment/notify';
            }
        } catch (\Throwable $e) {
            // ignore
        }

        foreach ($candidates as $raw) {
            if ($raw === '') {
                continue;
            }
            $url = $this->normalizeNotifyUrl($raw);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                if (stripos($url, 'https://') === 0) {
                    return $url;
                }
            }
        }

        return '';
    }

    /**
     * http 转 https（非本机）；仅域名无路径时补全回调路径
     */
    private function normalizeNotifyUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^http://#i', $url)) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && !preg_match('/^(127\.0\.0\.1|localhost)$/i', (string) $host)) {
                $url = preg_replace('#^http://#i', 'https://', $url, 1);
            }
        }
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return $url;
        }
        $path = $parts['path'] ?? '';
        if ($path === '' || $path === '/') {
            $scheme = ($parts['scheme'] ?? 'https') === 'http' ? 'http' : 'https';
            $base = $scheme . '://' . $parts['host'];
            if (!empty($parts['port'])) {
                $base .= ':' . $parts['port'];
            }
            return $base . '/api/payment/notify';
        }
        return $url;
    }
    
    /**
     * 测试配置有效性
     */
    public function testConfig()
    {
        $errors = [];
        $warnings = [];
        
        // 1. 检查配置是否存在
        if (!$this->config) {
            return [
                'success' => false,
                'message' => '微信支付配置不存在',
                'errors' => ['配置不存在，请先保存配置信息']
            ];
        }
        
        // 2. 验证AppID格式
        if (empty($this->config->app_id)) {
            $errors[] = 'AppID不能为空';
        } elseif (!preg_match('/^wx[a-zA-Z0-9]{16}$/', $this->config->app_id)) {
            $errors[] = 'AppID格式不正确（应为wx开头的18位字符）';
        }
        
        // 3. 验证商户号格式
        if (empty($this->config->mch_id)) {
            $errors[] = '商户号不能为空';
        } elseif (!preg_match('/^\d{10}$/', $this->config->mch_id)) {
            $errors[] = '商户号格式不正确（应为10位数字）';
        }
        
        // 4. 验证API密钥格式
        if (empty($this->config->api_key)) {
            $errors[] = 'API密钥不能为空';
        } elseif (strlen($this->config->api_key) != 32) {
            $errors[] = 'API密钥格式不正确（应为32位字符）';
        }
        
        // 5. 验证回调地址
        if (empty($this->config->notify_url)) {
            $warnings[] = '回调地址未配置';
        } elseif (!filter_var($this->config->notify_url, FILTER_VALIDATE_URL)) {
            $warnings[] = '回调地址格式不正确';
        } elseif (strpos($this->config->notify_url, 'https://') !== 0) {
            $warnings[] = '回调地址建议使用HTTPS协议';
        }
        
        // 6. 检查证书文件
        if (!empty($this->config->cert_path)) {
            $certPath = root_path() . ltrim($this->config->cert_path, '/');
            if (!file_exists($certPath)) {
                $warnings[] = '证书文件不存在：' . $certPath;
            } elseif (!is_readable($certPath)) {
                $warnings[] = '证书文件无法读取：' . $certPath;
            }
        } else {
            $warnings[] = '未配置证书路径（部分API可能需要证书）';
        }
        
        // 如果有错误，返回错误信息
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '配置验证失败',
                'errors' => $errors,
                'warnings' => $warnings
            ];
        }
        
        // 7. 测试API连通性（调用查询订单接口，使用一个不存在的订单号）
        try {
            $testResult = $this->testApiConnection();
            
            if ($testResult === true) {
                return [
                    'success' => true,
                    'message' => '配置验证通过，微信支付API连通正常',
                    'warnings' => $warnings,
                    'details' => [
                        'appid' => $this->config->app_id,
                        'mch_id' => $this->config->mch_id,
                        'api_key' => substr($this->config->api_key, 0, 6) . '******',
                        'cert_configured' => !empty($this->config->cert_path),
                        'notify_url' => $this->config->notify_url
                    ]
                ];
            } else {
                $warnings[] = 'API连通性测试失败：' . $testResult;
                return [
                    'success' => true,
                    'message' => '配置格式验证通过，但API连通性测试失败',
                    'warnings' => $warnings,
                    'details' => [
                        'appid' => $this->config->app_id,
                        'mch_id' => $this->config->mch_id
                    ]
                ];
            }
        } catch (\Exception $e) {
            $warnings[] = 'API连通性测试异常：' . $e->getMessage();
            return [
                'success' => true,
                'message' => '配置格式验证通过',
                'warnings' => $warnings
            ];
        }
    }
    
    /**
     * 测试API连通性
     */
    private function testApiConnection()
    {
        try {
            // 使用订单查询接口测试
            $url = $this->apiUrl . '/pay/orderquery';
            
            // 构建测试参数
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'out_trade_no' => 'TEST_' . time(),
                'nonce_str' => $this->getNonceStr(),
            ];
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 转换为XML
            $xml = $this->arrayToXml($params);
            
            // 发送请求
            $response = $this->httpPost($url, $xml);
            
            // 解析响应
            $result = $this->xmlToArray($response);
            
            // 检查返回
            if (isset($result['return_code'])) {
                if ($result['return_code'] == 'SUCCESS') {
                    // 签名验证通过，配置正确
                    return true;
                } elseif ($result['return_code'] == 'FAIL') {
                    // 返回失败原因
                    return $result['return_msg'] ?? '未知错误';
                }
            }
            
            return '响应格式异常';
            
        } catch (\Exception $e) {
            Log::error('微信支付API测试失败: ' . $e->getMessage());
            return $e->getMessage();
        }
    }
    
    /**
     * 生成签名
     */
    private function generateSign($params)
    {
        // 1. 参数排序
        ksort($params);
        
        // 2. 拼接字符串
        $stringA = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $key != 'sign') {
                $stringA .= $key . '=' . $value . '&';
            }
        }
        
        // 3. 拼接API密钥
        $stringSignTemp = $stringA . 'key=' . $this->config->api_key;
        
        // 4. MD5加密并转大写
        return strtoupper(md5($stringSignTemp));
    }
    
    /**
     * 生成随机字符串
     */
    private function getNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
    
    /**
     * 数组转XML
     */
    private function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach ($arr as $key => $val) {
            $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * XML转数组
     */
    private function xmlToArray($xml)
    {
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $arr;
    }
    
    /**
     * POST请求
     */
    private function httpPost($url, $data, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('CURL错误: ' . $error);
        }
        
        curl_close($ch);
        return $response;
    }
    
    /**
     * 验证签名
     */
    public function verifySign($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }
        
        $sign = $data['sign'];
        unset($data['sign']);
        
        $mySign = $this->generateSign($data);
        
        return $sign === $mySign;
    }

    /**
     * 微信「查询订单」接口（按商户订单号 out_trade_no）
     * @return array{success:bool,trade_state?:string,transaction_id?:string,message?:string}
     */
    public function queryOrderByOutTradeNo(string $outTradeNo): array
    {
        if (!$this->config) {
            return ['success' => false, 'message' => '微信支付配置未设置'];
        }
        if ($outTradeNo === '') {
            return ['success' => false, 'message' => '订单号为空'];
        }

        try {
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'out_trade_no' => $outTradeNo,
                'nonce_str' => $this->getNonceStr(),
            ];
            $params['sign'] = $this->generateSign($params);
            $xml = $this->arrayToXml($params);
            $response = $this->httpPost($this->apiUrl . '/pay/orderquery', $xml, 15);
            $result = $this->xmlToArray($response);

            if (($result['return_code'] ?? '') !== 'SUCCESS') {
                return [
                    'success' => false,
                    'message' => $result['return_msg'] ?? '通信失败',
                ];
            }
            if (!$this->verifySign($result)) {
                Log::error('微信查单响应验签失败: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
                return ['success' => false, 'message' => '查单结果签名校验失败'];
            }
            if (($result['result_code'] ?? '') !== 'SUCCESS') {
                return [
                    'success' => false,
                    'message' => $result['err_code_des'] ?? ($result['err_code'] ?? '查单失败'),
                ];
            }

            return [
                'success' => true,
                'trade_state' => $result['trade_state'] ?? '',
                'transaction_id' => $result['transaction_id'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::warning('微信查单异常: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 若微信侧已支付，将本地 Payment 更新为 success（用于异步通知未到达时的补偿）
     * @return array{updated:bool,trade_state?:string,error?:string}
     */
    public function syncPaymentIfWechatPaid(\app\model\Payment $payment): array
    {
        if ($payment->status === 'success') {
            return ['updated' => false, 'trade_state' => 'LOCAL_SUCCESS'];
        }
        $q = $this->queryOrderByOutTradeNo($payment->order_no);
        if (!$q['success']) {
            return ['updated' => false, 'error' => $q['message'] ?? '查单失败'];
        }
        $state = $q['trade_state'] ?? '';
        if ($state === 'SUCCESS') {
            $payment->status = 'success';
            $payment->transaction_id = $q['transaction_id'] ?: ($payment->transaction_id ?? '');
            $payment->paid_time = date('Y-m-d H:i:s');
            $payment->save();
            Log::info('微信查单同步支付成功: ' . $payment->order_no);
            return ['updated' => true, 'trade_state' => $state];
        }
        return ['updated' => false, 'trade_state' => $state];
    }
    
    /**
     * 创建模拟支付订单（用于测试）
     * @param array $orderData 订单数据
     * @return array
     */
    public function createMockPayment($orderData)
    {
        try {
            // 生成模拟二维码内容
            $qrcodeContent = $this->generateMockQrcode($orderData);
            
            // 生成二维码URL（使用公共二维码生成服务）
            $qrcodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrcodeContent);
            
            // 生成模拟支付页面URL
            $mockPayUrl = request()->domain() . '/api/payment/mock-pay?order_no=' . $orderData['order_no'];
            
            return [
                'success' => true,
                'data' => [
                    'order_no' => $orderData['order_no'],
                    'qrcode_url' => $qrcodeUrl,
                    'qrcode_content' => $qrcodeContent,
                    'mock_pay_url' => $mockPayUrl,
                    'expire_time' => date('Y-m-d H:i:s', time() + 1800) // 30分钟后过期
                ]
            ];
        } catch (\Exception $e) {
            Log::error('创建模拟支付失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '创建支付订单失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成模拟二维码内容
     */
    private function generateMockQrcode($orderData)
    {
        // 模拟微信支付二维码格式
        $domain = request()->domain();
        return $domain . '/api/payment/mock-pay?order_no=' . $orderData['order_no'] 
               . '&amount=' . $orderData['amount']
               . '&timestamp=' . time();
    }
    
    /**
     * 模拟支付成功回调
     * @param string $orderNo 支付订单号
     * @return array
     */
    public function mockPaymentSuccess($orderNo)
    {
        try {
            $payment = \app\model\Payment::where('order_no', $orderNo)->find();
            
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => '支付订单不存在'
                ];
            }
            
            if ($payment->status === 'success') {
                return [
                    'success' => false,
                    'message' => '订单已支付，请勿重复支付'
                ];
            }
            
            // 更新支付状态
            $payment->status = 'success';
            $payment->transaction_id = 'MOCK_' . time() . rand(1000, 9999);
            $payment->paid_time = date('Y-m-d H:i:s');
            $payment->save();
            
            return [
                'success' => true,
                'message' => '支付成功',
                'data' => [
                    'order_no' => $orderNo,
                    'transaction_id' => $payment->transaction_id,
                    'paid_time' => $payment->paid_time
                ]
            ];
        } catch (\Exception $e) {
            Log::error('模拟支付失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '支付失败: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 创建真实微信支付订单（Native扫码支付）
     * @param array $orderData 订单数据
     * @return array
     */
    public function createNativePayment($orderData)
    {
        try {
            if (!$this->config) {
                throw new \Exception('微信支付配置未设置');
            }

            $notifyUrl = $this->resolveNotifyUrl();
            if ($notifyUrl === '') {
                throw new \Exception('支付回调 notify_url 无效，请在后台填写 https 公网地址，例如：https://你的域名/api/payment/notify');
            }
            
            $url = $this->apiUrl . '/pay/unifiedorder';
            
            // 构建请求参数
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'nonce_str' => $this->getNonceStr(),
                'body' => $orderData['body'],
                'out_trade_no' => $orderData['order_no'],
                'total_fee' => intval($orderData['amount'] * 100), // 金额转为分
                'spbill_create_ip' => request()->ip(),
                'notify_url' => $notifyUrl,
                'trade_type' => 'NATIVE',
            ];
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 转换为XML
            $xml = $this->arrayToXml($params);
            
            // 发送请求
            $response = $this->httpPost($url, $xml);
            
            // 解析响应
            $result = $this->xmlToArray($response);
            
            // 记录完整响应用于调试
            Log::info('微信支付API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                // 生成二维码URL
                $qrcodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($result['code_url']);
                
                return [
                    'success' => true,
                    'data' => [
                        'order_no' => $orderData['order_no'],
                        'qrcode_url' => $qrcodeUrl,
                        'code_url' => $result['code_url'],
                        'prepay_id' => $result['prepay_id']
                    ]
                ];
            } else {
                // 构造详细错误信息
                $errorMsg = '';
                if (isset($result['return_code']) && $result['return_code'] == 'FAIL') {
                    $errorMsg = '通信失败：' . ($result['return_msg'] ?? '未知错误');
                } elseif (isset($result['result_code']) && $result['result_code'] == 'FAIL') {
                    $errorMsg = '业务失败：' . ($result['err_code_des'] ?? $result['err_code'] ?? '未知错误');
                } else {
                    $errorMsg = $result['return_msg'] ?? $result['err_code_des'] ?? '创建支付订单失败';
                }
                throw new \Exception($errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('创建微信支付订单失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 创建JSAPI支付订单（微信内H5支付）
     * @param array $orderData 订单数据，必须包含openid
     * @return array
     */
    public function createJsapiPayment($orderData)
    {
        try {
            if (!$this->config) {
                throw new \Exception('微信支付配置未设置');
            }
            
            // 检查是否有openid
            if (empty($orderData['openid'])) {
                throw new \Exception('JSAPI支付需要用户openid');
            }

            $notifyUrl = $this->resolveNotifyUrl();
            if ($notifyUrl === '') {
                throw new \Exception('支付回调 notify_url 无效，请在后台填写 https 公网地址，例如：https://你的域名/api/payment/notify');
            }
            
            $url = $this->apiUrl . '/pay/unifiedorder';
            
            // 构建请求参数
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'nonce_str' => $this->getNonceStr(),
                'body' => $orderData['body'],
                'out_trade_no' => $orderData['order_no'],
                'total_fee' => intval($orderData['amount'] * 100), // 金额转为分
                'spbill_create_ip' => request()->ip(),
                'notify_url' => $notifyUrl,
                'trade_type' => 'JSAPI',
                'openid' => $orderData['openid'], // JSAPI必需参数
            ];
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 转换为XML
            $xml = $this->arrayToXml($params);
            
            // 发送请求
            $response = $this->httpPost($url, $xml);
            
            // 解析响应
            $result = $this->xmlToArray($response);
            
            // 记录完整响应用于调试
            Log::info('微信JSAPI支付API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                // JSAPI需要生成支付参数给前端
                $prepayId = $result['prepay_id'];
                $jsapiParams = $this->generateJsapiParams($prepayId);
                
                return [
                    'success' => true,
                    'data' => [
                        'order_no' => $orderData['order_no'],
                        'prepay_id' => $prepayId,
                        'jsapi_params' => $jsapiParams, // 前端调起支付需要的参数
                        'trade_type' => 'JSAPI'
                    ]
                ];
            } else {
                // 构造详细错误信息
                $errorMsg = '';
                if (isset($result['return_code']) && $result['return_code'] == 'FAIL') {
                    $errorMsg = '通信失败：' . ($result['return_msg'] ?? '未知错误');
                } elseif (isset($result['result_code']) && $result['result_code'] == 'FAIL') {
                    $errorMsg = '业务失败：' . ($result['err_code_des'] ?? $result['err_code'] ?? '未知错误');
                } else {
                    $errorMsg = $result['return_msg'] ?? $result['err_code_des'] ?? '创建支付订单失败';
                }
                throw new \Exception($errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('创建微信JSAPI支付订单失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 生成JSAPI支付参数（供前端调起支付）
     * @param string $prepayId 预支付ID
     * @return array
     */
    private function generateJsapiParams($prepayId)
    {
        $params = [
            'appId' => $this->config->app_id,
            'timeStamp' => (string)time(),
            'nonceStr' => $this->getNonceStr(),
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'MD5'
        ];
        
        // 生成签名
        $params['paySign'] = $this->generateSign($params);
        
        return $params;
    }
    
    /**
     * 创建H5支付订单（手机浏览器外支付）
     * @param array $orderData 订单数据
     * @return array
     */
    public function createH5Payment($orderData)
    {
        try {
            if (!$this->config) {
                throw new \Exception('微信支付配置未设置');
            }

            $notifyUrl = $this->resolveNotifyUrl();
            if ($notifyUrl === '') {
                throw new \Exception('支付回调 notify_url 无效，请在后台填写 https 公网地址，例如：https://你的域名/api/payment/notify');
            }
            
            $url = $this->apiUrl . '/pay/unifiedorder';
            
            // 构建请求参数
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'nonce_str' => $this->getNonceStr(),
                'body' => $orderData['body'],
                'out_trade_no' => $orderData['order_no'],
                'total_fee' => intval($orderData['amount'] * 100), // 金额转为分
                'spbill_create_ip' => request()->ip(),
                'notify_url' => $notifyUrl,
                'trade_type' => 'MWEB', // H5支付类型
            ];
            
            // H5支付需要场景信息
            $sceneInfo = [
                'h5_info' => [
                    'type' => 'Wap',
                    'wap_url' => request()->domain(),
                    'wap_name' => '91家教'
                ]
            ];
            $params['scene_info'] = json_encode($sceneInfo, JSON_UNESCAPED_UNICODE);
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 转换为XML
            $xml = $this->arrayToXml($params);
            
            // 发送请求
            $response = $this->httpPost($url, $xml);
            
            // 解析响应
            $result = $this->xmlToArray($response);
            
            // 记录完整响应用于调试
            Log::info('微信H5支付API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                // H5支付返回mweb_url，用户需要在手机浏览器中打开
                $mwebUrl = $result['mweb_url'];
                
                // 可以添加回调地址
                if (!empty($orderData['redirect_url'])) {
                    $mwebUrl .= '&redirect_url=' . urlencode($orderData['redirect_url']);
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'order_no' => $orderData['order_no'],
                        'mweb_url' => $mwebUrl, // H5支付跳转URL
                        'prepay_id' => $result['prepay_id'],
                        'trade_type' => 'MWEB'
                    ]
                ];
            } else {
                // 构造详细错误信息
                $errorMsg = '';
                if (isset($result['return_code']) && $result['return_code'] == 'FAIL') {
                    $errorMsg = '通信失败：' . ($result['return_msg'] ?? '未知错误');
                } elseif (isset($result['result_code']) && $result['result_code'] == 'FAIL') {
                    $errorMsg = '业务失败：' . ($result['err_code_des'] ?? $result['err_code'] ?? '未知错误');
                } else {
                    $errorMsg = $result['return_msg'] ?? $result['err_code_des'] ?? '创建支付订单失败';
                }
                throw new \Exception($errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('创建微信H5支付订单失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 微信退款
     * @param array $refundData 退款数据
     * @return array
     */
    public function refund($refundData)
    {
        try {
            if (!$this->config) {
                throw new \Exception('微信支付配置未设置');
            }
            
            // 检查证书与私钥路径
            if (empty($this->config->cert_path)) {
                throw new \Exception('退款需要配置证书路径');
            }

            if (empty($this->config->key_path)) {
                throw new \Exception('退款需要配置密钥路径');
            }

            $certPath = root_path() . ltrim((string)$this->config->cert_path, '/');
            $keyPath = root_path() . ltrim((string)$this->config->key_path, '/');

            if (!file_exists($certPath)) {
                throw new \Exception('证书文件不存在: ' . $certPath);
            }

            if (!file_exists($keyPath)) {
                throw new \Exception('密钥文件不存在: ' . $keyPath);
            }

            if (!is_readable($certPath)) {
                throw new \Exception('证书文件不可读: ' . $certPath);
            }

            if (!is_readable($keyPath)) {
                throw new \Exception('密钥文件不可读: ' . $keyPath);
            }
            
            $url = $this->apiUrl . '/secapi/pay/refund';
            
            // 构建请求参数
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'nonce_str' => $this->getNonceStr(),
                'out_trade_no' => $refundData['order_no'], // 商户订单号
                'out_refund_no' => $refundData['refund_no'], // 商户退款单号
                'total_fee' => intval($refundData['total_amount'] * 100), // 订单总金额（分）
                'refund_fee' => intval($refundData['refund_amount'] * 100), // 退款金额（分）
                'refund_desc' => $refundData['refund_reason'] ?? '用户申请退款', // 退款原因
            ];
            
            // 如果有微信交易号，优先使用
            if (!empty($refundData['transaction_id'])) {
                unset($params['out_trade_no']);
                $params['transaction_id'] = $refundData['transaction_id'];
            }
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 转换为XML
            $xml = $this->arrayToXml($params);
            
            // 发送请求（需要证书）
            $response = $this->httpPostWithCert($url, $xml, $certPath, $keyPath);
            
            // 解析响应
            $result = $this->xmlToArray($response);
            
            // 记录完整响应用于调试
            Log::info('微信退款API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                return [
                    'success' => true,
                    'message' => '退款成功',
                    'data' => [
                        'refund_id' => $result['refund_id'], // 微信退款单号
                        'out_refund_no' => $result['out_refund_no'], // 商户退款单号
                        'refund_fee' => $result['refund_fee'] / 100, // 退款金额（元）
                        'settlement_refund_fee' => isset($result['settlement_refund_fee']) ? $result['settlement_refund_fee'] / 100 : null,
                        'refund_channel' => $result['refund_channel'] ?? null,
                    ]
                ];
            } else {
                // 构造详细错误信息
                $errorMsg = '';
                if (isset($result['return_code']) && $result['return_code'] == 'FAIL') {
                    $errorMsg = '通信失败：' . ($result['return_msg'] ?? '未知错误');
                } elseif (isset($result['result_code']) && $result['result_code'] == 'FAIL') {
                    $errorMsg = '退款失败：' . ($result['err_code_des'] ?? $result['err_code'] ?? '未知错误');
                } else {
                    $errorMsg = $result['return_msg'] ?? $result['err_code_des'] ?? '退款失败';
                }
                
                // 记录错误
                Log::error('微信退款失败: ' . $errorMsg);
                
                throw new \Exception($errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('微信退款异常: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 查询退款状态
     * @param string $refundNo 商户退款单号
     * @return array
     */
    public function queryRefund($refundNo)
    {
        try {
            if (!$this->config) {
                throw new \Exception('微信支付配置未设置');
            }
            
            $url = $this->apiUrl . '/pay/refundquery';
            
            // 构建请求参数
            $params = [
                'appid' => $this->config->app_id,
                'mch_id' => $this->config->mch_id,
                'nonce_str' => $this->getNonceStr(),
                'out_refund_no' => $refundNo, // 商户退款单号
            ];
            
            // 生成签名
            $params['sign'] = $this->generateSign($params);
            
            // 转换为XML
            $xml = $this->arrayToXml($params);
            
            // 发送请求
            $response = $this->httpPost($url, $xml);
            
            // 解析响应
            $result = $this->xmlToArray($response);
            
            // 记录完整响应用于调试
            Log::info('微信退款查询API响应: ' . json_encode($result, JSON_UNESCAPED_UNICODE));
            
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                // 解析退款信息
                $refundCount = intval($result['refund_count']);
                $refunds = [];
                
                for ($i = 0; $i < $refundCount; $i++) {
                    $refunds[] = [
                        'out_refund_no' => $result['out_refund_no_' . $i],
                        'refund_id' => $result['refund_id_' . $i],
                        'refund_fee' => $result['refund_fee_' . $i] / 100,
                        'refund_status' => $result['refund_status_' . $i],
                        'refund_recv_accout' => $result['refund_recv_accout_' . $i] ?? null,
                    ];
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'transaction_id' => $result['transaction_id'],
                        'out_trade_no' => $result['out_trade_no'],
                        'total_fee' => $result['total_fee'] / 100,
                        'refund_count' => $refundCount,
                        'refunds' => $refunds
                    ]
                ];
            } else {
                $errorMsg = $result['return_msg'] ?? $result['err_code_des'] ?? '查询退款失败';
                throw new \Exception($errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('查询退款异常: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * POST请求（带证书）
     */
    private function httpPostWithCert($url, $data, $certPath, $keyPath, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // 设置证书
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $keyPath);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('CURL错误: ' . $error);
        }
        
        curl_close($ch);
        return $response;
    }
}

