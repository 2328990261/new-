<?php
namespace app\service;

use app\model\EmailSubscription;
use app\model\TutorOrder;
use app\model\EmailLog;
use think\facade\Db;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 邮件发送服务
 */
class EmailService
{
    /**
     * 配置PHPMailer实例（统一配置，提高性能）
     */
    private static function configureSMTP($mail, $config)
    {
        // SMTP基础配置
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        
        // SSL/TLS配置
        if ($config['smtp_secure']) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
        }
        $mail->Port = $config['smtp_port'] ?: 465;
        
        // 性能优化配置
        $mail->Timeout = 10; // 减少超时时间
        $mail->SMTPKeepAlive = true; // 保持连接，批量发送时复用
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // 发件人
        $mail->setFrom($config['from_email'], $config['from_name'] ?: '家教信息平台');
    }
    
    /**
     * 通用邮件发送方法（用于重发等场景）
     */
    public function sendMail($to, $subject, $body, $toName = '')
    {
        // 获取邮件配置
        $config = Db::name('notification_config')->find(1);
        
        if (!$config || !$config['smtp_host']) {
            throw new \Exception('邮件配置未设置');
        }
        
        if (!$config['email_enabled']) {
            throw new \Exception('邮件通知未启用');
        }
        
        $mail = new PHPMailer(true);
        
        // 使用统一配置方法
        self::configureSMTP($mail, $config);
        
        // 收件人
        $mail->addAddress($to, $toName);
        
        // 邮件内容
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    }
    
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
            
            // 使用统一配置方法
            self::configureSMTP($mail, $config);
            
            // 收件人
            $mail->addAddress($admin->email);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '新的家教需求预约通知 - ' . $order->order_no;
            
            // 构建邮件内容
            $body = self::renderBookingTemplate($order);
            $mail->Body = $body;
            
            $mail->send();
            
            // 记录成功日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_BOOKING,
                'recipient_email' => $admin->email,
                'recipient_name' => $admin->nickname ?? null,
                'subject' => $mail->Subject,
                'body' => $body,
                'related_id' => $order->id,
                'status' => EmailLog::STATUS_SENT,
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            // 记录失败日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_BOOKING,
                'recipient_email' => $admin->email ?? null,
                'recipient_name' => $admin->nickname ?? null,
                'subject' => '新的家教需求预约通知 - ' . $order->order_no,
                'related_id' => $order->id,
                'status' => EmailLog::STATUS_FAILED,
                'error_msg' => $e->getMessage(),
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
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
            
            // 使用统一配置方法
            self::configureSMTP($mail, $config);
            
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
        EmailLog::log([
            'email_type' => EmailLog::TYPE_ORDER,
            'recipient_email' => $email,
            'subject' => '新家教信息通知',
            'related_id' => $orderId,
            'status' => $success ? EmailLog::STATUS_SENT : EmailLog::STATUS_FAILED,
            'error_msg' => $errorMsg,
            'send_time' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * 发送线索指派通知给客服（同步方式）
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
            
            // 使用统一配置方法
            self::configureSMTP($mail, $config);
            
            // 收件人
            $mail->addAddress($admin->email, $admin->nickname);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '新线索指派通知 - ' . $lead->lead_no;
            
            // 构建邮件内容
            $body = self::renderLeadAssignTemplate($lead);
            $mail->Body = $body;
            
            $mail->send();
            
            // 记录成功日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_LEAD_ASSIGN,
                'recipient_email' => $admin->email,
                'recipient_name' => $admin->nickname ?? null,
                'subject' => $mail->Subject,
                'body' => $body,
                'related_id' => $lead->id,
                'status' => EmailLog::STATUS_SENT,
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
            trace('线索指派邮件发送成功: ' . $admin->email, 'info');
            return true;
            
        } catch (Exception $e) {
            // 记录失败日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_LEAD_ASSIGN,
                'recipient_email' => $admin->email ?? null,
                'recipient_name' => $admin->nickname ?? null,
                'subject' => '新线索指派通知 - ' . $lead->lead_no,
                'related_id' => $lead->id,
                'status' => EmailLog::STATUS_FAILED,
                'error_msg' => $e->getMessage(),
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
            trace('线索指派邮件发送失败: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * 异步发送线索指派通知（仅添加到队列，不立即发送）
     */
    public static function sendLeadAssignNotificationAsync($admin, $lead)
    {
        try {
            // 检查管理员是否有邮箱
            if (empty($admin->email)) {
                trace('管理员邮箱未设置，跳过邮件通知: admin_id=' . $admin->id, 'info');
                return;
            }
            
            // 仅添加到队列，不立即发送，避免阻塞
            $subject = '新线索指派通知 - ' . $lead->lead_no;
            $body = self::renderLeadAssignTemplate($lead);
            
            \app\model\EmailQueue::create([
                'email_type' => \app\model\EmailQueue::TYPE_LEAD_ASSIGN,
                'recipient_email' => $admin->email,
                'recipient_name' => $admin->nickname,
                'subject' => $subject,
                'body' => $body,
                'related_id' => $lead->id,
                'status' => \app\model\EmailQueue::STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            trace('线索指派邮件已加入队列: admin_id=' . $admin->id . ', lead_id=' . $lead->id . ', email=' . $admin->email, 'info');
            
        } catch (\Exception $e) {
            // 即使队列失败也不影响主流程
            trace('添加邮件到队列失败（不影响主流程）: ' . $e->getMessage(), 'info');
        }
    }
    
    /**
     * 渲染线索指派邮件模板
     */
    private static function renderLeadAssignTemplate($lead)
    {
        $cityName = $lead->city ? $lead->city->name : '';
        $districtName = $lead->district ? $lead->district->name : '';
        
        // 获取系统配置中的管理后台地址
        $adminUrl = env('ADMIN_URL', 'http://localhost:5174');
        $leadDetailUrl = $adminUrl . '/#/lead-follow/' . $lead->id;
        
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $html .= '<div style="background: #667eea; color: white; padding: 20px; text-align: center;">';
        $html .= '<h2 style="margin: 0;">新线索指派通知</h2>';
        $html .= '</div>';
        
        // 紧急提醒和跟进按钮放在最前面
        $html .= '<div style="padding: 20px; background: #f9f9f9;">';
        $html .= '<div style="margin-bottom: 15px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px; text-align: center;">';
        $html .= '<p style="margin: 0 0 15px 0; color: #856404; font-size: 18px; font-weight: bold;">';
        $html .= '⏰ 请在1小时内联系客户并更新跟进情况';
        $html .= '</p>';
        $html .= '<p style="margin: 0 0 15px 0;">';
        $html .= '<a href="' . $leadDetailUrl . '" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">👉 立即跟进此线索</a>';
        $html .= '</p>';
        $html .= '<p style="margin: 0; font-size: 12px; color: #856404;">';
        $html .= '跟进链接：<span style="user-select: all; background: #fff; padding: 5px 10px; border-radius: 3px; display: inline-block; margin-top: 5px; word-break: break-all;">' . htmlspecialchars($leadDetailUrl) . '</span>';
        $html .= '</p>';
        $html .= '</div>';
        
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
        
        $html .= '</div>';
        
        $html .= '<div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">';
        $html .= '<p style="margin: 0;">此邮件由系统自动发送，请勿直接回复。</p>';
        $html .= '<p style="margin: 5px 0 0 0;">如无法点击按钮，请复制以下链接到浏览器：</p>';
        $html .= '<p style="margin: 5px 0 0 0; word-break: break-all;">' . $leadDetailUrl . '</p>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * 发送测试邮件
     */
    public function sendTestEmail($email)
    {
        try {
            // 获取邮件配置
            $config = Db::name('notification_config')->find(1);
            
            if (!$config || !$config['smtp_host']) {
                return ['success' => false, 'error' => '邮件配置未设置'];
            }
            
            if (!$config['email_enabled']) {
                return ['success' => false, 'error' => '邮件通知未启用，请先在配置中启用邮件通知'];
            }
            
            // 验证必要配置
            if (empty($config['smtp_host'])) {
                return ['success' => false, 'error' => 'SMTP服务器地址未设置'];
            }
            if (empty($config['smtp_username'])) {
                return ['success' => false, 'error' => 'SMTP用户名未设置'];
            }
            if (empty($config['smtp_password'])) {
                return ['success' => false, 'error' => 'SMTP密码未设置'];
            }
            
            // QQ邮箱特殊提示
            if (strpos($config['smtp_host'], 'qq.com') !== false) {
                if (strlen($config['smtp_password']) < 16) {
                    return ['success' => false, 'error' => 'QQ邮箱需要使用授权码，不是登录密码。授权码通常是16位字母，请在QQ邮箱设置中生成授权码'];
                }
            }
            
            $mail = new PHPMailer(true);
            
            // SMTP配置
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            
            // SSL/TLS配置
            if ($config['smtp_secure']) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            }
            $mail->Port = $config['smtp_port'] ?: 465;
            
            // 超时和SSL设置 - 关键：禁用SSL证书验证
            $mail->Timeout = 30;
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
            $mail->addAddress($email);
            
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = '测试邮件 - 邮件配置测试';
            
            $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
            $body .= '<h2 style="color: #667eea;">邮件配置测试</h2>';
            $body .= '<p>这是一封测试邮件，用于验证邮件配置是否正确。</p>';
            $body .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            $body .= '<p style="margin: 5px 0;"><strong>发送时间：</strong>' . date('Y-m-d H:i:s') . '</p>';
            $body .= '<p style="margin: 5px 0;"><strong>SMTP服务器：</strong>' . htmlspecialchars($config['smtp_host']) . '</p>';
            $body .= '<p style="margin: 5px 0;"><strong>发件人：</strong>' . htmlspecialchars($config['from_email']) . '</p>';
            $body .= '</div>';
            $body .= '<p style="color: #28a745;">✓ 如果您收到此邮件，说明邮件配置正确！</p>';
            $body .= '</div>';
            
            $mail->Body = $body;
            
            $mail->send();
            
            // 记录成功日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_TEST,
                'recipient_email' => $email,
                'subject' => $mail->Subject,
                'body' => $body,
                'status' => EmailLog::STATUS_SENT,
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'message' => '测试邮件发送成功，请检查收件箱'];
            
        } catch (Exception $e) {
            // 记录失败日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_TEST,
                'recipient_email' => $email,
                'subject' => '测试邮件 - 邮件配置测试',
                'status' => EmailLog::STATUS_FAILED,
                'error_msg' => $e->getMessage(),
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
            trace('测试邮件发送失败: ' . $e->getMessage(), 'error');
            return ['success' => false, 'error' => '发送失败：' . $e->getMessage()];
        }
    }
    
}

