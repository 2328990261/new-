<?php
/**
 * 原生PHP Session测试
 * 不使用ThinkPHP，直接用PHP的session
 */

// 设置CORS
header('Access-Control-Allow-Origin: http://localhost:5174');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// 获取动作
$action = $_GET['action'] ?? 'get';

if ($action === 'set') {
    // 启动原生Session
    session_start();
    
    // 设置Session数据
    $_SESSION['test_key'] = 'test_value_' . time();
    $_SESSION['test_time'] = date('Y-m-d H:i:s');
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_nickname'] = '测试管理员';
    
    echo json_encode([
        'success' => true,
        'message' => '原生Session已设置',
        'session_id' => session_id(),
        'session_name' => session_name(),
        'session_status' => session_status(),
        'data' => $_SESSION
    ], JSON_UNESCAPED_UNICODE);
    
} elseif ($action === 'get') {
    // 启动原生Session
    session_start();
    
    echo json_encode([
        'success' => true,
        'message' => '原生Session读取',
        'session_id' => session_id(),
        'session_name' => session_name(),
        'session_status' => session_status(),
        'has_data' => !empty($_SESSION),
        'data' => $_SESSION ?? []
    ], JSON_UNESCAPED_UNICODE);
    
} else {
    echo json_encode([
        'success' => false,
        'error' => '未知操作，使用 ?action=set 或 ?action=get'
    ], JSON_UNESCAPED_UNICODE);
}






