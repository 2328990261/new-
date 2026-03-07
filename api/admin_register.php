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

if (!$data || !isset($data['username']) || !isset($data['password']) || !isset($data['nickname'])) {
    echo json_encode(['success' => false, 'error' => '请提供完整的注册信息']);
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
    $nickname = $conn->real_escape_string($data['nickname']);
    
    // 检查用户名是否已存在
    $checkSql = "SELECT id FROM fa_admin WHERE username = '$username'";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => '该用户名已被注册']);
        exit;
    }
    
    // 密码加密
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // 插入新用户
    $sql = "INSERT INTO fa_admin (username, password, nickname) VALUES ('$username', '$hashedPassword', '$nickname')";
    
    if (!$conn->query($sql)) {
        throw new Exception('注册失败: ' . $conn->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '注册成功',
        'data' => [
            'id' => $conn->insert_id,
            'nickname' => $nickname
        ]
    ]);
    
} catch (Exception $e) {
    error_log('注册错误: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '注册失败，请稍后重试']);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 