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
            $type = trim((string)$this->request->post('type', ''));
            $templateId = trim((string)$this->request->post('template_id', ''));
            if ($templateId === '' && $type !== '') {
                $code = in_array($type, ['resume_review', 'teacher_review'], true) ? 'resume_review' : 'tutor_recommend';
                $templateId = SubscribeMessageService::getTemplateIdByCode($code);
            }
            if ($templateId === '') {
                $templateId = SubscribeMessageService::getTemplateIdByCode('tutor_recommend');
            }
            
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
        $type = trim((string)$this->request->get('type', 'tutor_recommend'));
        $code = 'tutor_recommend';
        if ($type === 'resume_review' || $type === 'teacher_review') {
            $code = 'resume_review';
        } elseif ($type !== '' && $type !== 'tutor_recommend') {
            $code = $type;
        }
        $tid = SubscribeMessageService::getTemplateIdByCode($code);

        return json([
            'code' => 200,
            'data' => [
                'template_id' => $tid,
                'template_code' => $code,
            ],
        ]);
    }

    /**
     * 小程序拉取已启用的订阅模板映射（code => template_id）
     * GET /api/subscribe-message/templates
     */
    public function templates()
    {
        try {
            $map = SubscribeMessageService::getEnabledTemplatesMap();

            return json([
                'code' => 200,
                'message' => 'ok',
                'data' => [
                    'templates' => $map,
                ],
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'message' => $e->getMessage()]);
        }
    }
}
