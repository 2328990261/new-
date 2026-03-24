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
            
            // 构建邮件内容（HTML + 纯文本副本）
            $tutorContent = self::generateTutorContent($order);
            $htmlBody = self::renderBookingEmailHtml($order, $tutorContent);
            $mail->Body = $htmlBody;
            $mail->AltBody = self::buildBookingEmailPlain($order, $tutorContent);
            
            $mail->send();
            
            // 记录成功日志
            EmailLog::log([
                'email_type' => EmailLog::TYPE_BOOKING,
                'recipient_email' => $admin->email,
                'recipient_name' => isset($admin->nickname) ? $admin->nickname : null,
                'subject' => $mail->Subject,
                'body' => $htmlBody,
                'related_id' => $order->id,
                'status' => EmailLog::STATUS_SENT,
                'send_time' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            // 记录失败日志
            try {
                EmailLog::log([
                    'email_type' => EmailLog::TYPE_BOOKING,
                    'recipient_email' => isset($admin->email) ? $admin->email : null,
                    'recipient_name' => isset($admin->nickname) ? $admin->nickname : null,
                    'subject' => '新的家教需求预约通知 - ' . $order->order_no,
                    'related_id' => $order->id,
                    'status' => EmailLog::STATUS_FAILED,
                    'error_msg' => $e->getMessage(),
                    'send_time' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $logError) {
                // 日志记录失败也不影响
                trace('邮件日志记录失败: ' . $logError->getMessage(), 'error');
            }
            
            trace('预约通知邮件发送失败: ' . $e->getMessage(), 'error');
            // 不再抛出异常，避免影响订单创建流程
            return false;
        } catch (\Exception $e) {
            // 捕获所有其他异常
            try {
                EmailLog::log([
                    'email_type' => EmailLog::TYPE_BOOKING,
                    'recipient_email' => isset($admin->email) ? $admin->email : null,
                    'recipient_name' => isset($admin->nickname) ? $admin->nickname : null,
                    'subject' => '新的家教需求预约通知 - ' . $order->order_no,
                    'related_id' => $order->id,
                    'status' => EmailLog::STATUS_FAILED,
                    'error_msg' => $e->getMessage(),
                    'send_time' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $logError) {
                trace('邮件日志记录失败: ' . $logError->getMessage(), 'error');
            }
            
            trace('预约通知邮件发送失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 预约通知邮件纯文本（支持「显示纯文本」时整封复制）
     */
    private static function buildBookingEmailPlain($order, $tutorContent)
    {
        $teacherName = '';
        if ($order->teacher_id) {
            try {
                $t = \app\model\Teacher::find($order->teacher_id);
                if ($t) {
                    $teacherName = $t->name;
                }
            } catch (\Exception $e) {
            }
        }
        $lines = [
            '【新的家教需求预约】',
            '订单号：' . ($order->order_no ?: ''),
            '称呼：' . ($order->parent_name ?: ''),
            '学生昵称：' . ($order->student_name ?: ''),
            '联系电话：' . ($order->parent_contact ?: ''),
            '预约教师：' . ($teacherName ?: '-'),
            '预约渠道：' . ($order->booking_channel ?: 'H5'),
            '提交时间：' . ($order->create_time ?: date('Y-m-d H:i:s')),
            '',
            '【家教单内容】',
            $tutorContent,
        ];
        if (!empty($order->remark)) {
            $lines[] = '';
            $lines[] = '备注：' . $order->remark;
        }
        $lines[] = '';
        $lines[] = '请登录管理后台「我的预约」查看并审核。';
        return implode("\n", $lines);
    }

    /**
     * 渲染预约通知邮件 HTML
     */
    private static function renderBookingEmailHtml($order, $tutorContent)
    {
        try {
            $teacherName = '';
            if ($order->teacher_id) {
                try {
                    $teacher = \app\model\Teacher::find($order->teacher_id);
                    if ($teacher) {
                        $teacherName = $teacher->name;
                    }
                } catch (\Exception $e) {
                }
            }

            $phone = trim((string) ($order->parent_contact ?: ''));
            $orderNo = trim((string) ($order->order_no ?: ''));
            $html = '<div style="font-family: Arial, \'PingFang SC\', \'Microsoft YaHei\', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
            $html .= '<h2 style="color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">新的家教需求预约</h2>';
            $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            $html .= '<p style="margin: 5px 0;"><strong>订单号：</strong>' . htmlspecialchars($orderNo) . '</p>';
            $html .= '<p style="margin: 5px 0;"><strong>称呼：</strong>' . htmlspecialchars($order->parent_name ?: '') . '</p>';
            $html .= '<p style="margin: 5px 0;"><strong>学生昵称：</strong>' . htmlspecialchars($order->student_name ?: '') . '</p>';
            if ($phone !== '') {
                $html .= '<p style="margin: 5px 0;"><strong>联系电话：</strong><a href="tel:' . htmlspecialchars(preg_replace('/\s+/', '', $phone)) . '" style="color:#2563eb;">' . htmlspecialchars($phone) . '</a></p>';
            } else {
                $html .= '<p style="margin: 5px 0;"><strong>联系电话：</strong>-</p>';
            }
            $html .= '<p style="margin: 5px 0;"><strong>预约教师：</strong>' . htmlspecialchars($teacherName ?: '-') . '</p>';
            $html .= '<p style="margin: 5px 0;"><strong>预约渠道：</strong>' . htmlspecialchars($order->booking_channel ?: 'H5') . '</p>';
            $html .= '<p style="margin: 5px 0;"><strong>提交时间：</strong>' . ($order->create_time ?: date('Y-m-d H:i:s')) . '</p>';
            $html .= '</div>';
            $html .= '<h3 style="color: #666;">家教单内容</h3>';
            $html .= '<div style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">';
            $html .= '<pre style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; font-size: 14px; line-height: 1.8; white-space: pre-wrap; word-break: break-all; color: #303133; margin: 0;-webkit-user-select:text;user-select:text;">' . htmlspecialchars($tutorContent) . '</pre>';
            $html .= '</div>';
            if (!empty($order->remark)) {
                $html .= '<div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 3px;">';
                $html .= '<p style="margin: 0; color: #1e40af;"><strong>备注：</strong>' . nl2br(htmlspecialchars($order->remark)) . '</p>';
                $html .= '</div>';
            }
            $html .= '<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">';
            $html .= '<p style="margin: 0; color: #856404;">请登录管理后台的「我的预约」模块查看并审核此订单。</p>';
            $html .= '</div>';
            $html .= '<div style="margin-top: 20px; text-align: center; color: #999; font-size: 12px;">';
            $html .= '<p>此邮件由系统自动发送，请勿回复。</p>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;
        } catch (\Exception $e) {
            trace('渲染邮件模板失败: ' . $e->getMessage(), 'error');
            return '<div style="font-family: Arial, sans-serif; padding: 20px;"><h2>新的家教需求预约</h2><p>订单号：' . htmlspecialchars($order->order_no ?: '') . '</p><p>请登录管理后台查看详情。</p></div>';
        }
    }
    
    /**
     * 合并城市区域与详细地址，避免标题里「省市区 + 整段地址」重复一段
     */
    private static function mergeCityAreaAndAddress($cityArea, $address)
    {
        $a = trim(preg_replace('/\s+/u', ' ', (string) $cityArea));
        $b = trim(preg_replace('/\s+/u', ' ', (string) $address));
        if ($b === '') {
            return $a !== '' ? $a : '';
        }
        if ($a === '') {
            return $b;
        }
        if (mb_strpos($b, $a) === 0) {
            return $b;
        }
        $ac = preg_replace('/\s+/u', '', $a);
        $bc = preg_replace('/\s+/u', '', $b);
        if ($ac !== '' && mb_strpos($bc, $ac) === 0) {
            return $b;
        }

        return trim($a . ' ' . $b);
    }

    /**
     * 生成家教单内容（与前端管理端展示逻辑一致）
     */
    private static function generateTutorContent($order)
    {
        try {
            // 仅从省市区 ID 解析城市区域（不再用地址首段冒充城市区，避免与整段地址重复）
            $cityAreaDb = '';
            if ($order->city_id) {
                try {
                    $city = \app\model\City::find($order->city_id);
                    if ($city) {
                        $cityAreaDb .= $city->name;
                    }
                } catch (\Exception $e) {
                    trace('获取城市信息失败: ' . $e->getMessage(), 'warning');
                }
            }
            if ($order->district_id) {
                try {
                    $district = \app\model\District::find($order->district_id);
                    if ($district) {
                        $cityAreaDb .= ($cityAreaDb !== '' ? ' ' : '') . $district->name;
                    }
                } catch (\Exception $e) {
                    trace('获取区域信息失败: ' . $e->getMessage(), 'warning');
                }
            }

            $address = trim(preg_replace('/\s+/u', ' ', (string) ($order->address ?: '')));
            if ($cityAreaDb === '') {
                $locationLine = $address !== '' ? $address : '';
            } else {
                $locationLine = self::mergeCityAreaAndAddress($cityAreaDb, $address);
            }
            if ($locationLine === '') {
                $locationLine = '-';
            }

            $grade = $order->grade ?: '';
            $subject = $order->subject ?: '';
            
            // 学生情况 - 包含性别和学生情况描述
            $studentGender = $order->student_gender ?: '';
            $studentInfo = $order->student_info ?: '';
            
            // 时间频率
            $frequency = $order->frequency ?: '';
            $duration = $order->duration ?: '';
            
            // 时薪范围
            $salary = $order->salary ?: '';
            if (empty($salary) && $order->budget_min && $order->budget_max) {
                $salary = $order->budget_min . '-' . $order->budget_max . '元/小时';
            }
            
            // 老师要求
            $teacherType = $order->teacher_type ?: '';
            $teacherGender = $order->teacher_gender ?: '';
            
            $content = "【{$locationLine} {$grade} {$subject}】\n";
            $content .= "【学生情况】{$studentGender}" . ($studentGender && $studentInfo ? '，' : '') . "{$studentInfo}\n";
            $content .= "【时间频率】{$frequency}" . ($frequency && $duration ? '，' : '') . "{$duration}\n";
            $content .= "【时薪范围】{$salary}\n";
            $content .= "【老师要求】{$teacherType}" . ($teacherType && $teacherGender ? '，' : '') . "{$teacherGender}\n";
            $content .= "【家长称呼】{$order->parent_name}\n";
            $content .= "【联系电话】{$order->parent_contact}";
            
            return $content;
        } catch (\Exception $e) {
            trace('生成家教单内容失败: ' . $e->getMessage(), 'error');
            // 返回基本信息
            return "订单号：{$order->order_no}\n学员年级：{$order->grade}\n辅导科目：{$order->subject}\n家长称呼：{$order->parent_name}\n联系方式：{$order->parent_contact}";
        }
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
                'recipient_name' => isset($admin->nickname) ? $admin->nickname : null,
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
                'recipient_email' => isset($admin->email) ? $admin->email : null,
                'recipient_name' => isset($admin->nickname) ? $admin->nickname : null,
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

