<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '无效的请求方法']);
    exit;
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['username']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => '请提供用户名和密码']);
    exit;
}

try {
    // 数据库连接
    $conn = new mysqli('123.207.52.46', 'myjiajiao', 'jE2se7DGe5HfE6zL', 'myjiajiao');
    
    if ($conn->connect_error) {
        throw new Exception('数据库连接失败: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    // 防SQL注入
    $username = $conn->real_escape_string($data['username']);
    $password = $data['password'];
    
    // 查询用户
    $sql = "SELECT id, nickname, password FROM fa_admin WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // 验证密码
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
        exit;
    }
    
    // 启动会话
    session_start();
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_nickname'] = $user['nickname'];
    
    echo json_encode([
        'success' => true,
        'message' => '登录成功',
        'data' => [
            'id' => $user['id'],
            'nickname' => $user['nickname']
        ]
    ]);
    
} catch (Exception $e) {
    error_log('登录错误: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '登录失败，请稍后重试']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 