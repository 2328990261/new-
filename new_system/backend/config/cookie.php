<?php
// +----------------------------------------------------------------------
// | Cookie设置
// +----------------------------------------------------------------------
return [
    // cookie 保存时间（86400秒 = 24小时）
    'expire'    => 86400,
    // cookie 保存路径
    'path'      => '/',
    // cookie 有效域名（留空表示当前域名）
    'domain'    => '',
    //  cookie 启用安全传输（HTTPS环境必须设为true）
    'secure'    => true,
    // httponly设置（设为true增加安全性）
    'httponly'  => true,
    // 是否使用 setcookie
    'setcookie' => true,
    // samesite 设置（Lax模式平衡安全和可用性）
    'samesite'  => 'Lax',
];
