<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\model\Admin;
use app\model\Lead as LeadModel;
use app\service\EmailService;

/**
 * 异步发送线索指派邮件命令
 */
class SendLeadAssignEmail extends Command
{
    protected function configure()
    {
        $this->setName('email:send-lead-assign')
            ->addArgument('admin_id', \think\console\input\Argument::REQUIRED, '管理员ID')
            ->addArgument('lead_id', \think\console\input\Argument::REQUIRED, '线索ID')
            ->setDescription('异步发送线索指派邮件通知');
    }

    protected function execute(Input $input, Output $output)
    {
        $adminId = $input->getArgument('admin_id');
        $leadId = $input->getArgument('lead_id');
        
        if (!$adminId || !$leadId) {
            $output->writeln('<error>参数错误：需要 admin_id 和 lead_id</error>');
            return 1;
        }
        
        try {
            // 查找管理员
            $admin = Admin::find($adminId);
            if (!$admin) {
                $output->writeln('<error>管理员不存在: ' . $adminId . '</error>');
                return 1;
            }
            
            // 查找线索（包含关联数据）
            $lead = LeadModel::with(['city', 'district'])->find($leadId);
            if (!$lead) {
                $output->writeln('<error>线索不存在: ' . $leadId . '</error>');
                return 1;
            }
            
            // 发送邮件
            EmailService::sendLeadAssignNotification($admin, $lead);
            
            $output->writeln('<info>邮件发送成功: ' . $admin->email . '</info>');
            return 0;
            
        } catch (\Exception $e) {
            $output->writeln('<error>邮件发送失败: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}
