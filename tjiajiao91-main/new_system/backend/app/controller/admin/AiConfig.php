<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\AiConfig as AiConfigModel;
use think\response\Json;

class AiConfig extends BaseController
{
    /**
     * 获取 AI 配置（api_key 脱敏返回）
     * GET /admin/api/ai-config
     */
    public function getConfig(): Json
    {
        try {
            $config = AiConfigModel::getConfig();

            if (!$config) {
                return json([
                    'code' => 0,
                    'msg'  => 'success',
                    'data' => [
                        'base_url'       => 'https://kuaipao.ai',
                        'api_key'        => '',
                        'api_key_masked' => '',
                        'api_key_set'    => false,
                    ],
                ]);
            }

            $rawKey = $config->getRawApiKey();
            $masked = '';
            if ($rawKey !== '') {
                $len    = strlen($rawKey);
                $masked = $len <= 10
                    ? str_repeat('*', $len)
                    : substr($rawKey, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($rawKey, -4);
            }

            return json([
                'code' => 0,
                'msg'  => 'success',
                'data' => [
                    'base_url'       => (string)($config->base_url ?? 'https://kuaipao.ai'),
                    'api_key'        => '',          // 不回传明文
                    'api_key_masked' => $masked,
                    'api_key_set'    => $rawKey !== '',
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取配置失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 保存 AI 配置
     * POST /admin/api/ai-config
     *
     * 入参：
     * - base_url: string  中转服务地址
     * - api_key:  string  密钥（含 * 表示未修改，留空也表示不修改）
     */
    public function saveConfig(): Json
    {
        try {
            $data = $this->request->post();

            $baseUrl = trim((string)($data['base_url'] ?? ''));
            $apiKey  = trim((string)($data['api_key']  ?? ''));

            if ($baseUrl === '') {
                return json(['code' => 1, 'msg' => '中转地址不能为空']);
            }

            $saveData = ['base_url' => rtrim($baseUrl, '/')];

            // api_key 含星号 → 用户没有修改，保留原值
            if ($apiKey !== '' && strpos($apiKey, '*') === false) {
                $saveData['api_key'] = $apiKey;
            }
            // api_key 为空 → 不修改（保留原值）

            AiConfigModel::saveConfig($saveData);

            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败: ' . $e->getMessage()]);
        }
    }
}
