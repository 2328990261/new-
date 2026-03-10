<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\EmailSubscription;
use app\service\EmailService;
use app\service\WechatNotificationService;
use think\facade\Db;

/**
 * 通知配置管理控制器（支持邮件和微信服务号）
 */
class Notification extends BaseController
{
    /**
     * 获取通知配置
     */
    public function getConfig()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $config = Db::name('notification_config')->find(1);
            
            // 隐藏敏感信息
            if ($config) {
                if (isset($config['smtp_password'])) {
                    $config['smtp_password'] = '******';
                }
                if (isset($config['wechat_app_secret'])) {
                    $config['wechat_app_secret'] = '******';
                }
            }
            
            return json(['success' => true, 'data' => $config]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 更新通知配置
     */
    public function updateConfig()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        // 如果密码是******，说明没有修改，移除该字段
        if (isset($data['smtp_password']) && $data['smtp_password'] === '******') {
            unset($data['smtp_password']);
        }
        if (isset($data['wechat_app_secret']) && $data['wechat_app_secret'] === '******') {
            unset($data['wechat_app_secret']);
        }
        
        // 处理微信分享配置
        if (isset($data['wechat_share_enabled'])) {
            $data['wechat_share_enabled'] = $data['wechat_share_enabled'] ? 1 : 0;
        }
        
        try {
            $config = Db::name('notification_config')->find(1);
            
            if ($config) {
                Db::name('notification_config')->where('id', 1)->update($data);
            } else {
                $data['id'] = 1;
                Db::name('notification_config')->insert($data);
            }
            
            return json(['success' => true, 'message' => '配置更新成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 测试邮件发送
     */
    public function testEmail()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $email = $this->request->post('email');
        
        if (empty($email)) {
            return json(['success' => false, 'error' => '请提供测试邮箱']);
        }
        
        try {
            $emailService = new EmailService();
            $result = $emailService->sendTestEmail($email);
            
            return json($result);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 测试微信消息发送
     */
    public function testWechat()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $openid = $this->request->post('openid');
        
        if (empty($openid)) {
            return json(['success' => false, 'error' => '请提供测试OpenID']);
        }
        
        try {
            $result = WechatNotificationService::sendTestMessage($openid);
            return json($result);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取微信AccessToken（用于调试）
     */
    public function getAccessToken()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $token = WechatNotificationService::getAccessToken(true);
            return json([
                'success' => true, 
                'data' => [
                    'access_token' => $token,
                    'message' => 'AccessToken获取成功'
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取微信模板列表
     */
    public function getWechatTemplates()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            $list = Db::name('wechat_templates')
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
            
            return json([
                'success' => true,
                'data' => $list->items(),
                'total' => $list->total(),
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 保存微信模板配置
     */
    public function saveWechatTemplate()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $data = $this->request->post();
        
        try {
            if (isset($data['id']) && $data['id']) {
                // 更新
                Db::name('wechat_templates')->where('id', $data['id'])->update($data);
                $message = '更新成功';
            } else {
                // 新增
                Db::name('wechat_templates')->insert($data);
                $message = '添加成功';
            }
            
            return json(['success' => true, 'message' => $message]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除微信模板
     */
    public function deleteWechatTemplate()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            Db::name('wechat_templates')->where('id', $id)->delete();
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 从微信平台同步模板列表
     */
    public function syncWechatTemplates()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $result = WechatNotificationService::getTemplateList();
            
            if (!$result['success']) {
                return json($result);
            }
            
            $templates = $result['data'];
            $syncCount = 0;
            
            foreach ($templates as $template) {
                // 检查是否已存在
                $exists = Db::name('wechat_templates')
                    ->where('template_id', $template['template_id'])
                    ->find();
                
                if (!$exists) {
                    // 新增
                    Db::name('wechat_templates')->insert([
                        'template_code' => 'template_' . $template['template_id'],
                        'template_name' => $template['title'],
                        'template_id' => $template['template_id'],
                        'title' => $template['title'],
                        'content' => $template['content'],
                        'primary_industry' => $template['primary_industry'] ?? '',
                        'deputy_industry' => $template['deputy_industry'] ?? '',
                        'is_enabled' => 0, // 默认不启用，需要手动配置
                        'field_mapping' => '{}',
                        'remark' => '从微信平台同步'
                    ]);
                    $syncCount++;
                }
            }
            
            return json([
                'success' => true,
                'message' => "同步成功，新增 {$syncCount} 个模板",
                'count' => $syncCount
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取订阅列表
     */
    public function subscriptions()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $status = $this->request->get('status');
            $isVerified = $this->request->get('is_verified');
            $keyword = $this->request->get('keyword');
            $channel = $this->request->get('channel', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            $query = Db::name('notification_subscriptions')->order('create_time', 'desc');
            
            if ($status !== '') $query->where('status', $status);
            if ($isVerified !== '') $query->where('is_verified', $isVerified);
            if ($channel !== '') $query->where('channel', $channel);
            if ($keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereOr('email', 'like', '%' . $keyword . '%')
                      ->whereOr('openid', 'like', '%' . $keyword . '%');
                });
            }
            
            $list = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            return json([
                'success' => true,
                'data' => $list->items(),
                'total' => $list->total(),
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 获取发送记录
     */
    public function logs()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        try {
            $receiver = $this->request->get('receiver');
            $status = $this->request->get('status');
            $channel = $this->request->get('channel', '');
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 20);
            
            $query = Db::name('notification_logs')->order('send_time', 'desc');
            
            if ($receiver) {
                $query->where(function($q) use ($receiver) {
                    $q->whereOr('receiver', 'like', '%' . $receiver . '%')
                      ->whereOr('email', 'like', '%' . $receiver . '%');
                });
            }
            if ($status !== '') $query->where('status', $status);
            if ($channel !== '') $query->where('channel', $channel);
            
            $list = $query->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);
            
            return json([
                'success' => true,
                'data' => $list->items(),
                'total' => $list->total(),
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 删除订阅
     */
    public function deleteSubscription()
    {
        // 启动 session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_id'])) {
            return json(['success' => false, 'error' => '未登录']);
        }
        
        $id = $this->request->param('id');
        
        try {
            Db::name('notification_subscriptions')->where('id', $id)->delete();
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            return json(['success' => false, 'error' => '删除失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取微信分享配置（公开接口）
     */
    public function getWechatShareConfig()
    {
        try {
            $config = Db::name('notification_config')->find(1);
            
            if (!$config) {
                return json([
                    'success' => false,
                    'error' => '配置不存在'
                ]);
            }
            
            // 返回微信分享相关配置
            $shareConfig = [
                'enabled' => !empty($config['wechat_share_enabled']) ? (bool)$config['wechat_share_enabled'] : false,
                'title' => $config['wechat_share_title'] ?? '',
                'description' => $config['wechat_share_description'] ?? '',
                'image' => $config['wechat_share_image'] ?? '',
                'app_id' => $config['wechat_app_id'] ?? ''
            ];
            
            return json([
                'success' => true,
                'data' => $shareConfig
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => '获取配置失败：' . $e->getMessage()
            ]);
        }
    }
}


