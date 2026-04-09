<?php
/**
 * UnionID 配置检查和修复脚本
 * 自动检查并修复常见的配置问题
 */

// 检测项目路径
$backendPath = __DIR__ . '/tjiajiao91-main/new_system/backend';
if (!is_dir($backendPath)) {
    echo "错误：找不到后端目录\n";
    echo "当前目录：" . __DIR__ . "\n";
    echo "请确保在项目根目录运行此脚本\n";
    exit(1);
}

require $backendPath . '/vendor/autoload.php';

// 加载 ThinkPHP
$app = require $backendPath . '/app/app.php';
$app->initialize();

use think\facade\Db;

echo "========================================\n";
echo "UnionID 配置检查和修复工具\n";
echo "========================================\n\n";

$issues = [];
$fixes = [];

// 1. 检查小程序配置
echo "1. 检查小程序配置...\n";
$miniAppId = env('WECHAT_MINI_APPID', '');
$miniSecret = env('WECHAT_MINI_SECRET', '');

if (empty($miniAppId)) {
    $issues[] = '小程序 AppID 未配置';
    echo "   ✗ 小程序 AppID 未配置\n";
} else {
    echo "   ✓ 小程序 AppID: {$miniAppId}\n";
}

if (empty($miniSecret)) {
    $issues[] = '小程序 Secret 未配置';
    echo "   ✗ 小程序 Secret 未配置\n";
} else {
    echo "   ✓ 小程序 Secret 已配置\n";
}

