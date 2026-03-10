<?php
/**
 * 用户位置信息保存API
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'code' => 405,
        'success' => false,
        'message' => '只允许POST请求'
    ]);
    exit;
}

// 数据库配置
$host = 'localhost';
$dbname = 'myjiajiao';
$username = 'jE2se7DGe5HfE6zL';
$password = 'myjiajiao';
$table_prefix = 'fa_';

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('无效的JSON数据');
    }
    
    // 验证必需字段
    if (empty($data['openid'])) {
        throw new Exception('openid不能为空');
    }
    
    $openid = $data['openid'];
    $latitude = isset($data['latitude']) ? floatval($data['latitude']) : null;
    $longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;
    $address = isset($data['address']) ? trim($data['address']) : null;
    $province = isset($data['province']) ? trim($data['province']) : null;
    $city = isset($data['city']) ? trim($data['city']) : null;
    $district = isset($data['district']) ? trim($data['district']) : null;
    
    // 检查用户是否存在
    $checkSql = "SELECT id FROM {$table_prefix}wechat_users WHERE openid = :openid";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['openid' => $openid]);
    $user = $checkStmt->fetch();
    
    if (!$user) {
        throw new Exception('用户不存在');
    }
    
    // 更新用户位置信息
    $updateSql = "UPDATE {$table_prefix}wechat_users 
                  SET latitude = :latitude,
                      longitude = :longitude,
                      address = :address,
                      province = :province,
                      city = :city,
                      district = :district,
                      update_time = NOW()
                  WHERE openid = :openid";
    
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([
        'latitude' => $latitude,
        'longitude' => $longitude,
        'address' => $address,
        'province' => $province,
        'city' => $city,
        'district' => $district,
        'openid' => $openid
    ]);
    
    if ($result) {
        echo json_encode([
            'code' => 200,
            'success' => true,
            'message' => '位置信息保存成功',
            'data' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'province' => $province,
                'city' => $city,
                'district' => $district
            ]
        ]);
    } else {
        throw new Exception('保存位置信息失败');
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'success' => false,
        'message' => '数据库错误: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'code' => 400,
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
