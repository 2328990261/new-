<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 检测服务器类型
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$isApache = stripos($serverSoftware, 'apache') !== false;
$isNginx = stripos($serverSoftware, 'nginx') !== false;

// 检测 mod_rewrite (仅 Apache)
$modRewrite = 'N/A';
if ($isApache && function_exists('apache_get_modules')) {
    $modRewrite = in_array('mod_rewrite', apache_get_modules()) ? 'Enabled' : 'Disabled';
} elseif ($isApache) {
    $modRewrite = 'Unknown (cannot detect)';
}

$info = [
    'php_version' => PHP_VERSION,
    'server_software' => $serverSoftware,
    'server_type' => $isApache ? 'Apache' : ($isNginx ? 'Nginx' : 'Other'),
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'mod_rewrite' => $modRewrite,
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
    'uploads_dir' => __DIR__ . '/uploads',
    'uploads_writable' => is_writable(__DIR__ . '/uploads') ? 'Yes' : 'No',
    'htaccess_exists' => file_exists(__DIR__ . '/.htaccess') ? 'Yes' : 'No',
];

echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
