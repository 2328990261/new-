<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'email:send-lead-assign' => 'app\command\SendLeadAssignEmail',
        'email:process-queue' => 'app\command\ProcessEmailQueue',
        'lead:send-reminders' => 'app\command\SendLeadReminders',
        'debug:which-class' => 'app\command\WhichClass',
    ],
];
