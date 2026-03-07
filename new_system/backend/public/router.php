<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

// 保存原始工作目录
$original_dir = getcwd();

// 获取请求URI
$request_uri = $_SERVER["REQUEST_URI"];
$document_root = $_SERVER["DOCUMENT_ROOT"];

// 处理phpMyAdmin请求
if (strpos($request_uri, '/phpMyAdmin4.8.5') === 0) {
    // 移除查询字符串
    $path = parse_url($request_uri, PHP_URL_PATH);
    
    // 如果是目录请求（以/结尾），加上index.php
    if (substr($path, -1) === '/') {
        $path .= 'index.php';
    }
    
    // 构建完整文件路径
    $file = $document_root . $path;
    
    // 如果文件存在，让PHP处理它
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        
        // 对于PHP文件，需要设置正确的工作目录并执行
        if ($ext === 'php') {
            // 切换到文件所在目录
            chdir(dirname($file));
            
            // 设置正确的SERVER变量
            $_SERVER['SCRIPT_FILENAME'] = $file;
            $_SERVER['SCRIPT_NAME'] = $path;
            $_SERVER['PHP_SELF'] = $path;
            
            // 执行文件
            require $file;
            
            // 恢复原始目录（虽然脚本会结束，但保持良好习惯）
            chdir($original_dir);
            exit;
        } else {
            // 对于其他文件（CSS、JS、图片等），返回false让PHP内置服务器处理
            return false;
        }
    }
    
    // 如果文件不存在，返回404
    header("HTTP/1.0 404 Not Found");
    echo "404 Not Found: " . htmlspecialchars($path);
    exit;
}

// 处理静态文件
if (is_file($document_root . $_SERVER["SCRIPT_NAME"])) {
    return false;
} else {
    // 处理ThinkPHP路由
    $_SERVER["SCRIPT_FILENAME"] = __DIR__ . '/index.php';
    require __DIR__ . "/index.php";
}
