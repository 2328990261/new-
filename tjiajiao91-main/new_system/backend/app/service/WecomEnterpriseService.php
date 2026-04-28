<?php
namespace app\service;

use think\facade\Cache;
use think\facade\Log;

/**
 * 企业微信企业管理服务
 */
class WecomEnterpriseService
{
    private $corpId;
    private $corpSecret;
    private $apiBase = 'https://qyapi.weixin.qq.com/cgi-bin';
    
    public function __construct()
    {
        // 从配置中读取企业微信配置
        $config = $this->getWecomConfig();
        $this->corpId = $config['corp_id'] ?? '';
        
        // 优先使用通讯录Secret（agent_secret 或 contact_secret）
        // 如果没有，则使用应用Secret（secret）
        $this->corpSecret = $config['agent_secret'] ?? '';  // 优先：通讯录Secret (agent_secret)
        if (empty($this->corpSecret)) {
            $this->corpSecret = $config['contact_secret'] ?? '';  // 备选：通讯录Secret (contact_secret)
        }
        if (empty($this->corpSecret)) {
            $this->corpSecret = $config['secret'] ?? '';  // 最后：应用Secret (secret)
        }
    }
    
    /**
     * 获取企业微信配置
     */
    private function getWecomConfig()
    {
        $config = \app\model\WecomConfig::where('id', 1)->find();
        if (!$config) {
            return ['corp_id' => '', 'agent_secret' => '', 'contact_secret' => '', 'secret' => ''];
        }
        return $config->toArray();
    }
    
    /**
     * 获取access_token
     */
    private function getAccessToken()
    {
        $cacheKey = 'wecom_enterprise_access_token';
        $token = Cache::get($cacheKey);
        
        if ($token) {
            return $token;
        }
        
        $url = "{$this->apiBase}/gettoken?corpid={$this->corpId}&corpsecret={$this->corpSecret}";
        $response = $this->httpGet($url);
        
        if (!$response || !isset($response['access_token'])) {
            // 详细记录错误信息
            $errorInfo = [
                'corp_id' => $this->corpId,
                'secret_preview' => substr($this->corpSecret, 0, 10) . '...',
                'secret_length' => strlen($this->corpSecret),
                'api_response' => $response,
                'error_time' => date('Y-m-d H:i:s')
            ];
            
            Log::error('获取企业微信access_token失败', $errorInfo);
            
            // 如果有错误码，记录详细说明
            if (isset($response['errcode'])) {
                $errcode = $response['errcode'];
                $errmsg = $response['errmsg'] ?? '';
                
                $errorMessages = [
                    40001 => 'Secret无效，请检查agent_secret是否正确',
                    40013 => '企业ID无效，请检查corp_id是否正确',
                    40084 => 'Secret参数非法，请检查Secret是否完整',
                    42001 => 'access_token已过期',
                    40014 => 'access_token无效',
                ];
                
                $detailMsg = $errorMessages[$errcode] ?? $errmsg;
                Log::error("企业微信API错误 [{$errcode}]: {$detailMsg}");
            }
            
            return null;
        }
        
        $token = $response['access_token'];
        $expiresIn = $response['expires_in'] ?? 7200;
        Cache::set($cacheKey, $token, $expiresIn - 300);
        
        Log::info('成功获取企业微信access_token', [
            'expires_in' => $expiresIn,
            'time' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    }
    
    /**
     * 获取部门列表
     */
    public function getDepartmentList($departmentId = null)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['errcode' => -1, 'errmsg' => '获取access_token失败'];
        }
        
        $url = "{$this->apiBase}/department/list?access_token={$accessToken}";
        if ($departmentId) {
            $url .= "&id={$departmentId}";
        }
        
        return $this->httpGet($url);
    }
    
