<?php
// +----------------------------------------------------------------------
// | Cookie设置
// +----------------------------------------------------------------------
return [
    // cookie 保存时间（0表示浏览器关闭时失效，也可以设置为7200秒=2小时）
    'expire'    => 7200,
    // cookie 保存路径
    'path'      => '/',
    // cookie 有效域名（留空表示当前域名）
    'domain'    => '',
    //  cookie 启用安全传输（HTTPS时设为true）
    'secure'    => false,
    // httponly设置（设为false以便JavaScript可以访问，但这会降低安全性）
    'httponly'  => false,
    // 是否使用 setcookie
    'setcookie' => true,
    // samesite 设置（设为空字符串或'none'以支持跨域，'lax'/'strict'会限制跨域）
    'samesite'  => '',
];
