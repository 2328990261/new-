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
            ->addOption('sleep-ms', null, \think\console\input\Option::VALUE_OPTIONAL, '每封邮件发送间隔(毫秒)，用于限速', 0)
            ->addOption('lock-key', null, \think\console\input\Option::VALUE_OPTIONAL, '防重入锁标识（同一锁标识同一时间只允许一个进程运行）', 'email_queue')
            ->setDescription('处理邮件队列并发送待发送邮件');
    }

    protected function execute(Input $input, Output $output)
    {
        $limit = (int)$input->getOption('limit');
        if ($limit <= 0) {
            $limit = 20;
        }
        $sleepMs = (int)$input->getOption('sleep-ms');
        if ($sleepMs < 0) {
            $sleepMs = 0;
        }
        $lockKey = trim((string)$input->getOption('lock-key'));
        if ($lockKey === '') {
            $lockKey = 'email_queue';
        }

        // 防重入锁：避免宝塔计划任务叠加/并发跑导致瞬时猛发
        $lockFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'think_email_process_' . md5($lockKey) . '.lock';
        $lockFp = @fopen($lockFile, 'c+');
        if ($lockFp === false) {
            $output->writeln('<comment>无法创建锁文件，继续执行（不建议）</comment>');
        } else {
            if (!@flock($lockFp, LOCK_EX | LOCK_NB)) {
                $output->writeln('<info>已有发送进程在运行，跳过本次执行</info>');
                @fclose($lockFp);
                return 0;
            }
            // 写入 PID 便于排查
            @ftruncate($lockFp, 0);
            @fwrite($lockFp, (string)getmypid());
            @fflush($lockFp);
        }

        $config = Db::name('notification_config')->find(1);
        if (!$config || empty($config['email_enabled'])) {
            $output->writeln('<comment>邮件通知未启用，跳过</comment>');
            if ($lockFp) {
                @flock($lockFp, LOCK_UN);
                @fclose($lockFp);
            }
            return 0;
        }

        if (empty($config['smtp_host']) || empty($config['smtp_username']) || empty($config['smtp_password'])) {
            $output->writeln('<error>SMTP配置不完整，无法发送</error>');
            if ($lockFp) {
                @flock($lockFp, LOCK_UN);
                @fclose($lockFp);
            }
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
            if ($lockFp) {
                @flock($lockFp, LOCK_UN);
                @fclose($lockFp);
            }
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

            // 限速：每封之间 sleep，避免触发 SMTP 风控/限流
            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $output->writeln("<info>处理完成：sent={$sent}, failed={$failed}</info>");
        if ($lockFp) {
            @flock($lockFp, LOCK_UN);
            @fclose($lockFp);
        }
        return $failed > 0 ? 1 : 0;
    }
}

