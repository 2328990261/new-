<?php
// 清除所有缓存
echo "清除缓存...\n";

// 清除 OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPcache 已清除\n";
} else {
    echo "- OPcache 未启用\n";
}

// 清除 runtime 缓存
$runtimePath = __DIR__ . '/runtime';
if (is_dir($runtimePath)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($runtimePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        @$todo($fileinfo->getRealPath());
    }
    echo "✓ Runtime 缓存已清除\n";
}

echo "\n缓存清除完成！\n";
echo "请重启 PHPStudy 后再次测试\n";
