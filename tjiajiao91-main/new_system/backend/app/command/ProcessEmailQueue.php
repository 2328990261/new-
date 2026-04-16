<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\service\EmailService;

/**
 * 处理邮件队列（fa_email_queue）
 *
 * 用法：
 * - php think email:process-queue
 * - php think email:process-queue --limit=50
 */
class ProcessEmailQueue extends Command
{
    protected function configure()
    {
        $this->setName('email:process-queue')
            ->addOption('limit', null, \think\console\input\Option::VALUE_OPTIONAL, '每次处理数量', 20)
            ->setDescription('处理邮件队列并发送待发送邮件');
    }

    protected function execute(Input $input, Output $output)
    {
        $limit = (int)$input->getOption('limit');
        if ($limit <= 0) {
            $limit = 20;
        }

        $config = Db::name('notification_config')->find(1);
        if (!$config || empty($config['email_enabled'])) {
            $output->writeln('<comment>邮件通知未启用，跳过</comment>');
            return 0;
        }

        if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
            $output->writeln('<error>SMTP配置不完整，无法发送</error>');
            return 1;
        }

        $rows = Db::name('email_queue')
            ->where('status', 'pending')
            ->order('created_at', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();

        if (empty($rows)) {
            $output->writeln('<info>没有待发送邮件</info>');
            return 0;
        }

        $service = new EmailService();
        $sent = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            // 抢占：避免并发重复发送
            $affected = Db::name('email_queue')
                ->where('id', $id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'sending',
                    'error_message' => null,
                ]);

            if ($affected <= 0) {
                continue;
            }

            $to = (string)($row['recipient_email'] ?? '');
            $toName = (string)($row['recipient_name'] ?? '');
            $subject = (string)($row['subject'] ?? '');
            $body = (string)($row['body'] ?? '');

            try {
                $service->sendMail($to, $subject, $body, $toName);
                Db::name('email_queue')->where('id', $id)->update([
                    'status' => 'sent',
                    'error_message' => null,
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                Db::name('email_queue')->where('id', $id)->update([
                    'status' => 'failed',
                    'error_message' => mb_substr((string)$e->getMessage(), 0, 500, 'UTF-8'),
                    // 失败也记录一次尝试时间（沿用 sent_at 字段作为“最近一次尝试时间”）
                    'sent_at' => date('Y-m-d H:i:s'),
                    'retry_count' => Db::raw('retry_count + 1'),
                ]);
                $failed++;
            }
        }

        $output->writeln("<info>处理完成：sent={$sent}, failed={$failed}</info>");
        return $failed > 0 ? 1 : 0;
    }
}

