<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/www/wwwlogs/tutor_error.log');

// 设置错误处理函数
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        'success' => false,
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error, JSON_UNESCAPED_UNICODE);
    exit;
}
set_error_handler('handleError');

// 设置异常处理函数
function handleException($e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($error, JSON_UNESCAPED_UNICODE);
    exit;
}
set_exception_handler('handleException');

// 允许跨域请求
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_domains = [
    'http://localhost',
    'http://127.0.0.1',
    'http://123.207.52.46:8088'
];

if (in_array($origin, $allowed_domains)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // 如果不在允许列表中，设置为默认域名
    header('Access-Control-Allow-Origin: http://123.207.52.46:8088');
}

// 移除强制 HTTPS 跳转
// if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
//     if (!headers_sent()) {
//         header("HTTP/1.1 301 Moved Permanently");
//         header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//         exit();
//     }
// }

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// 设置响应类型
header('Content-Type: application/json; charset=utf-8');

// 创建数据库表（如果不存在）
function createTableIfNotExists() {
    $conn = getConnection();
    $sql = "CREATE TABLE IF NOT EXISTS fa_tutor_orders (
        id INT(11) NOT NULL AUTO_INCREMENT,
        content TEXT NOT NULL,
        city VARCHAR(50) DEFAULT NULL,
        district VARCHAR(50) DEFAULT NULL,
        grade VARCHAR(50) DEFAULT NULL,
        subject VARCHAR(50) DEFAULT NULL,
        salary VARCHAR(50) DEFAULT NULL,
        is_urgent TINYINT(1) DEFAULT 0,
        create_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$conn->query($sql)) {
        throw new Exception('创建数据表失败: ' . $conn->error);
    }
    $conn->close();
}

// 数据库配置
$db_config = [
    'host' => '123.207.52.46',
    'port' => 3306,
    'username' => 'myjiajiao',
    'password' => 'jE2se7DGe5HfE6zL',
    'database' => 'myjiajiao',
    'charset' => 'utf8mb4'
];

// 创建数据库连接
function getConnection() {
    global $db_config;
    try {
        $conn = new mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database'],
            $db_config['port']
        );
        
        if ($conn->connect_error) {
            error_log('数据库连接失败: ' . $conn->connect_error);
            throw new Exception('数据库连接失败: ' . $conn->connect_error);
        }
        
        $conn->set_charset($db_config['charset']);
        return $conn;
    } catch (Exception $e) {
        error_log('数据库连接异常: ' . $e->getMessage());
        throw new Exception('数据库连接失败: ' . $e->getMessage());
    }
}

