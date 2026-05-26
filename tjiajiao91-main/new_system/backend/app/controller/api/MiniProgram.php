<?php

namespace app\controller\api;

use app\BaseController;
use app\service\MiniProgramConfigService;
use think\facade\Request;

class MiniProgram extends BaseController
{
    public function clientConfig()
    {
        try {
            $platform = strtolower(trim((string)Request::get('platform', 'wechat')));
            if (!in_array($platform, ['wechat', 'alipay'], true)) {
                $platform = 'wechat';
            }

            $service = new MiniProgramConfigService();
            $cfg = $service->getRuntimeConfig($platform);

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'platform' => $cfg['platform'],
                    'app_id' => $cfg['app_id'],
                    'env_version' => $cfg['env_version'],
                    'source' => $cfg['source'],
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => '获取失败: ' . $e->getMessage()]);
        }
    }
}
