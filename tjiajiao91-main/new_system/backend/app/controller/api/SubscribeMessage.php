<?php
namespace app\controller\api;

use app\BaseController;
use app\service\SubscribeMessageService;
use think\facade\Db;

/**
 * 订阅消息控制器
 */
class SubscribeMessage extends BaseController
{
    /**
     * 记录用户订阅
     * POST /api/subscribe-message/record
     */
    public function record()
    {
        try {
            $userId = $this->request->post('user_id');
            $openid = $this->request->post('openid');
            $templateId = $this->request->post('template_id', SubscribeMessageService::TEMPLATE_ID);
            
            if (!$openid) {
                return json(['code' => 400, 'message' => '参数错误']);
            }

            if (!$userId) {
                $userId = Db::name('users')->where('openid', $openid)->value('id');
                if (!$userId) {
                    $userId = Db::name('fa_users')->where('openid', $openid)->value('id');
                }
            }
            
            $result = SubscribeMessageService::recordSubscribe($userId, $openid, $templateId);
            
            if ($result) {
                return json(['code' => 200, 'message' => '订阅记录成功']);
            } else {
                return json(['code' => 500, 'message' => '订阅记录失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取模板ID
     * GET /api/subscribe-message/template-id
     */
    public function getTemplateId()
    {
        return json([
            'code' => 200,
            'data' => [
                'template_id' => SubscribeMessageService::TEMPLATE_ID
            ]
        ]);
    }
}