// 获取所有订单
function getOrders() {
    try {
        // 检查是否登录
        session_start();
        if (!isset($_SESSION['admin_id'])) {
            error_log('未登录用户尝试访问');
            return ['success' => false, 'error' => '请先登录'];
        }

        $conn = getConnection();
        $admin_id = $_SESSION['admin_id']; // 获取当前登录的管理员ID
        
        // 获取请求头中的X-View-All值
        $headers = getallheaders();
        error_log('请求头信息: ' . print_r($headers, true));
        
        // 检查请求头中的X-View-All值
        $viewAll = false;
        if (isset($headers['X-View-All'])) {
            $viewAll = $headers['X-View-All'] === '1';
        } else if (isset($headers['x-view-all'])) {  // 添加小写检查
            $viewAll = $headers['x-view-all'] === '1';
        }
        
        error_log('查看全部订单状态: ' . ($viewAll ? 'true' : 'false'));
        error_log('当前管理员ID: ' . $admin_id);
        
        // 修改SQL查询，根据viewAll参数决定是否只查看当前管理员的订单
        $sql = "SELECT o.*, a.nickname as admin_nickname 
                FROM fa_tutor_orders o 
                LEFT JOIN fa_admin a ON o.admin_id = a.id";
        
        // 只有在不是查看全部订单时，才添加管理员ID的限制
        if (!$viewAll) {
            $sql .= " WHERE o.admin_id = $admin_id";
        }
        
        $sql .= " ORDER BY o.create_time DESC";
        
        error_log('执行SQL: ' . $sql);
        $result = $conn->query($sql);
        
        if (!$result) {
            error_log('SQL查询失败: ' . $conn->error);
            throw new Exception('查询失败: ' . $conn->error);
        }
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        error_log('查询到的订单数量: ' . count($orders));
        
        $conn->close();
        return ['success' => true, 'data' => $orders];
    } catch (Exception $e) {
        error_log('获取订单列表失败: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 添加订单
function addOrder($data) {
    try {
        // 检查是否登录
        session_start();
        if (!isset($_SESSION['admin_id'])) {
            return ['success' => false, 'error' => '请先登录'];
        }

        error_log('开始添加订单: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $conn = getConnection();
        
        // 检查是否存在重复内容
        $content = $conn->real_escape_string($data['content']);
        $checkSql = "SELECT id FROM fa_tutor_orders WHERE content = '$content'";
        error_log('检查重复SQL: ' . $checkSql);
        
        $result = $conn->query($checkSql);
        if (!$result) {
            error_log('检查重复失败: ' . $conn->error);
            throw new Exception('检查重复失败: ' . $conn->error);
        }
        
        if ($result->num_rows > 0) {
            error_log('订单内容重复');
            return ['success' => false, 'error' => '该信息已存在'];
        }
        
        $city = $conn->real_escape_string($data['city'] ?? '');
        $district = $conn->real_escape_string($data['district'] ?? '');
        $grade = $conn->real_escape_string($data['grade'] ?? '');
        $subject = $conn->real_escape_string($data['subject'] ?? '');
        $salary = $conn->real_escape_string($data['salary'] ?? '');
        $is_urgent = isset($data['is_urgent']) ? (int)$data['is_urgent'] : 0;
        $admin_id = $_SESSION['admin_id']; // 获取当前登录的管理员ID
        
        $sql = "INSERT INTO fa_tutor_orders (admin_id, content, city, district, grade, subject, salary, is_urgent) 
                VALUES ($admin_id, '$content', '$city', '$district', '$grade', '$subject', '$salary', $is_urgent)";
        error_log('插入SQL: ' . $sql);
        
        $success = $conn->query($sql);
        if (!$success) {
            error_log('插入失败: ' . $conn->error);
            throw new Exception('插入失败: ' . $conn->error);
        }
        
        error_log('订单添加成功，ID: ' . $conn->insert_id);
        $conn->close();
        return ['success' => true, 'message' => '添加成功', 'id' => $conn->insert_id];
    } catch (Exception $e) {
        error_log('添加订单失败: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 更新订单
function updateOrder($id, $data) {
    try {
        error_log('开始更新订单，ID: ' . $id . ', 数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $conn = getConnection();
        
        $content = $conn->real_escape_string($data['content']);
        $city = $conn->real_escape_string($data['city'] ?? '');
        $district = $conn->real_escape_string($data['district'] ?? '');
        $grade = $conn->real_escape_string($data['grade'] ?? '');
        $subject = $conn->real_escape_string($data['subject'] ?? '');
        $salary = $conn->real_escape_string($data['salary'] ?? '');
        $is_urgent = isset($data['is_urgent']) ? (int)$data['is_urgent'] : 0;
        
        $sql = "UPDATE fa_tutor_orders 
                SET content = '$content',
                    city = '$city',
                    district = '$district',
                    grade = '$grade',
                    subject = '$subject',
                    salary = '$salary',
                    is_urgent = $is_urgent
                WHERE id = $id";
        error_log('更新SQL: ' . $sql);
        
        $success = $conn->query($sql);
        if (!$success) {
            error_log('更新失败: ' . $conn->error);
            throw new Exception('更新失败: ' . $conn->error);
        }
        
        error_log('订单更新成功');
        $conn->close();
        return ['success' => true, 'message' => '更新成功'];
    } catch (Exception $e) {
        error_log('更新订单失败: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 删除订单
function deleteOrder($id) {
    try {
        error_log('开始删除订单，ID: ' . $id);
        $conn = getConnection();
        
        $sql = "DELETE FROM fa_tutor_orders WHERE id = $id";
        error_log('删除SQL: ' . $sql);
        
        $success = $conn->query($sql);
        if (!$success) {
            error_log('删除失败: ' . $conn->error);
            throw new Exception('删除失败: ' . $conn->error);
        }
        
        error_log('订单删除成功');
        $conn->close();
        return ['success' => true, 'message' => '删除成功'];
    } catch (Exception $e) {
        error_log('删除订单失败: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 批量添加订单
function addOrders($orders) {
    // 检查是否登录
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        return ['success' => false, 'error' => '请先登录'];
    }

    $conn = getConnection();
    $savedCount = 0;
    $duplicateCount = 0;
    $errors = [];
    $admin_id = $_SESSION['admin_id']; // 获取当前登录的管理员ID
    
    foreach ($orders as $order) {
        // 检查重复
        $content = $conn->real_escape_string($order['content']);
        $checkSql = "SELECT id FROM fa_tutor_orders WHERE content = '$content'";
        $result = $conn->query($checkSql);
        
        if ($result->num_rows > 0) {
            $duplicateCount++;
            continue;
        }
        
        // 添加新订单
        $city = $conn->real_escape_string($order['city'] ?? '');
        $district = $conn->real_escape_string($order['district'] ?? '');
        $grade = $conn->real_escape_string($order['grade'] ?? '');
        $subject = $conn->real_escape_string($order['subject'] ?? '');
        $salary = $conn->real_escape_string($order['salary'] ?? '');
        $is_urgent = isset($order['is_urgent']) ? (int)$order['is_urgent'] : 0;
        
        $sql = "INSERT INTO fa_tutor_orders (admin_id, content, city, district, grade, subject, salary, is_urgent) 
                VALUES ($admin_id, '$content', '$city', '$district', '$grade', '$subject', '$salary', $is_urgent)";
        
        if ($conn->query($sql)) {
            $savedCount++;
        } else {
            $errors[] = $conn->error;
        }
    }
    
    $conn->close();
    return [
        'success' => true,
        'savedCount' => $savedCount,
        'duplicateCount' => $duplicateCount,
        'errors' => $errors
    ];
}

// 批量删除订单
function deleteOrders($ids) {
    $conn = getConnection();
    
    $idList = array_map(function($id) use ($conn) {
        return (int)$id;
    }, $ids);
    
    $idString = implode(',', $idList);
    $sql = "DELETE FROM fa_tutor_orders WHERE id IN ($idString)";
    
    $success = $conn->query($sql);
    $error = $success ? '' : $conn->error;
    
    $conn->close();
    return ['success' => $success, 'error' => $error];
}

// 切换加急状态
function toggleUrgent($id) {
    try {
        error_log('开始切换加急状态，ID: ' . $id);
        $conn = getConnection();
        
        // 先检查记录是否存在
        $checkSql = "SELECT id FROM fa_tutor_orders WHERE id = " . (int)$id;
        $checkResult = $conn->query($checkSql);
        if (!$checkResult || $checkResult->num_rows === 0) {
            throw new Exception('记录不存在');
        }
        
        $sql = "UPDATE fa_tutor_orders SET is_urgent = NOT is_urgent WHERE id = " . (int)$id;
        error_log('执行SQL: ' . $sql);
        
        $success = $conn->query($sql);
        if (!$success) {
            error_log('切换加急状态失败: ' . $conn->error);
            throw new Exception('切换加急状态失败: ' . $conn->error);
        }
        
        error_log('切换加急状态成功');
        $conn->close();
        return ['success' => true, 'message' => '操作成功'];
    } catch (Exception $e) {
        error_log('切换加急状态失败: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 获取城市选项
function getCityOptions() {
    $conn = getConnection();
    $sql = "SELECT DISTINCT city FROM fa_tutor_orders WHERE city IS NOT NULL AND city != '' ORDER BY city";
    $result = $conn->query($sql);
    
    $cities = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if (!empty($row['city'])) {
                $cities[] = $row['city'];
            }
        }
    }
    
    $conn->close();
    return array_values(array_unique($cities));
}

// 获取区域选项
function getDistrictOptions($city) {
    $conn = getConnection();
    $city = $conn->real_escape_string($city);
    $sql = "SELECT DISTINCT district FROM fa_tutor_orders WHERE city = '$city' AND district IS NOT NULL AND district != '' ORDER BY district";
    $result = $conn->query($sql);
    
    $districts = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if (!empty($row['district'])) {
                $districts[] = $row['district'];
            }
        }
    }
    
    $conn->close();
    return array_values(array_unique($districts));
}

// 获取年级选项
function getGradeOptions() {
    $conn = getConnection();
    $sql = "SELECT DISTINCT grade FROM fa_tutor_orders WHERE grade IS NOT NULL AND grade != '' ORDER BY grade";
    $result = $conn->query($sql);
    
    $grades = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if (!empty($row['grade'])) {
                $grades[] = $row['grade'];
            }
        }
    }
    
    $conn->close();
    return array_values(array_unique($grades));
}

// 获取科目选项
function getSubjectOptions() {
    $conn = getConnection();
    $sql = "SELECT DISTINCT subject FROM fa_tutor_orders WHERE subject IS NOT NULL AND subject != '' ORDER BY subject";
    $result = $conn->query($sql);
    
    $subjects = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            if (!empty($row['subject'])) {
                $subjects[] = $row['subject'];
            }
        }
    }
    
    $conn->close();
    return array_values(array_unique($subjects));
}

// 处理请求
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'error' => '未知错误'];

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 加载配置文件中的数据
$cityDistricts = [
    '深圳' => ['南山区', '福田区', '罗湖区', '宝安区', '龙岗区', '龙华区', '盐田区', '坪山区', '光明区'],
    '广州' => ['天河区', '海珠区', '越秀区', '白云区', '黄埔区', '番禺区', '花都区', '南沙区', '从化区', '增城区'],
    '东莞' => ['莞城区', '东城区', '南城区', '万江区', '石碣镇', '石龙镇', '茶山镇', '石排镇', '企石镇', '横沥镇', '桥头镇', '谢岗镇', '东坑镇', '常平镇', '寮步镇', '大朗镇', '黄江镇', '清溪镇', '塘厦镇', '凤岗镇', '长安镇', '虎门镇', '厚街镇', '沙田镇', '道滘镇', '洪梅镇', '麻涌镇', '中堂镇', '高埗镇', '樟木头镇', '大岭山镇', '望牛墩镇']
];

$gradePatterns = [
    '小学一年级' => true,
    '小学二年级' => true,
    '小学三年级' => true,
    '小学四年级' => true,
    '小学五年级' => true,
    '小学六年级' => true,
    '初中一年级' => true,
    '初中二年级' => true,
    '初中三年级' => true,
    '高中一年级' => true,
    '高中二年级' => true,
    '高中三年级' => true
];

$subjectPatterns = [
    '语文' => true,
    '数学' => true,
    '英语' => true,
    '物理' => true,
    '化学' => true,
    '生物' => true,
    '政治' => true,
    '历史' => true,
    '地理' => true,
    '科学' => true
];

// 初始化数据库表
createTableIfNotExists();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'cities':
                        $response = ['success' => true, 'data' => getCityOptions()];
                        break;
                    case 'districts':
                        $city = $_GET['city'] ?? '';
                        $response = ['success' => true, 'data' => getDistrictOptions($city)];
                        break;
                    case 'grades':
                        $response = ['success' => true, 'data' => getGradeOptions()];
                        break;
                    case 'subjects':
                        $response = ['success' => true, 'data' => getSubjectOptions()];
                        break;
                    default:
                        $response = ['success' => false, 'error' => '未知的操作类型'];
                }
            } else {
                $response = ['success' => true, 'data' => getOrders()];
            }
            break;
            
        case 'POST':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // 记录接收到的数据
            error_log('Received POST data: ' . $input);
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                $response = [
                    'success' => false, 
                    'error' => '无效的 JSON 数据: ' . json_last_error_msg(),
                    'received' => $input
                ];
            } else if (!$data) {
                $response = [
                    'success' => false, 
                    'error' => '无效的数据格式',
                    'received' => $input
                ];
            } else {
                try {
                    if (isset($data[0])) { // 批量添加
                        $response = addOrders($data);
                        error_log('Batch add response: ' . json_encode($response));
                    } else { // 单个添加
                        $response = addOrder($data);
                        error_log('Single add response: ' . json_encode($response));
                    }
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ];
                    error_log('Add order error: ' . $e->getMessage());
                }
            }
            break;
            
        case 'PUT':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('无效的 JSON 数据: ' . json_last_error_msg());
            }
            if ($data && isset($data['id'])) {
                if (isset($data['action']) && $data['action'] === 'toggle_urgent') {
                    $response = toggleUrgent($data['id']);
                } else {
                    $response = updateOrder($data['id'], $data);
                }
            } else {
                $response = ['success' => false, 'error' => '无效的数据格式或缺少ID'];
            }
            break;
            
        case 'DELETE':
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('无效的 JSON 数据: ' . json_last_error_msg());
                }
                if ($data && isset($data['ids'])) { // 批量删除
                    $response = deleteOrders($data['ids']);
                }
            } else { // 单个删除
                $id = $_GET['id'] ?? null;
                if ($id) {
                    $response = deleteOrder($id);
                } else {
                    $response = ['success' => false, 'error' => '缺少ID参数'];
                }
            }
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE); 