    /**
     * 获取部门成员详情列表
     */
    public function getUserDetailList($departmentId = 1, $fetchChild = true)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('获取人员列表失败：access_token为空');
            return ['errcode' => -1, 'errmsg' => '获取access_token失败，请检查企业微信配置'];
        }
        
        $url = "{$this->apiBase}/user/list?access_token={$accessToken}&department_id={$departmentId}";
        if ($fetchChild) {
            $url .= "&fetch_child=1";
        }
        
        $response = $this->httpGet($url);
        
        // 记录API调用结果
        if (isset($response['errcode']) && $response['errcode'] != 0) {
            Log::error('企业微信获取人员列表失败', [
                'department_id' => $departmentId,
                'errcode' => $response['errcode'],
                'errmsg' => $response['errmsg'] ?? '',
                'time' => date('Y-m-d H:i:s')
            ]);
        }
        
        if (isset($response['userlist']) && is_array($response['userlist'])) {
            $list = [];
            foreach ($response['userlist'] as $user) {
                $list[] = $this->formatUserData($user);
            }
            
            Log::info('成功获取企业微信人员列表', [
                'count' => count($list),
                'department_id' => $departmentId
            ]);
            
            return ['errcode' => 0, 'errmsg' => 'ok', 'userlist' => $list];
        }
        
        return $response;
    }
    
    /**
     * 读取单个成员信息
     */
    public function getUser($userid)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['errcode' => -1, 'errmsg' => '获取access_token失败'];
        }
        
        $url = "{$this->apiBase}/user/get?access_token={$accessToken}&userid={$userid}";
        $response = $this->httpGet($url);
        
        if (isset($response['userid'])) {
            return ['errcode' => 0, 'errmsg' => 'ok', 'user' => $this->formatUserData($response)];
        }
        
        return $response;
    }
    
    /**
     * 格式化用户数据
     */
    private function formatUserData($user)
    {
        $statusMap = [1 => '在职', 2 => '在职', 4 => '在职', 5 => '离职'];
        
        $departmentNames = [];
        if (isset($user['department']) && is_array($user['department'])) {
            foreach ($user['department'] as $deptId) {
                $departmentNames[] = $this->getDepartmentName($deptId);
            }
        }
        
        return [
            'id' => $user['userid'] ?? '',
            'userid' => $user['userid'] ?? '',
            'name' => $user['name'] ?? '',
            'phone' => $user['mobile'] ?? '',
            'employment_status' => $statusMap[$user['status'] ?? 1] ?? '在职',
            'employment_type' => ($user['isleader'] ?? 0) ? '管理层' : '员工',
            'department' => implode('、', $departmentNames),
            'position' => $user['position'] ?? '',
            'avatar' => $user['avatar'] ?? '',
            'email' => $user['email'] ?? '',
            'entry_date' => '',
            'remark' => ''
        ];
    }
    
    /**
     * 获取部门名称
     */
    private function getDepartmentName($departmentId)
    {
        static $cache = [];
        if (isset($cache[$departmentId])) return $cache[$departmentId];
        
        $deptList = $this->getDepartmentList();
        if (isset($deptList['department'])) {
            foreach ($deptList['department'] as $dept) {
                if ($dept['id'] == $departmentId) {
                    $cache[$departmentId] = $dept['name'];
                    return $dept['name'];
                }
            }
        }
        return "部门{$departmentId}";
    }
    
    /**
     * HTTP GET请求
     */
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // 记录CURL错误
        if ($curlError) {
            Log::error('CURL请求失败', [
                'url' => $url,
                'error' => $curlError,
                'http_code' => $httpCode
            ]);
            return null;
        }
        
        // 记录HTTP错误
        if ($httpCode != 200) {
            Log::error('HTTP请求失败', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }
        
        $result = json_decode($response, true);
        
        // 记录JSON解析错误
        if ($result === null && $response !== 'null') {
            Log::error('JSON解析失败', [
                'url' => $url,
                'response' => substr($response, 0, 500)
            ]);
        }
        
        return $result;
    }
}
