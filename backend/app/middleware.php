<?php
// 全局中间件定义文件
return [
    // CORS跨域支持（必须放在最前面）
    \app\middleware\Cors::class,
    
    // ⚠️ 禁用 ThinkPHP Session 中间件（避免与原生Session冲突）
    // 使用原生 PHP Session 管理，在 Auth 控制器中统一配置
    // \think\middleware\SessionInit::class,
];