// 2. 检查公众号配置
echo "\n2. 检查公众号配置...\n";
try {
    $notificationConfig = Db::name('notification_config')->where('id', 1)->find();
    if ($notificationConfig) {
        $mpAppId = $notificationConfig['wechat_app_id'] ?? '';
        $mpSecret = $notificationConfig['wechat_app_secret'] ?? '';
        
        if (empty($mpAppId)) {
            $issues[] = '公众号 AppID 未配置';
            echo "   ✗ 公众号 AppID 未配置\n";
        } else {
            echo "   ✓ 公众号 AppID: {$mpAppId}\n";
        }
        
        if (empty($mpSecret)) {
            $issues[] = '公众号 Secret 未配置';
            echo "   ✗ 公众号 Secret 未配置\n";
        } else {
            echo "   ✓ 公众号 Secret 已配置\n";
        }
        
        // 检查是否可能是同一主体
        if (!empty($miniAppId) && !empty($mpAppId)) {
            if ($miniAppId === $mpAppId) {
                $issues[] = '小程序和公众号使用了相同的 AppID（这是错误的）';
                echo "   ✗ 小程序和公众号 AppID 相同（配置错误）\n";
            } else {
                echo "   ✓ 小程序和公众号 AppID 不同\n";
                echo "   ⚠ 请确认两者已绑定到同一微信开放平台\n";
            }
        }
    } else {
        $issues[] = '公众号配置表不存在';
        echo "   ✗ notification_config 表中没有配置记录\n";
        
        // 尝试创建默认配置
        echo "   → 是否创建默认配置记录？(y/n): ";
        $create = trim(fgets(STDIN));
        if (strtolower($create) === 'y') {
            try {
                Db::name('notification_config')->insert([
                    'id' => 1,
                    'wechat_app_id' => '',
                    'wechat_app_secret' => '',
                    'wechat_access_token' => '',
                    'wechat_access_token_expire' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                echo "   ✓ 已创建默认配置记录\n";
                $fixes[] = '创建了 notification_config 默认记录';
            } catch (\Exception $e) {
                echo "   ✗ 创建失败: " . $e->getMessage() . "\n";
            }
        }
    }
} catch (\Exception $e) {
    $issues[] = '无法查询公众号配置: ' . $e->getMessage();
    echo "   ✗ 查询失败: " . $e->getMessage() . "\n";
}

// 3. 检查数据库表结构
echo "\n3. 检查数据库表结构...\n";

$requiredTables = [
    'wechat_users' => ['openid', 'unionid', 'subscribe'],
    'wechat_openid_bindings' => ['mini_openid', 'mp_openid', 'unionid', 'is_subscribed'],
    'users' => ['openid', 'user_type']
];

foreach ($requiredTables as $table => $columns) {
    try {
        $exists = Db::query("SHOW TABLES LIKE 'fa_{$table}'");
        if (empty($exists)) {
            $issues[] = "表 fa_{$table} 不存在";
            echo "   ✗ 表 fa_{$table} 不存在\n";
            continue;
        }
        
        echo "   ✓ 表 fa_{$table} 存在\n";
        
        // 检查字段
        $tableColumns = Db::query("SHOW COLUMNS FROM fa_{$table}");
        $existingColumns = array_column($tableColumns, 'Field');
        
        foreach ($columns as $column) {
            if (!in_array($column, $existingColumns)) {
                $issues[] = "表 fa_{$table} 缺少字段 {$column}";
                echo "     ✗ 缺少字段: {$column}\n";
            }
        }
        
    } catch (\Exception $e) {
        $issues[] = "检查表 fa_{$table} 失败: " . $e->getMessage();
        echo "   ✗ 检查失败: " . $e->getMessage() . "\n";
    }
}

// 4. 检查数据一致性
echo "\n4. 检查数据一致性...\n";

try {
    // 检查有 openid 但没有 unionid 的用户
    $usersWithoutUnionid = Db::name('users')
        ->alias('u')
        ->leftJoin('wechat_users wu', 'u.openid = wu.openid')
        ->where('u.openid', '<>', '')
        ->where(function($query) {
            $query->whereNull('wu.unionid')->whereOr('wu.unionid', '');
        })
        ->count();
    
    if ($usersWithoutUnionid > 0) {
        echo "   ⚠ 发现 {$usersWithoutUnionid} 个用户没有 unionid\n";
        echo "   → 是否尝试从绑定表补全？(y/n): ";
        $fix = trim(fgets(STDIN));
        
        if (strtolower($fix) === 'y') {
            $fixed = 0;
            $users = Db::name('users')
                ->alias('u')
                ->leftJoin('wechat_users wu', 'u.openid = wu.openid')
                ->where('u.openid', '<>', '')
                ->where(function($query) {
                    $query->whereNull('wu.unionid')->whereOr('wu.unionid', '');
                })
                ->field('u.openid')
                ->limit(100)
                ->select()
                ->toArray();
            
            foreach ($users as $user) {
                $openid = $user['openid'];
                
                // 从绑定表查找 unionid
                $binding = Db::name('wechat_openid_bindings')
                    ->where('mini_openid', $openid)
                    ->where('unionid', '<>', '')
                    ->find();
                
                if ($binding && !empty($binding['unionid'])) {
                    $unionid = $binding['unionid'];
                    
                    // 更新 wechat_users
                    $wu = Db::name('wechat_users')->where('openid', $openid)->find();
                    if ($wu) {
                        Db::name('wechat_users')->where('openid', $openid)->update([
                            'unionid' => $unionid,
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        Db::name('wechat_users')->insert([
                            'openid' => $openid,
                            'unionid' => $unionid,
                            'subscribe' => 0,
                            'create_time' => date('Y-m-d H:i:s'),
                            'update_time' => date('Y-m-d H:i:s')
                        ]);
                    }
                    
                    $fixed++;
                }
            }
            
            echo "   ✓ 已补全 {$fixed} 个用户的 unionid\n";
            $fixes[] = "补全了 {$fixed} 个用户的 unionid";
        }
    } else {
        echo "   ✓ 所有用户都有 unionid 或没有 openid\n";
    }
    
    // 检查绑定表中有 unionid 但没有 mp_openid 的记录
    $bindingsWithoutMp = Db::name('wechat_openid_bindings')
        ->where('unionid', '<>', '')
        ->where(function($query) {
            $query->whereNull('mp_openid')->whereOr('mp_openid', '');
        })
        ->count();
    
    if ($bindingsWithoutMp > 0) {
        echo "   ⚠ 发现 {$bindingsWithoutMp} 条绑定记录有 unionid 但没有公众号 openid\n";
        echo "     （这可能是正常的，如果用户未关注公众号）\n";
    }
    
} catch (\Exception $e) {
    echo "   ✗ 检查失败: " . $e->getMessage() . "\n";
}

// 5. 检查日志配置
echo "\n5. 检查日志配置...\n";
$logPath = __DIR__ . '/tjiajiao91-main/new_system/backend/runtime/log';
if (is_dir($logPath) && is_writable($logPath)) {
    echo "   ✓ 日志目录可写\n";
} else {
    $issues[] = '日志目录不可写';
    echo "   ✗ 日志目录不可写或不存在\n";
}

// 6. 生成报告
echo "\n========================================\n";
echo "检查报告\n";
echo "========================================\n\n";

if (empty($issues)) {
    echo "✓ 所有检查通过！\n\n";
} else {
    echo "发现 " . count($issues) . " 个问题：\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
    echo "\n";
}

if (!empty($fixes)) {
    echo "已执行 " . count($fixes) . " 个修复：\n\n";
    foreach ($fixes as $i => $fix) {
        echo ($i + 1) . ". {$fix}\n";
    }
    echo "\n";
}

// 7. 提供建议
echo "========================================\n";
echo "配置建议\n";
echo "========================================\n\n";

if (empty($miniAppId) || empty($miniSecret)) {
    echo "1. 配置小程序信息\n";
    echo "   编辑文件：tjiajiao91-main/new_system/backend/.env\n";
    echo "   添加或修改：\n";
    echo "   WECHAT_MINI_APPID=你的小程序AppID\n";
    echo "   WECHAT_MINI_SECRET=你的小程序Secret\n\n";
}

if (empty($mpAppId ?? '') || empty($mpSecret ?? '')) {
    echo "2. 配置公众号信息\n";
    echo "   方式一：在后台管理系统中配置\n";
    echo "   方式二：直接修改数据库 fa_notification_config 表\n\n";
}

echo "3. 绑定微信开放平台（推荐）\n";
echo "   - 访问：https://open.weixin.qq.com/\n";
echo "   - 完成企业认证\n";
echo "   - 将小程序和公众号绑定到同一账号\n";
echo "   - 绑定后用户登录时会自动返回 unionid\n\n";

echo "4. 测试 unionid 获取\n";
echo "   运行测试脚本：php test-unionid-fetch.php\n";
echo "   需要在小程序中获取 code 后输入测试\n\n";

echo "5. 查看详细文档\n";
echo "   参考：UnionID获取实现方案.md\n\n";

echo "========================================\n";
echo "检查完成\n";
echo "========================================\n";
