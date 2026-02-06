<?php
namespace app\controller\api;

use app\BaseController;
use app\model\Agreement as AgreementModel;

/**
 * 协议API控制器（供前端用户使用）
 */
class Agreement extends BaseController
{
    /**
     * 获取支付协议
     */
    public function payment()
    {
        try {
            // 查找支付类型的当前版本协议
            $agreement = AgreementModel::where('type', 'payment')
                ->where('is_current', 1)
                ->where('status', 1)
                ->find();
            
            // 如果没有找到，返回默认协议内容
            if (!$agreement) {
                return json([
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'id' => 0,
                        'type' => 'payment',
                        'title' => '91家教接单协议',
                        'content' => $this->getDefaultPaymentAgreement(),
                        'version' => '1.0',
                        'effective_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $agreement->id,
                    'type' => $agreement->type,
                    'title' => $agreement->title,
                    'content' => $agreement->content,
                    'version' => $agreement->version,
                    'effective_time' => $agreement->effective_time,
                    'update_time' => $agreement->update_time
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取教师注册协议
     */
    public function teacher()
    {
        try {
            $agreement = AgreementModel::where('type', 'teacher')
                ->where('is_current', 1)
                ->where('status', 1)
                ->find();
            
            if (!$agreement) {
                return json([
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'id' => 0,
                        'type' => 'teacher',
                        'title' => '教师注册协议',
                        'content' => $this->getDefaultTeacherAgreement(),
                        'version' => '1.0',
                        'effective_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $agreement->id,
                    'type' => $agreement->type,
                    'title' => $agreement->title,
                    'content' => $agreement->content,
                    'version' => $agreement->version,
                    'effective_time' => $agreement->effective_time,
                    'update_time' => $agreement->update_time
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取用户协议
     */
    public function user()
    {
        try {
            $agreement = AgreementModel::where('type', 'user')
                ->where('is_current', 1)
                ->where('status', 1)
                ->find();
            
            if (!$agreement) {
                return json([
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'id' => 0,
                        'type' => 'user',
                        'title' => '用户服务协议',
                        'content' => $this->getDefaultUserAgreement(),
                        'version' => '1.0',
                        'effective_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $agreement->id,
                    'type' => $agreement->type,
                    'title' => $agreement->title,
                    'content' => $agreement->content,
                    'version' => $agreement->version,
                    'effective_time' => $agreement->effective_time,
                    'update_time' => $agreement->update_time
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取隐私政策
     */
    public function privacy()
    {
        try {
            $agreement = AgreementModel::where('type', 'privacy')
                ->where('is_current', 1)
                ->where('status', 1)
                ->find();
            
            if (!$agreement) {
                return json([
                    'code' => 200,
                    'message' => '获取成功',
                    'data' => [
                        'id' => 0,
                        'type' => 'privacy',
                        'title' => '隐私政策',
                        'content' => $this->getDefaultPrivacyPolicy(),
                        'version' => '1.0',
                        'effective_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
            
            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $agreement->id,
                    'type' => $agreement->type,
                    'title' => $agreement->title,
                    'content' => $agreement->content,
                    'version' => $agreement->version,
                    'effective_time' => $agreement->effective_time,
                    'update_time' => $agreement->update_time
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取默认支付协议内容
     */
    private function getDefaultPaymentAgreement()
    {
        return '<div style="padding: 20px; line-height: 1.8; color: #333;">
  <h4>91家教接单协议</h4>
  <p>欢迎使用91家教平台服务。在使用本平台前，请仔细阅读以下协议条款：</p>
  
  <h5>第一条 服务内容</h5>
  <p>1.1 本平台为用户提供家教信息发布、查询、匹配等服务。</p>
  <p>1.2 用户可通过平台发布家教需求或应聘家教职位。</p>
  <p>1.3 平台不直接参与教学活动，仅提供信息中介服务。</p>
  
  <h5>第二条 用户权利与义务</h5>
  <p>2.1 用户有权获得真实、准确的家教信息。</p>
  <p>2.2 用户应提供真实的个人信息和需求信息。</p>
  <p>2.3 用户不得发布虚假、违法或有害信息。</p>
  
  <h5>第三条 费用说明</h5>
  <p>3.1 用户需支付信息费以获取家教联系方式。</p>
  <p>3.2 信息费一经支付，原则上不予退还。</p>
  <p>3.3 如因平台原因导致服务无法提供，将全额退款。</p>
  
  <h5>第四条 隐私保护</h5>
  <p>4.1 平台承诺保护用户隐私信息安全。</p>
  <p>4.2 未经用户同意，不会向第三方泄露用户信息。</p>
  <p>4.3 用户信息仅用于提供相关服务。</p>
  
  <h5>第五条 免责声明</h5>
  <p>5.1 平台不对教学质量承担责任。</p>
  <p>5.2 用户与家教之间的纠纷由双方自行解决。</p>
  <p>5.3 平台不承担因不可抗力导致的损失。</p>
  
  <h5>第六条 协议变更</h5>
  <p>6.1 平台有权根据需要修改本协议。</p>
  <p>6.2 协议变更后将在平台公布。</p>
  <p>6.3 继续使用服务视为同意变更后的协议。</p>
  
  <h5>第七条 争议解决</h5>
  <p>7.1 因本协议产生的争议，双方应友好协商解决。</p>
  <p>7.2 协商不成的，可向平台所在地法院起诉。</p>
  
  <h5>第八条 其他条款</h5>
  <p>8.1 本协议自用户点击同意时生效。</p>
  <p>8.2 本协议的解释权归91家教平台所有。</p>
  <p>8.3 如有疑问，请联系客服咨询。</p>
  
  <p style="margin-top: 30px; text-align: center; color: #666;">感谢您使用91家教平台！</p>
</div>';
    }
    
    /**
     * 获取默认教师注册协议内容
     */
    private function getDefaultTeacherAgreement()
    {
        return '<div style="padding: 20px; line-height: 1.8; color: #333;">
  <h4>教师注册协议</h4>
  <p>欢迎注册成为91家教平台的教师。请仔细阅读以下协议条款：</p>
  
  <h5>第一条 注册资格</h5>
  <p>1.1 教师应具备相应的教学能力和资质。</p>
  <p>1.2 教师应提供真实、准确的个人信息。</p>
  <p>1.3 教师应遵守国家法律法规和平台规则。</p>
  
  <h5>第二条 教师权利</h5>
  <p>2.1 教师有权获得真实的家教需求信息。</p>
  <p>2.2 教师有权自主选择是否接受家教任务。</p>
  <p>2.3 教师有权获得合理的教学报酬。</p>
  
  <h5>第三条 教师义务</h5>
  <p>3.1 教师应认真履行教学职责。</p>
  <p>3.2 教师应保护学生的隐私和安全。</p>
  <p>3.3 教师应遵守教学时间和地点约定。</p>
  
  <p style="margin-top: 30px; text-align: center; color: #666;">感谢您的注册！</p>
</div>';
    }
    
    /**
     * 获取默认用户协议内容
     */
    private function getDefaultUserAgreement()
    {
        return '<div style="padding: 20px; line-height: 1.8; color: #333;">
  <h4>用户服务协议</h4>
  <p>欢迎使用91家教平台。请仔细阅读以下协议条款：</p>
  
  <h5>第一条 服务说明</h5>
  <p>1.1 本平台提供家教信息中介服务。</p>
  <p>1.2 用户应遵守平台规则和国家法律法规。</p>
  
  <h5>第二条 用户权利</h5>
  <p>2.1 用户有权获得优质的服务。</p>
  <p>2.2 用户有权保护个人隐私。</p>
  
  <p style="margin-top: 30px; text-align: center; color: #666;">感谢您的使用！</p>
</div>';
    }
    
    /**
     * 获取默认隐私政策内容
     */
    private function getDefaultPrivacyPolicy()
    {
        return '<div style="padding: 20px; line-height: 1.8; color: #333;">
  <h4>隐私政策</h4>
  <p>我们重视您的隐私保护。本政策说明我们如何收集、使用和保护您的个人信息。</p>
  
  <h5>第一条 信息收集</h5>
  <p>1.1 我们收集您主动提供的信息。</p>
  <p>1.2 我们收集您使用服务时产生的信息。</p>
  
  <h5>第二条 信息使用</h5>
  <p>2.1 我们使用信息提供和改进服务。</p>
  <p>2.2 我们不会向第三方出售您的信息。</p>
  
  <h5>第三条 信息保护</h5>
  <p>3.1 我们采取安全措施保护您的信息。</p>
  <p>3.2 我们定期审查安全措施。</p>
  
  <p style="margin-top: 30px; text-align: center; color: #666;">感谢您的信任！</p>
</div>';
    }
}
