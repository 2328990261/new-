<?php
// 调试支付API
require __DIR__ . '/vendor/autoload.php';

$app = new think\App();
$app->initialize();

echo "调试支付API...\n";
echo str_repeat('=', 60) . "\n";

// 1. 检查类是否存在
$className = 'app\\controller\\api\\Payment';
echo "1. 检查类: {$className}\n";
if (class_exists($className)) {
    echo "✓ 类存在\n";
    $reflection = new ReflectionClass($className);
    echo "  文件: " . $reflection->getFileName() . "\n";
    echo "  命名空间: " . $reflection->getNamespaceName() . "\n";
} else {
    echo "✗ 类不存在\n";
}

// 2. 检查文件是否存在
$filePath = __DIR__ . '/app/controller/api/Payment.php';
echo "\n2. 检查文件: {$filePath}\n";
if (file_exists($filePath)) {
    echo "✓ 文件存在\n";
    
    // 读取文件前几行检查命名空间
    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $i => $line) {
        if (strpos($line, 'namespace') !== false) {
            echo "  第" . ($i + 1) . "行: {$line}\n";
            break;
        }
        if ($i > 10) break; // 只检查前10行
    }
} else {
    echo "✗ 文件不存在\n";
}

// 3. 模拟路由解析
echo "\n3. 模拟路由解析\n";
try {
    // 模拟请求
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/payment/create';
    $_SERVER['PATH_INFO'] = '/api/payment/create';
    
    $request = $app->request;
    $request->withMethod('POST')->withPathInfo('/api/payment/create');
    
    echo "  请求方法: " . $request->method() . "\n";
    echo "  路径信息: " . $request->pathinfo() . "\n";
    
    // 尝试解析路由
    $route = $app->route;
    $dispatch = $route->check($request, '');
    
    echo "  路由类型: " . get_class($dispatch) . "\n";
    
} catch (Exception $e) {
    echo "✗ 路由解析错误: " . $e->getMessage() . "\n";
    echo "  文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 4. 直接实例化控制器测试
echo "\n4. 直接实例化控制器\n";
try {
    $controller = new \app\controller\api\Payment();
    echo "✓ 控制器实例化成功\n";
    
    if (method_exists($controller, 'create')) {
        echo "✓ create 方法存在\n";
    } else {
        echo "✗ create 方法不存在\n";
    }
} catch (Exception $e) {
    echo "✗ 控制器实例化失败: " . $e->getMessage() . "\n";
}