<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;

/**
 * 邮箱日志管理控制器（通知模块子模块）
 */
class EmailLog extends BaseController
{
    /**
     * 获取邮箱日志列表
     */
    public function getList()
    {
        try {
            $page = (int)$this->request->get('page', 1);
            $limit = (int)$this->request->get('limit', 20);
            $emailType = $this->request->get('email_type', '');
            $status = $this->request->get('status', '');
            $email = $this->request->get('email', '');
            $startDate = $this->request->get('start_date', '');
            $endDate = $this->request->get('end_date', '');
            
            // 构建WHERE条件
            $where = '1=1';
            
            if ($emailType !== '') {
                $where .= " AND email_type = '" . addslashes($emailType) . "'";
            }
            
            if ($status !== '') {
                $where .= " AND status = '" . addslashes($status) . "'";
            }
            
            if ($email) {
                $where .= " AND recipient_email LIKE '%" . addslashes($email) . "%'";
            }
            
            if ($startDate) {
                $where .= " AND sent_at >= '" . addslashes($startDate) . " 00:00:00'";
            }
            
            if ($endDate) {
                $where .= " AND sent_at <= '" . addslashes($endDate) . " 23:59:59'";
            }
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM fa_email_queue WHERE {$where}";
            $countResult = Db::query($countSql);
            $total = $countResult[0]['total'] ?? 0;
            
            // 查询列表
            $offset = ($page - 1) * $limit;
            $sql = "SELECT id, email_type, recipient_email, recipient_name, subject, related_id, status, error_message, sent_at, created_at 
                    FROM fa_email_queue 
                    WHERE {$where}
                    ORDER BY created_at DESC 
                    LIMIT {$limit} OFFSET {$offset}";
            
            $list = Db::query($sql);
            
            // 处理返回数据
            $result = [];
            foreach ($list as $item) {
                $result[] = $this->formatLogItem($item);
            }
            
            return json([
                'success' => true,
                'data' => $result,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * 获取日志详情
     */
    public function getDetail($id = 0)
    {
        try {
            $id = (int)($id ?: $this->request->get('id', 0));
            
            if (!$id) {
                return json(['success' => false, 'error' => '缺少日志ID']);
            }
            
            $sql = "SELECT * FROM fa_email_queue WHERE id = {$id} LIMIT 1";
            $result = Db::query($sql);
            
            if (empty($result)) {
                return json(['success' => false, 'error' => '日志不存在']);
            }
            
            $data = $this->formatLogItem($result[0], true);
            
            return json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * 重发邮件
     */
    public function resend($id = 0)
    {
        try {
            $id = (int)($id ?: $this->request->post('id', 0));
            
            if (!$id) {
                return json(['success' => false, 'error' => '缺少日志ID']);
            }
            
            // 获取邮件记录
            $sql = "SELECT * FROM fa_email_queue WHERE id = {$id} LIMIT 1";
            $result = Db::query($sql);
            
            if (empty($result)) {
                return json(['success' => false, 'error' => '日志不存在']);
            }
            
            $log = $result[0];
            
            // 只有失败、待发送、发送中的邮件才能重发
            if (!in_array($log['status'], ['failed', 'pending', 'sending'])) {
                return json(['success' => false, 'error' => '该邮件状态不支持重发']);
            }
            
            // 获取邮件配置
            $config = Db::name('notification_config')->find(1);
            if (!$config || !$config['email_enabled']) {
                return json(['success' => false, 'error' => '邮件通知未启用']);
            }
            
            if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
                return json(['success' => false, 'error' => 'SMTP配置不完整']);
            }
            
            // 更新状态为发送中
            Db::execute("UPDATE fa_email_queue SET status = 'sending' WHERE id = {$id}");
            
            // 使用PHPMailer发送
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'] ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            // 端口默认值需要与加密方式匹配：SMTPS(SSL)=465，STARTTLS(TLS)=587
            $port = (int)($config['smtp_port'] ?? 0);
            if ($port <= 0) {
                $port = $config['smtp_secure'] ? 465 : 587;
            }
            $mail->Port = $port;
            $mail->Timeout = 10;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // 发件人兜底：避免 From 与登录账号不一致导致 DATA 阶段拒收
            $fromEmail = trim((string)($config['from_email'] ?? ''));
            if ($fromEmail === '') {
                $fromEmail = trim((string)($config['smtp_username'] ?? ''));
            }
            $mail->setFrom($fromEmail, $config['from_name'] ?: '家教信息平台');
            $mail->Sender = $fromEmail;
            $mail->addAddress($log['recipient_email'], $log['recipient_name'] ?? '');
            $mail->isHTML(true);
            $mail->Subject = $log['subject'];
            $mail->Body = $log['body'];
            
            $mail->send();
            
            // 发送成功
            Db::execute("UPDATE fa_email_queue SET status = 'sent', sent_at = NOW(), error_message = NULL WHERE id = {$id}");
            return json(['success' => true, 'message' => '邮件重发成功']);
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            // PHPMailer异常
            if (isset($id) && $id > 0) {
                $errorMsg = addslashes($e->getMessage());
                Db::execute("UPDATE fa_email_queue SET status = 'failed', error_message = '{$errorMsg}', retry_count = retry_count + 1 WHERE id = {$id}");
            }
            return json(['success' => false, 'error' => '邮件发送失败: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            // 其他异常
            if (isset($id) && $id > 0) {
                $errorMsg = addslashes($e->getMessage());
                Db::execute("UPDATE fa_email_queue SET status = 'failed', error_message = '{$errorMsg}', retry_count = retry_count + 1 WHERE id = {$id}");
            }
            return json(['success' => false, 'error' => '邮件发送失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取统计数据
     */
    public function getStatistics()
    {
        try {
            $startDate = $this->request->get('start_date', date('Y-m-d', strtotime('-7 days')));
            $endDate = $this->request->get('end_date', date('Y-m-d'));
            
            // 使用原生SQL查询，避免ORM的复杂性
            $sql = "
                SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
                FROM fa_email_queue
                WHERE sent_at IS NOT NULL 
                  AND sent_at >= '{$startDate} 00:00:00'
                  AND sent_at <= '{$endDate} 23:59:59'
                  AND status IN ('sent', 'failed')
            ";
            
            $stats = Db::query($sql);
            $totalCount = (int)($stats[0]['total_count'] ?? 0);
            $successCount = (int)($stats[0]['success_count'] ?? 0);
            $failedCount = (int)($stats[0]['failed_count'] ?? 0);
            
            // 待发送数量
            $pendingSql = "SELECT COUNT(*) as count FROM fa_email_queue WHERE status = 'pending'";
            $pendingResult = Db::query($pendingSql);
            $pendingCount = (int)($pendingResult[0]['count'] ?? 0);
            
            // 成功率
            $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 2) : 0;
            
            // 按类型统计
            $typeSql = "
                SELECT email_type, COUNT(*) as count
                FROM fa_email_queue
                WHERE sent_at >= '{$startDate} 00:00:00'
                  AND sent_at <= '{$endDate} 23:59:59'
                  AND status IN ('sent', 'failed')
                GROUP BY email_type
            ";
            $typeStats = Db::query($typeSql);
            
            // 每日发送趋势
            $dailySql = "
                SELECT DATE(sent_at) as date, 
                       COUNT(*) as total,
                       SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as success,
                       SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM fa_email_queue
                WHERE sent_at IS NOT NULL 
                  AND sent_at >= '{$startDate} 00:00:00'
                  AND sent_at <= '{$endDate} 23:59:59'
                  AND status IN ('sent', 'failed')
                GROUP BY DATE(sent_at)
                ORDER BY date ASC
            ";
            $dailyStats = Db::query($dailySql);
            
            return json([
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'pending_count' => $pendingCount,
                    'success_rate' => $successRate,
                    'type_stats' => $typeStats ?: [],
                    'daily_stats' => $dailyStats ?: []
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * 删除日志
     */
    public function delete()
    {
        try {
            $id = (int)$this->request->post('id', 0);
            
            if (!$id) {
                return json(['success' => false, 'error' => '缺少日志ID']);
            }
            
            Db::execute("DELETE FROM fa_email_queue WHERE id = {$id}");
            
            return json(['success' => true, 'message' => '删除成功']);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * 批量删除日志
     */
    public function batchDelete()
    {
        try {
            $ids = $this->request->post('ids/a', []);
            
            if (empty($ids)) {
                return json(['success' => false, 'error' => '请选择要删除的日志']);
            }
            
            $idsStr = implode(',', array_map('intval', $ids));
            Db::execute("DELETE FROM fa_email_queue WHERE id IN ({$idsStr})");
            
            return json(['success' => true, 'message' => '批量删除成功']);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * 清理旧日志
     */
    public function cleanOldLogs()
    {
        try {
            $days = (int)$this->request->post('days', 30);
            $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            $result = Db::execute("DELETE FROM fa_email_queue WHERE created_at < '{$date}'");
            
            return json([
                'success' => true,
                'message' => "成功清理旧日志"
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }
    
    /**
     * 格式化日志项
     */
    private function formatLogItem($item, $includeBody = false)
    {
        $typeMap = [
            'lead_assign' => '线索指派通知',
            'booking' => '预约通知',
            'order' => '订单通知',
            'test' => '测试邮件',
        ];
        
        $statusMap = [
            'pending' => '待发送',
            'sending' => '发送中',
            'sent' => '已发送',
            'failed' => '失败',
        ];
        
        $emailType = $item['email_type'] ?? '';
        $status = $item['status'] ?? '';
        
        $data = [
            'id' => $item['id'],
            'email_type' => $emailType,
            'email_type_text' => $typeMap[$emailType] ?? $emailType,
            'recipient_email' => $item['recipient_email'] ?? '',
            'recipient_name' => $item['recipient_name'] ?? '',
            'subject' => $item['subject'] ?? '',
            'related_id' => $item['related_id'] ?? null,
            'status' => $status,
            'status_text' => $statusMap[$status] ?? $status,
            'error_message' => $item['error_message'] ?? '',
            'sent_at' => $item['sent_at'] ?? null,
            'created_at' => $item['created_at'] ?? null,
        ];
        
        if ($includeBody) {
            $data['body'] = $item['body'] ?? '';
            $data['retry_count'] = $item['retry_count'] ?? 0;
        }
        
        return $data;
    }
    
    /**
     * 错误响应
     */
    private function errorResponse(\Exception $e)
    {
        trace('邮箱日志错误: ' . $e->getMessage() . ' 在 ' . $e->getFile() . ':' . $e->getLine(), 'error');
        
        return json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
