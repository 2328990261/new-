<?php
// +----------------------------------------------------------------------
// | 会话设置 - 30天免登录
// +----------------------------------------------------------------------

// 30天 = 2592000秒
$thirtyDays = 2592000;

// 设置 PHP Session 垃圾回收时间（30天）
// 这个设置确保服务器端的 session 文件不会被过早清理
ini_set('session.gc_maxlifetime', $thirtyDays);

// 设置 session cookie 的默认有效期（30天）
ini_set('session.cookie_lifetime', $thirtyDays);

// 设置 session 保存路径到 runtime/session 目录（避免被系统清理）
$sessionPath = dirname(__DIR__) . '/runtime/session';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

// 禁用 session 的严格模式，避免 session ID 被拒绝
ini_set('session.use_strict_mode', 0);

// 确保 session cookie 在浏览器关闭后仍然有效
ini_set('session.cookie_httponly', 1);

return [
    // session name
    'name'           => 'PHPSESSID',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => 'file',
    // 存储连接标识 当type使用cache的时候有效
    'store'          => null,
    // 过期时间（30天）- 实际由 Auth 控制器中的 session_set_cookie_params 控制
    'expire'         => $thirtyDays,
    // 前缀
    'prefix'         => 'think',
    // ⚠️ 禁用自动启动，使用原生 PHP Session，在 Auth 控制器中手动管理
    'auto_start'     => false,
    // Session 垃圾回收概率（1/10000，大幅降低GC频率，避免session被过早清理）
    'gc_probability' => 1,
    'gc_divisor'     => 10000,
];
