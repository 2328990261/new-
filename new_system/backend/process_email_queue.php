<?php
/**
 * 邮件队列处理脚本
 * 用法：php process_email_queue.php
 * 建议：使用Windows计划任务每分钟执行一次
 */

require __DIR__ . '/vendor/autoload.php';

use app\model\EmailQueue;
use think\facade\Db;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$app = new think\App();
$app->initialize();

// 设置最大执行时间
set_time_limit(300); // 5分钟

echo "[" . date('Y-m-d H:i:s') . "] 开始处理邮件队列\n";

try {
    // 获取邮件配置
    $config = Db::name('notification_config')->find(1);
    
    if (!$config || !$config['email_enabled']) {
        echo "邮件通知未启用，退出\n";
        exit(0);
    }
    
    if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
        echo "SMTP配置不完整，退出\n";
        exit(0);
    }
    
    // 获取待发送的邮件（最多10封）
    $emails = EmailQueue::where('status', EmailQueue::STATUS_PENDING)
        ->where('retry_count', '<', 3) // 最多重试3次
        ->order('created_at', 'asc')
        ->limit(10)
        ->select();
    
    if ($emails->isEmpty()) {
        echo "没有待发送的邮件\n";
        exit(0);
    }
    
    echo "找到 " . count($emails) . " 封待发送邮件\n";
    
    // 创建PHPMailer实例（复用连接）
    $mail = new PHPMailer(true);
    
    // 配置SMTP
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    
    if ($config['smtp_secure']) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port = $config['smtp_port'] ?: 465;
    
    $mail->Timeout = 5; // 缩短超时时间到5秒
    $mail->SMTPKeepAlive = true; // 保持连接
    $mail->SMTPDebug = 0; // 关闭调试输出
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    // 设置脚本超时时间
    set_time_limit(60); // 1分钟
    
    $mail->setFrom($config['from_email'], $config['from_name'] ?: '家教信息平台');
    $mail->isHTML(true);
    
    $successCount = 0;
    $failCount = 0;
    
    // 逐个发送
    foreach ($emails as $email) {
        try {
            // 更新状态为发送中
            $email->status = EmailQueue::STATUS_SENDING;
            $email->save();
            
            // 清除之前的收件人
            $mail->clearAddresses();
            
            // 设置收件人
            $mail->addAddress($email->recipient_email, $email->recipient_name);
            
            // 设置邮件内容
            $mail->Subject = $email->subject;
            $mail->Body = $email->body;
            
            // 发送
            $mail->send();
            
            // 更新状态为已发送
            $email->status = EmailQueue::STATUS_SENT;
            $email->sent_at = date('Y-m-d H:i:s');
            $email->save();
            
            $successCount++;
            echo "✓ 发送成功: {$email->recipient_email} - {$email->subject}\n";
            
        } catch (Exception $e) {
            // 发送失败
            $email->status = EmailQueue::STATUS_FAILED;
            $email->retry_count += 1;
            $email->error_message = $e->getMessage();
            $email->save();
            
            $failCount++;
            echo "✗ 发送失败: {$email->recipient_email} - {$e->getMessage()}\n";
            
            // 如果是认证错误，停止继续发送
            if (strpos($e->getMessage(), 'authenticate') !== false) {
                echo "SMTP认证失败，停止处理\n";
                break;
            }
        }
        
        // 短暂延迟，避免发送过快
        usleep(100000); // 0.1秒
    }
    
    // 关闭连接
    $mail->smtpClose();
    
    echo "\n处理完成: 成功 {$successCount} 封，失败 {$failCount} 封\n";
    echo "[" . date('Y-m-d H:i:s') . "] 结束\n";
    
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
