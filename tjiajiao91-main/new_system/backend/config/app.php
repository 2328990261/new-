<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    'app_host'         => env('app.host', ''),
    'app_namespace'    => '',
    'with_route'       => true,
    'default_app'      => 'index',
    'default_timezone' => 'Asia/Shanghai',

    // 开发环境经常以 /admin/... /api/... 作为“路径前缀”，
    // 但 ThinkPHP 多应用会把第一个 path 段当成应用名，导致 /admin/api/login 被解析为「应用=admin，控制器=Api」而不是命中 route/admin.php 的路由组。
    // 将常用前缀映射回默认应用，让路由按预期匹配（例如 /admin/api/login 命中 Route::group('admin/api', ...)）。
    'app_map'          => [
        'admin' => 'index',
        'api'   => 'index',
    ],
    'domain_bind'      => [],
    'deny_app_list'    => [],

    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    'error_message'    => '页面错误！请稍后再试～',
    'show_error_msg'   => false,
    
    'admin_notification_emails' => env('admin.notification_emails', []),

    /**
     * 订单邮件调试开关（用于线上临时只发给指定邮箱，避免误发）
     * - order.email_debug_only=1 时生效
     * - order.email_debug_target 为收件邮箱（必须是合法 email）
     */
    // 默认关闭；线上临时调试请在 .env 设置：
    // order.email_debug_only=1
    // order.email_debug_target=2328990261@qq.com
    'order_email_debug_only' => (int) env('order.email_debug_only', 0) === 1,
    'order_email_debug_target' => env('order.email_debug_target', ''),
];
