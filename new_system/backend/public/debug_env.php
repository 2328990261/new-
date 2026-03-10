<?php
// 直接读取 .env 文件
$envFile = __DIR__ . '/../.env';
echo "Reading .env file from: $envFile\n\n";

if (file_exists($envFile)) {
    echo "File exists!\n\n";
    echo "Content:\n";
    echo file_get_contents($envFile);
} else {
    echo "File does NOT exist!\n";
}
