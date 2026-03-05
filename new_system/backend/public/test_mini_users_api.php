<?php
/**
 * 测试小程序用户 API
 * 访问: http://your-domain/test_mini_users_api.php
 */

// 引入ThinkPHP
require __DIR__ . '/../vendor/autoload.php';

// 启动应用
$app = new think\App();
$app->initialize();

use think\facade\Db;

echo "<h2>测试小程序用户 API</h2>";
echo "<hr>";

// 1. 检查表是否存在
echo "<h3>1. 检查 fa_users 表是否存在</h3>";
try {
    $tables = Db::query("SHOW TABLES LIKE 'fa_users'");
    if (empty($tables)) {
        echo "<p style='color:red'>❌ fa_users 表不存在！</p>";
    } else {
        echo "<p style='color:green'>✅ fa_users 表存在</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

// 2. 查看表结构
echo "<h3>2. 查看表结构</h3>";
try {
    $columns = Db::query("DESC fa_users");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>字段</th><th>类型</th><th>允许NULL</th><th>键</th><th>默认值</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (\Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

// 3. 统计数据条数
echo "<h3>3. 统计数据条数</h3>";
try {
    $count = Db::name('users')->count();
    echo "<p>使用 Db::name('users'): <strong>{$count}</strong> 条</p>";
    
    $count2 = Db::table('fa_users')->count();
    echo "<p>使用 Db::table('fa_users'): <strong>{$count2}</strong> 条</p>";
} catch (\Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

// 4. 查看数据
echo "<h3>4. 查看最近的数据</h3>";
try {
    $users = Db::name('users')->order('create_time', 'desc')->limit(5)->select()->toArray();
    if (empty($users)) {
        echo "<p style='color:orange'>⚠️ 表中没有数据</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>OpenID</th><th>手机号</th><th>昵称</th><th>头像</th><th>端口</th><th>创建时间</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>" . substr($user['openid'], 0, 20) . "...</td>";
            echo "<td>{$user['phone']}</td>";
            echo "<td>{$user['nickname']}</td>";
            echo "<td>" . (empty($user['avatar']) ? '无' : '有') . "</td>";
            echo "<td>{$user['platform']}</td>";
            echo "<td>{$user['create_time']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
}

// 5. 测试控制器方法
echo "<h3>5. 测试控制器方法</h3>";
try {
    $controller = new \app\controller\admin\MiniProgramUser(app());
    
    // 模拟请求参数
    $_GET['page'] = 1;
    $_GET['pageSize'] = 20;
    
    $response = $controller->list();
    $data = json_decode($response->getContent(), true);
    
    echo "<p>响应代码: <strong>{$data['code']}</strong></p>";
    echo "<p>响应消息: <strong>{$data['message']}</strong></p>";
    
    if (isset($data['data'])) {
        echo "<p>数据总数: <strong>{$data['data']['total']}</strong></p>";
        echo "<p>返回条数: <strong>" . count($data['data']['list']) . "</strong></p>";
        
        if (!empty($data['data']['list'])) {
            echo "<p style='color:green'>✅ API 返回了数据</p>";
        } else {
            echo "<p style='color:orange'>⚠️ API 返回空列表</p>";
        }
    }
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
} catch (\Exception $e) {
    echo "<p style='color:red'>错误: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p>测试完成！</p>";
