<?php
require __DIR__ . '/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use think\facade\Db;

$columns = Db::query('SHOW COLUMNS FROM fa_payments');
echo "fa_payments 表结构：\n\n";
foreach($columns as $col) {
    echo "字段名: " . $col['Field'] . " | 类型: " . $col['Type'] . " | 允许空: " . $col['Null'] . "\n";
}
