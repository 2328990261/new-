<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 检查是否存在管理员会话
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_nickname'])) {
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $_SESSION['admin_id'],
            'nickname' => $_SESSION['admin_nickname']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => '未登录'
    ]);
} 