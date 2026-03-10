<?php
namespace app\service;

use app\model\EmailSubscription;
use app\model\TutorOrder;
use think\facade\Db;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 邮件发送服务
 */
class EmailService
{
    /**
     * 发送家长预约通知给管理员
     */
    public static function sendBookingNotification($admin, $order)
    {
        try {
            // 获取邮件配置
            $config = Db::name('notification_config')->find(1);
            
            if (!$config || !$config['smtp_host']) {
                throw new \Exception('邮件配置未设置');
            }
            
            $mail = new PHPMailer(true);
            
            // SMTP配置
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'] ? PHPMailer::ENCRYPTION_SMTPS : '';
            $mail->Port = $config['smtp_port'] ?: 465;
            
            // 超时和SSL设置
            $mail->Timeout = 10;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // 发件人
            $mail->setFrom($config['from_email'], $config['from_name'] ?: '家教信息平台');
            
            // 收件人
            $mail->addAddress($admin->email);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '新的家教需求预约通知 - ' . $order->order_no;
            
            // 构建邮件内容
            $body = self::renderBookingTemplate($order);
            $mail->Body = $body;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            trace('预约通知邮件发送失败: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 渲染预约通知邮件模板
     */
    private static function renderBookingTemplate($order)
    {
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= '<h2 style="color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">新的家教需求预约</h2>';
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">';
        $html .= '<p style="margin: 5px 0;"><strong>订单号：</strong>' . htmlspecialchars($order->order_no) . '</p>';
        $html .= '<p style="margin: 5px 0;"><strong>学员年级：</strong>' . htmlspecialchars($order->grade) . '</p>';
        $html .= '<p style="margin: 5px 0;"><strong>辅导科目：</strong>' . htmlspecialchars($order->subject) . '</p>';
        $html .= '<p style="margin: 5px 0;"><strong>家长称呼：</strong>' . htmlspecialchars($order->parent_name) . '</p>';
        $html .= '<p style="margin: 5px 0;"><strong>联系方式：</strong>' . htmlspecialchars($order->parent_contact) . '</p>';
        $html .= '<p style="margin: 5px 0;"><strong>提交时间：</strong>' . $order->create_time . '</p>';
        $html .= '</div>';
        $html .= '<h3 style="color: #666;">详细信息</h3>';
        $html .= '<div style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">';
        $html .= '<p><strong>学生情况：</strong></p>';
        $html .= '<p style="color: #666;">' . nl2br(htmlspecialchars($order->student_info)) . '</p>';
        $html .= '<p><strong>辅导频率：</strong></p>';
        $html .= '<p style="color: #666;">' . htmlspecialchars($order->frequency) . '</p>';
        $html .= '<p><strong>老师要求：</strong></p>';
        $html .= '<p style="color: #666;">' . nl2br(htmlspecialchars($order->teacher_requirement)) . '</p>';
        $html .= '<p><strong>授课地址：</strong></p>';
        $html .= '<p style="color: #666;">' . htmlspecialchars($order->address) . '</p>';
        if ($order->remark) {
            $html .= '<p><strong>备注：</strong></p>';
            $html .= '<p style="color: #666;">' . nl2br(htmlspecialchars($order->remark)) . '</p>';
        }
        $html .= '</div>';
        $html .= '<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
        $html .= '<p style="margin: 0; color: #856404;">请登录管理后台的"我的订单"模块查看并审核此订单。</p>';
        $html .= '</div>';
        $html .= '<div style="margin-top: 20px; text-align: center; color: #999; font-size: 12px;">';
        $html .= '<p>此邮件由系统自动发送，请勿回复。</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * 发送新订单通知给匹配的订阅者
     */
    public function sendOrderNotification($orderId)
    {
        try {
            // 获取订单信息
            $order = TutorOrder::with(['city', 'district', 'subject'])
                ->find($orderId);
            
            if (!$order) {
                return ['success' => false, 'message' => '订单不存在'];
            }
            
            // 查找匹配的订阅者
            $subscribers = $this->getMatchedSubscribers($order);
            
            if (empty($subscribers)) {
                return ['success' => true, 'message' => '没有匹配的订阅者', 'count' => 0];
            }
            
            // 发送邮件
            $successCount = 0;
            $failCount = 0;
            
            foreach ($subscribers as $subscriber) {
                $result = $this->sendEmail($subscriber['email'], $order);
                if ($result) {
                    $successCount++;
                    // 记录发送日志
                    $this->logEmailSend($subscriber['email'], $orderId, true);
                } else {
                    $failCount++;
                    $this->logEmailSend($subscriber['email'], $orderId, false);
                }
            }
            
            return [
                'success' => true,
                'message' => "邮件发送完成",
                'total' => count($subscribers),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * 获取匹配订单的订阅者
     */
    private function getMatchedSubscribers($order)
    {
        $query = EmailSubscription::where('status', 1)
            ->where('is_verified', 1);
        
        $subscribers = $query->select()->toArray();
        
        $matched = [];
        foreach ($subscribers as $subscriber) {
            $model = new EmailSubscription();
            $model->data($subscriber);
            
            if ($model->matchesOrder($order)) {
                $matched[] = $subscriber;
            }
        }
        
        return $matched;
    }
    
    /**
     * 发送邮件
     */
    public function sendEmail($to, $order)
    {
        try {
            // 获取邮件配置
            $config = Db::name('notification_config')->find(1);
            
            if (!$config || !$config['smtp_host']) {
                throw new \Exception('邮件配置未设置');
            }
            
            $mail = new PHPMailer(true);
            
            // SMTP配置
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'] ? PHPMailer::ENCRYPTION_SMTPS : '';
            $mail->Port = $config['smtp_port'] ?: 465;
            
            // 超时和SSL设置
            $mail->Timeout = 10;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // 发件人
            $mail->setFrom($config['from_email'], $config['from_name'] ?: '家教信息平台');
            
            // 收件人
            $mail->addAddress($to);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '新家教信息通知';
            
            // 替换模板变量
            $content = $this->renderTemplate($config['email_template'], $order);
            $mail->Body = $content;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            // 记录错误日志
            trace('邮件发送失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 渲染邮件模板
     */
    private function renderTemplate($template, $order)
    {
        $replacements = [
            '{{city}}' => $order->city ? $order->city->name : '',
            '{{district}}' => $order->district ? $order->district->name : '',
            '{{grade}}' => $order->grade ?: '',
            '{{subject}}' => $order->subject ? $order->subject->name : '',
            '{{salary}}' => $order->salary ?: '',
            '{{content}}' => nl2br(htmlspecialchars($order->content)),
        ];
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }
    
    /**
     * 记录邮件发送日志
     */
    private function logEmailSend($email, $orderId, $success, $errorMsg = '')
    {
        Db::name('email_logs')->insert([
            'email' => $email,
            'order_id' => $orderId,
            'subject' => '新家教信息通知',
            'status' => $success ? 1 : 0,
            'error_msg' => $errorMsg,
            'send_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * 发送线索指派通知给客服
     */
    public static function sendLeadAssignNotification($admin, $lead)
    {
        try {
            // 检查管理员是否有邮箱
            if (empty($admin->email)) {
                throw new \Exception('管理员邮箱未设置');
            }
            
            // 获取邮件配置
            $config = Db::name('notification_config')->find(1);
            
            if (!$config || !$config['smtp_host']) {
                throw new \Exception('邮件配置未设置');
            }
            
            $mail = new PHPMailer(true);
            
            // SMTP配置
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'] ? PHPMailer::ENCRYPTION_SMTPS : '';
            $mail->Port = $config['smtp_port'] ?: 465;
            
            // 超时和SSL设置
            $mail->Timeout = 10;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // 发件人
            $mail->setFrom($config['from_email'], $config['from_name'] ?: '家教信息平台');
            
            // 收件人
            $mail->addAddress($admin->email, $admin->nickname);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '新线索指派通知 - ' . $lead->lead_no;
            
            // 构建邮件内容
            $body = self::renderLeadAssignTemplate($lead);
            $mail->Body = $body;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            trace('线索指派邮件发送失败: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 渲染线索指派邮件模板
     */
    private static function renderLeadAssignTemplate($lead)
    {
        $cityName = $lead->city ? $lead->city->name : '';
        $districtName = $lead->district ? $lead->district->name : '';
        
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<div style="background: #667eea; color: white; padding: 20px; text-align: center;">';
        $html .= '<h2 style="margin: 0;">新线索指派通知</h2>';
        $html .= '</div>';
        $html .= '<div style="padding: 20px; background: #f9f9f9;">';
        $html .= '<div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px;">';
        $html .= '<p style="margin: 10px 0;"><strong>线索编号：</strong>' . htmlspecialchars($lead->lead_no) . '</p>';
        
        if ($lead->contact_name || $lead->phone) {
            $html .= '<p style="margin: 10px 0;"><strong>客户信息：</strong>';
            if ($lead->contact_name) {
                $html .= htmlspecialchars($lead->contact_name) . ' ';
            }
            if ($lead->phone) {
                $html .= htmlspecialchars($lead->phone);
            }
            $html .= '</p>';
        }
        
        if ($cityName || $districtName) {
            $html .= '<p style="margin: 10px 0;"><strong>城市区域：</strong>' . htmlspecialchars($cityName . ' ' . $districtName) . '</p>';
        }
        
        if ($lead->grade) {
            $html .= '<p style="margin: 10px 0;"><strong>年级：</strong>' . htmlspecialchars($lead->grade) . '</p>';
        }
        
        if ($lead->subject) {
            $html .= '<p style="margin: 10px 0;"><strong>科目：</strong>' . htmlspecialchars($lead->subject) . '</p>';
        }
        
        $html .= '</div>';
        
        $html .= '<div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px;">';
        $html .= '<p style="margin: 0 0 10px 0;"><strong>需求详情：</strong></p>';
        $html .= '<div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; line-height: 1.6;">';
        $html .= nl2br(htmlspecialchars($lead->raw_content));
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
        $html .= '<p style="margin: 0; color: #856404; font-size: 14px;">';
        $html .= '⏰ <strong>请在1小时内联系客户并在系统中跟进回复</strong>';
        $html .= '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '<div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">';
        $html .= '<p style="margin: 0;">此邮件由系统自动发送，请勿直接回复。</p>';
        $html .= '<p style="margin: 5px 0 0 0;">请登录管理后台进行线索跟进操作。</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
}

