<?php
/**
 * SSL证书自动续约脚本
 * 建议通过crontab定时执行，例如每天凌晨2点执行一次
 * 0 2 * * * /usr/bin/php /path/to/ssl_auto_renew.php
 */

// 设置脚本运行时间限制
set_time_limit(0);

// 引入ThinkPHP框架
require_once __DIR__ . '/vendor/autoload.php';

use think\facade\Db;
use think\facade\Log;

echo "SSL证书自动续约脚本开始执行...\n";
echo "执行时间: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 查找需要续约的SSL证书
    $expireDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    $configs = Db::name('ssl_config')
        ->where('auto_renew', 1)
        ->where('is_enabled', 1)
        ->where('cert_expire_time', '<=', $expireDate)
        ->where('status', 'active')
        ->select()
        ->toArray();
    
    if (empty($configs)) {
        echo "没有需要续约的SSL证书\n";
        exit(0);
    }
    
    echo "找到 " . count($configs) . " 个需要续约的SSL证书\n\n";
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($configs as $config) {
        echo "正在处理域名: {$config['domain']}\n";
        
        try {
            // 检查域名解析
            if (!checkDomainResolution($config['domain'])) {
                throw new Exception('域名解析不正确');
            }
            
            // 检查HTTP访问
            if (!checkHttpAccess($config['domain'])) {
                throw new Exception('域名HTTP访问不可用');
            }
            
            // 模拟续约过程（实际应用中需要调用相应的API）
            $renewResult = renewSslCertificate($config);
            
            if ($renewResult['success']) {
                // 更新证书信息
                Db::name('ssl_config')->where('id', $config['id'])->update([
                    'cert_expire_time' => date('Y-m-d H:i:s', strtotime('+90 days')),
                    'last_renew_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                
                // 记录续约日志
                Db::name('ssl_renew_log')->insert([
                    'ssl_config_id' => $config['id'],
                    'domain' => $config['domain'],
                    'action' => 'renew',
                    'status' => 'success',
                    'message' => '自动续约成功',
                    'cert_expire_time' => date('Y-m-d H:i:s', strtotime('+90 days')),
                    'execute_time' => date('Y-m-d H:i:s')
                ]);
                
                echo "  ✓ 续约成功\n";
                $successCount++;
                
            } else {
                throw new Exception($renewResult['error']);
            }
            
        } catch (Exception $e) {
            echo "  ✗ 续约失败: " . $e->getMessage() . "\n";
            
            // 记录失败日志
            Db::name('ssl_renew_log')->insert([
                'ssl_config_id' => $config['id'],
                'domain' => $config['domain'],
                'action' => 'renew',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'execute_time' => date('Y-m-d H:i:s')
            ]);
            
            $failCount++;
        }
        
        echo "\n";
    }
    
    echo "SSL证书自动续约完成\n";
    echo "成功: {$successCount}个\n";
    echo "失败: {$failCount}个\n";
    
    // 记录执行日志
    Log::info("SSL证书自动续约完成", [
        'success_count' => $successCount,
        'fail_count' => $failCount,
        'total_count' => count($configs)
    ]);
    
} catch (Exception $e) {
    echo "脚本执行失败: " . $e->getMessage() . "\n";
    Log::error("SSL证书自动续约脚本执行失败", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}

/**
 * 检查域名解析
 */
function checkDomainResolution($domain) {
    try {
        $ip = gethostbyname($domain);
        return $ip !== $domain; // 如果解析成功，IP地址会不同于域名
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 检查HTTP访问
 */
function checkHttpAccess($domain) {
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $result = @file_get_contents("http://{$domain}", false, $context);
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 续约SSL证书
 */
function renewSslCertificate($config) {
    try {
        // 这里集成实际的SSL证书续约服务
        // 可以是Let's Encrypt、阿里云、腾讯云等
        
        $domain = $config['domain'];
        
        // 模拟续约过程
        // 实际应用中需要调用相应的API
        
        // 检查证书是否真的需要续约
        $expireTime = strtotime($config['cert_expire_time']);
        $currentTime = time();
        $daysLeft = ($expireTime - $currentTime) / (24 * 3600);
        
        if ($daysLeft > 30) {
            return ['success' => false, 'error' => '证书还有' . ceil($daysLeft) . '天过期，暂不需要续约'];
        }
        
        // 模拟续约成功
        return ['success' => true, 'message' => '证书续约成功'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "脚本执行完成\n";
?>
