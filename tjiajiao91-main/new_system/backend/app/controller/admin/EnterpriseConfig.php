<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\EnterpriseConfig as EnterpriseConfigModel;
use think\response\Json;

class EnterpriseConfig extends BaseController
{
    /**
     * 获取企业配置
     */
    public function getConfig(): Json
    {
        try {
            $config = EnterpriseConfigModel::getConfig();
            
            if (!$config) {
                return json([
                    'code' => 0,
                    'msg' => 'success',
                    'data' => null
                ]);
            }

            // 转换为数组并手动处理敏感字段
            $data = $config->toArray();
            
            // 手动隐藏敏感信息（用于前端显示）
            if (!empty($data['agent_secret'])) {
                $data['agent_secret'] = substr($data['agent_secret'], 0, 8) . str_repeat('*', max(0, strlen($data['agent_secret']) - 8));
            }
            
            if (!empty($data['contacts_secret'])) {
                $data['contacts_secret'] = substr($data['contacts_secret'], 0, 8) . str_repeat('*', max(0, strlen($data['contacts_secret']) - 8));
            }

            return json([
                'code' => 0,
                'msg' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '获取配置失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 保存企业配置
     */
    public function saveConfig(): Json
    {
        try {
            $data = $this->request->post();

            // 验证必填字段
            if (empty($data['corp_id'])) {
                return json([
                    'code' => 1,
                    'msg' => '企业ID不能为空'
                ]);
            }

            if (empty($data['agent_id'])) {
                return json([
                    'code' => 1,
                    'msg' => '应用AgentId不能为空'
                ]);
            }

            // 如果secret字段包含星号，说明没有修改，需要保留原值
            $existingConfig = EnterpriseConfigModel::getConfig();
            
            if (isset($data['agent_secret']) && strpos($data['agent_secret'], '*') !== false) {
                if ($existingConfig) {
                    $data['agent_secret'] = $existingConfig->getRawAgentSecret();
                } else {
                    unset($data['agent_secret']);
                }
            }

            if (isset($data['contacts_secret']) && strpos($data['contacts_secret'], '*') !== false) {
                if ($existingConfig) {
                    $data['contacts_secret'] = $existingConfig->getRawContactsSecret();
                } else {
                    unset($data['contacts_secret']);
                }
            }

            // 处理可见成员列表
            if (isset($data['visible_users']) && is_string($data['visible_users'])) {
                $data['visible_users'] = json_decode($data['visible_users'], true) ?: [];
            }

            // 保存配置
            $config = EnterpriseConfigModel::saveConfig($data);

            return json([
                'code' => 0,
                'msg' => '保存成功',
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '保存失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 测试企业微信连接
     */
    public function testConnection(): Json
    {
        try {
            $config = EnterpriseConfigModel::getConfig();
            
            if (!$config) {
                return json([
                    'code' => 1,
                    'msg' => '请先配置企业微信信息'
                ]);
            }

            // 获取access_token测试连接
            $corpId = $config->corp_id;
            $agentSecret = $config->getRawAgentSecret();

            if (empty($corpId) || empty($agentSecret)) {
                return json([
                    'code' => 1,
                    'msg' => '企业ID或应用Secret未配置'
                ]);
            }

            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpId}&corpsecret={$agentSecret}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['access_token'])) {
                return json([
                    'code' => 0,
                    'msg' => '连接成功',
                    'data' => [
                        'expires_in' => $result['expires_in']
                    ]
                ]);
            } else {
                return json([
                    'code' => 1,
                    'msg' => '连接失败: ' . ($result['errmsg'] ?? '未知错误'),
                    'data' => $result
                ]);
            }
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '测试失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 同步企业微信通讯录
     */
    public function syncContacts(): Json
    {
        try {
            $config = EnterpriseConfigModel::getConfig();
            
            if (!$config) {
                return json([
                    'code' => 1,
                    'msg' => '请先配置企业微信信息'
                ]);
            }

            $corpId = $config->corp_id;
            $contactsSecret = $config->getRawContactsSecret();

            if (empty($corpId) || empty($contactsSecret)) {
                return json([
                    'code' => 1,
                    'msg' => '企业ID或通讯录Secret未配置'
                ]);
            }

            // 获取通讯录access_token
            $tokenUrl = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpId}&corpsecret={$contactsSecret}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $tokenResponse = curl_exec($ch);
            curl_close($ch);

            $tokenResult = json_decode($tokenResponse, true);

            if (!isset($tokenResult['access_token'])) {
                return json([
                    'code' => 1,
                    'msg' => '获取access_token失败: ' . ($tokenResult['errmsg'] ?? '未知错误')
                ]);
            }

            $accessToken = $tokenResult['access_token'];

            // 获取部门成员列表（这里获取根部门，department_id=1）
            $userListUrl = "https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token={$accessToken}&department_id=1&fetch_child=1";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $userListUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $userListResponse = curl_exec($ch);
            curl_close($ch);

            $userListResult = json_decode($userListResponse, true);

            if ($userListResult['errcode'] != 0) {
                return json([
                    'code' => 1,
                    'msg' => '获取成员列表失败: ' . $userListResult['errmsg']
                ]);
            }

            // 同步到人员表
            $personnelModel = new \app\model\Personnel();
            $syncCount = 0;
            $updateCount = 0;

            foreach ($userListResult['userlist'] as $user) {
                // 检查是否已存在
                $existing = $personnelModel->where('phone', $user['mobile'] ?? '')->find();
                
                $personnelData = [
                    'name' => $user['name'],
                    'phone' => $user['mobile'] ?? '',
                    'employment_status' => $user['status'] == 1 ? '在职' : '离职',
                    'employment_type' => $user['isleader'] == 1 ? '管理层' : '员工',
                    'department' => isset($user['department']) ? implode(',', $user['department']) : '',
                    'position' => $user['position'] ?? '',
                ];

                if ($existing) {
                    $existing->save($personnelData);
                    $updateCount++;
                } else {
                    $personnelModel->create($personnelData);
                    $syncCount++;
                }
            }

            return json([
                'code' => 0,
                'msg' => '同步成功',
                'data' => [
                    'total' => count($userListResult['userlist']),
                    'new' => $syncCount,
                    'updated' => $updateCount
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => '同步失败: ' . $e->getMessage()
            ]);
        }
    }
}
