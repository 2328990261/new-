<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\model\Lead;
use app\model\Admin;
use app\service\EmailService;

/**
 * 发送线索跟进提醒邮件命令
 * 
 * 使用方法：
 * php think lead:send-reminders
 * 
 * 定时任务配置（每5分钟执行一次）：
 * 每5分钟: cd /path/to/backend && php think lead:send-reminders >> /var/log/lead-reminders.log 2>&1
 */
class SendLeadReminders extends Command
{
    protected function configure()
    {
        $this->setName('lead:send-reminders')
            ->setDescription('发送线索跟进提醒邮件');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('========================================');
        $output->writeln('开始检查需要发送提醒的线索...');
        $output->writeln('执行时间: ' . date('Y-m-d H:i:s'));
        $output->writeln('========================================');
        
        try {
            // 查询需要提醒的线索
            // 条件：
            // 1. reminder_time <= 当前时间（已到提醒时间）
            // 2. reminder_sent = 0（未发送）
            // 3. status = '跟进中'（状态为跟进中）
            // 4. assigned_admin_id > 0（已分配客服）
            $leads = Lead::where('reminder_time', '<=', date('Y-m-d H:i:s'))
                ->where('reminder_sent', '=', 0)
                ->where('status', '=', '跟进中')
                ->where('assigned_admin_id', '>', 0)
                ->with(['city', 'district'])  // 预加载关联数据
                ->select();

            if ($leads->isEmpty()) {
                $output->writeln('<info>没有需要发送提醒的线索</info>');
                return 0;
            }

            $output->writeln('<info>找到 ' . count($leads) . ' 条需要发送提醒的线索</info>');
            $output->writeln('');

            $successCount = 0;
            $failCount = 0;
            $skipCount = 0;

            foreach ($leads as $lead) {
                $output->write('处理线索 ' . $lead->lead_no . ' (ID: ' . $lead->id . ')...');
                
                try {
                    // 获取负责客服
                    $admin = Admin::find($lead->assigned_admin_id);
                    
                    if (!$admin) {
                        $output->writeln('<error>失败 - 客服不存在</error>');
                        $skipCount++;
                        continue;
                    }
                    
                    if (empty($admin->email)) {
                        $output->writeln('<comment>跳过 - 客服未设置邮箱</comment>');
                        $skipCount++;
                        // 标记为已发送，避免重复检查
                        $lead->reminder_sent = 1;
                        $lead->save();
                        continue;
                    }
                    
                    // 发送邮件
                    EmailService::sendLeadReminderNotification($admin, $lead);
                    
                    // 标记为已发送
                    $lead->reminder_sent = 1;
                    $lead->save();
                    
                    $output->writeln('<info>成功 - 已发送至 ' . $admin->email . '</info>');
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $output->writeln('<error>失败 - ' . $e->getMessage() . '</error>');
                    $failCount++;
                    
                    // 记录错误日志
                    trace('发送线索提醒邮件失败: lead_id=' . $lead->id . ', error=' . $e->getMessage(), 'error');
                }
            }

            $output->writeln('');
            $output->writeln('========================================');
            $output->writeln('执行完成！');
            $output->writeln('成功: ' . $successCount . ' 封');
            $output->writeln('失败: ' . $failCount . ' 封');
            $output->writeln('跳过: ' . $skipCount . ' 封');
            $output->writeln('========================================');
            
            return 0;
            
        } catch (\Exception $e) {
            $output->writeln('<error>执行失败: ' . $e->getMessage() . '</error>');
            trace('SendLeadReminders命令执行失败: ' . $e->getMessage(), 'error');
            return 1;
        }
    }
}
