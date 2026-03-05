<?php
require __DIR__ . '/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use think\facade\Db;

$tables = Db::query('SHOW TABLES');
echo "数据库中的表：\n\n";
foreach($tables as $t) {
    echo "- " . implode(' | ', $t) . "\n";
}
